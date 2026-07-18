<?php
namespace HaminShop\B2BSuite\Application\ACL;

class RoleManager {

    public function registerRoles(): void {

        // 1. Pending B2B Customer
        add_role('b2b_pending', 'همکار در انتظار', [
            'read' => true,
            'upload_b2b_documents' => true, // Secure custom cap instead of wide upload_files
        ]);

        // 2. Verified B2B Customer
        add_role('b2b_verified', 'همکار تایید شده', [
            'read' => true,
            'read_b2b_prices' => true,
            'create_b2b_quote' => true,
            'pay_via_cheque' => true,
            'pay_via_split' => true,
            'manage_o2o_location' => true,
            'request_rma' => true,
        ]);

        // 3. Sales Representative
        add_role('b2b_sales_rep', 'کارشناس فروش', [
            'read' => true,
            'read_b2b_quotes' => true,
            'edit_b2b_quotes' => true,
            'view_assigned_customers' => true,
            'manage_b2b_negotiations' => true,
            'approve_b2b_quote' => true,
            'set_allowed_payment_methods' => true,
            'view_rfm_data' => true, // Limited scope checked dynamically
        ]);

        // 4. Accountant
        add_role('b2b_accountant', 'حسابدار B2B', [
            'read' => true,
            'verify_cheques' => true,
            'inquiry_sayyad' => true,
            'edit_b2b_credit_limits' => true,
            'verify_b2b_documents' => true,
            'manage_wallets_manually' => true,
            'view_all_b2b_orders' => true,
            'change_to_processing' => true,
            'view_rfm_data' => true,
        ]);

        // 5. Warehouse / Logistics
        add_role('b2b_warehouse', 'انباردار', [
            'read' => true,
            'view_all_b2b_orders' => true,
            'generate_packing_slip' => true,
            'set_palletizing' => true,
            'set_insurance_label' => true,
            'register_bijak' => true,
            'complete_b2b_orders' => true,
        ]);

        // B2C Customer already exists in WooCommerce as 'customer',
        // we just ensure they have basic B2C price viewing (standard WP/WC).

        // Add all custom capabilities to administrator role
        $this->addCapabilitiesToAdmin();
    }

    private function addCapabilitiesToAdmin(): void {
        $role = get_role('administrator');
        if (!$role) return;

        $capabilities = [
            'read_b2b_prices', 'create_b2b_quote', 'pay_via_cheque', 'pay_via_split',
            'manage_o2o_location', 'request_rma', 'read_b2b_quotes', 'edit_b2b_quotes',
            'view_assigned_customers', 'manage_b2b_negotiations', 'approve_b2b_quote',
            'set_allowed_payment_methods', 'verify_cheques', 'inquiry_sayyad',
            'edit_b2b_credit_limits', 'verify_b2b_documents', 'manage_wallets_manually',
            'view_all_b2b_orders', 'change_to_processing', 'generate_packing_slip',
            'set_palletizing', 'set_insurance_label', 'register_bijak',
            'complete_b2b_orders', 'view_rfm_data', 'manage_b2b_settings'
        ];

        foreach ($capabilities as $cap) {
            $role->add_cap($cap);
        }
    }
}
