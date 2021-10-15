<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Queue_Processor
{
    /**
     * @var WC_Mulberry_Logger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new WC_Mulberry_Logger();
    }

    /**
     * @param $order
     * @param $action_type
     *
     * @throws Exception
     */
    public function process($order, $action_type)
    {
        try {
            $queue = $this->get_by_order_id_and_action_type($order->get_id(), $action_type);

            if (!$queue) {
                throw new Exception(
                    __(sprintf('Invalid action type for order "#%1$s" and type "%2$s" is missing',
                        $order->get_id(), $action_type), 'woocommerce-mulberry-warranty')
                );
            }

            switch ($action_type) {
                case 'order':
                    $send_order = new WC_Mulberry_Api_Rest_Send_Order();
                    $response = $send_order->send_order($order);
                    break;
                case 'cart':
                    $send_order = new WC_Mulberry_Api_Rest_Send_Cart();
                    $response = $send_order->send_cart($order);
                    break;
                default:
                    $response = [
                        'status' => 'skipped',
                        'response' => __(sprintf('Invalid action type for order "#%1"', $order->get_order_number()),
                            'woocommerce-mulberry-warranty'),
                    ];
                    break;
            }

            if ($response['status'] !== 'synced') {
                $this->logger->log(sprintf('Queue processor sync failed - %s', json_encode($response)));
            }

            $date = as_get_datetime_object();
            ActionScheduler_TimezoneHelper::set_local_timezone($date);
            $date_local = $date->format('Y-m-d H:i:s');

            $queue->set('sync_status', $response['status']);
            $queue->set('sync_date', $date_local);
            $queue->save();

        } catch (Exception $e) {
            $this->logger->log(sprintf('Mulberry queue processor - %s', $e->getMessage()));
        }
    }

    /**
     * @param $order_id
     * @param $action_type
     *
     * @return false|WC_Mulberry_Queue_Model
     */
    public function get_by_order_id_and_action_type($order_id, $action_type)
    {
        $queue_model = new WC_Mulberry_Queue_Model();

        return $queue_model->get_by_order_id_and_action_type($order_id, $action_type);
    }
}
