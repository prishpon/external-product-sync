<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Product_Importer
{
    public function import(array $data): string
    {
        $type = $data['type'] ?? 'simple';

        if ($type === 'variable') {
            $variable_importer = new EPS_Variable_Importer();
            return $variable_importer->import($data);
        }

        return $this->import_simple($data);
    }

    private function import_simple(array $data): string
    {
        $external_id   = sanitize_text_field((string) ($data['id'] ?? ''));
        $sku           = sanitize_text_field((string) ($data['sku'] ?? ''));
        $name          = sanitize_text_field((string) ($data['name'] ?? ''));
        $description   = wp_kses_post((string) ($data['description'] ?? ''));
        $regular_price = wc_format_decimal((string) ($data['regular_price'] ?? ''));
        $sale_price    = wc_format_decimal((string) ($data['sale_price'] ?? ''));
        $stock_qty     = isset($data['stock_quantity']) ? absint($data['stock_quantity']) : 0;

        if ($external_id === '' || $name === '') {
            return 'failed';
        }

        $product_id = $this->find_product($external_id, $sku);
        $is_new = ! $product_id;

        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

        if (! $product) {
            return 'failed';
        }

        $product->set_name($name);
        $product->set_description($description);
        $product->set_sku($sku);
        $product->set_regular_price($regular_price);
        $product->set_sale_price($sale_price);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_qty);
        $product->set_stock_status($stock_qty > 0 ? 'instock' : 'outofstock');
        $product->set_status('publish');

        $saved_id = $product->save();

        update_post_meta($saved_id, EPS_Sync_Manager::META_EXTERNAL_ID, $external_id);

        if (! empty($data['categories']) && is_array($data['categories'])) {
            $this->assign_categories($saved_id, $data['categories']);
        }

        if (! empty($data['image'])) {
            $image_importer = new EPS_Image_Importer();
            $image_importer->import_featured_image($saved_id, $data['image']);
        }

        return $is_new ? 'created' : 'updated';
    }

    private function find_product(string $external_id, string $sku): int
    {
        global $wpdb;

        $product_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                EPS_Sync_Manager::META_EXTERNAL_ID,
                $external_id
            )
        );

        if ($product_id > 0) {
            return $product_id;
        }

        if ($sku !== '') {
            return (int) wc_get_product_id_by_sku($sku);
        }

        return 0;
    }

    private function assign_categories(int $product_id, array $categories): void
    {
        $term_ids = [];

        foreach ($categories as $category_name) {
            $category_name = sanitize_text_field((string) $category_name);

            if ($category_name === '') {
                continue;
            }

            $term = term_exists($category_name, 'product_cat');

            if (! $term) {
                $term = wp_insert_term($category_name, 'product_cat');
            }

            if (! is_wp_error($term)) {
                $term_ids[] = (int) $term['term_id'];
            }
        }

        if ($term_ids) {
            wp_set_object_terms($product_id, $term_ids, 'product_cat', false);
        }
    }
}
