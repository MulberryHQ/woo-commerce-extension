<?php
/**
 * Plugin Name: WooCommerce Mulberry Warranty Integration
 * Description: A Mulberry extended warranty WooCommerce integration plugin.
 * Author: GetMulberry.com
 * Author URI: https://getmulberry.com/
 * Version: 1.2.0
 */

/**
 * Register custom interfaces
 */
function register_critical_mulberry_assets()
{
    include_once 'src/queue/interface-wc-mulberry-queue-model.php';
    include_once 'src/exceptions/class-wc-mulberry-exception.php';
    include_once 'src/logger/wc-mulberry-logger.php';
    include_once 'src/queue/class-wc-mulberry-queue-cron.php';
}

register_critical_mulberry_assets();

register_activation_hook( __FILE__, array( 'WC_Mulberry_Warranty', 'install' ) );
register_deactivation_hook( __FILE__, array( 'WC_Mulberry_Warranty', 'deactivate' ) );

if (!class_exists('WC_Mulberry_Warranty')) {
    class WC_Mulberry_Warranty
    {
        /**
         * Construct the plugin.
         */
        public function __construct()
        {
            /**
             * Initialize the plugin.
             */
            $this->define_constants();

            add_action('plugins_loaded', array($this, 'init'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_actions'));
        }

        /**
         * Initialize the plugin.
         */
        public function init()
        {
            $this->register_helpers();
            $this->register_integrations();
            $this->register_custom_models();
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
         * Register custom helpers
         */
        private function register_helpers()
        {
            include_once 'src/helper/class-wc-mulberry-container-helper.php';
        }

        /**
         * Register custom models
         */
        private function register_custom_models()
        {
            include_once 'src/queue/class-wc-mulberry-queue-model.php';
            include_once 'src/queue/class-wc-mulberry-queue-processor.php';
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

        /**
         * Init installation/activation hooks
         */
        public static function install()
        {
            /**
             * Create a custom table to process orders & post purchase events async
             */
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $table = $wpdb->prefix . WC_Mulberry_Queue_Model_Interface::TABLE_NAME;

            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                entity_id BIGINT UNSIGNED NOT NULL auto_increment,
                order_id BIGINT UNSIGNED NOT NULL,
                action_type varchar(32) DEFAULT NULL,
                sync_status varchar(32) DEFAULT NULL,
                sync_date timestamp NULL DEFAULT NULL,
              PRIMARY KEY (entity_id),
              KEY order_id (order_id),
              CONSTRAINT `order_id` FOREIGN KEY (`order_id`) REFERENCES `{$wpdb->prefix}posts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql);

            /**
             * Set up crons.
             */
            $cron = new WC_Mulberry_Queue_Cron();
            $cron->activate();
        }

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            $cron = new WC_Mulberry_Queue_Cron();
            $cron->deactivate();
        }
    }

    $WC_Mulberry_Warranty = new WC_Mulberry_Warranty();
}
