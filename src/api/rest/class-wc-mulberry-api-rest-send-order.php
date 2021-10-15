<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2020 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Api_Rest_Send_Order
{
    /**
     * Endpoint URI for sending order information
     */
    const ORDER_SEND_ENDPOINT_URL = '/api/checkout';

    /**
     * @var WC_Mulberry_Api_Rest_Service
     */
    private $service;

    /**
     * @var bool $order_has_warranty_products
     */
    private $order_has_warranty_products = false;

    /**
     * @var array $warranty_items_payload
     */
    private $warranty_items_payload = array();

    /**
     * @var WC_Order $order
     */
    private $order;

    /**
     * WC_Mulberry_Api_Rest_Send_Order constructor.
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
    public function send_order(WC_Order $order)
    {
        $this->order = $order;
        $this->prepare_items_payload();

        if (!$this->order_has_warranty_products) {
            return $this->parse_response([
                'is_successful' => false,
                'error' => __(sprintf('No warranty products for order "#%1"', $order->get_order_number()),
                    'woocommerce-mulberry-warranty'),
            ]);
        }

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
            $this->prepare_item_payload($item);
        }
    }

    /**
     * @return array
     */
    private function get_order_payload()
    {
        /**
         * @var $order WC_Order
         */
        $order = $this->order;

        return array(
            'id' => $order->get_order_number(),
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email(),
            'cart_token' => $order->get_cart_hash(),
            'billing_address' => $this->prepare_address_data(),
            'line_items' => $this->warranty_items_payload,
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
         * Set flag, that the order has warranty products included
         */
        if ($warranty_meta_data = $item->get_meta('mulberry_warranty')) {
            $this->order_has_warranty_products = true;

            for ($i = 0; $i < (int) $item->get_quantity(); $i++) {
                $this->warranty_items_payload[] = array(
                    'warranty_hash' => $warranty_meta_data['warranty_hash'],
                    'warranty_offer_id' => $warranty_meta_data['warranty_offer_id'],
                );
            }
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

    public function order_has_warranty_products(WC_Order $order)
    {
        /**
         * @var $item WC_Order_Item
         */
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('mulberry_warranty')) {
                return true;
            }
        }

        return false;
    }
}
