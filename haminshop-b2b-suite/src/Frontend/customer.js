import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {

    // B2B Customer Panel Data Component
    Alpine.data('b2bCustomerPanel', () => ({
        activeTab: 'quotes',
        quotes: [],
        rmaReason: '',
        splitPayment: { cash: 0, wallet: 0, cheque: 0, sayyad_id: '' },
        message: '',
        loading: false,

        init() {
            console.log('HaminShop B2B Customer Panel Initialized.');
            this.fetchQuotes();
        },

        async fetchQuotes() {
            this.loading = true;
            try {
                // In reality, this would fetch from /wp-json/haminshop/v1/customers/me
                // For demo, we assume we have quotes loaded.
                this.quotes = [
                    { id: 101, status: 'wc-quote-approved', total: 5000000 },
                    { id: 102, status: 'wc-quote-pending', total: 12000000 }
                ];
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        async submitRma(orderId) {
            if (!this.rmaReason) {
                alert('لطفا دلیل مرجوعی را وارد کنید.');
                return;
            }

            this.loading = true;
            try {
                const apiUrl = window.haminshopData?.api_url || '/wp-json/haminshop/v1';
                const response = await fetch(`${apiUrl}/rma/request`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.haminshopData?.nonce || ''
                    },
                    body: JSON.stringify({ order_id: orderId, reason: this.rmaReason })
                });
                const result = await response.json();
                if (result.success) {
                    this.message = result.message;
                    this.rmaReason = '';
                } else {
                    alert(result.message || 'خطا در ثبت مرجوعی');
                }
            } catch (e) {
                alert('ارتباط با سرور برقرار نشد.');
            } finally {
                this.loading = false;
            }
        },

        async processSplitPayment(orderId) {
            this.loading = true;
            try {
                const payload = {
                    order_id: orderId,
                    cash_amount: this.splitPayment.cash,
                    wallet_amount: this.splitPayment.wallet,
                    cheque_amount: this.splitPayment.cheque,
                    sayyad_id: this.splitPayment.sayyad_id
                };

                const apiUrl = window.haminshopData?.api_url || '/wp-json/haminshop/v1';
                const response = await fetch(`${apiUrl}/payments/split`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.haminshopData?.nonce || ''
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    this.message = 'پرداخت با موفقیت انجام شد!';
                } else {
                    alert(result.message || 'خطا در پرداخت');
                }
            } catch (e) {
                alert('خطا در ارتباط با سرور.');
            } finally {
                this.loading = false;
            }
        }
    }));
});

window.Alpine = Alpine;
Alpine.start();
