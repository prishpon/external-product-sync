<?php

if (! defined('ABSPATH')) {
    exit;
}

class EPS_Image_Importer
{
    public function import_featured_image(int $product_id, string $image_url): void
    {
        if (! filter_var($image_url, FILTER_VALIDATE_URL)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image($image_url, $product_id, null, 'id');

        if (! is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }
}
