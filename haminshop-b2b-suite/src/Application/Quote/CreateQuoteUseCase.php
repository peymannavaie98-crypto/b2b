<?php
namespace HaminShop\B2BSuite\Application\Quote;

class CreateQuoteUseCase {
    public function execute(array $quoteData): int {
        $userId = get_current_user_id();
        if (!current_user_can('create_b2b_quote')) {
            throw new \Exception('شما دسترسی ایجاد پیش‌فاکتور را ندارید.');
        }

        // 1. Create WooCommerce Order with specific status
        $order = wc_create_order([
            'customer_id' => $userId,
            'status'      => 'wc-quote-pending' // Initial status from state machine
        ]);

        if (is_wp_error($order)) {
            throw new \Exception('خطا در ایجاد پیش‌فاکتور.');
        }

        // 2. Add Items
        foreach ($quoteData['items'] as $item) {
            $product = wc_get_product($item['product_id']);
            if (!$product) {
                throw new \Exception(sprintf('محصول با شناسه %d یافت نشد.', $item['product_id']));
            }

            $quantity = (int) $item['quantity'];
            if ($quantity <= 0) {
                throw new \Exception('تعداد سفارش باید بزرگتر از صفر باشد.');
            }

            $order->add_product($product, $quantity);
        }

        // 3. Set Logistics / Shipping
        if (isset($quoteData['shipping_method'])) {
            $item = new \WC_Order_Item_Shipping();
            $item->set_method_title($quoteData['shipping_method']);
            $order->add_item($item);
        }

        $order->calculate_totals();

        // 4. Implement 48h Price Lock (Scenario 4)
        $lockDuration = apply_filters('haminshop_price_lock_duration', 48);
        $expiryTime = time() + ($lockDuration * HOUR_IN_SECONDS);

        $order->update_meta_data('_haminshop_quote_expires_at', $expiryTime);
        $order->save();

        // 5. Schedule Action via Action Scheduler
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action($expiryTime, 'haminshop_check_price_lock_expired', ['order_id' => $order->get_id()]);
        }

        // 6. Dispatch Domain Events
        do_action('haminshop_quote_created', $order);
        do_action('haminshop_quote_price_locked', $order->get_id(), (new \DateTimeImmutable())->setTimestamp($expiryTime));

        return $order->get_id();
    }
}
