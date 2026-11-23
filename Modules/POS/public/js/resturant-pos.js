/**
 * Restaurant POS - Main JavaScript (Complete Version)
 */

// ===== SERVICE WORKER =====
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.getRegistrations().then(function (registrations) {
            registrations.forEach(reg => {
                if (reg.scope !== window.location.origin + '/pos/') reg.unregister();
            });
        });
        navigator.serviceWorker.register(window.RPOS_CONFIG.routes.serviceWorker, {
            scope: '/pos/', updateViaCache: 'none'
        }).then(reg => {
            reg.addEventListener('updatefound', function () {
                const nw = reg.installing;
                nw.addEventListener('statechange', function () {
                    if (nw.state === 'installed' && navigator.serviceWorker.controller)
                        nw.postMessage({ type: 'SKIP_WAITING' });
                });
            });
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data?.type === 'SYNC_TRANSACTIONS') rposSyncPendingTransactions();
            });

            // كاش الصفحة الحالية مباشرة من المتصفح (مع الـ session cookies)
            function cacheSelfPage() {
                if (!('caches' in window)) return;
                const cacheName = 'pos-v1.5.0-dynamic';
                const pageUrl = window.location.pathname; // e.g. /pos/restaurant
                caches.open(cacheName).then(function (cache) {
                    fetch(pageUrl, { credentials: 'include' })
                        .then(function (res) {
                            if (res.ok) {
                                cache.put(pageUrl, res);
                                console.log('[RPOS] Page cached for offline:', pageUrl);
                            }
                        })
                        .catch(function () {});
                });
            }

            // شغّل الكاش بعد ثانيتين (بعد ما الصفحة تتحمل كاملاً)
            setTimeout(cacheSelfPage, 2000);

        }).catch(err => console.warn('[RPOS] SW failed:', err));
    });
}

(function () {
    "use strict";
    const CFG = window.RPOS_CONFIG;

    // ===== STATE =====
    let rCart = [];
    let rSelectedCategory = '';
    let rItemsCache = CFG.itemsData || {};
    let rInitialItems = CFG.initialProductsData || [];
    let rIsOnline = navigator.onLine;
    let rDb = null;
    let rOrderType = 'takeaway';
    let rPreviousOrderType = 'takeaway';
    let rCurrentPriceGroupId = CFG.firstPriceGroupId || null;
    let rDeliveryFee = 0;
    let rSelectedTable = null;
    let rSelectedTableName = '';
    let rInvoiceNotes = '';
    let rDeliveryCustomerId = null;
    let rDeliveryCustomerName = '';
    let rDeliveryAddress = '';
    let rDeliveryDriverId = null;

    // منع التشغيل المتوازي لدورة المزامنة
    let isSyncing = false;

    // ===== SYNC =====
    window.rposSyncPendingTransactions = function () {
        if (!rDb || !rIsOnline) return;
        rDb.getPendingTransactions().then(pending => {
            const toSync = (pending || []).filter(t => t.sync_status !== 'held' && !t.server_id);
            if (!toSync.length) return;
            showToast(CFG.i18n.syncing_offline_orders, 'info');
            $.ajax({
                url: CFG.routes.sync,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ transactions: toSync }),
                headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                success: function (res) {
                    (res.synced || []).forEach(s => {
                        rDb.updateTransactionServerId(s.local_id, s.server_id).catch(() => {});
                    });
                    if (res.synced?.length > 0) {
                        showToast(CFG.i18n.syncing_offline_orders + ' (' + res.synced.length + ')', 'success');
                    }
                    // عرض أخطاء الفواتير اللي فشلت
                    (res.failed || []).forEach(f => {
                        const msg = f.error || 'خطأ غير معروف';
                        showToast('⚠️ فاتورة ' + (f.local_id || '') + ': ' + msg, 'error');
                        console.error('[POS Sync] failed tx:', f.local_id, msg);
                    });
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || xhr.responseJSON?.error || xhr.statusText || 'خطأ غير معروف';
                    showToast('⚠️ ' + msg, 'error');
                    console.error('[POS Sync] error:', msg, xhr.responseJSON);
                }
            });
        }).catch(() => {});
    };

    function syncPendingCustomers() {
        if (!rDb || !rIsOnline) return;
        rDb.getPendingCustomers().then(pending => {
            if (!pending.length) return;
            pending.forEach(c => {
                $.ajax({
                    url: CFG.routes.saveDeliveryCustomer,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                    data: { name: c.name, phone: c.phone, address: c.address || '' },
                    success: function (res) {
                        if (res.success && res.customer?.id) {
                            rDb.markCustomerSynced(c.local_id, res.customer.id).catch(() => {});
                            // لو الـ state لسه بيشاور على الـ temp id، حدّثه
                            if (rDeliveryCustomerId === c.id) {
                                rDeliveryCustomerId = res.customer.id;
                            }
                        }
                    }
                });
            });
        }).catch(() => {});
    }

    // ===== SYNC: HELD ORDERS =====
    async function syncPendingHeldOrders() {
        if (!rDb || !rIsOnline) return;
        let pending = [];
        try {
            const all = await rDb.getHeldOrders();
            pending = (all || []).filter(o => o.sync_status === 'pending' && !o.server_id);
        } catch (e) { return; }
        for (const order of pending) {
            try {
                const res = await $.ajax({
                    url: CFG.routes.holdOrder,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(order),
                    headers: { 'X-CSRF-TOKEN': CFG.csrfToken }
                });
                if (res.success && res.held_order_id) {
                    await rDb.updateHeldOrderServerId(order.local_id, res.held_order_id).catch(() => {});
                }
            } catch (e) {
                console.error('[POS Sync] held order failed:', order.local_id, e);
            }
        }
    }

    // ===== SYNC: PAYOUTS =====
    async function syncPendingPayouts() {
        if (!rDb || !rIsOnline) return;
        let pending = [];
        try {
            pending = await rDb.getPendingPayouts();
        } catch (e) { return; }
        for (const payout of (pending || [])) {
            try {
                const res = await $.ajax({
                    url: CFG.routes.payOut,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                    data: {
                        amount: payout.amount,
                        cash_account_id: payout.cash_account_id,
                        expense_account_id: payout.expense_account_id,
                        description: payout.description,
                        notes: payout.notes || ''
                    }
                });
                if (res.success) {
                    await rDb.markPayoutSynced(payout.local_id).catch(() => {});
                }
            } catch (e) {
                console.error('[POS Sync] payout failed:', payout.local_id, e);
            }
        }
    }

    // ===== SYNC: ALL (دورة موحدة) =====
    async function syncAll() {
        if (!rDb || !rIsOnline || isSyncing) return;
        isSyncing = true;
        try {
            await syncPendingCustomers();
            await syncPendingHeldOrders();
            await syncPendingPayouts();
            await rposSyncPendingTransactions();
        } finally {
            isSyncing = false;
        }
    }

    // ===== HELPERS =====
    window.showToast = function (msg, type) {
        const bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b';
        const el = $(`<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:${bg};color:#fff;padding:.75rem 1.5rem;border-radius:8px;z-index:99999;font-family:Cairo,sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.2)">${msg}</div>`);
        $('body').append(el);
        setTimeout(() => el.fadeOut(() => el.remove()), 3000);
    };

    const generateUUID = () => 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });

    const getModal = id => {
        const el = document.getElementById(id);
        if (!el) return { show: () => {}, hide: () => {} };
        return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    };

    const hideModal = id => {
        const m = bootstrap.Modal.getInstance(document.getElementById(id));
        if (m) m.hide();
    };

    // ===== PRODUCTS =====
    function getItemDisplayPrice(item) {
        const cached = rItemsCache[item.id] || item;
        if (rCurrentPriceGroupId && cached.prices) {
            const pg = cached.prices.find(p => p.id == rCurrentPriceGroupId);
            if (pg) return parseFloat(pg.value);
        }
        return parseFloat(cached.sale_price || 0);
    }

    function renderProducts(items) {
        const grid = $('#rposProductsGrid');
        grid.empty();
        if (!items || items.length === 0) {
            grid.html(`<div class="text-center text-muted py-5 w-100"><i class="fas fa-box-open fa-2x mb-2"></i><p>${CFG.i18n.no_records_found}</p></div>`);
            return;
        }
        let html = '';
        items.forEach(item => {
            const cached = rItemsCache[item.id] || item;
            const icon = cached.is_weight_scale ? 'fa-weight' : 'fa-utensils';
            const scaleBadge = cached.is_weight_scale ? '<span class="rpos-product-card__scale-badge"><i class="fas fa-weight"></i></span>' : '';
            const desc = item.notes ? `<p class="rpos-product-card__desc">${item.notes.substring(0, 30)}</p>` : '';
            const displayPrice = getItemDisplayPrice(item);
            const imgHtml = cached.image
                ? `<img src="${cached.image}" alt="${item.name}" loading="lazy" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='block';"><i class="fas ${icon}" style="display:none"></i>`
                : `<i class="fas ${icon}"></i>`;
            html += `<div class="rpos-product-card" data-item-id="${item.id}">
                <div class="rpos-product-card__icon">${imgHtml}${scaleBadge}</div>
                <div class="rpos-product-card__body">
                    <h6 class="rpos-product-card__name">${item.name}</h6>
                    ${desc}
                    <div class="rpos-product-card__footer">
                        <span class="rpos-product-card__price">${displayPrice.toFixed(2)}</span>
                        <button class="rpos-product-card__add" data-item-id="${item.id}"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </div>`;
        });
        grid.html(html);
    }

    function applyFilters() {
        const term = $('#rpos-search').val().trim().toLowerCase();

        // لو مفيش تصنيف مختار ومفيش بحث → الـ grid فاضي
        if (!rSelectedCategory && !term) {
            $('#rposProductsGrid').empty();
            return;
        }

        let filtered = rInitialItems;
        if (rSelectedCategory) filtered = filtered.filter(i => i.category_id == rSelectedCategory);
        if (term) filtered = filtered.filter(i => i.name.toLowerCase().includes(term) || (i.code && i.code.toLowerCase().includes(term)));
        renderProducts(filtered);
    }

    // ===== CART =====
    function addToCart(itemId) {
        const item = rItemsCache[itemId];
        if (!item) { console.warn('[RPOS] item not in cache:', itemId); return; }
        const price = getItemDisplayPrice(item);
        console.log('[RPOS] addToCart:', item.name, 'price:', price);
        const existing = rCart.find(i => i.id == itemId);
        if (existing) { existing.quantity++; } else {
            rCart.push({ id: item.id, name: item.name, price, quantity: 1, note: '' });
        }
        renderCart(itemId);
    }

    function renderCart(focusId = null) {
        const container = $('#rposCartItems');
        if (rCart.length === 0) {
            container.html(`<div class="rpos-cart__empty"><i class="fas fa-utensils"></i><p>${CFG.i18n.cart_empty}</p></div>`);
            updateTotals();
            return;
        }
        const html = rCart.map((item, idx) => `
            <div class="rpos-cart-item" data-idx="${idx}">
                <div class="rpos-cart-item__main">
                    <div class="rpos-cart-item__info">
                        <div class="rpos-cart-item__name">${item.name}</div>
                        <div class="rpos-cart-item__price">${(item.price * item.quantity).toFixed(2)}</div>
                    </div>
                    <div class="rpos-cart-item__qty">
                        <button class="rpos-cart-item__qty-btn rpos-qty-minus" data-idx="${idx}"><i class="fas fa-minus"></i></button>
                        <span class="rpos-cart-item__qty-val">${item.quantity}</span>
                        <button class="rpos-cart-item__qty-btn rpos-qty-plus" data-idx="${idx}"><i class="fas fa-plus"></i></button>
                    </div>
                    <button class="rpos-cart-item__remove-btn rpos-remove-item" data-idx="${idx}"><i class="fas fa-times"></i></button>
                </div>
                <div class="rpos-cart-item__note-row visible">
                    <input type="text" class="rpos-cart-item__note-input rpos-note-input" data-idx="${idx}" value="${item.note || ''}" placeholder="${CFG.i18n.item_note}">
                </div>
            </div>`).join('');
        container.html(html);
        if (focusId) {
            const fIdx = rCart.findIndex(i => i.id == focusId);
            if (fIdx !== -1) container.find(`.rpos-cart-item[data-idx="${fIdx}"] input`).focus();
        }
        updateTotals();
    }

    function updateTotals() {
        const subtotal = rCart.reduce((s, i) => s + (i.price * i.quantity), 0);
        $('#rposSubtotal').text(subtotal.toFixed(2));
        $('.rpos-cart__total-row.delivery-fee-row').remove();
        if (rDeliveryFee > 0) {
            $('#rposSubtotal').parent().after(`<div class="rpos-cart__total-row delivery-fee-row"><span>${CFG.i18n.delivery_fee}</span><span>${rDeliveryFee.toFixed(2)}</span></div>`);
        }
        const total = subtotal + rDeliveryFee;
        $('#rposTotal').text(total.toFixed(2));
        $('#paymentTotal').text(total.toFixed(2));
        $('#paymentTotalVal, #cashAmount').val(total.toFixed(2));
    }

    // ===== INVOICE =====
    function buildInvoiceData() {
        // اسم مجموعة السعر الحالية
        const pgName = rCurrentPriceGroupId && CFG.priceGroups
            ? (CFG.priceGroups.find(p => p.id == rCurrentPriceGroupId)?.name || '')
            : '';

        return {
            local_id: generateUUID(),
            items: rCart,
            customer_id: rDeliveryCustomerId || CFG.defaultCustomerId,
            table_id: rSelectedTable,
            order_type: rOrderType,
            payment_method: $('#paymentMethod').val(),
            cash_amount: $('#cashAmount').val() || 0,
            card_amount: $('#cardAmount').val() || 0,
            cash_account_id: $('#cashAccountId').val() || null,
            bank_account_id: $('#bankAccountId').val() || null,
            delivery_fee: rDeliveryFee,
            delivery_address: rDeliveryAddress,
            notes: rInvoiceNotes,
            price_group_id: rCurrentPriceGroupId,
            price_group_name: pgName,
            cashier_name: CFG.currentUserName || '',
            invoice_type: 103
        };
    }

    // ===== LOCAL PRINT =====
    // silent=false → نافذة عرض تُطبع عند onload
    // silent=true  → إرسال مباشر للـ PrintAgent (localhost:5000) بدون نافذة
    // printWin     → النافذة المفتوحة مسبقاً (للـ non-silent فقط)
    function printLocalReceipt(data, silent = false, printWin = null) {
        const ps      = (CFG.printSettings || {});
        const appName = document.title.replace(' - نظام نقاط البيع', '') || 'نظام نقاط البيع';
        const now     = new Date();
        const dateStr = now.toLocaleDateString('ar-SA');
        const timeStr = now.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const subtotal   = data.items.reduce((s, i) => s + (parseFloat(i.price) * parseFloat(i.quantity)), 0);
        const deliveryFee = parseFloat(data.delivery_fee || 0);
        const discount   = parseFloat(data.discount || 0);
        const additional = parseFloat(data.additional || 0);
        const total      = subtotal + deliveryFee - discount + additional;
        const paid       = parseFloat(data.cash_amount || 0) + parseFloat(data.card_amount || 0);
        const change     = paid > total ? paid - total : 0;

        const paperW   = ps.cashierPaperWidth  || '78mm';
        const fontSize = ps.cashierFontSize     || 11;
        const sepStyle = ps.cashierSepStyle     || 'dashed';
        const title    = ps.cashierTitle        || 'فاتورة مبيعات';
        const footer   = ps.cashierFooter       || 'شكراً لك!';

        // ترجمة نوع الطلب
        const orderTypeMap = { dining: 'طاولة', takeaway: 'تيك اواي', delivery: 'توصيل' };
        const orderTypeLabel = orderTypeMap[data.order_type] || data.order_type || '';

        // ── Silent Print: إرسال مباشر للـ PrintAgent ──────────────────────
        if (silent) {
            const stations = CFG.printerStations || [];
            const defaultStation = stations.find(s => s.is_default) || stations[0];
            if (!defaultStation) { console.warn('[RPOS] No printer station'); if (printWin) printWin.close(); return; }

            const sep = '================================';
            const lines = [
                sep, appName, title, sep,
                ps.cashierShowOrderNumber !== false && data.server_id      ? `رقم الطلب: #${data.server_id}` : '',
                ps.cashierShowBonNumber   !== false && data.invoice_number ? `رقم البون: ${data.invoice_number}` : '',
                ps.cashierShowDatetime    !== false ? `التاريخ: ${dateStr}  الوقت: ${timeStr}` : '',
                ps.cashierShowSeller      !== false && data.cashier_name   ? `البائع: ${data.cashier_name}` : '',
                ps.cashierShowOrderType   !== false && orderTypeLabel      ? `نوع الطلب: ${orderTypeLabel}` : '',
                ps.cashierShowTable       !== false && data.table_id       ? `الطاولة: ${data.table_id}` : '',
                '--------------------------------',
                ...data.items.map(i => `${parseFloat(i.quantity)}x ${i.name}  ${(parseFloat(i.price) * parseFloat(i.quantity)).toFixed(2)}`),
                sep,
                ps.cashierShowSubtotal    !== false ? `الاجمالي: ${subtotal.toFixed(2)}` : '',
                ps.cashierShowDiscount    !== false && discount > 0   ? `الخصم: -${discount.toFixed(2)}` : '',
                ps.cashierShowAdditional  !== false && additional > 0 ? `الاضافي: +${additional.toFixed(2)}` : '',
                deliveryFee > 0 ? `توصيل: ${deliveryFee.toFixed(2)}` : '',
                ps.cashierShowNet         !== false ? `الصافي: ${total.toFixed(2)}` : '',
                ps.cashierShowPayment     !== false && paid > 0   ? `المدفوع: ${paid.toFixed(2)}` : '',
                ps.cashierShowPayment     !== false && change > 0 ? `الباقي: ${change.toFixed(2)}` : '',
                sep, footer, '', '', '',
            ].filter(l => l !== '');

            fetch(CFG.printAgentUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ printer: defaultStation.printer_name, content: lines.join('\n') }),
            }).then(res => { if (!res.ok) console.warn('[RPOS] PrintAgent error:', res.status); })
              .catch(err => { console.warn('[RPOS] PrintAgent unreachable:', err.message); });

            if (printWin) printWin.close();
            return;
        }

        // ── Normal Print: نافذة عرض ──────────────────────────────────────
        const itemsHtml = data.items.map(i => `
            <div class="item-row">
                <div class="item-name">${i.name || ''}</div>
                <div class="item-qty">${parseFloat(i.quantity)}</div>
                <div class="item-price">${parseFloat(i.price).toFixed(2)}</div>
                <div class="item-total">${(parseFloat(i.price) * parseFloat(i.quantity)).toFixed(2)}</div>
            </div>`).join('');

        const headerImgHtml = ps.cashierHeaderImage
            ? `<div style="text-align:center;margin-bottom:4px"><img src="${ps.cashierHeaderImage}" style="max-width:100%;max-height:60px"></div>` : '';
        const footerImgHtml = ps.cashierFooterImage
            ? `<div style="text-align:center;margin-top:4px"><img src="${ps.cashierFooterImage}" style="max-width:100%;max-height:50px"></div>` : '';

        const html = `<!DOCTYPE html><html lang="ar" dir="rtl"><head>
        <meta charset="utf-8">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:'Cairo',Arial,sans-serif;font-size:${fontSize}px;direction:rtl;background:#fff}
            .receipt{width:${paperW};margin:0 0 0 0;padding:5mm 5mm 5mm 2mm}
            .center{text-align:center}.bold{font-weight:700}
            .sep{border-top:1px ${sepStyle} #000;margin:4px 0}
            .sep2{border-top:2px solid #000;margin:4px 0}
            .row{display:flex;justify-content:space-between;margin-bottom:2px;font-size:${fontSize - 1}px}
            .item-row{display:flex;justify-content:space-between;margin-bottom:3px;font-size:${fontSize - 2}px;border-bottom:1px dotted #ccc;padding-bottom:2px}
            .item-name{flex:1;padding-left:3px}
            .item-qty,.item-price{width:28px;text-align:center}
            .item-total{width:40px;text-align:left;font-weight:600}
            .items-header{display:flex;justify-content:space-between;font-weight:600;font-size:${fontSize - 2}px;border-bottom:1px solid #000;padding-bottom:2px;margin-bottom:3px}
            .grand{font-size:${fontSize + 1}px;font-weight:700}
            @media print{@page{size:${paperW} auto;margin:0 0 0 0}body{margin:0;width:${paperW}}.receipt{margin:0;padding:3mm 3mm 3mm 1mm}}
            @media screen{body{padding:20px;background:#f5f5f5;display:flex;justify-content:flex-start}.receipt{box-shadow:0 2px 10px rgba(0,0,0,.1)}}
        </style></head>
        <body onload="window.print();">
        <div class="receipt">
            ${headerImgHtml}
            ${ps.cashierShowLogo !== false ? `<div class="center bold" style="font-size:${fontSize + 3}px">${appName}</div>` : ''}
            <div class="center" style="font-size:${fontSize - 1}px">${title}</div>
            <div class="sep2"></div>
            ${ps.cashierShowOrderNumber !== false && data.server_id      ? `<div class="row"><span>رقم الطلب:</span><span>#${data.server_id}</span></div>` : ''}
            ${ps.cashierShowBonNumber   !== false && data.invoice_number ? `<div class="row"><span>رقم البون:</span><span>${data.invoice_number}</span></div>` : ''}
            ${ps.cashierShowDatetime    !== false ? `<div class="row"><span>التاريخ:</span><span>${dateStr} ${timeStr}</span></div>` : ''}
            ${ps.cashierShowSeller      !== false && data.cashier_name   ? `<div class="row"><span>البائع:</span><span>${data.cashier_name}</span></div>` : ''}
            ${ps.cashierShowOrderType   !== false && orderTypeLabel      ? `<div class="row"><span>نوع الطلب:</span><span>${orderTypeLabel}</span></div>` : ''}
            ${ps.cashierShowPriceSource !== false && data.price_group_name ? `<div class="row"><span>مصدر الطلب:</span><span>${data.price_group_name}</span></div>` : ''}
            ${ps.cashierShowTable       !== false && data.table_id       ? `<div class="row"><span>الطاولة:</span><span>${data.table_id}</span></div>` : ''}
            <div class="sep"></div>
            <div class="items-header"><span>الصنف</span><span>الكمية</span><span>السعر</span><span>المجموع</span></div>
            ${itemsHtml}
            ${ps.cashierShowNotes !== false && data.notes ? `<div class="row" style="font-size:${fontSize-2}px;color:#555"><span>ملاحظات:</span><span>${data.notes}</span></div>` : ''}
            <div class="sep2"></div>
            ${ps.cashierShowSubtotal   !== false ? `<div class="row"><span>الاجمالي:</span><span>${subtotal.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowDiscount   !== false && discount > 0   ? `<div class="row"><span>الخصم:</span><span>-${discount.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowAdditional !== false && additional > 0 ? `<div class="row"><span>الاضافي:</span><span>+${additional.toFixed(2)}</span></div>` : ''}
            ${deliveryFee > 0 ? `<div class="row"><span>توصيل:</span><span>${deliveryFee.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowNet !== false ? `<div class="row grand"><span>الصافي:</span><span>${total.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowPayment !== false && paid > 0   ? `<div class="sep"></div><div class="row"><span>المدفوع:</span><span>${paid.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowPayment !== false && change > 0 ? `<div class="row"><span>الباقي:</span><span>${change.toFixed(2)}</span></div>` : ''}
            ${ps.cashierShowBarcode !== false && (data.invoice_number || data.server_id) ? `<div class="center" style="font-family:monospace;font-size:${fontSize+1}px;margin:4px 0">*${data.invoice_number || data.server_id}*</div>` : ''}
            <div class="sep2"></div>
            <div class="center" style="font-size:${fontSize - 2}px">${footer}</div>
            ${footerImgHtml}
        </div></body></html>`;

        // ── Normal Print: اكتب في النافذة المفتوحة مسبقاً ──────────────
        if (printWin && !printWin.closed) {
            printWin.document.write(html);
            printWin.document.close();
            return;
        }

        // ── Modal Print: عرض الفاتورة في iframe داخل modal (fallback) ──
        const frame = document.getElementById('printPreviewFrame');
        const printBtn = document.getElementById('printPreviewBtn');
        if (frame) {
            frame.srcdoc = html;
            printBtn.onclick = () => frame.contentWindow.print();
            const modal = bootstrap.Modal.getOrCreate(document.getElementById('printPreviewModal'));
            modal.show();
        } else {
            // last resort
            const w = window.open('', '_blank', 'width=420,height=650');
            if (w) { w.document.write(html); w.document.close(); }
        }
    }

    // ===== KITCHEN / PREPARATION PRINT =====
    function printKitchenReceipt(data) {
        const ps       = CFG.printSettings || {};
        const paperW   = ps.kitchenPaperWidth  || '78mm';
        const fontSize = parseInt(ps.kitchenFontSize) || 14;
        const title    = ps.kitchenTitle       || 'طلب مطبخ';
        const footer   = ps.kitchenFooter      || '';
        const sepStyle = ps.kitchenSepStyle    || 'solid';
        const copies   = parseInt(ps.kitchenCopies) || 1;

        const now     = new Date();
        const dateStr = now.toLocaleDateString('ar-SA');
        const timeStr = now.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const orderTypeMap = { dining: 'طاولة', takeaway: 'تيك اواي', delivery: 'توصيل' };
        const orderTypeLabel = orderTypeMap[data.order_type] || data.order_type || '';

        const headerImgHtml = ps.kitchenHeaderImage
            ? `<div style="text-align:center;margin-bottom:4px"><img src="${ps.kitchenHeaderImage}" style="max-width:100%;max-height:60px"></div>` : '';
        const footerImgHtml = ps.kitchenFooterImage
            ? `<div style="text-align:center;margin-top:4px"><img src="${ps.kitchenFooterImage}" style="max-width:100%;max-height:50px"></div>` : '';

        const itemsHtml = data.items.map(i => {
            const noteHtml = (ps.kitchenShowNotes !== false && i.note)
                ? `<div style="font-size:${fontSize - 3}px;color:#555;padding-right:4px">↳ ${i.note}</div>` : '';
            const priceHtml = ps.kitchenShowPrices === true
                ? `<span style="font-size:${fontSize - 2}px;color:#666">${(parseFloat(i.price) * parseFloat(i.quantity)).toFixed(2)}</span>` : '';
            return `<div style="display:flex;justify-content:space-between;align-items:baseline;border-bottom:1px dashed #999;padding:3px 0;margin-bottom:2px">
                <span style="font-size:${fontSize + 1}px;font-weight:700">${parseFloat(i.quantity)}x ${i.name}</span>
                ${priceHtml}
            </div>${noteHtml}`;
        }).join('');

        // بناء محتوى الورقة
        const bodyContent = `
            ${headerImgHtml}
            <div style="text-align:center;font-size:${fontSize + 4}px;font-weight:900;border-bottom:3px solid #000;padding-bottom:4px;margin-bottom:6px">${title}</div>
            ${ps.kitchenShowOrderNumber !== false && data.server_id      ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>رقم الطلب:</span><span style="font-weight:700">#${data.server_id}</span></div>` : ''}
            ${ps.kitchenShowBonNumber   !== false && data.invoice_number ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>رقم البون:</span><span style="font-weight:700">${data.invoice_number}</span></div>` : ''}
            ${ps.kitchenShowDatetime    !== false ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>التاريخ:</span><span>${dateStr}</span></div>` : ''}
            ${ps.kitchenShowTime        !== false ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>الوقت:</span><span style="font-weight:700">${timeStr}</span></div>` : ''}
            ${ps.kitchenShowTable       !== false && data.table_id       ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px;font-size:${fontSize + 2}px;font-weight:900"><span>الطاولة:</span><span>${data.table_id}</span></div>` : ''}
            ${ps.kitchenShowCashier     !== false && data.cashier_name   ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>الكاشير:</span><span>${data.cashier_name}</span></div>` : ''}
            ${ps.kitchenShowOrderType   !== false && orderTypeLabel      ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>نوع الطلب:</span><span>${orderTypeLabel}</span></div>` : ''}
            ${ps.kitchenShowPriceSource !== false && data.price_group_name ? `<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>مصدر الطلب:</span><span>${data.price_group_name}</span></div>` : ''}
            <div style="border-top:3px solid #000;margin:6px 0"></div>
            ${itemsHtml}
            <div style="border-top:3px solid #000;margin:6px 0"></div>
            ${footer ? `<div style="text-align:center;font-size:${fontSize - 2}px;color:#555;margin-top:4px">${footer}</div>` : ''}
            ${footerImgHtml}
        `;

        // بناء صفحة HTML للطباعة — نسخ متعددة في نفس الصفحة
        let copiesHtml = '';
        for (let c = 0; c < copies; c++) {
            copiesHtml += `<div class="kitchen-copy">${bodyContent}</div>`;
        }

        const html = `<!DOCTYPE html><html lang="ar" dir="rtl"><head>
        <meta charset="utf-8">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:'Cairo',Arial,sans-serif;font-size:${fontSize}px;direction:rtl;background:#fff}
            .kitchen-copy{width:${paperW};padding:5mm 4mm;page-break-after:always}
            .kitchen-copy:last-child{page-break-after:avoid}
            @media print{@page{size:${paperW} auto;margin:0}body{margin:0;width:${paperW}}.kitchen-copy{padding:3mm 3mm}}
            @media screen{body{padding:20px;background:#f5f5f5;display:flex;flex-direction:column;align-items:flex-start;gap:20px}.kitchen-copy{box-shadow:0 2px 10px rgba(0,0,0,.15)}}
        </style></head>
        <body onload="window.print();">${copiesHtml}</body></html>`;

        // فتح نافذة الطباعة
        const w = window.open('', '_blank', 'width=420,height=650');
        if (w) { w.document.write(html); w.document.close(); }
    }

    /**
     * saveRPosInvoice(print, silent)
     *  print=false  → حفظ محلي فقط
     *  print=true, silent=false → حفظ محلي + عرض الفاتورة ثم طباعة
     *  print=true, silent=true  → حفظ محلي + طباعة صامتة بدون عرض
     */
    window.saveRPosInvoice = function (print = false, silent = false) {
        if (rCart.length === 0) return;
        const data = buildInvoiceData();

        // فتح نافذة الطباعة فوراً في الـ click event (قبل أي async)
        // silent → نافذة مخفية للـ PrintAgent
        // normal print → نافذة عادية للعرض والطباعة
        const printWin = print
            ? window.open('', '_blank', silent ? 'width=1,height=1,left=-9999' : 'width=420,height=650')
            : null;

        // 1) حفظ محلي فوري → reset + طباعة بدون انتظار السيرفر
        rDb.saveTransaction(data).then(({ local_id }) => {
            rposResetOrder();
            showToast('✅ ' + CFG.i18n.saved_successfully, 'success');
            if (print) printLocalReceipt(data, silent, printWin);

            // طباعة ورقة التحضير تلقائياً بناءً على الإعدادات
            const ps = CFG.printSettings || {};
            if (ps.enableKitchenPrint !== false) {
                // تأخير بسيط لتجنب تعارض نوافذ الطباعة
                setTimeout(() => printKitchenReceipt(data), print ? 800 : 0);
            }

            // 2) بعت للسيرفر في الخلفية بالتوازي (fire & forget)
            if (rIsOnline) {
                $.ajax({
                    url: CFG.routes.store,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                    success: function (res) {
                        if (res.success && res.cashier_transaction_id) {
                            rDb.updateTransactionServerId(local_id, res.cashier_transaction_id).catch(() => {});
                        }
                    },
                    error: function (xhr) {
                        // فشل الإرسال — يفضل pending ويتزامن في الدورة القادمة
                        const msg = xhr.responseJSON?.message || xhr.responseJSON?.error || xhr.statusText || 'خطأ غير معروف';
                        showToast('⚠️ ' + msg, 'error');
                        console.warn('[RPOS] store failed, will sync later:', local_id, msg);
                    }
                });
            }
        }).catch((err) => {
            if (printWin) printWin.close();
            const msg = err?.message || err || CFG.i18n.save_error || 'خطأ في الحفظ المحلي';
            showToast('❌ ' + msg, 'error');
            console.error('[RPOS] saveTransaction failed:', err);
        });
    };

    function rposResetOrder() {
        rCart = []; rSelectedTable = null; rInvoiceNotes = '';
        rDeliveryCustomerId = null; rDeliveryCustomerName = ''; rDeliveryAddress = '';
        renderCart();
        hideModal('paymentModal');
        $('#rposCartTableTag').hide().text('');
        $('#rposTableText').text(CFG.i18n.table_free);
        $('.rpos-table-badge__dot').removeClass('rpos-table-badge__dot--busy').addClass('rpos-table-badge__dot--free');
    }

    // ===== INIT =====
    $(document).ready(function () {
        // تهيئة أزرار الطباعة من الإعدادات
        const ps = CFG.printSettings || {};
        $('#chkPrint').prop('checked', ps.enablePrint !== false);
        $('#chkDirectPrint').prop('checked', ps.enablePrint !== false && ps.silentPrint === true);
        $('#chkDirectPrintWrapper').toggle(!!$('#chkPrint').is(':checked'));

        // إظهار/إخفاء الطباعة الصامتة عند تغيير حالة الطباعة
        $(document).on('change', '#chkPrint', function () {
            $('#chkDirectPrintWrapper').toggle(this.checked);
            if (!this.checked) $('#chkDirectPrint').prop('checked', false);
        });

        rDb = new POSIndexedDB();
        window._rposDb = rDb;
        rDb.open().then(() => {
            const itemsToCache = Object.values(rItemsCache);
            if (itemsToCache.length > 0) {
                rDb.saveItems(itemsToCache).catch(() => {});
            } else {
                rDb.getAllItems().then(items => {
                    if (items.length > 0) {
                        rInitialItems = items;
                        items.forEach(i => { rItemsCache[i.id] = i; });
                    }
                });
            }
            // تحميل العملاء في IndexedDB لو أونلاين
            if (rIsOnline) {
                syncAll();
                $.get(CFG.routes.searchCustomerPhone, { phone: '', load_all: 1 }, function (res) {
                    if (res.customers?.length > 0) rDb.saveCustomers(res.customers).catch(() => {});
                }).fail(() => {});
            }
        });

        // DateTime
        function updateTime() {
            const now = new Date();
            $('#rposDateTime').text(now.toLocaleDateString('ar-SA') + ' ' + now.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' }));
        }
        updateTime(); setInterval(updateTime, 60000);

        // Online status - ping فعلي بدل navigator.onLine
        const setOnlineState = (isOnline) => {
            rIsOnline = isOnline;
            window.rposIsOnline = isOnline;
            $('#rposOnlineStatus').toggleClass('rpos-online-dot--on', isOnline).toggleClass('rpos-online-dot--off', !isOnline);
            $('#rposFooterDot').toggleClass('rpos-footer__dot--on', isOnline).toggleClass('rpos-footer__dot--off', !isOnline);
        };
        const pingAndUpdate = async () => {
            if (!navigator.onLine) { setOnlineState(false); return; }
            try {
                const res = await fetch(CFG.routes.ping, { cache: 'no-store', credentials: 'include' });
                setOnlineState(res.ok);
                if (res.ok) { syncAll(); }
            } catch (e) {
                setOnlineState(false);
            }
        };
        pingAndUpdate();
        window.addEventListener('online', pingAndUpdate);
        window.addEventListener('offline', () => setOnlineState(false));
        setInterval(pingAndUpdate, 30000);

        // Search & Categories
        let rSearchTimer;
        $('#rpos-search').on('input', function () { clearTimeout(rSearchTimer); rSearchTimer = setTimeout(applyFilters, 200); });

        $(document).on('click', '.rpos-cat-item', function () {
            $('.rpos-cat-item').removeClass('rpos-cat-item--active');
            $(this).addClass('rpos-cat-item--active');
            rSelectedCategory = $(this).data('category') || '';
            applyFilters();
        });

        // Price Group
        $(document).on('click', '.rpos-pg-btn', function () {
            $('.rpos-pg-btn').removeClass('rpos-pg-btn--active');
            $(this).addClass('rpos-pg-btn--active');
            rCurrentPriceGroupId = $(this).data('pg-id') || null;
            rCart.forEach(cartItem => {
                const cached = rItemsCache[cartItem.id];
                if (cached) cartItem.price = getItemDisplayPrice(cached);
            });
            renderCart(); updateTotals();
            if (rSelectedCategory) applyFilters();
        });

        // Add to cart — stop propagation on the + button to avoid double-add
        $(document).on('click', '.rpos-product-card__add', function (e) {
            e.stopPropagation();
            const itemId = parseInt($(this).data('item-id'));
            if (itemId) addToCart(itemId);
        });
        $(document).on('click', '.rpos-product-card', function () {
            const itemId = parseInt($(this).data('item-id'));
            if (itemId) addToCart(itemId);
        });

        // Cart controls
        $(document).on('click', '.rpos-qty-plus', e => { rCart[$(e.currentTarget).data('idx')].quantity++; renderCart(); });
        $(document).on('click', '.rpos-qty-minus', e => {
            const idx = $(e.currentTarget).data('idx');
            if (--rCart[idx].quantity <= 0) rCart.splice(idx, 1);
            renderCart();
        });
        $(document).on('click', '.rpos-remove-item', e => { rCart.splice($(e.currentTarget).data('idx'), 1); renderCart(); });
        $(document).on('input', '.rpos-note-input', e => { rCart[$(e.currentTarget).data('idx')].note = $(e.currentTarget).val(); });

        // Void
        $('#rposVoidBtn, #rposVoidIconBtn').on('click', function () {
            if (rCart.length === 0) return;
            if (confirm(CFG.i18n.confirm_void)) { rCart = []; renderCart(); showToast(CFG.i18n.order_voided, 'info'); }
        });

        // Notes
        $('#rposNotesBtn').on('click', () => getModal('notesModal').show());
        document.getElementById('notesModal')?.addEventListener('hidden.bs.modal', () => {
            rInvoiceNotes = $('#invoiceNotes').val() || '';
        });

        // Print btn in cart
        $('#rposPrintBtn').on('click', function () {
            if (rCart.length === 0) { showToast(CFG.i18n.cart_empty, 'warning'); return; }
            // kitchen print / receipt preview — open checkout with print flag
            openCheckout(true);
        });

        // ===== ORDER TYPE TABS =====
        $('.rpos-order-type').on('click', function () {
            $('.rpos-order-type').removeClass('rpos-order-type--active');
            $(this).addClass('rpos-order-type--active');
            const newType = $(this).data('type');
            if (newType === 'delivery') rPreviousOrderType = rOrderType;
            rOrderType = newType;

            if (rOrderType === 'dining') {
                rDeliveryFee = 0; updateTotals();
                $('#rposDeliveryPanel').hide();
                getModal('orderTypeModal').show();
            } else if (rOrderType === 'delivery') {
                rDeliveryFee = 0; rSelectedTable = null; rSelectedTableName = '';
                $('#rposCartTableTag').hide().text('');
                $('#rposTableText').text(CFG.i18n.table_free);
                $('.rpos-table-badge__dot').removeClass('rpos-table-badge__dot--busy').addClass('rpos-table-badge__dot--free');
                $('#rposDeliveryPanel').show();
                getModal('deliveryCustomerModal').show();
            } else {
                rDeliveryFee = 0; rSelectedTable = null; rSelectedTableName = '';
                $('#rposCartTableTag').hide().text('');
                $('#rposTableText').text(CFG.i18n.table_free);
                $('.rpos-table-badge__dot').removeClass('rpos-table-badge__dot--busy').addClass('rpos-table-badge__dot--free');
                $('#rposDeliveryPanel').hide();
                updateTotals();
            }
        });

        // Table modal
        $(document).on('click', '.rpos-table-card', function () {
            if ($(this).data('table-status') === 'occupied') { showToast(CFG.i18n.table_occupied, 'warning'); return; }
            $('.rpos-table-card').removeClass('rpos-table-card--selected');
            $(this).addClass('rpos-table-card--selected');
            rSelectedTable = $(this).data('table-id');
            rSelectedTableName = $(this).data('table-name');
            $('#confirmTableBtn').prop('disabled', false);
        });

        $('#confirmTableBtn').on('click', function () {
            if (!rSelectedTable) return;
            $('#rposTableText').text(rSelectedTableName);
            $('.rpos-table-badge__dot').removeClass('rpos-table-badge__dot--free').addClass('rpos-table-badge__dot--busy');
            $('#rposCartTableTag').text(rSelectedTableName).show();
            hideModal('orderTypeModal');
            showToast(CFG.i18n.table_selected + ' ' + rSelectedTableName, 'success');
        });

        $('#rposTableBadge').on('click', function () {
            if (rOrderType === 'dining') getModal('orderTypeModal').show();
        });

        // Delivery modal
        document.getElementById('deliveryCustomerModal')?.addEventListener('hidden.bs.modal', function () {
            if (!rDeliveryCustomerId && !$('#deliveryCustomerName, #rposDeliveryName').val()?.trim()) {
                rOrderType = rPreviousOrderType;
                $('.rpos-order-type').removeClass('rpos-order-type--active');
                $(`.rpos-order-type[data-type="${rPreviousOrderType}"]`).addClass('rpos-order-type--active');
                $('#rposDeliveryPanel').hide();
            } else {
                $('#rposDeliveryPanel').show();
            }
        });

        // ===== DELIVERY MODAL: phone search =====
        let rDeliveryModalPhoneTimer;
        $(document).on('input', '#deliveryPhoneSearch', function () {
            clearTimeout(rDeliveryModalPhoneTimer);
            const phone = $(this).val().trim();
            $('#deliveryCustomerStatus').hide().html('');
            $('#deliverySearchResults').hide().empty();
            $('#deliveryCustomerName').val('');
            $('#deliveryCustomerId').val('');
            $('#deliveryAddressOptions').hide();
            $('#deliveryAddressBtns').empty();
            $('#deliveryAddressInput').val('');
            rDeliveryCustomerId = null;
            rDeliveryCustomerName = '';

            if (phone.length < 3) return;

            $('#deliveryPhoneSpinner').css('display', 'flex');

            function showDeliveryResults(customers) {
                $('#deliveryPhoneSpinner').hide();
                if (customers.length > 0) {
                    let html = '';
                    customers.forEach(c => {
                        html += `<button type="button" class="list-group-item list-group-item-action rpos-delivery-modal-result"
                            data-id="${c.id}" data-name="${c.name}" data-phone="${c.phone}"
                            data-addr="${c.address || ''}" data-addr2="${c.address2 || ''}" data-addr3="${c.address3 || ''}">
                            <span class="fw-bold">${c.name}</span>
                            <small class="text-muted ms-2" dir="ltr">${c.phone}</small>
                        </button>`;
                    });
                    $('#deliverySearchResults').html(html).show();
                    $('#deliveryCustomerStatus')
                        .html('<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>العميل موجود</span>')
                        .show();
                } else {
                    $('#deliveryCustomerStatus')
                        .html('<span class="badge bg-warning text-dark"><i class="fas fa-user-plus me-1"></i>عميل جديد — أدخل الاسم والعنوان</span>')
                        .show();
                    $('#deliveryCustomerName').focus();
                }
            }

            rDeliveryModalPhoneTimer = setTimeout(function () {
                // البحث في IndexedDB أولاً
                rDb.searchCustomersByPhone(phone).then(function (localResults) {
                    if (localResults.length > 0) {
                        showDeliveryResults(localResults);
                    } else if (rIsOnline) {
                        // fallback للسيرفر لو مش موجود محلياً
                        $.get(CFG.routes.searchCustomerPhone, { phone }, function (res) {
                            showDeliveryResults(res.customers || []);
                            // حفظ النتائج في IndexedDB
                            if (res.customers?.length > 0) rDb.saveCustomers(res.customers).catch(() => {});
                        }).fail(() => {
                            $('#deliveryPhoneSpinner').hide();
                            $('#deliveryCustomerStatus')
                                .html('<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>حدث خطأ في البحث</span>')
                                .show();
                        });
                    } else {
                        showDeliveryResults([]);
                    }
                }).catch(() => {
                    // لو IndexedDB فشل روح للسيرفر
                    if (rIsOnline) {
                        $.get(CFG.routes.searchCustomerPhone, { phone }, function (res) {
                            showDeliveryResults(res.customers || []);
                        }).fail(() => { $('#deliveryPhoneSpinner').hide(); });
                    } else {
                        $('#deliveryPhoneSpinner').hide();
                    }
                });
            }, 400);
        });

        // اختيار عميل من نتائج البحث
        $(document).on('click', '.rpos-delivery-modal-result', function () {
            rDeliveryCustomerId = $(this).data('id');
            rDeliveryCustomerName = $(this).data('name');
            $('#deliveryCustomerId').val(rDeliveryCustomerId);
            $('#deliveryCustomerName').val(rDeliveryCustomerName);
            $('#deliveryPhoneSearch').val($(this).data('phone'));
            $('#deliverySearchResults').hide().empty();
            $('#deliveryCustomerStatus')
                .html('<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>تم اختيار: ' + rDeliveryCustomerName + '</span>')
                .show();

            // عرض العناوين المحفوظة
            const addrs = [
                { label: 'العنوان 1', val: $(this).data('addr') },
                { label: 'العنوان 2', val: $(this).data('addr2') },
                { label: 'العنوان 3', val: $(this).data('addr3') },
            ].filter(a => a.val && a.val.trim() !== '');

            if (addrs.length > 0) {
                let btnsHtml = '';
                addrs.forEach(a => {
                    btnsHtml += `<button type="button" class="btn btn-outline-secondary btn-sm rpos-addr-pick" data-addr="${a.val}">${a.label}: ${a.val}</button>`;
                });
                $('#deliveryAddressBtns').html(btnsHtml);
                $('#deliveryAddressOptions').show();
                $('#deliveryAddressInput').val(addrs[0].val);
                rDeliveryAddress = addrs[0].val;
                $('#deliveryAddressBtns .rpos-addr-pick').first().addClass('active btn-secondary').removeClass('btn-outline-secondary');
            } else {
                $('#deliveryAddressOptions').hide();
                $('#deliveryAddressInput').val('').focus();
            }
        });

        // اختيار عنوان محفوظ
        $(document).on('click', '.rpos-addr-pick', function () {
            $('.rpos-addr-pick').removeClass('active btn-secondary').addClass('btn-outline-secondary');
            $(this).addClass('active btn-secondary').removeClass('btn-outline-secondary');
            rDeliveryAddress = $(this).data('addr');
            $('#deliveryAddressInput').val(rDeliveryAddress);
        });

        $(document).on('input', '#deliveryAddressInput', function () {
            rDeliveryAddress = $(this).val().trim();
        });

        // تأكيد الـ delivery
        $(document).on('click', '#confirmDeliveryBtn', function () {
            const name = $('#deliveryCustomerName').val().trim();
            const phone = $('#deliveryPhoneSearch').val().trim();
            const address = $('#deliveryAddressInput').val().trim();

            if (!name) { showToast(CFG.i18n.enter_customer_name, 'warning'); return; }
            if (!phone) { showToast(CFG.i18n.enter_customer_phone, 'warning'); return; }

            rDeliveryAddress = address;
            rDeliveryCustomerName = name;
            rDeliveryDriverId = $('#rposDeliveryDriver').val() || null;

            const btn = $(this).prop('disabled', true);

            if (rDeliveryCustomerId) {
                // عميل موجود — حدّث العنوان لو اتغير
                if (address && CFG.routes.updateDeliveryCustomerAddress) {
                    $.ajax({
                        url: CFG.routes.updateDeliveryCustomerAddress,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                        data: { customer_id: rDeliveryCustomerId, address, field: 'address' }
                    });
                }
                $('#rposCartTableTag').text(`${CFG.i18n.delivery}: ${name}`).show();
                hideModal('deliveryCustomerModal');
                updateTotals();
                btn.prop('disabled', false);
            } else {
                // عميل جديد — حفظ في IndexedDB أولاً
                const tempId = 'temp_' + Date.now();
                const customerData = { id: tempId, name, phone, address: address || '', address2: '' };

                rDb.saveCustomer(customerData).then(() => {
                    return rDb.queueNewCustomer(customerData);
                }).then(() => {
                    rDeliveryCustomerId = tempId;
                    $('#deliveryCustomerId').val(tempId);
                    $('#rposCartTableTag').text(`${CFG.i18n.delivery}: ${name}`).show();
                    hideModal('deliveryCustomerModal');
                    updateTotals();
                    btn.prop('disabled', false);

                    if (rIsOnline) {
                        // زامن فوراً مع السيرفر وحدّث الـ id
                        $.ajax({
                            url: CFG.routes.saveDeliveryCustomer,
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
                            data: { name, phone, address },
                            success: function (res) {
                                if (res.success && res.customer?.id) {
                                    rDb.markCustomerSynced(tempId, res.customer.id).catch(() => {});
                                    if (rDeliveryCustomerId === tempId) rDeliveryCustomerId = res.customer.id;
                                    showToast(CFG.i18n.customer_saved, 'success');
                                }
                            }
                        });
                    } else {
                        showToast(CFG.i18n.customer_saved + ' (offline)', 'info');
                    }
                }).catch(() => {
                    showToast(CFG.i18n.error_occurred, 'error');
                    btn.prop('disabled', false);
                });
            }
        });

        // reset المودال عند الفتح
        document.getElementById('deliveryCustomerModal')?.addEventListener('show.bs.modal', function () {
            $('#deliveryPhoneSearch').val('');
            $('#deliveryCustomerName').val('');
            $('#deliveryCustomerId').val('');
            $('#deliveryAddressInput').val('');
            $('#deliveryCustomerStatus').hide().html('');
            $('#deliverySearchResults').hide().empty();
            $('#deliveryAddressOptions').hide();
            $('#deliveryAddressBtns').empty();
        });

        // Delivery driver (cart panel)
        $(document).on('change', '#rposDeliveryDriver', function () {
            rDeliveryDriverId = $(this).val() || null;
        });

        // Header buttons → modals
        $('#rposHeldBtn').on('click', () => getModal('heldOrdersModal').show());
        $('#rposRecentBtn').on('click', () => getModal('recentTransactionsModal').show());
        $('#rposPendingBtn').on('click', () => getModal('pendingTransactionsModal').show());
        $('#rposPayOutBtn').on('click', e => { e.preventDefault(); getModal('payOutModal').show(); });
        $('#rposReturnInvoiceBtn').on('click', e => { e.preventDefault(); getModal('returnInvoiceModal').show(); });

        // ===== CHECKOUT =====
        function openCheckout(autoPrint = false) {
            const total = parseFloat($('#rposTotal').text()) || 0;
            // #paymentTotal هو span للعرض — نحدّثه بـ .text()
            $('#paymentTotal').text(total.toFixed(2));
            // hidden input للقيمة الفعلية
            if (!$('#paymentTotalVal').length) {
                $('<input type="hidden" id="paymentTotalVal">').appendTo('#paymentModal');
            }
            $('#paymentTotalVal').val(total.toFixed(2));
            $('#cashAmount').val(total.toFixed(2));
            $('#cardAmount').val(0);
            $('#changeAmountDiv').hide();
            $('#changeAmount').text('0.00');
            $('#cardAmountDiv, #bankAccountDiv').hide();
            $('#cashAmountDiv, #cashAccountDiv').show();
            // Quick amounts
            const amounts = [Math.ceil(total / 5) * 5, Math.ceil(total / 10) * 10, Math.ceil(total / 50) * 50].filter((v, i, a) => v > total && a.indexOf(v) === i).slice(0, 3);
            let quickHtml = `<button class="rpos-quick-amt rpos-quick-amt--exact" data-amt="${total.toFixed(2)}">${CFG.i18n.exact} ${total.toFixed(2)}</button>`;
            amounts.forEach(a => { quickHtml += `<button class="rpos-quick-amt" data-amt="${a}">${a}</button>`; });
            $('#rposQuickAmounts').html(quickHtml);
            if (autoPrint) { window._rposPrintOnSave = true; }
            getModal('paymentModal').show();
        }

        $('#rposCheckoutBtn').on('click', function () {
            if (rCart.length === 0) { showToast(CFG.i18n.cart_empty, 'warning'); return; }
            openCheckout(false);
        });

        // Payment method toggle
        $('#paymentMethod').on('change', function () {
            const method = $(this).val();
            $('#cashAmountDiv, #cashAccountDiv').toggle(method !== 'card');
            $('#cardAmountDiv, #bankAccountDiv').toggle(method !== 'cash');
            if (method === 'card') {
                const total = parseFloat($('#paymentTotalVal').val()) || parseFloat($('#paymentTotal').text()) || 0;
                $('#cardAmount').val(total.toFixed(2));
                $('#cashAmount').val(0);
            } else if (method === 'cash') {
                const total = parseFloat($('#paymentTotalVal').val()) || parseFloat($('#paymentTotal').text()) || 0;
                $('#cashAmount').val(total.toFixed(2));
                $('#cardAmount').val(0);
            }
            $('#changeAmountDiv').hide();
        });

        // Quick amounts
        $(document).on('click', '.rpos-quick-amt', function () {
            const amt = parseFloat($(this).data('amt'));
            $('#cashAmount').val(amt.toFixed(2));
            const total = parseFloat($('#paymentTotalVal').val()) || parseFloat($('#paymentTotal').text()) || 0;
            const change = amt - total;
            if (change >= 0) {
                $('#changeAmount').text(change.toFixed(2));
                $('#changeAmountDiv').show();
            }
        });

        // Cash amount change → show change
        $('#cashAmount').on('input', function () {
            const paid = parseFloat($(this).val()) || 0;
            const total = parseFloat($('#paymentTotalVal').val()) || parseFloat($('#paymentTotal').text()) || 0;
            const change = paid - total;
            if (change >= 0) { $('#changeAmount').text(change.toFixed(2)); $('#changeAmountDiv').show(); }
            else { $('#changeAmountDiv').hide(); }
        });

        // Save button — event delegation عشان الـ modal بيتحمل بعد الـ ready
        $(document).on('click', '#rposSaveBtn', () => {
            const print  = $('#chkPrint').is(':checked');
            const silent = print && $('#chkDirectPrint').is(':checked');
            console.log('[RPOS] Save clicked — rDb:', !!rDb, 'cart:', rCart.length, 'print:', print, 'silent:', silent);
            saveRPosInvoice(print, silent);
        });
        // backward compat
        $(document).on('click', '#saveAndPrintBtn', () => saveRPosInvoice(true, false));
        $(document).on('click', '#saveAndSilentPrintBtn', () => saveRPosInvoice(true, true));
        $(document).on('click', '#saveOnlyBtn', () => saveRPosInvoice(false));

        // Refresh items
        $('#rposRefreshBtn').on('click', function () {
            const btn = $(this).prop('disabled', true);
            btn.find('i').addClass('fa-spin');
            $.get(CFG.routes.allItemsDetails, res => {
                if (res?.items) {
                    rInitialItems = res.items;
                    res.items.forEach(i => rItemsCache[i.id] = i);
                    rDb.saveItems(res.items);
                    applyFilters();
                    showToast(CFG.i18n.items_refreshed, 'success');
                }
            }).always(() => btn.prop('disabled', false).find('i').removeClass('fa-spin'));
        });

        // Pending badge update
        function updatePendingBadge() {
            if (!rDb) return;
            rDb.getPendingTransactions().then(pending => {
                const count = (pending || []).filter(t => t.status !== 'held' && !t.server_id).length;
                if (count > 0) { $('#rposPendingBadge').text(count).show(); } else { $('#rposPendingBadge').hide(); }
            }).catch(() => {});
        }
        setInterval(updatePendingBadge, 10000);

    }); // end document.ready

})(); // end IIFE 0000