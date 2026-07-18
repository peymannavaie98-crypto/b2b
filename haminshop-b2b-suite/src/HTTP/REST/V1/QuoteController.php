<?php
namespace HaminShop\B2BSuite\HTTP\REST\V1;

use WP_REST_Controller;
use WP_REST_Server;

class QuoteController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'haminshop/v1';
        $this->rest_base = 'quotes';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_quote'],
                'permission_callback' => [$this, 'check_create_permission']
            ]
        ]);

        // Approve, Negotiate, etc...
    }

    public function check_create_permission() {
        return current_user_can('create_b2b_quote');
    }

    public function create_quote($request) {
        $params = $request->get_json_params();
        if (empty($params['items'])) {
            return new \WP_Error('invalid_data', 'موارد پیش‌فاکتور نمی‌تواند خالی باشد.', ['status' => 400]);
        }

        try {
            $useCase = new \HaminShop\B2BSuite\Application\Quote\CreateQuoteUseCase();
            $quoteId = $useCase->execute($params);

            return rest_ensure_response([
                'success' => true,
                'quote_id' => $quoteId,
                'message' => 'پیش‌فاکتور با موفقیت ایجاد شد و موجودی/قیمت برای ۴۸ ساعت قفل گردید.'
            ]);
        } catch (\Exception $e) {
            return new \WP_Error('quote_creation_failed', $e->getMessage(), ['status' => 500]);
        }
    }
}
