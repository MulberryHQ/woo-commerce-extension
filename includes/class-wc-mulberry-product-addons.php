<?php
/**
 * This class is listening for product-related hooks, e.g. adding warranty to the shopping cart
 */

defined('ABSPATH') || exit;

class WC_Mulberry_Product_Addons
{
    /**
     * WC_Mulberry_Product_Addons constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_add_cart_item_data', array($this, 'mulberry_add_warranty_to_product'), 10, 3);
        add_filter('woocommerce_before_calculate_totals', array($this, 'mulberry_calculate_totals'));
        add_filter('woocommerce_before_mini_cart_contents', array($this, 'mulberry_calculate_totals'));
        add_filter('woocommerce_get_item_data', array($this, 'mulberry_display_selected_warranty_option'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'mulberry_add_order_item_meta'), 10, 4);
    }

    /**
     * Validate and add warranty information to quote item
     *
     *
     * @param $quote_item_data
     * @return mixed
     * @throws Exception
     */
    public function mulberry_add_warranty_to_product($quote_item_data)
    {
        $is_module_active = WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes';
        $product_id = $_POST['add-to-cart'];
        $product = wc_get_product( $product_id );

        if ($is_module_active) {
            if (array_key_exists('warranty', $_POST)) {
                $warranty_sku = $_POST['warranty']['sku'];
                $isValidWarrantyHash = false;

                /**
                 * Check whether we need to add warranty for this product or not
                 */
                if (isset($_POST['warranty']['hash']) && !empty($_POST['warranty']['hash']) && $warranty_sku === (string) $this->get_selected_product_sku($product)) {
                    $isValidWarrantyHash = true;
                }

                /**
                 * Process additional warranty product add-to-cart
                 */
                if ($product && $product->get_id() && $isValidWarrantyHash) {
                    $warranty_hash = $_POST['warranty']['hash'];

                    /**
                     * Prepare buyRequest and other options for warranty quote item
                     */
                    $warranty_information = $this->get_warranty_information_by_hash($warranty_hash);

                    if (!empty($warranty_information)) {
                        $quote_item_data['mulberry_warranty'] = $warranty_information;
                    }
                }
            }
        }

        return $quote_item_data;
    }

    /**
     * @param WC_Product $product
     * @return int|string
     */
    private function get_selected_product_sku(WC_Product $product)
    {
        return $product->get_sku() ? $product->get_sku() : $product->get_id();
    }

    /**
     * @param $cart
     */
    public function mulberry_calculate_totals($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        /**
         * Make cart object accessible for mini cart contents rendering
         */
        if (current_action() === 'woocommerce_before_mini_cart_contents') {
            global $woocommerce;

            $cart = $woocommerce->cart;
        }

        foreach ($cart->get_cart() as $key => $item) {
            if (isset($item['mulberry_warranty'])) {
                /**
                 * @var $product WC_Product
                 */
                $product = $item['data'];
		 $wcProduct = wc_get_product( $item['product_id'] );

                $original_price = $wcProduct->get_price(); // get product's original price here.
                $updated_price = (float) $original_price + (float) $item['mulberry_warranty']['warranty_price'];

                $product->set_price($updated_price);
            }
        }
    }

    /**
     * Display Mulberry warranty option
     *
     * @param $item_data
     * @param $cart_item
     * @return array
     */
    public function mulberry_display_selected_warranty_option($item_data, $cart_item)
    {
        if (!empty($cart_item['mulberry_warranty'])) {
            $warranty_information = $cart_item['mulberry_warranty'];
            $value = $this->prepare_display_value_from_mulberry_meta($warranty_information);

            $item_data[] = array(
                'key' => 'Mulberry Extended Protection',
                'value' => $value,
                'display' => $value,
            );
        }

        return $item_data;
    }

    /**
     * @param $warranty_information
     * @return string
     */
    private function prepare_display_value_from_mulberry_meta($warranty_information)
    {
        return sprintf('Service Type: %s, Duration (months): %s, Price: %s',
            wc_clean($warranty_information['service_type']),
            wc_clean($warranty_information['duration_months']),
            wc_price($warranty_information['warranty_price'])
        );
    }

    /**
     * Add Mulberry warranty data to the order item meta
     *
     * @param WC_Order_Item $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     */
    public function mulberry_add_order_item_meta(WC_Order_Item $item, $cart_item_key, $values, $order)
    {
        if (!empty($values['mulberry_warranty'])) {
            $item->add_meta_data('mulberry_warranty', $values['mulberry_warranty']);
            $item->add_meta_data('Mulberry Extended Protection', $this->prepare_display_value_from_mulberry_meta($values['mulberry_warranty']));
        }
    }

    /**
     * Retrieve warranty information by hash
     *
     * @param $hash
     * @return array
     * @throws Exception
     */
    public function get_warranty_information_by_hash($hash)
    {
        $service = new WC_Mulberry_Api_Rest_Validate_Warranty();

        return $service->get_warranty_by_hash($hash);
    }
}

$WC_Mulberry_Product_Addons = new WC_Mulberry_Product_Addons();
