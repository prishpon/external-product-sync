<?php

/**
 * Plugin Name: External Product Sync
 * Description: Sync WooCommerce products from an external API.
 * Author:prishpon 
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) {
    exit;
}

define('EPS_PLUGIN_FILE', __FILE__);
define('EPS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EPS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once EPS_PLUGIN_PATH . 'includes/class-eps-plugin.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-settings.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-admin.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-api-client.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-sync-manager.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-product-importer.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-image-importer.php';
require_once EPS_PLUGIN_PATH . 'includes/class-eps-variable-importer.php';

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    $plugin = new EPS_Plugin();
    $plugin->init();
});
