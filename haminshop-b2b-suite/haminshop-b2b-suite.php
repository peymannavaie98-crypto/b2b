<?php
/**
 * Plugin Name: HaminShop Premium B2B Suite
 * Plugin URI: https://haminshop.com/b2b-suite
 * Description: مستندات جامع معماری، تحلیل و طراحی پلتفرم یکپارچه فروش عمده برای صنعت پت‌شاپ، کلینیک‌های دامپزشکی و داروخانه‌های دامی بر بستر وردپرس و ووکامرس.
 * Version: 3.0.0
 * Author: HaminShop Dev Team
 * Author URI: https://haminshop.com
 * Text Domain: haminshop-b2b-suite
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * WC requires at least: 8.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('HAMINSHOP_B2B_VERSION', '3.0.0');
define('HAMINSHOP_B2B_FILE', __FILE__);
define('HAMINSHOP_B2B_DIR', plugin_dir_path(__FILE__));
define('HAMINSHOP_B2B_URL', plugin_dir_url(__FILE__));

// Autoloader via Composer
if (file_exists(HAMINSHOP_B2B_DIR . 'vendor/autoload.php')) {
    require_once HAMINSHOP_B2B_DIR . 'vendor/autoload.php';
}

// Hook to WooCommerce HPOS feature
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize Plugin Core
add_action('plugins_loaded', function() {
    if (class_exists('HaminShop\B2BSuite\Core\Plugin')) {
        $plugin = \HaminShop\B2BSuite\Core\Plugin::getInstance();
        $plugin->init();
    }
});

// Activation Hook
register_activation_hook(__FILE__, function() {
    if (class_exists('HaminShop\B2BSuite\Infrastructure\Persistence\Migrations\Installer')) {
        \HaminShop\B2BSuite\Infrastructure\Persistence\Migrations\Installer::runMigrations();
    }
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});
