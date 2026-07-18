<?php
namespace HaminShop\B2BSuite\Modules\PricingEngine;

class PricingModule {

    public function init(): void {
        // Hook into WooCommerce price filters
        add_filter('woocommerce_product_get_price', [$this, 'applyB2BPrice'], 10, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'applyB2BPrice'], 10, 2);

        // Change price HTML to show B2B price alongside crossed-out B2C price
        add_filter('woocommerce_get_price_html', [$this, 'modifyPriceHtml'], 10, 2);
    }

    public function applyB2BPrice($price, $product) {
        if (!current_user_can('read_b2b_prices')) {
            return $price;
        }

        // Logic to get B2B base price (e.g., from product meta)
        $b2bPrice = $product->get_meta('_haminshop_b2b_price');

        if (empty($b2bPrice)) {
            return $price; // Fallback to B2C price if no B2B price set
        }

        // 1. Get B2B Base Price
        $finalPrice = (float) $b2bPrice;

        // 2. Apply RFM Grade Discount
        $finalPrice = $this->applyRFMDiscount($finalPrice, get_current_user_id());

        // 3. Apply Public Filters (as per spec: haminshop_b2b_price)
        $finalPrice = apply_filters('haminshop_b2b_price', $finalPrice, $product, get_current_user_id());

        return $finalPrice;
    }

    private function applyRFMDiscount(float $price, int $userId): float {
        $cache_key = 'haminshop_rfm_grade_' . $userId;
        $grade = wp_cache_get($cache_key);

        if ($grade === false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'haminshop_b2b_customers';
            $grade = $wpdb->get_var($wpdb->prepare("SELECT rfm_grade FROM {$table_name} WHERE user_id = %d", $userId));
            if (!$grade) {
                $grade = 'D'; // Default fallback
            }
            wp_cache_set($cache_key, $grade, '', 24 * HOUR_IN_SECONDS);
        }

        $discounts = [
            'A+' => 0.10, // 10% discount
            'A'  => 0.07, // 7% discount
            'B'  => 0.05, // 5% discount
            'C'  => 0.02, // 2% discount
            'D'  => 0.00
        ];

        $discountPercent = $discounts[$grade] ?? 0.00;
        return $price - ($price * $discountPercent);
    }

    public function modifyPriceHtml($price_html, $product) {
        if (!current_user_can('read_b2b_prices')) {
            return $price_html;
        }

        $rawB2bPrice = $this->applyB2BPrice($product->get_regular_price(), $product);

        $b2cPrice = wc_get_price_to_display($product, ['qty' => 1, 'price' => $product->get_regular_price()]);
        $b2bPriceDisplay = wc_get_price_to_display($product, ['qty' => 1, 'price' => $rawB2bPrice]);

        // If prices are different, show B2C crossed out and B2B active
        if ($b2bPriceDisplay != $b2cPrice) {
            $formatted_b2c = wc_price($b2cPrice);
            $formatted_b2b = wc_price($b2bPriceDisplay);

            return sprintf(
                '<del class="b2c-price" style="opacity:0.6; font-size:0.8em;">%s</del> <ins class="b2b-price" style="text-decoration:none; color:#059669; font-weight:bold;">%s</ins>',
                $formatted_b2c,
                $formatted_b2b
            );
        }

        return $price_html;
    }
}
