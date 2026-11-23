<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('pos::pos.self_order.page_title') }} - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        :root {
            --so-primary: #2ab88d;
            --so-primary-dark: #1e9a76;
            --so-bg: #f8f9fa;
            --so-card-radius: 14px;
            --so-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        body { font-family: 'Cairo', sans-serif; background: var(--so-bg); }

        /* ── Navbar ── */
        .so-navbar {
            background: #fff;
            box-shadow: var(--so-shadow);
            position: sticky; top: 0; z-index: 100;
        }
        .so-navbar .brand { font-weight: 700; font-size: 1.1rem; color: var(--so-primary); }
        .so-navbar .table-badge {
            background: var(--so-primary);
            color: #fff;
            border-radius: 20px;
            padding: 2px 12px;
            font-size: .85rem;
        }

        /* ── Category Pills ── */
        .category-pills { gap: .5rem; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; }
        .category-pills::-webkit-scrollbar { height: 3px; }
        .category-pills::-webkit-scrollbar-thumb { background: var(--so-primary); border-radius: 4px; }
        .cat-pill {
            border: 2px solid var(--so-primary);
            color: var(--so-primary);
            background: #fff;
            border-radius: 20px;
            padding: 4px 16px;
            font-size: .85rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all .2s;
        }
        .cat-pill.active, .cat-pill:hover {
            background: var(--so-primary);
            color: #fff;
        }

        /* ── Item Card ── */
        .item-card {
            background: #fff;
            border-radius: var(--so-card-radius);
            box-shadow: var(--so-shadow);
            overflow: hidden;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            height: 100%;
        }
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.12); }
        .item-card img { width: 100%; height: 140px; object-fit: cover; }
        .item-card .no-img {
            width: 100%; height: 140px;
            background: #f0faf6;
            display: flex; align-items: center; justify-content: center;
            color: var(--so-primary); font-size: 2.5rem;
        }
        .item-card .card-body { padding: .75rem; }
        .item-card .item-name { font-weight: 600; font-size: .9rem; margin-bottom: 4px; }
        .item-card .item-price { color: var(--so-primary); font-weight: 700; font-size: 1rem; }
        .item-card .btn-add {
            background: var(--so-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: .8rem;
            transition: background .2s;
        }
        .item-card .btn-add:hover { background: var(--so-primary-dark); }

        /* ── Cart Sidebar ── */
        .cart-sidebar {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,.12);
            z-index: 200;
            max-height: 70vh;
            overflow-y: auto;
            transition: transform .3s ease;
        }
        .cart-sidebar.collapsed { transform: translateY(calc(100% - 60px)); }
        .cart-handle {
            padding: 12px 20px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-badge {
            background: var(--so-primary);
            color: #fff;
            border-radius: 50%;
            width: 22px; height: 22px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700;
        }
        .cart-item { padding: .6rem 1rem; border-bottom: 1px solid #f5f5f5; }
        .qty-btn {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 1.5px solid var(--so-primary);
            background: #fff;
            color: var(--so-primary);
            font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all .15s;
        }
        .qty-btn:hover { background: var(--so-primary); color: #fff; }
        .qty-display { min-width: 28px; text-align: center; font-weight: 600; }

        /* ── Checkout Button ── */
        .btn-checkout {
            background: var(--so-primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            transition: background .2s;
        }
        .btn-checkout:hover:not(:disabled) { background: var(--so-primary-dark); }
        .btn-checkout:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Success Screen ── */
        .success-screen {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: var(--so-bg);
        }
        .success-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--so-shadow);
            max-width: 380px; width: 100%;
        }
        .success-icon { font-size: 4rem; color: var(--so-primary); }
        .order-number { font-size: 2.5rem; font-weight: 800; color: var(--so-primary); }

        /* ── Spacer for cart ── */
        .cart-spacer { height: 80px; }

        @media (min-width: 768px) {
            .cart-sidebar {
                position: fixed;
                top: 0; right: 0; bottom: 0; left: auto;
                width: 340px;
                border-radius: 0;
                max-height: 100vh;
                transform: none !important;
                border-left: 1px solid #eee;
            }
            .cart-handle { display: none; }
            .main-content { margin-left: 340px; }
            .cart-spacer { display: none; }
        }
    </style>
</head>
<body>

<div
    x-data="selfOrderApp({{ json_encode($items) }}, {{ json_encode($categories) }}, {{ $table_id ?? 'null' }})"
    x-init="init()"
>

    {{-- ── Success Screen ── --}}
    <div x-show="orderPlaced" x-cloak class="success-screen">
        <div class="success-card">
            <div class="success-icon mb-3"><i class="fas fa-check-circle"></i></div>
            <h2 class="mb-1">{{ __('pos::pos.self_order.order_confirmed') }}</h2>
            <p class="text-muted mb-3">{{ __('pos::pos.self_order.order_number_label') }}</p>
            <div class="order-number mb-4" x-text="orderNumber"></div>
            <p class="text-muted small">{{ __('pos::pos.self_order.order_preparing') }}</p>
            <button class="btn btn-outline-secondary mt-3" @click="resetOrder()">
                <i class="fas fa-plus me-1"></i>{{ __('pos::pos.self_order.new_order') }}
            </button>
        </div>
    </div>

    {{-- ── Main Menu ── --}}
    <div x-show="!orderPlaced">

        {{-- Navbar --}}
        <nav class="so-navbar px-3 py-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="brand"><i class="fas fa-utensils me-2"></i>{{ config('app.name') }}</span>
                @if($table_id)
                    <span class="table-badge">
                        <i class="fas fa-chair me-1"></i>{{ __('pos::pos.self_order.table') }} {{ $table_id }}
                    </span>
                @endif
            </div>
        </nav>

        <div class="main-content">

            {{-- Category Filter --}}
            @if(count($categories) > 0)
            <div class="px-3 pt-3 pb-1">
                <div class="d-flex category-pills">
                    <button
                        class="cat-pill"
                        :class="{ active: activeCategory === null }"
                        @click="activeCategory = null"
                    >{{ __('pos::pos.self_order.all') }}</button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button
                            class="cat-pill"
                            :class="{ active: activeCategory === cat.id }"
                            @click="activeCategory = cat.id"
                            x-text="cat.name"
                        ></button>
                    </template>
                </div>
            </div>
            @endif

            {{-- Search --}}
            <div class="px-3 pt-2 pb-1">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-start-0"
                        placeholder="{{ __('pos::pos.self_order.search_placeholder') }}"
                        x-model="search"
                    >
                </div>
            </div>

            {{-- Items Grid --}}
            <div class="px-3 py-2">
                <div class="row g-3">
                    <template x-for="item in filteredItems" :key="item.id">
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="item-card" @click="addToCart(item)">
                                <template x-if="item.image">
                                    <img :src="item.image" :alt="item.name" loading="lazy">
                                </template>
                                <template x-if="!item.image">
                                    <div class="no-img"><i class="fas fa-box-open"></i></div>
                                </template>
                                <div class="card-body">
                                    <div class="item-name" x-text="item.name"></div>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <span class="item-price" x-text="formatPrice(item.sale_price)"></span>
                                        <button class="btn-add" @click.stop="addToCart(item)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Empty State --}}
                    <div x-show="filteredItems.length === 0" class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-search fa-2x mb-2 d-block"></i>
                        {{ __('pos::pos.self_order.no_items') }}
                    </div>
                </div>
            </div>

            <div class="cart-spacer"></div>
        </div>

        {{-- ── Cart Sidebar ── --}}
        <div class="cart-sidebar" :class="{ collapsed: cartCollapsed }">

            {{-- Handle (mobile) --}}
            <div class="cart-handle" @click="cartCollapsed = !cartCollapsed">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shopping-cart text-success"></i>
                    <strong>{{ __('pos::pos.self_order.cart') }}</strong>
                    <span class="cart-badge" x-text="cartCount" x-show="cartCount > 0"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-success" x-text="formatPrice(cartTotal)"></span>
                    <i class="fas fa-chevron-up" :class="{ 'fa-rotate-180': !cartCollapsed }"></i>
                </div>
            </div>

            <div class="p-3">

                {{-- Empty Cart --}}
                <div x-show="cart.length === 0" class="text-center text-muted py-3">
                    <i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>
                    <small>{{ __('pos::pos.self_order.cart_empty') }}</small>
                </div>

                {{-- Cart Items --}}
                <template x-for="(item, index) in cart" :key="item.id">
                    <div class="cart-item">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1 me-2">
                                <div class="fw-600 small" x-text="item.name"></div>
                                <div class="text-success small" x-text="formatPrice(item.price)"></div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button class="qty-btn" @click="decreaseQty(index)">
                                    <i class="fas fa-minus" style="font-size:.65rem"></i>
                                </button>
                                <span class="qty-display" x-text="item.quantity"></span>
                                <button class="qty-btn" @click="increaseQty(index)">
                                    <i class="fas fa-plus" style="font-size:.65rem"></i>
                                </button>
                            </div>
                            <div class="ms-2 text-end" style="min-width:60px">
                                <span class="fw-bold small" x-text="formatPrice(item.subtotal)"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Notes --}}
                <div x-show="cart.length > 0" class="mt-2">
                    <textarea
                        class="form-control form-control-sm"
                        rows="2"
                        placeholder="{{ __('pos::pos.self_order.notes_placeholder') }}"
                        x-model="notes"
                        maxlength="500"
                    ></textarea>
                </div>

                {{-- Total --}}
                <div x-show="cart.length > 0" class="d-flex justify-content-between align-items-center py-2 border-top mt-2">
                    <strong>{{ __('pos::pos.self_order.total') }}</strong>
                    <strong class="text-success fs-5" x-text="formatPrice(cartTotal)"></strong>
                </div>

                {{-- Error Message --}}
                <div x-show="errorMsg" class="alert alert-danger py-2 small mt-2" x-text="errorMsg"></div>

                {{-- Checkout Button --}}
                <button
                    class="btn-checkout mt-2"
                    :disabled="cart.length === 0 || submitting"
                    @click="submitOrder()"
                >
                    <span x-show="!submitting">
                        <i class="fas fa-paper-plane me-1"></i>
                        {{ __('pos::pos.self_order.place_order') }}
                    </span>
                    <span x-show="submitting">
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        {{ __('pos::pos.self_order.submitting') }}
                    </span>
                </button>

            </div>
        </div>

    </div>{{-- end !orderPlaced --}}

</div>{{-- end x-data --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
function selfOrderApp(allItems, categories, tableId) {
    return {
        // State
        allItems:       allItems,
        categories:     categories,
        activeCategory: null,
        search:         '',
        cart:           [],
        notes:          '',
        tableId:        tableId,
        cartCollapsed:  true,
        submitting:     false,
        errorMsg:       '',
        orderPlaced:    false,
        orderNumber:    null,

        // Init
        init() {
            // فتح السلة تلقائياً على الشاشات الكبيرة
            if (window.innerWidth >= 768) {
                this.cartCollapsed = false;
            }
        },

        // Computed
        get filteredItems() {
            return this.allItems.filter(item => {
                const matchCat = this.activeCategory === null || item.category_id === this.activeCategory;
                const matchSearch = !this.search || item.name.toLowerCase().includes(this.search.toLowerCase());
                return matchCat && matchSearch;
            });
        },

        get cartCount() {
            return this.cart.reduce((sum, i) => sum + i.quantity, 0);
        },

        get cartTotal() {
            return this.cart.reduce((sum, i) => sum + i.subtotal, 0);
        },

        // Methods
        addToCart(item) {
            const existing = this.cart.find(i => i.id === item.id);
            if (existing) {
                existing.quantity++;
                existing.subtotal = parseFloat((existing.price * existing.quantity).toFixed(2));
            } else {
                this.cart.push({
                    id:       item.id,
                    name:     item.name,
                    price:    item.sale_price,
                    quantity: 1,
                    unit_id:  item.units?.[0]?.id ?? null,
                    image:    item.image,
                    subtotal: item.sale_price,
                });
            }
            if (this.cartCollapsed) this.cartCollapsed = false;
        },

        increaseQty(index) {
            const item = this.cart[index];
            item.quantity++;
            item.subtotal = parseFloat((item.price * item.quantity).toFixed(2));
        },

        decreaseQty(index) {
            const item = this.cart[index];
            if (item.quantity <= 1) {
                this.cart.splice(index, 1);
            } else {
                item.quantity--;
                item.subtotal = parseFloat((item.price * item.quantity).toFixed(2));
            }
        },

        formatPrice(value) {
            return parseFloat(value || 0).toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        async submitOrder() {
            if (this.cart.length === 0) return;

            this.submitting = true;
            this.errorMsg   = '';

            const payload = {
                items: this.cart.map(i => ({
                    id:       i.id,
                    quantity: i.quantity,
                    price:    i.price,
                    unit_id:  i.unit_id,
                    name:     i.name,
                })),
                table_id: this.tableId,
                notes:    this.notes || null,
                _token:   document.querySelector('meta[name="csrf-token"]').content,
            };

            try {
                const res = await fetch('{{ route("pos.self-order.place") }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': payload._token,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (data.success) {
                    this.orderNumber = data.order_number;
                    this.orderPlaced = true;
                    this.cart        = [];
                } else {
                    this.errorMsg = data.message || '{{ __("pos::pos.self_order.order_failed") }}';
                }
            } catch (err) {
                this.errorMsg = '{{ __("pos::pos.self_order.network_error") }}';
            } finally {
                this.submitting = false;
            }
        },

        resetOrder() {
            this.orderPlaced = false;
            this.orderNumber = null;
            this.notes       = '';
            this.errorMsg    = '';
        },
    };
}
</script>

</body>
</html>
