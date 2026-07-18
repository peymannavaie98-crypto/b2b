<?php
namespace HaminShop\B2BSuite\HTTP\REST\V1;

use WP_REST_Controller;
use WP_REST_Server;
use HaminShop\B2BSuite\Application\RMA\ApproveRMAUseCase;

class RMAController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'haminshop/v1';
        $this->rest_base = 'rma';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/request', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'request_rma'],
                'permission_callback' => [$this, 'check_request_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/approve', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'approve_rma'],
                'permission_callback' => [$this, 'check_staff_permission']
            ]
        ]);
    }

    public function check_request_permission() {
        return current_user_can('request_rma');
    }

    public function check_staff_permission() {
        return current_user_can('manage_woocommerce'); // Or specific accountant role
    }

    public function request_rma($request) {
        $params = $request->get_json_params();
        $orderId = (int) ($params['order_id'] ?? 0);
        $reason = sanitize_text_field($params['reason'] ?? '');

        $order = wc_get_order($orderId);
        if (!$order || $order->get_customer_id() !== get_current_user_id()) {
            return new \WP_Error('invalid_order', 'سفارش نامعتبر است.', ['status' => 400]);
        }

        if ($order->get_status() !== 'completed') {
            return new \WP_Error('invalid_state', 'فقط برای سفارش‌های تکمیل شده امکان ثبت مرجوعی وجود دارد.', ['status' => 400]);
        }

        $order->update_meta_data('_haminshop_rma_reason', $reason);
        // For simplicity, request full amount
        $order->update_meta_data('_haminshop_rma_amount', $order->get_total());

        $order->update_status('wc-rma-requested', 'درخواست مرجوعی توسط همکار ثبت شد: ' . $reason);
        $order->save();

        do_action('haminshop_rma_requested', $orderId);

        return rest_ensure_response(['success' => true, 'message' => 'درخواست مرجوعی با موفقیت ثبت شد.']);
    }

    public function approve_rma($request) {
        $rmaId = (int) $request->get_param('id');
        $params = $request->get_json_params();
        $method = sanitize_text_field($params['refund_method'] ?? 'wallet');

        try {
            $useCase = new ApproveRMAUseCase();
            $useCase->execute($rmaId, $method);
            return rest_ensure_response(['success' => true, 'message' => 'RMA با موفقیت تایید شد.']);
        } catch (\Exception $e) {
            return new \WP_Error('rma_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
