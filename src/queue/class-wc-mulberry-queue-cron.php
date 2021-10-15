<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Queue_Cron
{
    const MULBERRY_QUEUE_CRON_HOOK = 'mulberry_queue_cron';
    const MULBERRY_CRON_SCHEDULE = 'mulberry_every_three_minutes';

    /**
     * @var WC_Mulberry_Logger
     */
    private $logger;

    /**
     * Init logger in the construct
     */
    public function __construct()
    {
        $this->logger = new WC_Mulberry_Logger();
    }

    /**
     * Process cron
     */
    public function run()
    {
        try {
            $cron_sync_enabled = WC_Integration_Mulberry_Warranty::get_config_value('enable_cron_sync') === 'yes';

            if ($cron_sync_enabled) {
                $processor = new WC_Mulberry_Queue_Processor();
                foreach ($this->get_records_to_process() as $queue) {
                    $action_type = $queue->get('action_type');
                    $order = wc_get_order($queue->get('order_id'));
                    $processor->process($order, $action_type);
                }
            }
        } catch (Exception $e) {
            $this->logger->log(sprintf('Mulberry Queue Cron - %s', $e->getMessage()));
        }
    }

    /**
     * @return array|false|mixed
     */
    private function get_records_to_process()
    {
        $queue = new WC_Mulberry_Queue_Model();

        return $queue->get_pending_records();
    }

    /**
     * Deactivate the existing crons on plugins deactivate
     */
    public function deactivate()
    {
        $cron_context = array('WP Cron');

        if ($next_scheduled_time = wp_next_scheduled(self::MULBERRY_QUEUE_CRON_HOOK, $cron_context)) {
            wp_unschedule_event($next_scheduled_time, self::MULBERRY_QUEUE_CRON_HOOK);
        }

        wp_clear_scheduled_hook(self::MULBERRY_QUEUE_CRON_HOOK);
    }

    /**
     * Activate cron on plugin init
     */
    public function activate()
    {
        add_filter('cron_schedules', array($this, 'add_wp_cron_schedule'));

        // Check for and remove any WP Cron hook scheduled, which didn't include the $context param
        $next_timestamp = wp_next_scheduled(self::MULBERRY_QUEUE_CRON_HOOK);
        if ($next_timestamp) {
            wp_unschedule_event($next_timestamp, self::MULBERRY_QUEUE_CRON_HOOK);
        }

        $cron_context = array('WP Cron');

        if (!wp_next_scheduled(self::MULBERRY_QUEUE_CRON_HOOK, $cron_context)) {
            $schedule = apply_filters('action_scheduler_run_schedule', self::MULBERRY_CRON_SCHEDULE);
            wp_schedule_event(time(), $schedule, self::MULBERRY_QUEUE_CRON_HOOK, $cron_context);
        }

        add_action(self::MULBERRY_QUEUE_CRON_HOOK, array($this, 'run'));
    }

    /**
     * @param $schedules
     * @return mixed
     */
    public function add_wp_cron_schedule($schedules)
    {
        $schedules[self::MULBERRY_CRON_SCHEDULE] = array(
            'interval' => 180, // in seconds
            'display' => __('Every three minutes (Mulberry)', 'woocommerce-mulberry-warranty'),
        );

        return $schedules;
    }
}
