<?php
/**
 * This class is listening for order-related hooks
 */

defined('ABSPATH') || exit;

class WC_Mulberry_Order_Addons
{
    /**
     * @var WC_Mulberry_Logger
     */
    private $logger;

    /**
     * WC_Mulberry_Order_Addons constructor.
     */
    public function __construct()
    {
        $this->logger = new WC_Mulberry_Logger();

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
        $order = wc_get_order($order_id);

        $this->mulberry_send_order_hook($result, $order);
        $this->mulberry_send_cart_data_hook($result, $order);

        return $result;
    }

    /**
     * @param $order_id
     * @return mixed
     * @throws Exception
     */
    public function process_order_cancel_hooks($order_id)
    {
        $order = wc_get_order($order_id);

        $this->mulberry_cancel_order_hook($order);

        return $order_id;
    }

    /**
     * Register order in Mulberry system
     *
     * @param $result
     * @param WC_Order $order
     * @return $this
     */
    private function mulberry_send_order_hook($result, WC_Order $order)
    {
        try {
            $is_module_active = WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes';
            $send_order = new WC_Mulberry_Api_Rest_Send_Order();

            if ($is_module_active && $send_order->order_has_warranty_products($order)) {
                $queue = new WC_Mulberry_Queue_Model();
                $queue->set('order_id', $order->get_id());
                $queue->set('action_type', 'order');
                $queue->save();

                $cron_sync_enabled = WC_Integration_Mulberry_Warranty::get_config_value('enable_cron_sync') === 'yes';

                /**
                 * If async data sync is disabled, process this record immediately
                 */
                if (!$cron_sync_enabled) {
                    $queue_processor = new WC_Mulberry_Queue_Processor();
                    $queue_processor->process($order, 'order');
                }
            }
        } catch (Exception $e) {
            $this->logger->log('Send Order Hook - '. $e->getMessage());
        }

        return $this;
    }

    /**
     * Send hook to cancel Mulberry warranty order
     *
     * @param WC_Order $order
     * @return $this
     * @throws Exception
     */
    private function mulberry_cancel_order_hook(WC_Order $order)
    {
        try {
            if (WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes') {
                $send_order = new WC_Mulberry_Api_Rest_Cancel_Order();
                $send_order->cancel_order($order);
            }
        } catch (Exception $e) {
            $this->logger->log('Order Cancel - '. $e->getMessage());
        }

        return $this;
    }

    /**
     * Send cart data hook
     *
     * @param $result
     * @param WC_Order $order
     * @return $this
     * @throws Exception
     */
    private function mulberry_send_cart_data_hook($result, WC_Order $order)
    {
        try {
            $is_module_active = WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes';
            $is_post_purchase_enabled = WC_Integration_Mulberry_Warranty::get_config_value('send_cart_data') === 'yes';

            if ($is_module_active && $is_post_purchase_enabled) {
                $queue = new WC_Mulberry_Queue_Model();
                $queue->set('order_id', $order->get_id());
                $queue->set('action_type', 'cart');
                $queue->save();

                /**
                 * If async data sync is disabled, process this record immediately
                 */
                $cron_sync_enabled = WC_Integration_Mulberry_Warranty::get_config_value('enable_cron_sync') === 'yes';
                if (!$cron_sync_enabled) {
                    $queue_processor = new WC_Mulberry_Queue_Processor();
                    $queue_processor->process($order, 'cart');
                }
            }
        } catch (Exception $e) {
            $this->logger->log('Send Cart Hook - '. $e->getMessage());
        }

        return $this;
    }
}

$WC_Mulberry_Order_Addons = new WC_Mulberry_Order_Addons();
