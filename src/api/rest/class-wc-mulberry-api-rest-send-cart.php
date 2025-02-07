<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2020 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Api_Rest_Send_Cart
{
    /**
     * Endpoint URI for sending order information
     */
    const ORDER_SEND_ENDPOINT_URL = '/api/carts';

    /**
     * @var Mulberry_Warranty_Model_Api_Rest_Service
     */
    private $service;

    /**
     * @var array $items_payload
     */
    private $items_payload = array();

    /**
     * @var WC_Order $order
     */
    private $order;

    /**
     * Mulberry_Warranty_Model_Api_Rest_Send_Order constructor.
     */
    public function __construct()
    {
        $this->service = new WC_Mulberry_Api_Rest_Service();
    }

    /**
     * Send order payload to Mulberry system
     *
     * @param WC_Order $order
     * @return array
     * @throws Exception
     */
    public function send_cart(WC_Order $order)
    {
        $this->order = $order;

        /**
         * Reset values
         */
        $this->items_payload = [];

        $this->prepare_items_payload();

        $payload = $this->get_order_payload();
        $response = $this->service->make_request(self::ORDER_SEND_ENDPOINT_URL, $payload, 'POST');

        return $this->parse_response($response);
    }

    /**
     * Prepare payload for order items
     */
    private function prepare_items_payload()
    {
        /**
         * @var $item WC_Order_Item
         */
        foreach ($this->order->get_items() as $item) {
            if ($item->get_meta('mulberry_warranty')) {
                continue;
            }

            $this->prepare_item_payload($item);
        }
    }

    /**
     * @return array
     */
    private function get_order_payload()
    {
        return array(
            'line_items' => $this->items_payload,
            'billing_address' => $this->prepare_address_data(),
            'order_id' => (string) $this->order->get_order_number(),
        );
    }

    /**
     * Retrieve billing address data for order payload
     *
     * @return array
     */
    private function prepare_address_data()
    {
        return array(
            'first_name' => $this->order->get_billing_first_name(),
            'last_name' => $this->order->get_billing_last_name(),
            'address1' => $this->order->get_billing_address_1(),
            'address2' => $this->order->get_billing_address_2(),
            'phone' => $this->order->get_billing_phone(),
            'email' => $this->order->get_billing_email(),
            'city' => $this->order->get_billing_city(),
            'state' => $this->order->get_billing_state(),
            'zip' => $this->order->get_billing_postcode(),
            'country' => WC()->countries->countries[$this->order->get_billing_country()],
            'country_code' => $this->order->get_billing_country(),
            'province_code' => $this->order->get_billing_state(),
        );
    }

    /**
     * Prepare payload single warranty item,
     * payload should contain separate object for each item purchased (no qty support in API at the moment)
     *
     * @param WC_Order_Item $item
     */
    private function prepare_item_payload(WC_Order_Item $item)
    {
        /**
         * @var $product WC_Product
         */
        $product = $item->get_product();

        for ($i = 0; $i < (int) $item->get_quantity(); $i++) {
            $this->items_payload[] = array(
                'product_id' => $product->get_sku(),
                'product_price' => (float) $product->get_price(),
                'product_title' => $product->get_name(),
            );
        }
    }

    /**
     * @param $response
     *
     * @return array
     */
    private function parse_response($response)
    {
        return array(
            'status' => $response['is_successful'] ? 'synced' : 'failed',
            'response' => $response
        );
    }
}
