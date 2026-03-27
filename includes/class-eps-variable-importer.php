<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Variable_Importer
{
    public function import(array $data): string
    {
        $external_id = sanitize_text_field((string) ($data['id'] ?? ''));
        $sku         = sanitize_text_field((string) ($data['sku'] ?? ''));
        $name        = sanitize_text_field((string) ($data['name'] ?? ''));

        if ($external_id === '' || $name === '') {
            return 'failed';
        }

        $product_id = $this->find_variable_product($external_id, $sku);
        $is_new = ! $product_id;

        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Variable();

        if (! $product) {
            return 'failed';
        }

        $product->set_name($name);
        $product->set_description(wp_kses_post((string) ($data['description'] ?? '')));
        $product->set_sku($sku);
        $product->set_status('publish');

        $saved_id = $product->save();

        update_post_meta($saved_id, EPS_Sync_Manager::META_EXTERNAL_ID, $external_id);

        $this->sync_attributes($saved_id, $data['attributes'] ?? []);
        $this->sync_variations($saved_id, $data['variations'] ?? []);

        return $is_new ? 'created' : 'updated';
    }

    private function find_variable_product(string $external_id, string $sku): int
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

    private function sync_attributes(int $product_id, array $attributes): void
    {
        $product_attributes = [];

        foreach ($attributes as $index => $attribute) {
            if (empty($attribute['name']) || empty($attribute['options']) || ! is_array($attribute['options'])) {
                continue;
            }

            $wc_attribute = new WC_Product_Attribute();
            $wc_attribute->set_name(sanitize_text_field($attribute['name']));
            $wc_attribute->set_options(array_map('sanitize_text_field', $attribute['options']));
            $wc_attribute->set_visible(true);
            $wc_attribute->set_variation(true);
            $wc_attribute->set_position($index);

            $product_attributes[] = $wc_attribute;
        }

        $product = wc_get_product($product_id);

        if ($product instanceof WC_Product_Variable) {
            $product->set_attributes($product_attributes);
            $product->save();
        }
    }

    private function sync_variations(int $parent_id, array $variations): void
    {
        foreach ($variations as $variation_data) {
            $variation_id = $this->find_variation($variation_data['id'] ?? '');

            $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();

            $variation->set_parent_id($parent_id);
            $variation->set_sku(sanitize_text_field((string) ($variation_data['sku'] ?? '')));
            $variation->set_regular_price(wc_format_decimal((string) ($variation_data['regular_price'] ?? '')));
            $variation->set_sale_price(wc_format_decimal((string) ($variation_data['sale_price'] ?? '')));
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity(absint($variation_data['stock_quantity'] ?? 0));
            $variation->set_stock_status(absint($variation_data['stock_quantity'] ?? 0) > 0 ? 'instock' : 'outofstock');

            $attributes = [];
            foreach (($variation_data['attributes'] ?? []) as $name => $value) {
                $attributes[sanitize_title($name)] = sanitize_text_field($value);
            }
            $variation->set_attributes($attributes);

            $saved_variation_id = $variation->save();

            if (! empty($variation_data['id'])) {
                update_post_meta($saved_variation_id, '_eps_variation_external_id', sanitize_text_field((string) $variation_data['id']));
            }
        }
    }

    private function find_variation(string $external_variation_id): int
    {
        global $wpdb;

        if ($external_variation_id === '') {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                '_eps_variation_external_id',
                $external_variation_id
            )
        );
    }
}
