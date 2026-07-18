<?php
namespace HaminShop\B2BSuite\Modules\Payment;

class ChequeGateway extends \WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'haminshop_b2b_cheque';
        $this->icon               = apply_filters('woocommerce_cheque_icon', '');
        $this->has_fields         = true;
        $this->method_title       = 'پرداخت با چک صیادی';
        $this->method_description = 'ثبت چک صیادی بنفش با امکان استعلام آنلاین از بانک مرکزی.';

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'فعال/غیرفعال',
                'type'    => 'checkbox',
                'label'   => 'فعال‌سازی پرداخت با چک',
                'default' => 'yes'
            ],
            'title' => [
                'title'       => 'عنوان',
                'type'        => 'text',
                'description' => 'عنوانی که کاربر در زمان پرداخت می‌بیند.',
                'default'     => 'چک صیادی بنفش',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'توضیحات',
                'type'        => 'textarea',
                'default'     => 'لطفاً شناسه ۱۶ رقمی صیادی را وارد کنید. سیستم بلافاصله وضعیت را از بانک مرکزی استعلام می‌گیرد.',
            ]
        ];
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        // Ensure user has capability
        if (!current_user_can('pay_via_cheque')) {
            echo '<p style="color:red">شما مجوز پرداخت چکی را ندارید. لطفاً با کارشناس فروش تماس بگیرید.</p>';
            return;
        }

        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Field for Sayyad 16-digit ID
        woocommerce_form_field('sayyad_id', [
            'type'        => 'text',
            'class'       => ['form-row-wide'],
            'label'       => 'شناسه ۱۶ رقمی صیاد',
            'required'    => true,
            'placeholder' => '1234567890123456',
            'custom_attributes' => [
                'maxlength' => 16,
                'minlength' => 16,
                'pattern' => '\d{16}'
            ]
        ]);

        echo '</fieldset>';
    }

    public function validate_fields() {
        if (!current_user_can('pay_via_cheque')) {
            wc_add_notice('شما مجوز پرداخت چکی را ندارید.', 'error');
            return false;
        }

        $sayyadId = sanitize_text_field($_POST['sayyad_id'] ?? '');
        if (empty($sayyadId) || strlen($sayyadId) !== 16 || !is_numeric($sayyadId)) {
            wc_add_notice('شناسه صیاد باید دقیقاً ۱۶ رقم عدد باشد.', 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $sayyadId = sanitize_text_field($_POST['sayyad_id']);

        // Mock API Call (Scenario 7)
        $api = new \HaminShop\B2BSuite\Infrastructure\External\SayyadAPI();
        $status = $api->inquiry($sayyadId);

        if ($status === 'rejected') {
            $order->update_status('wc-cheque-rejected', 'استعلام صیاد رد شد (وضعیت قرمز). شناسه: ' . $sayyadId);

            // Apply behavioral penalty (Scenario 7 - Boundary Event)
            $this->applyBehavioralPenalty($order->get_customer_id());

            wc_add_notice('استعلام چک شما قرمز است و قابل پذیرش نیست. امتیاز اعتباری شما کاهش یافت.', 'error');
            return ['result' => 'fail', 'redirect' => ''];
        }

        // Store cheque metadata (in real app, encrypt sensitive data)
        $order->update_meta_data('_haminshop_cheque_sayyad_id', $sayyadId);
        $order->update_meta_data('_haminshop_cheque_status', 'pending_accountant');

        $order->update_status('wc-pending-financial', 'چک در انتظار بررسی فیزیکی توسط حسابدار.');
        $order->save();

        do_action('haminshop_cheque_uploaded', $order_id, $sayyadId);

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }

    private function applyBehavioralPenalty(int $userId): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'haminshop_b2b_customers';

        // Downgrade grade directly to D or reduce score logic
        $wpdb->update(
            $table_name,
            ['rfm_grade' => 'D'],
            ['user_id' => $userId],
            ['%s'],
            ['%d']
        );

        do_action('haminshop_rfm_grade_changed', $userId, 'D'); // simplified
    }
}
