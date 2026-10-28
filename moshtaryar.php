<?php
/**
 * Plugin Name: مشتریار — تحلیل‌گر مشتری ووکامرس (RFM)
 * Description: تحلیل RFM مشتریان ووکامرس، سگمنت‌بندی فارسی، داشبورد مدیریتی و نقشه حرارتی استان‌های ایران.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * Text Domain: moshtaryar
 * Domain Path: /languages
 *
 * @package Moshtaryar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MY_VER', '1.0.0' );
define( 'MY_FILE', __FILE__ );
define( 'MY_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_URL', plugin_dir_url( __FILE__ ) );
define( 'MY_DB_VERSION', '1' );

require_once MY_DIR . 'includes/functions.php';
require_once MY_DIR . 'includes/class-rfm-engine.php';
require_once MY_DIR . 'includes/class-activator.php';
require_once MY_DIR . 'includes/class-cron.php';
require_once MY_DIR . 'includes/class-export.php';
require_once MY_DIR . 'includes/class-moshtaryar.php';

/**
 * اعلام سازگاری با جدول‌های سفارش اختصاصی ووکامرس (HPOS).
 */
function moshtaryar_declare_hpos() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'moshtaryar_declare_hpos' );

register_activation_hook( __FILE__, array( 'Moshtaryar_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Moshtaryar_Cron', 'deactivate' ) );

/**
 * راه‌اندازی هسته پس از بارگذاری کامل ووکامرس؛ در نبود ووکامرس فقط پیام ادمین، بدون Fatal.
 */
function moshtaryar_bootstrap() {
	load_plugin_textdomain( 'moshtaryar', false, dirname( plugin_basename( MY_FILE ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'moshtaryar_wc_missing_notice' );
		return;
	}

	Moshtaryar::instance();
}
add_action( 'plugins_loaded', 'moshtaryar_bootstrap', 11 );

/**
 * پیام نبود ووکامرس در پیشخوان.
 */
function moshtaryar_wc_missing_notice() {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'افزونه «مشتریار» برای کارکرد به ووکامرس نیاز دارد. لطفاً ابتدا ووکامرس را نصب و فعال کنید.', 'moshtaryar' ) . '</p></div>';
}
