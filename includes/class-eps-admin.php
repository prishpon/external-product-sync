<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Admin
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function menu(): void
    {
        add_submenu_page(
            'woocommerce',
            'External Product Sync',
            'External Product Sync',
            'manage_woocommerce',
            'external-product-sync',
            [$this, 'render']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'eps_settings_group',
            EPS_Settings::OPTION_NAME,
            ['sanitize_callback' => [EPS_Settings::class, 'sanitize']]
        );
    }

    public function render(): void
    {
        $settings = EPS_Settings::get();
?>
        <div class="wrap">
            <h1>External Product Sync</h1>
            <form method="post" action="options.php">
                <?php settings_fields('eps_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Base URL</th>
                        <td>
                            <input type="url" name="eps_settings[api_base_url]" value="<?php echo esc_attr($settings['api_base_url']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>API Token</th>
                        <td>
                            <input type="password" name="eps_settings[api_token]" value="<?php echo esc_attr($settings['api_token']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>Sync Interval</th>
                        <td>
                            <select name="eps_settings[sync_interval]">
                                <option value="hourly" <?php selected($settings['sync_interval'], 'hourly'); ?>>Hourly</option>
                                <option value="twicedaily" <?php selected($settings['sync_interval'], 'twicedaily'); ?>>Twice Daily</option>
                                <option value="daily" <?php selected($settings['sync_interval'], 'daily'); ?>>Daily</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Enable Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="eps_settings[sync_enabled]" value="1" <?php checked($settings['sync_enabled'], 1); ?>>
                                Enable scheduled sync
                            </label>
                        </td>
                    </tr>
                    <p>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=eps_manual_sync'), 'eps_manual_sync')); ?>" class="button button-primary">
                            Run Manual Sync
                        </a>
                    </p>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }
}
