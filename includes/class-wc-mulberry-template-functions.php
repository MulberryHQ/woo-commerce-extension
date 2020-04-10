<?php
/**
 * WooCommerce Template
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce\Functions
 * @version  2.5.0
 */

defined('ABSPATH') || exit;

if (!function_exists('add_mulberry_product_container')) {
    function add_mulberry_product_container()
    {
        if (WC_Integration_Mulberry_Warranty::is_plugin_configured()) {
            /**
             * Render template on product page
             */
            wc_get_template('mulberry-warranty-container.php', array(), 'woocommerce-mulberry-warranty/', WC_MULBERRY_TEMPLATE_PATH);

            /**
             * Add the necessary JS logic
             */
            wp_enqueue_script('woocommerce_mulberry_library', plugins_url('assets/js/library.js', dirname(__FILE__)), array('jquery'), false, true);
            wp_enqueue_script('woocommerce_mulberry_product_page', plugins_url('assets/js/product-page.js', dirname(__FILE__)), array('jquery', 'woocommerce_mulberry_library'), false, true);
        }
    }
}

add_action('woocommerce_after_add_to_cart_button', 'add_mulberry_product_container');
