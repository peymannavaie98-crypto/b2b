<?php
namespace HaminShop\B2BSuite\Application\RMA;

class ApproveRMAUseCase {
    public function execute(int $rmaId, string $refundMethod): void {
        // Mock DB connection for demonstration purposes
        global $wpdb;
        $order = wc_get_order($rmaId); // Assuming RMA uses the order object or a linked entity

        if (!$order) {
            throw new \Exception('درخواست مرجوعی یافت نشد.');
        }

        $refundAmount = (float) $order->get_meta('_haminshop_rma_amount');

        // Based on scenario 10, refund to wallet or standard
        if ($refundMethod === 'wallet') {
            $table = $wpdb->prefix . 'haminshop_b2b_customers';
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET wallet_balance = wallet_balance + %f WHERE user_id = %d",
                $refundAmount, $order->get_customer_id()
            ));

            $order->add_order_note(sprintf('مبلغ %s بابت مرجوعی به کیف پول همکار اضافه شد.', wc_price($refundAmount)));
        } else {
            // e.g. bank transfer, replacement product handled manually by ops
            $order->add_order_note('مرجوعی تایید و پرداخت از طریق حسابداری (حواله/چک) انجام می‌شود.');
        }

        $order->update_status('wc-rma-approved', 'درخواست مرجوعی (RMA) تایید شد.');
        $order->save();

        do_action('haminshop_rma_approved', $rmaId);
        do_action('haminshop_rma_refunded', $rmaId, $refundMethod);
    }
}
