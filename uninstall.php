<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// حذف کامل جداول.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}moshtaryar_orders" );    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}moshtaryar_customers" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

// حذف optionها.
delete_option( 'moshtaryar_version' );
delete_option( 'moshtaryar_db_version' );
delete_option( 'moshtaryar_last_recalc' );
delete_option( 'moshtaryar_m_thresholds' );
delete_option( 'moshtaryar_counted_statuses' );

// لغو رویداد شبانه.
wp_clear_scheduled_hook( 'moshtaryar_daily_recalc' );
