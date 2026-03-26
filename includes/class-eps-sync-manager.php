<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Sync_Manager
{
    public const META_EXTERNAL_ID = '_eps_external_id';
    public const CRON_HOOK = 'eps_run_sync';

    private EPS_Api_Client $api_client;
    private EPS_Product_Importer $product_importer;

    public function __construct()
    {
        $this->api_client = new EPS_Api_Client();
        $this->product_importer = new EPS_Product_Importer();
    }

    public function init(): void
    {
        add_action(self::CRON_HOOK, [$this, 'run']);
    }

    public function run(): array
    {
        if (get_transient('eps_sync_lock')) {
            return ['success' => false, 'message' => 'Sync already running'];
        }

        set_transient('eps_sync_lock', 1, 10 * MINUTE_IN_SECONDS);

        $products = $this->api_client->fetch_products();

        if (is_wp_error($products)) {
            delete_transient('eps_sync_lock');
            return ['success' => false, 'message' => $products->get_error_message()];
        }

        $remote_ids = [];
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($products as $product_data) {
            if (empty($product_data['id'])) {
                $failed++;
                continue;
            }

            $remote_ids[] = (string) $product_data['id'];

            $result = $this->product_importer->import($product_data);

            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $failed++;
            }
        }

        $this->unpublish_missing_products($remote_ids);

        delete_transient('eps_sync_lock');

        return [
            'success' => true,
            'message' => "Created: {$created}, Updated: {$updated}, Failed: {$failed}",
        ];
    }

    private function unpublish_missing_products(array $remote_ids): void
    {
        $query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'private'],
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => self::META_EXTERNAL_ID,
                    'compare' => 'EXISTS',
                ],
            ],
            'fields' => 'ids',
        ]);

        foreach ($query->posts as $product_id) {
            $external_id = get_post_meta($product_id, self::META_EXTERNAL_ID, true);

            if ($external_id && ! in_array((string) $external_id, $remote_ids, true)) {
                wp_update_post([
                    'ID'          => $product_id,
                    'post_status' => 'draft',
                ]);
            }
        }
    }
}
