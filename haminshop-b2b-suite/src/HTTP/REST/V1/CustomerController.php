<?php
namespace HaminShop\B2BSuite\HTTP\REST\V1;

use WP_REST_Controller;
use WP_REST_Server;

class CustomerController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'haminshop/v1';
        $this->rest_base = 'customers';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/me', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_current_customer'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_current_customer'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/rfm', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_rfm_score'],
                'permission_callback' => [$this, 'check_staff_permission']
            ]
        ]);
    }

    public function check_permission() {
        return is_user_logged_in();
    }

    public function check_staff_permission() {
        return current_user_can('view_rfm_data');
    }

    public function get_current_customer($request) {
        // Return current logged in B2B Customer data
        return rest_ensure_response(['success' => true, 'data' => []]);
    }

    public function update_current_customer($request) {
        return rest_ensure_response(['success' => true]);
    }

    public function get_rfm_score($request) {
        return rest_ensure_response(['success' => true, 'score' => 'A']);
    }
}
