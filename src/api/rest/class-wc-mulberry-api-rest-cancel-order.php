<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2020 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Api_Rest_Cancel_Order
{
    /**
     * Endpoint URI for sending order cancellation information
     */
    const ORDER_CANCEL_ENDPOINT_URL = '/api/order_cancelled';

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
     * Send order cancellation payload to Mulberry system
     *
     * @param WC_Order $order
     * @return array
     * @throws Exception
     */
    public function cancel_order(WC_Order $order)
    {
        $this->order = $order;
        $this->prepare_items_payload();

        if (!$this->order_has_warranty_products) {
            return array();
        }

        $payload = $this->get_order_cancellation_payload();
        $response = $this->service->make_request(self::ORDER_CANCEL_ENDPOINT_URL, $payload, 'POST');

        return $this->parse_response($response);
    }

    /**
     * Prepare payload for order items
     */
    private function prepare_items_payload()
    {
        /**
         * @var WC_Order_Item $item
         */
        foreach ($this->order->get_items() as $item) {
            $this->prepare_item_payload($item);
        }
    }

    /**
     * Prepare full payload to be sent, when order is cancelled
     *
     * @return array
     * @throws Exception
     */
    private function get_order_cancellation_payload()
    {
        $date = new WC_DateTime();

        return array(
            'cancelled_date' => $date->date('Y-m-d'),
            'order_id' => (string) $this->order->get_id(),
            'line_items' => $this->warranty_items_payload,
        );
    }

    /**
     * Prepare cancellation payload for order item
     *
     * @param WC_Order_Item $item
     */
    private function prepare_item_payload(WC_Order_Item $item)
    {
        if ($warranty_meta_data = $item->get_meta('mulberry_warranty')) {
            $this->order_has_warranty_products = true;

            /**
             * @var $product WC_Product
             */
            $product = $item->get_product();

            for ($i = 0; $i < (int) $item->get_quantity(); $i++) {
                $this->warranty_items_payload[] = array(
                    'product_id' => $product->get_sku(),
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
        return $response;
    }
}
