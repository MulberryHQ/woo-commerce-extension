<?php
/**
 * Uninstall Mulberry Warranty module
 *
 * Remove:
 * - Mulberry Queue table
 *
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Disable Action Schedule Queue Runner.
if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
    ActionScheduler_QueueRunner::instance()->unhook_dispatch_async_request();
}

global $wpdb;

$tablesToDrop = [
    WC_Mulberry_Queue_Model_Interface::TABLE_NAME,
];

// Delete all our custom tables.
foreach ( $tablesToDrop as $tableName ) {
    $table = $wpdb->prefix . $tableName;

    $wpdb->query( 'DROP TABLE IF EXISTS ' . $table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// Delete all the plugin settings.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce_mulberry%'" );

// Delete all entries from the action scheduler table.
$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE hook LIKE 'woocommerce_mulberry%'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_groups WHERE slug = 'woocommerce_mulberry'" );
