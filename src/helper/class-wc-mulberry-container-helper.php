<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Container_Helper
{
    public function get_breadcrumbs($args = array())
    {
        $breadcrumbs = new WC_Breadcrumb();

        $breadcrumbs->add_crumb(_x('Home', 'breadcrumb', 'woocommerce'),
            apply_filters('woocommerce_breadcrumb_home_url', home_url()));

        $args['breadcrumb'] = $breadcrumbs->generate();

        /**
         * WooCommerce Breadcrumb hook
         *
         * @hooked WC_Structured_Data::generate_breadcrumblist_data() - 10
         */
        do_action('woocommerce_breadcrumb', $breadcrumbs, $args);

        return $this->format_breadcrumbs($breadcrumbs);
    }

    private function format_breadcrumbs($breadcrumbs)
    {
        $result = array();
        $crumbs = $breadcrumbs->get_breadcrumb();

        /**
         * Remove the product crumb
         */
        array_pop($crumbs);

        foreach ($crumbs as $crumb) {
            $result[] = [
                'category' => $crumb[0],
                'url' => $crumb[1],
            ];
        }

        return json_encode($result);
    }

    /**
     * @return false|string
     */
    public function get_product_images($product)
    {
        $attachment_ids = array();
        $product_image = $product->get_image_id();

        // Add featured image.
        if (!empty($product_image)) {
            $attachment_ids[] = $product_image;
        }

        // Add gallery images.
        $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());

        foreach ($attachment_ids as $attachment_id) {
            $result[] = ['src' => wp_get_attachment_url($attachment_id)];
        }

        return json_encode($result);
    }
}
