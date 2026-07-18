<?php
namespace HaminShop\B2BSuite\Modules\Logistics;

class WarehouseModule {

    public function init(): void {
        add_action('rest_api_init', function () {
            register_rest_route('haminshop/v1', '/shipping/(?P<id>[\d]+)/bijak', [
                'methods'             => 'POST',
                'callback'            => [$this, 'registerBijak'],
                'permission_callback' => [$this, 'checkWarehousePermission']
            ]);

            register_rest_route('haminshop/v1', '/shipping/(?P<id>[\d]+)/packing', [
                'methods'             => 'POST',
                'callback'            => [$this, 'startPacking'],
                'permission_callback' => [$this, 'checkWarehousePermission']
            ]);
        });
    }

    public function checkWarehousePermission() {
        return current_user_can('register_bijak') || current_user_can('generate_packing_slip');
    }

    public function startPacking(\WP_REST_Request $request) {
        $orderId = (int) $request->get_param('id');
        $order = wc_get_order($orderId);

        if (!$order) {
            return new \WP_Error('not_found', 'سفارش یافت نشد.', ['status' => 404]);
        }

        if ($order->get_status() !== 'processing') {
            return new \WP_Error('invalid_state', 'فقط سفارشات در حال پردازش قابل انتقال به بسته‌بندی هستند.', ['status' => 400]);
        }

        $order->update_status('wc-packing', 'فرآیند بسته‌بندی/پالت‌بندی توسط انباردار آغاز شد.');
        do_action('haminshop_packing_started', $orderId);

        return rest_ensure_response(['success' => true, 'message' => 'وضعیت به بسته‌بندی تغییر کرد.']);
    }

    public function registerBijak(\WP_REST_Request $request) {
        $orderId = (int) $request->get_param('id');
        $params = $request->get_json_params();

        $bijakCode = sanitize_text_field($params['bijak_code'] ?? '');
        $isPalletized = isset($params['is_palletized']) && $params['is_palletized'];
        $hasInsurance = isset($params['has_insurance']) && $params['has_insurance'];

        if (empty($bijakCode)) {
            return new \WP_Error('invalid_data', 'کد بیجک الزامی است.', ['status' => 400]);
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return new \WP_Error('not_found', 'سفارش یافت نشد.', ['status' => 404]);
        }

        $order->update_meta_data('_haminshop_bijak_code', $bijakCode);
        $order->update_meta_data('_haminshop_is_palletized', $isPalletized ? 'yes' : 'no');
        $order->update_meta_data('_haminshop_has_insurance', $hasInsurance ? 'yes' : 'no');

        $order->update_status('wc-shipped', "تحویل باربری شد. کد بیجک: {$bijakCode}");
        $order->save();

        do_action('haminshop_bijak_registered', $orderId, $bijakCode);
        do_action('haminshop_order_shipped', $orderId);

        // SMS would be triggered via hook or action scheduler here.

        return rest_ensure_response(['success' => true, 'message' => 'بیجک ثبت و سفارش ارسال شد.']);
    }
}
