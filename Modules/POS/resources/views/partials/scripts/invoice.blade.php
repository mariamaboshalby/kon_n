    // Save Functions
    $('#saveAndPrintBtn').on('click', function() {
        saveInvoice(true);
    });

    $('#saveOnlyBtn').on('click', function() {
        saveInvoice(false);
    });

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async function saveInvoice(print = false) {
        const localId = generateUUID();

        const data = {
            local_id: localId,
            items: cart,
            customer_id: selectedCustomer,
            table: selectedTable,
            notes: invoiceNotes,
            payment_method: $('#paymentMethod').val(),
            cash_amount: $('#cashAmount').val() || 0,
            card_amount: $('#cardAmount').val() || 0,
            cash_account_id: $('#cashAccountId').val() || null,
            bank_account_id: $('#bankAccountId').val() || null,
            invoice_type: $('#invoiceTypeSelect').val() || 102,
        };

        if (!isOnline) {
            try {
                await db.saveTransaction(data);
                showToast(POS_TRANS.saved_locally, 'info');
                const paymentModalInstance = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                if (paymentModalInstance) paymentModalInstance.hide();
                cart = [];
                updateCartDisplay();
                updatePendingCount();
                return;
            } catch (err) {
                showToast(POS_TRANS.save_local_error, 'error');
                return;
            }
        }

        // فتح نافذة الطباعة قبل الـ AJAX عشان المتصفح ما يبلوكهاش
        let printWindow = null;
        if (print) {
            printWindow = window.open('about:blank', '_blank');
        }

        $.ajax({
            url: '{{ route("pos.api.store") }}',
            method: 'POST',
            data: data,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                showToast(POS_TRANS.saved_successfully, 'success');

                const paymentModalInstance = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                if (paymentModalInstance) paymentModalInstance.hide();

                cart = [];
                selectedTable = null;
                invoiceNotes = '';
                updateCartDisplay();

                if (response.server_id && data.local_id) {
                    db.updateTransactionServerId(data.local_id, response.server_id);
                }

                updatePendingCount();

                if (print && response.operhead_id && printWindow) {
                    const printUrl = '{{ route("pos.print", ":id") }}'.replace(':id', response.operhead_id);
                    printWindow.location.href = printUrl;
                } else if (printWindow) {
                    printWindow.close();
                }
            },
            error: function(xhr) {
                if (printWindow) printWindow.close();
                db.saveTransaction(data).then(() => {
                    showToast(POS_TRANS.saved_locally_later, 'warning');
                    const paymentModalInstance = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    if (paymentModalInstance) paymentModalInstance.hide();
                    cart = [];
                    updateCartDisplay();
                    updatePendingCount();
                }).catch(() => {
                    showToast(POS_TRANS.save_error, 'error');
                });
            }
        });
    }
