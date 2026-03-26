<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Settings
{
    public const OPTION_NAME = 'eps_settings';

    public static function defaults(): array
    {
        return [
            'api_base_url'   => 'https://example-api.test/wp-json/external-sync/v1/products',
            'api_token'      => '',
            'sync_enabled'   => 1,
            'sync_interval'  => 'hourly',
            'missing_action' => 'draft',
            'request_timeout' => 20,
        ];
    }

    public static function get(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args(is_array($saved) ? $saved : [], self::defaults());
    }

    public static function sanitize(array $input): array
    {
        return [
            'api_base_url'    => esc_url_raw($input['api_base_url'] ?? ''),
            'api_token'       => sanitize_text_field($input['api_token'] ?? ''),
            'sync_enabled'    => empty($input['sync_enabled']) ? 0 : 1,
            'sync_interval'   => in_array(($input['sync_interval'] ?? 'hourly'), ['hourly', 'twicedaily', 'daily'], true)
                ? $input['sync_interval']
                : 'hourly',
            'missing_action'  => 'draft',
            'request_timeout' => max(5, min(60, absint($input['request_timeout'] ?? 20))),
        ];
    }
}
