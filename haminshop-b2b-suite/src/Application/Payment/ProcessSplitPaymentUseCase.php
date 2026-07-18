<?php
namespace HaminShop\B2BSuite\Application\Payment;

use HaminShop\B2BSuite\Domain\Payment\SplitPayment;

class ProcessSplitPaymentUseCase {
    public function execute(array $paymentData): bool {
        $orderId = (int) $paymentData['order_id'];
        $order = wc_get_order($orderId);

        if (!$order) {
            throw new \Exception('سفارش یافت نشد.');
        }

        if ($order->get_customer_id() !== get_current_user_id()) {
            throw new \Exception('شما مجوز دسترسی به این سفارش را ندارید.');
        }

        $split = new SplitPayment();
        $split->orderId = $orderId;
        $split->totalAmount = (float) $order->get_total();
        $split->cashAmount = (float) ($paymentData['cash_amount'] ?? 0);
        $split->walletAmount = (float) ($paymentData['wallet_amount'] ?? 0);
        $split->chequeAmount = (float) ($paymentData['cheque_amount'] ?? 0);

        if (!$split->validate()) {
            throw new \Exception('مجموع مبالغ پرداختی با مبلغ کل سفارش همخوانی ندارد.');
        }

        // 1. Process Wallet Payment
        if ($split->walletAmount > 0) {
            $this->deductFromWallet($order->get_customer_id(), $split->walletAmount);
            $order->add_order_note(sprintf('مبلغ %s از کیف پول کسر شد.', wc_price($split->walletAmount)));
        }

        // 2. Process Cheque Payment (if any)
        if ($split->chequeAmount > 0) {
            $sayyadId = sanitize_text_field($paymentData['sayyad_id']);
            $api = new \HaminShop\B2BSuite\Infrastructure\External\SayyadAPI();
            $status = $api->inquiry($sayyadId);

            if ($status === 'rejected') {
                // Gateway Exception (Scenario 4 / BPMN Boundary)
                // Refund wallet if cheque fails immediately, or put on hold.
                $this->refundWallet($order->get_customer_id(), $split->walletAmount);
                $order->update_status('wc-cheque-rejected', 'استعلام صیاد رد شد و مبلغ کیف پول برگشت داده شد.');
                throw new \Exception('استعلام چک رد شد.');
            }
            $order->update_meta_data('_haminshop_cheque_amount', $split->chequeAmount);
            $order->update_meta_data('_haminshop_cheque_sayyad_id', $sayyadId);
            $order->add_order_note(sprintf('مبلغ %s طی چک صیادی %s ثبت شد.', wc_price($split->chequeAmount), $sayyadId));
        }

        // 3. Process Cash (IPG)
        // For cash part, we would typically generate a payment link or redirect to IPG.
        // In this use case, if cash is > 0, we set status to pending-payment and redirect.
        if ($split->cashAmount > 0) {
            $order->update_meta_data('_haminshop_cash_amount_pending', $split->cashAmount);
            $order->update_status('wc-pending', 'در انتظار پرداخت نقدی الباقی مبلغ.');
        } else {
            // Only wallet + cheque
            $order->update_status('wc-pending-financial', 'پرداخت ترکیبی با موفقیت ثبت شد و در انتظار تایید حسابدار است.');
        }

        $order->save();

        do_action('haminshop_split_payment_started', $orderId, $paymentData);

        return true;
    }

    private function deductFromWallet(int $userId, float $amount): void {
        global $wpdb;
        $table = $wpdb->prefix . 'haminshop_b2b_customers';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET wallet_balance = wallet_balance - %f WHERE user_id = %d AND wallet_balance >= %f",
            $amount, $userId, $amount
        ));

        if ($wpdb->rows_affected === 0) {
            throw new \Exception('موجودی کیف پول کافی نیست.');
        }
    }

    private function refundWallet(int $userId, float $amount): void {
        global $wpdb;
        $table = $wpdb->prefix . 'haminshop_b2b_customers';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET wallet_balance = wallet_balance + %f WHERE user_id = %d",
            $amount, $userId
        ));
    }
}
