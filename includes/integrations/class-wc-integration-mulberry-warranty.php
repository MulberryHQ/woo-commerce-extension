<?php
/**
 * Integration Demo Integration.
 *
 * @package  WC_Integration_Demo_Integration
 * @category Integration
 * @author   WooThemes
 */

if (!class_exists('WC_Integration_Mulberry_Warranty')) {
    /**
     * Class WC_Integration_Mulberry_Warranty
     */
    class WC_Integration_Mulberry_Warranty extends WC_Integration
    {
        /**
         * Init and hook in the integration.
         */
        public function __construct()
        {
            global $woocommerce;

            $this->id = 'mulberry-warranty';
            $this->method_title = __('Mulberry Warranty', 'woocommerce-mulberry-warranty');
            $this->method_description = __('A WooCommerce plugin for Mulberry Extended Warranty.',
                'woocommerce-mulberry-warranty');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Actions.
            add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Initialize integration settings form fields.
         *
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'active' => array(
                    'title' => __('Enable Mulberry Warranty Plugin', 'woocommerce-mulberry-warranty'),
                    'type' => 'checkbox',
                    'label' => __('Enable Plugin', 'woocommerce-mulberry-warranty'),
                    'default' => 'no',
                    'description' => '',
                ),
                'api_url' => array(
                    'title' => __('Mulberry API URL', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('API URL used for Mulberry requests, for example: https://staging.getmulberry.com', 'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'partner_url' => array(
                    'title' => __('Mulberry Partner Base URL', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('Mulberry Partner URL used for Mulberry requests, for example: https://partner-staging.getmulberry.com',
                        'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'platform_domain' => array(
                    'title' => __('Platform Domain Name', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('Used for API init, if no value is set, $_SERVER[\'SERVER_NAME\'] is used.', 'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'retailer_id' => array(
                    'title' => __('Mulberry Retailer ID', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('Retailer ID used for send order API call.', 'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'api_token' => array(
                    'title' => __('Mulberry API Token', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('Auth token to be used for API calls.', 'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'public_token' => array(
                    'title' => __('Mulberry Public Token', 'woocommerce-mulberry-warranty'),
                    'type' => 'text',
                    'description' => __('Public token to be used for offer init.', 'woocommerce-mulberry-warranty'),
                    'desc_tip' => true,
                    'default' => '',
                ),
                'send_cart_data' => array(
                    'title' => __('Enable Post Purchase', 'woocommerce-mulberry-warranty'),
                    'type' => 'checkbox',
                    'label' => __('Yes', 'woocommerce-mulberry-warranty'),
                    'default' => 'no',
                    'description' => '',
                ),
            );
        }

        /**
         * Display errors by overriding the display_errors() method
         *
         * @see display_errors()
         */
        public function display_errors()
        {
            // loop through each error and display it
            foreach ($this->errors as $key => $value) {
                ?>
                <div class="error">
                    <p><?php __(sprintf('Looks like you made a mistake with the %1 field', $value),
                            'woocommerce-mulberry-warranty') ?></p>
                </div>
                <?php
            }
        }

        /**
         * Check whether module is configured in admin panel.
         *
         * @return bool
         */
        public static function is_plugin_configured()
        {
            $required_settings = [
                'active',
                'api_url',
                'partner_url',
                'retailer_id',
                'api_token',
                'public_token',
            ];

            $settings = get_option('woocommerce_mulberry-warranty_settings');

            if ($settings && is_array($settings)) {
                foreach ($required_settings as $setting) {
                    if (!array_key_exists($setting, $settings)) {
                        return false;
                    }
                }
            } else {
                return false;
            }

            return true;
        }

        /**
         * Return config value by key
         *
         * @param $key
         * @return string|null
         */
        public static function get_config_value($key)
        {
            if (self::is_plugin_configured()) {
                $settings = get_option('woocommerce_mulberry-warranty_settings');

                if (array_key_exists($key, $settings)) {
                    return $settings[$key];
                }
            }

            return null;
        }
    }
}
