<?php
namespace HaminShop\B2BSuite\Frontend;

class FrontendManager {

    public function init(): void {
        add_shortcode('haminshop_b2b_panel', [$this, 'renderCustomerPanel']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueScripts(): void {
        // Only load Alpine script if we are on a page containing the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'haminshop_b2b_panel')) {
            $js_url = HAMINSHOP_B2B_URL . 'assets/build/customer.js';

            wp_enqueue_script('haminshop-b2b-customer-js', $js_url, [], HAMINSHOP_B2B_VERSION, true);

            wp_localize_script('haminshop-b2b-customer-js', 'haminshopData', [
                'nonce' => wp_create_nonce('wp_rest'),
                'api_url' => esc_url_raw(rest_url('haminshop/v1'))
            ]);
        }
    }

    public function renderCustomerPanel(): string {
        if (!is_user_logged_in()) {
            return '<p>لطفا برای مشاهده پنل همکاران وارد شوید.</p>';
        }

        // Output the Alpine.js HTML structure
        ob_start();
        ?>
        <div x-data="b2bCustomerPanel" class="haminshop-b2b-panel bg-gray-50 p-6 rounded-lg shadow" dir="rtl">
            <h2 class="text-2xl font-bold mb-4 text-emerald-700">پنل کاربری همکاران B2B</h2>

            <div x-show="loading" class="text-blue-500 mb-4">در حال ارتباط با سرور...</div>
            <div x-show="message" x-text="message" class="bg-green-100 text-green-800 p-3 rounded mb-4"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quotations -->
                <div class="bg-white p-4 border rounded">
                    <h3 class="font-bold text-lg mb-2 border-b pb-2">پیش‌فاکتورهای من</h3>
                    <template x-if="quotes.length === 0">
                        <p class="text-sm text-gray-500">پیش‌فاکتوری یافت نشد.</p>
                    </template>
                    <ul class="space-y-2 mt-2">
                        <template x-for="quote in quotes" :key="quote.id">
                            <li class="flex justify-between p-2 bg-gray-50 border rounded text-sm">
                                <span x-text="'سفارش #' + quote.id"></span>
                                <span x-text="quote.status" class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- RMA -->
                <div class="bg-white p-4 border rounded">
                    <h3 class="font-bold text-lg mb-2 border-b pb-2">ثبت مرجوعی (RMA)</h3>
                    <input type="text" x-model="rmaReason" placeholder="دلیل مرجوعی..." class="w-full p-2 border rounded mb-2 text-sm" />
                    <button @click="submitRma(101)" class="w-full bg-red-600 text-white py-2 rounded text-sm hover:bg-red-700">ارسال درخواست برای سفارش ۱۰۱</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
