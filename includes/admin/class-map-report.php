<?php
/**
 * گزارش نقشه حرارتی استان‌ها — سازنده نقشه ایران
 *
 * خلاصه تعداد سفارش و درآمد هر استان را از جدول سفارش‌های آماری
 * (که استان حمل هر سفارش در آن ذخیره شده) می‌گیرد و برای رندر SVG
 * به صفحه نقشه تحویل می‌دهد.
 *
 * @package Moshtaryar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس گزارش نقشه ایران.
 */
class Moshtaryar_Map_Report {

	/**
	 * نامک صفحه نقشه در پیشخوان.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'moshtaryar-map';

	/**
	 * نگاشت کد استان ووکامرس ایران به نام رسمی فارسی.
	 * کلیدها باید دقیقاً با کلیدهای assets/js/iran-map.js یکی باشد.
	 *
	 * @var array<string, string>
	 */
	private static $provinces = array(
		'THR' => 'تهران',
		'ALZ' => 'البرز',
		'QHM' => 'قم',
		'QAZ' => 'قزوین',
		'MKZ' => 'مرکزی',
		'HMD' => 'همدان',
		'KRD' => 'کردستان',
		'KRH' => 'کرمانشاه',
		'ILM' => 'ایلام',
		'KHZ' => 'خوزستان',
		'LRS' => 'لرستان',
		'BHR' => 'بوشهر',
		'CHB' => 'چهارمحال و بختیاری',
		'KBD' => 'کهگیلویه و بویراحمد',
		'FAR' => 'فارس',
		'HRZ' => 'هرمزگان',
		'ESF' => 'اصفهان',
		'YZD' => 'یزد',
		'SMN' => 'سمنان',
		'MDL' => 'مازندران',
		'GLS' => 'گلستان',
		'GIL' => 'گیلان',
		'ADL' => 'اردبیل',
		'EAST' => 'آذربایجان شرقی',
		'WEST' => 'آذربایجان غربی',
		'ZJN' => 'زنجان',
		'KRN' => 'کرمان',
		'SKH' => 'خراسان جنوبی',
		'RKH' => 'خراسان رضوی',
		'MKD' => 'خراسان شمالی',
		'SBK' => 'سیستان و بلوچستان',
	);

	/**
	 * ثبت هوک‌های صفحهٔ نقشه: زیرمنو زیر «مشتریار» و بارگذاری دارایی‌ها.
	 * یکپارچه‌ساز این متد را در محیط ادمین صدا می‌زند.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * افزودن زیرمنوی نقشه به منوی سطح بالای مشتریار.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'moshtaryar-dashboard',
			__( 'نقشه حرارتی استان‌ها — مشتریار', 'moshtaryar' ),
			__( 'نقشه استان‌ها', 'moshtaryar' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * برچسب سطل استان نامشخص (خارج از ۳۱ استان رسمی).
	 *
	 * @return string
	 */
	public static function unknown_label() {
		return apply_filters( 'moshtaryar_unknown_state_label', __( 'نامشخص', 'moshtaryar' ) );
	}

	/**
	 * داده خام نقشه: تعداد سفارش، درآمد و تعداد مشتری هر استان.
	 *
	 * استان هر سطر از جدول سفارش‌های آماری می‌آید؛ شمار مشتریان
	 * به‌صورت مشتریان یکتای هر استان محاسبه می‌شود.
	 *
	 * @return array<string, array{name_fa:string, orders:int, customers:int, revenue:float}>
	 */
	public static function get_data() {
		global $wpdb;

		$table_orders    = $wpdb->prefix . 'moshtaryar_orders';
		$existing_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_orders ) ) );
		if ( empty( $existing_tables ) ) {
			return array();
		}

		// تجمیع سفارش‌ها به تفکیک استان حمل.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT shipping_state, COUNT(*) AS orders, SUM(total_amount) AS revenue, COUNT(DISTINCT customer_id) AS customers
				 FROM {$table_orders}
				 WHERE shipping_state <> %s
				 GROUP BY shipping_state",
				''
			),
			ARRAY_A
		);

		$data = array();
		foreach ( self::$provinces as $code => $name_fa ) {
			$data[ $code ] = array(
				'name_fa'   => $name_fa,
				'orders'    => 0,
				'customers' => 0,
				'revenue'   => 0.0,
			);
		}

		foreach ( (array) $rows as $row ) {
			$state = is_string( $row['shipping_state'] ) ? strtoupper( trim( $row['shipping_state'] ) ) : '';
			if ( ! isset( $data[ $state ] ) ) {
				// استان خارج از فهرست رسمی → سطل نامشخص.
				$state = 'unknown';
				if ( ! isset( $data[ $state ] ) ) {
					$data[ $state ] = array(
						'name_fa'   => self::unknown_label(),
						'orders'    => 0,
						'customers' => 0,
						'revenue'   => 0.0,
					);
				}
			}
			$data[ $state ]['orders']    += (int) $row['orders'];
			$data[ $state ]['customers'] += (int) $row['customers'];
			$data[ $state ]['revenue']   += (float) $row['revenue'];
		}

		/**
		 * امکان تغییر داده نقشه پیش از نمایش.
		 *
		 * @param array $data داده استان‌ها با کلید کد استان.
		 */
		return apply_filters( 'moshtaryar_map_data', $data );
	}

	/**
	 * حالت نمایش فعلی (تعداد سفارش یا درآمد) از GET، با مقدار مجاز.
	 *
	 * @return string orders|revenue
	 */
	public static function current_metric() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط خواندن حالت نمایش، بدون کنش نوشتنی.
		$metric = isset( $_GET['metric'] ) ? sanitize_key( wp_unslash( $_GET['metric'] ) ) : 'orders';
		return in_array( $metric, array( 'orders', 'revenue' ), true ) ? $metric : 'orders';
	}

	/**
	 * بارگذاری دارایی‌های نقشه فقط روی صفحه خودش.
	 *
	 * @param string $hook_suffix صفحه جاری پیشخوان.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, self::PAGE_SLUG ) ) {
			return;
		}

		// استایل عمومی ادمین مشتریار برای چیدمان/فونت مشترک صفحات افزونه.
		wp_enqueue_style(
			'moshtaryar-admin',
			MY_URL . 'assets/css/moshtaryar-admin.css',
			array(),
			MY_VER
		);

		wp_enqueue_style(
			'moshtaryar-iran-map',
			MY_URL . 'assets/css/iran-map.css',
			array(),
			MY_VER
		);

		wp_enqueue_script(
			'moshtaryar-iran-paths',
			MY_URL . 'assets/js/iran-paths.js',
			array(),
			MY_VER,
			true
		);

		wp_enqueue_script(
			'moshtaryar-iran-map',
			MY_URL . 'assets/js/iran-map.js',
			array( 'moshtaryar-iran-paths' ),
			MY_VER,
			true
		);

		wp_localize_script(
			'moshtaryar-iran-map',
			'moshtaryarMapData',
			array(
				'provinces' => self::get_data(),
				'metric'    => self::current_metric(),
				'i18n'      => array(
					'orders'  => __( 'تعداد سفارش', 'moshtaryar' ),
					'revenue' => __( 'درآمد', 'moshtaryar' ),
					'unknown' => self::unknown_label(),
					'noData'  => __( 'داده‌ای ثبت نشده است.', 'moshtaryar' ),
				),
				'colors'    => array( '#fef3c7', '#fde68a', '#f59e0b', '#dc2626', '#991b1b' ),
			)
		);
	}

	/**
	 * رندر صفحه نقشه حرارتی.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این صفحه را ندارید.', 'moshtaryar' ) );
		}

		$data = self::get_data();
		require MY_DIR . 'includes/admin/views/map-tab.php';
	}
}
