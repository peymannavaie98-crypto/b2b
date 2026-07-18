<?php
namespace HaminShop\B2BSuite\Modules\Compliance;

class SamaneModyanModule {

    public function init(): void {
        // Queue order for tax invoice submission when order is completed
        add_action('woocommerce_order_status_completed', [$this, 'queueForTaxSubmission']);
    }

    public function queueForTaxSubmission(int $orderId): void {
        $order = wc_get_order($orderId);
        if (!$order) return;

        // Ensure it's a B2B order (has a B2B customer ID or quote metadata)
        $isB2B = $order->get_meta('_haminshop_b2b_order') || in_array('b2b_verified', (array) get_userdata($order->get_customer_id())->roles ?? []);

        if ($isB2B) {
            // In a real application, we would use Action Scheduler to safely retry.
            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(time() + 60, 'haminshop_send_to_samane_modyan', ['order_id' => $orderId]);
            }
        }
    }

    // This would be hooked to 'haminshop_send_to_samane_modyan'
    public function submitInvoice(int $orderId): void {
        $order = wc_get_order($orderId);
        if (!$order) return;

        // Mock API Call to Iranian Tax Authority (Samane Modyan)
        // Usually involves signing the payload with a hardware token or private key
        $success = true;

        if ($success) {
            $taxId = 'TAX-' . wp_generate_uuid4();
            $order->update_meta_data('_samane_modyan_tax_id', $taxId);
            $order->add_order_note('صورتحساب الکترونیکی با موفقیت به سامانه مودیان ارسال شد. شناسه: ' . $taxId);
            $order->save();
        } else {
            // Log failure and let Action Scheduler retry
            error_log('Failed to send invoice to Samane Modyan for order: ' . $orderId);
            throw new \Exception('ارتباط با سامانه مودیان برقرار نشد.');
        }
    }
}
