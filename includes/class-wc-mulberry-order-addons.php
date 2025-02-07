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

        add_filter('woocommerce_thankyou', array($this, 'process_order_place_hooks'), 10, 2);
        add_filter('woocommerce_order_status_cancelled', array($this, 'process_order_cancel_hooks'));
    }

    /**
     * @param $order_id
     * @return void
     * @throws Exception
     */
    public function process_order_place_hooks($order_id)
    {
        $order = wc_get_order($order_id);

        $this->mulberry_send_order_hook($order);
        $this->mulberry_send_cart_data_hook($order);
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
     * @param WC_Order $order
     * @return $this
     */
    private function mulberry_send_order_hook(WC_Order $order)
    {
        try {
            $is_module_active = WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes';

            if ($is_module_active) {
                $send_order = new WC_Mulberry_Api_Rest_Send_Order();

                if ($send_order->order_has_warranty_products($order)) {
                    $queue = new WC_Mulberry_Queue_Model();

                    /**
                     * Do not do anything if the order has been added to the queue already
                     */
                    $existing_queue_record = $queue->get_by_order_id_and_action_type($order->get_id(), 'order');
                    if ($existing_queue_record && is_array($existing_queue_record) && !empty($existing_queue_record)) {
                        return $this;
                    }

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
    private function mulberry_send_cart_data_hook(WC_Order $order)
    {
        try {
            $is_module_active = WC_Integration_Mulberry_Warranty::get_config_value('active') === 'yes';
            $is_post_purchase_enabled = WC_Integration_Mulberry_Warranty::get_config_value('send_cart_data') === 'yes';

            if ($is_module_active && $is_post_purchase_enabled) {
                $queue = new WC_Mulberry_Queue_Model();

                /**
                 * Do not do anything if the order has been added to the queue already
                 */
                $existing_queue_record = $queue->get_by_order_id_and_action_type($order->get_id(), 'order');
                if ($existing_queue_record && is_array($existing_queue_record) && !empty($existing_queue_record)) {
                    return $this;
                }

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
