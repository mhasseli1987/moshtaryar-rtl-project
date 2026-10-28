<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * لایهٔ اصلی افزونه: اتصال هوک‌ها، بررسی ارتقای اسکیما و بارگذاری شرطی ادمین.
 *
 * @package Moshtaryar
 */
class Moshtaryar {

	/**
	 * نمونهٔ یکتا.
	 *
	 * @var Moshtaryar|null
	 */
	private static $instance = null;

	/**
	 * دریافت نمونهٔ یکتا.
	 *
	 * @return Moshtaryar
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// بازمحاسبهٔ تک‌مشتری پس از هر تغییر وضعیت سفارش (سازگار HPOS).
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );

		// بررسی ارتقای اسکیمای جدول‌ها پس از به‌روزرسانی افزونه.
		add_action( 'init', array( $this, 'maybe_upgrade' ) );

		if ( is_admin() ) {
			$this->load_admin();
		}
	}

	/**
	 * بازمحاسبهٔ مشتریِ سفارش وقتی وضعیتش از یا به وضعیت شمارش‌شده تغییر کند.
	 *
	 * @param int      $order_id   شناسهٔ سفارش.
	 * @param string   $old_status وضعیت قبلی.
	 * @param string   $new_status وضعیت جدید.
	 */
	public function order_status_changed( $order_id, $old_status, $new_status ) {
		$counted = Moshtaryar_RFM_Engine::counted_statuses();
		if ( in_array( $old_status, $counted, true ) || in_array( $new_status, $counted, true ) ) {
			Moshtaryar_RFM_Engine::recalc_customer( (int) $order_id );
		}
	}

	/**
	 * اجرای dbDelta دوباره اگر نسخهٔ اسکیما عقب باشد.
	 */
	public function maybe_upgrade() {
		if ( get_option( 'moshtaryar_db_version' ) !== MY_DB_VERSION ) {
			Moshtaryar_Activator::activate();
		}
	}

	/**
	 * بارگذاری صفحات مدیریت (ساختهٔ سازندهٔ رابط) اگر فایل‌هایش موجود باشد.
	 */
	private function load_admin() {
		$pages    = MY_DIR . 'includes/admin/class-admin-pages.php';
		$provider = MY_DIR . 'includes/admin/class-data-provider.php';

		if ( ! file_exists( $pages ) ) {
			return; // صفحات ادمین هنوز ساخته نشده‌اند.
		}

		if ( file_exists( $provider ) && ! class_exists( 'Moshtaryar_Data_Provider' ) ) {
			require_once $provider;
		}
		if ( ! class_exists( 'Moshtaryar_Admin_Pages' ) ) {
			require_once $pages;
		}

		new Moshtaryar_Admin_Pages();

		// صفحهٔ نقشهٔ حرارتی (ساختهٔ سازندهٔ نقشه) با همان والد منو.
		$map = MY_DIR . 'includes/admin/class-map-report.php';
		if ( file_exists( $map ) && ! class_exists( 'Moshtaryar_Map_Report' ) ) {
			require_once $map;
		}
		if ( class_exists( 'Moshtaryar_Map_Report' ) ) {
			Moshtaryar_Map_Report::register_hooks();
		}
	}
}
