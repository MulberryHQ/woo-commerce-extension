<?php
/**
 * Plugin Name: WooCommerce Mulberry Warranty Integration
 * Description: A Mulberry extended warranty WooCommerce integration plugin.
 * Author: GetMulberry.com
 * Author URI: https://getmulberry.com/
 * Version: 1.0.0
 */

if (!class_exists('WC_Mulberry_Warranty')) {
    class WC_Mulberry_Warranty
    {
        /**
         * Construct the plugin.
         */
        public function __construct()
        {
            $this->define_constants();

            add_action('plugins_loaded', array($this, 'init'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_actions'));
        }

        /**
         * Initialize the plugin.
         */
        public function init()
        {
            $this->register_exceptions();
            $this->register_integrations();
            $this->register_templates();
            $this->register_addons();
            $this->register_api_services();
        }

        /**
         * Define module constants required for module usage
         */
        private function define_constants()
        {
            define('WC_SETTINGS_SLUG', 'wc-settings');
            define('WC_MULBERRY_TEMPLATE_PATH', untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/');
        }

        /**
         * Register settings within "Integrations" area in admin panel
         */
        private function register_integrations()
        {
            // Checks if WooCommerce is installed.
            if (class_exists('WC_Integration')) {
                // Include our integration class.
                include_once 'includes/integrations/class-wc-integration-mulberry-warranty.php';

                // Register the integration.
                add_filter('woocommerce_integrations', array($this, 'add_integration'));
            } else {
                // throw an admin error if you like
            }
        }

        /**
         * Register template files
         */
        private function register_templates()
        {
            include_once 'includes/class-wc-mulberry-template-functions.php';
        }

        /**
         * Register hooks to operate with products
         */
        private function register_addons()
        {
            include_once 'includes/class-wc-mulberry-product-addons.php';
            include_once 'includes/class-wc-mulberry-order-addons.php';
        }

        /**
         * Register API service classes
         */
        private function register_api_services()
        {
            include_once 'src/api/rest/class-wc-mulberry-api-rest-service.php';
            include_once 'src/api/rest/class-wc-mulberry-api-rest-validate-warranty.php';
            include_once 'src/api/rest/class-wc-mulberry-api-rest-send-order.php';
            include_once 'src/api/rest/class-wc-mulberry-api-rest-cancel-order.php';
            include_once 'src/api/rest/class-wc-mulberry-api-rest-send-cart.php';
//            include_once 'src/api/rest/class-wc-mulberry-api-rest-get-personalized-warranty.php';
        }

        /**
         * Register custom exception classes
         */
        private function register_exceptions()
        {
            include_once 'src/exceptions/class-wc-mulberry-exception.php';
        }

        /**
         * Add a new integration section to WooCommerce.
         *
         * @param $integrations
         * @return array
         */
        public function add_integration($integrations)
        {
            $integrations[] = 'WC_Integration_Mulberry_Warranty';

            return $integrations;
        }

        /**
         * @param $actions
         * @return array
         */
        public function add_plugin_actions($actions)
        {
            $settings = array('settings' => '<a href="' . menu_page_url(WC_SETTINGS_SLUG, false) . '&tab=integration&section=mulberry-warranty">Settings</a>');

            return array_merge($settings, $actions);
        }
    }

    $WC_Mulberry_Warranty = new WC_Mulberry_Warranty();
}
