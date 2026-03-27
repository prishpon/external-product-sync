<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Plugin
{
    public function init(): void
    {
        $admin = new EPS_Admin();
        $admin->init();

        $sync_manager = new EPS_Sync_Manager();
        $sync_manager->init();

        register_activation_hook(EPS_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(EPS_PLUGIN_FILE, [$this, 'deactivate']);

        add_action('admin_post_eps_manual_sync', [$this, 'manual_sync']);
    }

    public function activate(): void
    {
        $settings = EPS_Settings::get();

        if (! wp_next_scheduled(EPS_Sync_Manager::CRON_HOOK)) {
            wp_schedule_event(time() + 300, $settings['sync_interval'], EPS_Sync_Manager::CRON_HOOK);
        }
    }

    public function deactivate(): void
    {
        $timestamp = wp_next_scheduled(EPS_Sync_Manager::CRON_HOOK);

        if ($timestamp) {
            wp_unschedule_event($timestamp, EPS_Sync_Manager::CRON_HOOK);
        }
    }

    public function manual_sync(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die('Permission denied');
        }

        check_admin_referer('eps_manual_sync');

        $sync_manager = new EPS_Sync_Manager();
        $result = $sync_manager->run();

        wp_safe_redirect(add_query_arg([
            'page'    => 'external-product-sync',
            'message' => rawurlencode($result['message']),
        ], admin_url('admin.php')));
        exit;
    }
}
