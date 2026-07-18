<?php
namespace HaminShop\B2BSuite\HTTP\REST\V1;

use WP_REST_Controller;
use WP_REST_Server;

class PaymentController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'haminshop/v1';
        $this->rest_base = 'payments';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/split', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'process_split_payment'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
    }

    public function check_permission() {
        return current_user_can('pay_via_split');
    }

    public function process_split_payment($request) {
        $params = $request->get_json_params();

        try {
            $useCase = new \HaminShop\B2BSuite\Application\Payment\ProcessSplitPaymentUseCase();
            $useCase->execute($params);

            return rest_ensure_response([
                'success' => true,
                'message' => 'پرداخت ترکیبی با موفقیت ثبت شد.'
            ]);
        } catch (\Exception $e) {
            return new \WP_Error('payment_failed', $e->getMessage(), ['status' => 400]);
        }
    }
}
