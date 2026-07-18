<?php
namespace HaminShop\B2BSuite\Admin;

class AdminMenu {

    public function init(): void {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function registerMenus(): void {
        // We add a single main page for the B2B Dashboards. The React router will handle the rest.
        add_menu_page(
            'مدیریت HaminShop B2B',
            'HaminShop B2B',
            'read', // Lowest capability required to see the menu. React handles deeper ACL.
            'haminshop-b2b-dashboard',
            [$this, 'renderReactApp'],
            'dashicons-store',
            56
        );
    }

    public function renderReactApp(): void {
        echo '<div class="wrap"><div id="haminshop-b2b-admin-root">بارگذاری داشبورد...</div></div>';
    }

    public function enqueueScripts(string $hook): void {
        if ($hook !== 'toplevel_page_haminshop-b2b-dashboard') {
            return;
        }

        $js_url = HAMINSHOP_B2B_URL . 'assets/build/admin.js';

        wp_enqueue_script('haminshop-b2b-admin-js', $js_url, [], HAMINSHOP_B2B_VERSION, true);

        // Pass essential data to React
        wp_localize_script('haminshop-b2b-admin-js', 'haminshopData', [
            'nonce' => wp_create_nonce('wp_rest'),
            'api_url' => esc_url_raw(rest_url('haminshop/v1')),
            'current_role' => $this->getCurrentUserHighestRole()
        ]);

        // Tailwind/CSS enqueue if compiled separately, but currently vite bundles it or relies on classes
    }

    private function getCurrentUserHighestRole(): string {
        $user = wp_get_current_user();
        if (in_array('administrator', (array) $user->roles)) return 'admin';
        if (in_array('b2b_accountant', (array) $user->roles)) return 'accountant';
        if (in_array('b2b_warehouse', (array) $user->roles)) return 'warehouse';
        if (in_array('b2b_sales_rep', (array) $user->roles)) return 'sales';
        return 'unknown';
    }
}
