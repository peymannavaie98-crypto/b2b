<?php
namespace HaminShop\B2BSuite\Modules\Customer;

class OnboardingModule {

    public function init(): void {
        // Hook into WooCommerce / WordPress registration
        add_action('user_register', [$this, 'handleB2BRegistration'], 10, 1);

        // Custom REST endpoint for document upload
        add_action('rest_api_init', function () {
            register_rest_route('haminshop/v1', '/customers/documents', [
                'methods'             => 'POST',
                'callback'            => [$this, 'uploadDocuments'],
                'permission_callback' => [$this, 'checkPermission']
            ]);
        });
    }

    public function handleB2BRegistration(int $userId): void {
        // We only want to handle this if it's a B2B registration form
        // For standard WC registration, they become 'customer'.
        // If a specific B2B hidden field or form is used:
        if (isset($_POST['is_b2b_registration']) && $_POST['is_b2b_registration'] === '1') {

            $user = get_userdata($userId);
            if ($user) {
                $user->set_role('b2b_pending');
            }

            // Create entry in custom table
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'haminshop_b2b_customers',
                [
                    'user_id' => $userId,
                    'status' => 'pending',
                    'business_type' => sanitize_text_field($_POST['business_type'] ?? 'petshop'),
                    'business_name' => sanitize_text_field($_POST['business_name'] ?? ''),
                    'docs_deadline' => gmdate('Y-m-d H:i:s', strtotime('+7 days')),
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            // Dispatch domain event
            do_action('haminshop_customer_registered', $userId);
        }
    }

    public function checkPermission(): bool {
        return current_user_can('upload_b2b_documents');
    }

    public function uploadDocuments(\WP_REST_Request $request) {
        $userId = get_current_user_id();

        // Require files
        $files = $request->get_file_params();
        if (empty($files)) {
            return new \WP_Error('no_files', 'هیچ فایلی آپلود نشده است.', ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded_files = [];
        foreach ($files as $file_key => $file_array) {
            $attachment_id = media_handle_sideload($file_array, 0);
            if (!is_wp_error($attachment_id)) {
                $uploaded_files[$file_key] = wp_get_attachment_url($attachment_id);
            }
        }

        // Dispatch domain event that docs are uploaded
        do_action('haminshop_docs_uploaded', $userId, $uploaded_files);

        // Update user status
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'haminshop_b2b_customers',
            ['status' => 'docs_incomplete'], // Waiting for accountant review
            ['user_id' => $userId],
            ['%s'],
            ['%d']
        );

        return rest_ensure_response(['success' => true, 'files' => $uploaded_files]);
    }
}
