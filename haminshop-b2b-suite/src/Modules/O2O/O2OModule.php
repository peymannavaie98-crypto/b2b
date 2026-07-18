<?php
namespace HaminShop\B2BSuite\Modules\O2O;

class O2OModule {

    public function init(): void {
        // Hook when an order is shipped/completed to activate O2O
        add_action('haminshop_order_shipped', [$this, 'activateO2OForOrder']);

        // Background job to clean up expired O2O listings
        add_action('haminshop_check_o2o_expiration', [$this, 'cleanupExpiredO2O']);

        // Show O2O locations on single product page (B2C view)
        add_action('woocommerce_after_single_product_summary', [$this, 'renderO2OMap'], 15);
    }

    public function activateO2OForOrder(int $orderId): void {
        $order = wc_get_order($orderId);
        if (!$order) return;

        $customerId = $order->get_customer_id();

        // Check if customer has O2O enabled in DB
        global $wpdb;
        $table = $wpdb->prefix . 'haminshop_b2b_customers';
        $o2oEnabled = $wpdb->get_var($wpdb->prepare("SELECT o2o_enabled FROM {$table} WHERE user_id = %d", $customerId));

        if ($o2oEnabled != 1) {
            return;
        }

        $durationDays = apply_filters('haminshop_o2o_display_duration', 60);
        $expiryTime = time() + ($durationDays * DAY_IN_SECONDS);

        // Store O2O data per product bought
        foreach ($order->get_items() as $item) {
            $productId = $item->get_product_id();

            // We use WP options table or custom table for relationship (Product -> B2B Customer Location)
            // For simplicity here, storing as transient/option array mapped by product_id
            $locations = get_option("haminshop_o2o_product_{$productId}", []);

            $locations[$customerId] = [
                'expires_at' => $expiryTime,
                'order_id'   => $orderId
            ];

            update_option("haminshop_o2o_product_{$productId}", $locations, false);

            do_action('haminshop_o2o_activated', $productId, $customerId);
        }
    }

    public function cleanupExpiredO2O(): void {
        global $wpdb;
        $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'haminshop_o2o_product_%'");

        $now = time();
        foreach ($options as $opt) {
            $locations = maybe_unserialize($opt->option_value);
            $changed = false;

            if (is_array($locations)) {
                foreach ($locations as $customerId => $data) {
                    if ($data['expires_at'] < $now) {
                        unset($locations[$customerId]);
                        $changed = true;

                        // Extract product id
                        $productId = str_replace('haminshop_o2o_product_', '', $opt->option_name);
                        do_action('haminshop_o2o_expired', (int)$productId, $customerId);
                    }
                }

                if ($changed) {
                    if (empty($locations)) {
                        delete_option($opt->option_name);
                    } else {
                        update_option($opt->option_name, $locations, false);
                    }
                }
            }
        }
    }

    public function renderO2OMap(): void {
        global $product;
        if (!$product) return;

        // If the user is B2B, maybe they don't need to see the B2C map, but we'll show it generally
        $locations = get_option("haminshop_o2o_product_{$product->get_id()}", []);

        if (empty($locations)) return;

        // In a real scenario, we'd fetch lat/lng from B2B customers table for active $locations
        echo '<div class="haminshop-o2o-map-container" style="margin-top:2rem; padding:1rem; border:1px solid #e2e8f0; border-radius:8px;">';
        echo '<h3><span style="color:#059669;">✔</span> قابل تهیه در پت‌شاپ‌های منتخب (فروش حضوری)</h3>';
        echo '<div id="o2o-map" style="height:300px; background:#f1f5f9; display:flex; align-items:center; justify-content:center;">نقشه تعاملی نشان (Leaflet) در این قسمت لود می‌شود.</div>';
        echo '</div>';
    }
}
