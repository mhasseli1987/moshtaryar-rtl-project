<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ساخت جدول‌ها با dbDelta، ثبت optionهای پیش‌فرض و زمان‌بندی cron در فعال‌سازی.
 *
 * @package Moshtaryar
 */
class Moshtaryar_Activator {

	/**
	 * اجرای فعال‌سازی.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$customers_table = $wpdb->prefix . 'moshtaryar_customers';
		$orders_table    = $wpdb->prefix . 'moshtaryar_orders';

		dbDelta(
			"CREATE TABLE {$customers_table} (
			  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			  user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			  billing_email VARCHAR(190) NOT NULL DEFAULT '',
			  display_name VARCHAR(190) NOT NULL DEFAULT '',
			  last_order_date DATETIME NULL DEFAULT NULL,
			  recency_days SMALLINT UNSIGNED NULL DEFAULT NULL,
			  order_count INT UNSIGNED NOT NULL DEFAULT 0,
			  total_spent DECIMAL(26,8) NOT NULL DEFAULT 0,
			  avg_order_value DECIMAL(26,8) NOT NULL DEFAULT 0,
			  r_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  f_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  m_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  segment VARCHAR(32) NOT NULL DEFAULT '',
			  segment_updated_at DATETIME NULL DEFAULT NULL,
			  PRIMARY KEY  (id),
			  UNIQUE KEY identity (user_id, billing_email),
			  KEY segment (segment),
			  KEY last_order_date (last_order_date),
			  KEY total_spent (total_spent),
			  KEY order_count (order_count)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$orders_table} (
			  order_id BIGINT(20) UNSIGNED NOT NULL,
			  customer_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			  user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			  date_created DATETIME NULL DEFAULT NULL,
			  total_amount DECIMAL(26,8) NOT NULL DEFAULT 0,
			  shipping_state VARCHAR(20) NOT NULL DEFAULT '',
			  PRIMARY KEY  (order_id),
			  KEY customer_id (customer_id),
			  KEY date_created (date_created),
			  KEY shipping_state (shipping_state)
			) {$charset_collate};"
		);

		add_option( 'moshtaryar_version', MY_VER );
		add_option( 'moshtaryar_db_version', MY_DB_VERSION );
		add_option( 'moshtaryar_last_recalc', 0 );
		add_option(
			'moshtaryar_m_thresholds',
			array( 1000000, 5000000, 20000000, 50000000 )
		);
		add_option( 'moshtaryar_counted_statuses', array( 'wc-completed' ) );

		Moshtaryar_Cron::schedule();
	}
}
