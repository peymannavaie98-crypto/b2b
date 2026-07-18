<?php
namespace HaminShop\B2BSuite\Modules\Order;

class QuoteModule {

    public function init(): void {
        // Register custom order statuses
        add_action('init', [$this, 'registerCustomStatuses']);
        add_filter('wc_order_statuses', [$this, 'addStatusesToWooCommerce']);

        // Handle Scheduled Event
        add_action('haminshop_check_price_lock_expired', [$this, 'handlePriceLockExpiration']);
    }

    public function registerCustomStatuses(): void {
        $statuses = [
            'wc-quote-pending' => 'در انتظار بررسی',
            'wc-quote-negotiating' => 'در حال مذاکره',
            'wc-quote-approved' => 'تایید شده',
            'wc-quote-expired' => 'منقضی شده',
            'wc-pending-financial' => 'در انتظار تایید مالی',
            'wc-cheque-verifying' => 'در حال استعلام صیاد',
            'wc-cheque-rejected' => 'چک رد شده',
            'wc-packing' => 'در حال بسته‌بندی',
            'wc-ready-to-ship' => 'آماده تحویل به باربری',
            'wc-shipped' => 'تحویل باربری',
            'wc-rma-requested' => 'درخواست مرجوعی'
        ];

        foreach ($statuses as $slug => $label) {
            register_post_status($slug, [
                'label'                     => $label,
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>')
            ]);
        }
    }

    public function addStatusesToWooCommerce(array $order_statuses): array {
        $new_statuses = [];
        foreach ($order_statuses as $key => $status) {
            $new_statuses[$key] = $status;
        }

        $new_statuses['wc-quote-pending'] = 'در انتظار بررسی';
        $new_statuses['wc-quote-approved'] = 'پیش‌فاکتور تایید شده';
        $new_statuses['wc-quote-expired'] = 'پیش‌فاکتور منقضی شده';
        // Add others as needed to WC dropdowns...

        return $new_statuses;
    }

    public function handlePriceLockExpiration(int $orderId): void {
        $order = wc_get_order($orderId);
        if (!$order) return;

        // If the order is still in quote-pending or quote-negotiating, expire it
        if (in_array($order->get_status(), ['quote-pending', 'quote-negotiating'])) {
            $order->update_status('wc-quote-expired', 'تایمر ۴۸ ساعته قفل قیمت به پایان رسید.');
            do_action('haminshop_quote_expired', $orderId);

            // Note: If we reserved stock, we should release it here.
        }
    }
}
