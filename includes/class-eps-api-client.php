<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Api_Client
{
    public function fetch_products(int $page = 1, int $per_page = 100)
    {
        $settings = EPS_Settings::get();

        $url = add_query_arg([
            'page'     => $page,
            'per_page' => $per_page,
        ], $settings['api_base_url']);

        $response = wp_remote_get($url, [
            'timeout' => (int) $settings['request_timeout'],
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $settings['api_token'],
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('eps_api_error', 'API returned status code ' . $code);
        }

        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['products']) || ! is_array($data['products'])) {
            return new WP_Error('eps_invalid_response', 'Invalid API response format.');
        }

        return $data['products'];
    }
}
