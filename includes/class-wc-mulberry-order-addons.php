<?php
/**
 * This class is listening for order-related hooks
 */

defined('ABSPATH') || exit;

class WC_Mulberry_Order_Addons
{
    /**
     * WC_Mulberry_Order_Addons constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_payment_successful_result', array($this, 'process_order_place_hooks'), 10, 2);
        add_filter('woocommerce_order_status_cancelled', array($this, 'process_order_cancel_hooks'));
    }

    /**
     * @param $result
     * @param $order_id
     * @return mixed
     * @throws Exception
     */
    public function process_order_place_hooks($result, $order_id)
    {
        $this->mulberry_send_order_hook($result, $order_id);
        $this->mulberry_send_cart_data_hook($result, $order_id);

        return $result;
    }

    /**
     * @param $order_id
     * @return mixed
     * @throws Exception
     */
    public function process_order_cancel_hooks($order_id)
    {
        $this->mulberry_cancel_order_hook($order_id);

        return $order_id;
    }

    /**
     * Register order in Mulberry system
     *
     * @param $result
     * @param $order_id
     * @return $this
     * @throws Exception
     */
    private function mulberry_send_order_hook($result, $order_id)
    {
        if (WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes') {
            $order = wc_get_order($order_id);

            $send_order = new WC_Mulberry_Api_Rest_Send_Order();
            $send_order->send_order($order);
        }

        return $this;
    }

    /**
     * Send hook to cancel Mulberry warranty order
     *
     * @param $order_id
     * @return $this
     * @throws Exception
     */
    private function mulberry_cancel_order_hook($order_id)
    {
        if (WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes') {
            $order = wc_get_order($order_id);

            $send_order = new WC_Mulberry_Api_Rest_Cancel_Order();
            $send_order->cancel_order($order);
        }

        return $this;
    }

    /**
     * Send cart data hook
     *
     * @param $result
     * @param $order_id
     * @return $this
     * @throws Exception
     */
    private function mulberry_send_cart_data_hook($result, $order_id)
    {
        if (WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes') {
            $order = wc_get_order($order_id);

            $send_order = new WC_Mulberry_Api_Rest_Send_Cart();
            $send_order->send_cart($order);
        }

        return $this;
    }
}

$WC_Mulberry_Order_Addons = new WC_Mulberry_Order_Addons();
