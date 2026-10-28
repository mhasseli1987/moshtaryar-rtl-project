<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * رابط مدیریت مشتریار: منو، صفحات داشبورد و مشتریان، کنش‌های فرم و بارگذاری دارایی‌ها.
 *
 * بارگذار مرکزی افزونه باید این کلاس را فقط در محیط ادمین و پس از بررسی ووکامرس
 * نمونه‌سازی کند: new Moshtaryar_Admin_Pages();
 *
 * @package Moshtaryar
 */
class Moshtaryar_Admin_Pages {

	/**
	 * نمونهٔ ارائه‌دهندهٔ داده (ساخت با اولین استفاده).
	 *
	 * @var Moshtaryar_Data_Provider|null
	 */
	private $provider = null;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_moshtaryar_recalc', array( $this, 'handle_recalc' ) );
		add_action( 'admin_post_moshtaryar_export_csv', array( $this, 'handle_export_csv' ) );
	}

	/**
	 * نمونهٔ ارائه‌دهندهٔ داده.
	 *
	 * @return Moshtaryar_Data_Provider
	 */
	private function provider() {
		if ( null === $this->provider ) {
			$this->provider = Moshtaryar_Data_Provider::instance();
		}

		return $this->provider;
	}

	/**
	 * منوی سطح بالای «مشتریار» و زیرمنوهای داشبورد و مشتریان.
	 *
	 * صفحهٔ نقشه (moshtaryar-map) را سازندهٔ نقشه با والد همین اسلاگ اضافه می‌کند.
	 */
	public function register_menu() {
		$capability = 'manage_woocommerce';

		add_menu_page(
			__( 'مشتریار — تحلیل مشتریان', 'moshtaryar' ),
			__( 'مشتریار', 'moshtaryar' ),
			$capability,
			'moshtaryar-dashboard',
			array( $this, 'render_dashboard' ),
			$this->menu_icon(),
			56
		);

		add_submenu_page(
			'moshtaryar-dashboard',
			__( 'داشبورد مشتریار', 'moshtaryar' ),
			__( 'داشبورد', 'moshtaryar' ),
			$capability,
			'moshtaryar-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'moshtaryar-dashboard',
			__( 'مشتریان فروشگاه — مشتریار', 'moshtaryar' ),
			__( 'مشتریان', 'moshtaryar' ),
			$capability,
			'moshtaryar-customers',
			array( $this, 'render_customers' )
		);
	}

	/**
	 * آیکن منو: SVG دست‌ساز داخل data URI — بدون فونت‌آیکن بیرونی.
	 *
	 * @return string
	 */
	private function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><g fill="#a7aaad"><rect x="2.5" y="11" width="3.4" height="6.5" rx="1"/><rect x="8.3" y="7" width="3.4" height="10.5" rx="1"/><rect x="14.1" y="3" width="3.4" height="14.5" rx="1"/></g></svg>';

		return 'data:image/svg+xml;charset=utf-8,' . rawurlencode( $svg );
	}

	/**
	 * بارگذاری دارایی‌ها فقط روی صفحات خود افزونه.
	 *
	 * @param string $hook هوک صفحهٔ جاری ادمین.
	 */
	public function enqueue_assets( $hook ) {
		unset( $hook );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط تشخیص صفحه، بدون تغییر داده.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $page, array( 'moshtaryar-dashboard', 'moshtaryar-customers' ), true ) ) {
			return;
		}

		$version = defined( 'MY_VER' ) ? MY_VER : '1.0.0';
		$base    = $this->assets_url();

		wp_enqueue_style( 'moshtaryar-admin', $base . '/css/moshtaryar-admin.css', array(), $version );
		wp_enqueue_script( 'moshtaryar-admin', $base . '/js/moshtaryar-admin.js', array(), $version, true );

		if ( 'moshtaryar-dashboard' === $page ) {
			wp_localize_script( 'moshtaryar-admin', 'moshtaryarCharts', $this->charts_payload() );
		}
	}

	/**
	 * نشانی پوشهٔ assets افزونه.
	 *
	 * @return string
	 */
	private function assets_url() {
		if ( defined( 'MY_URL' ) && MY_URL ) {
			$root = untrailingslashit( MY_URL );
		} else {
			// مسیر جایگزین بر پایهٔ محل همین فایل: includes/admin/ → ریشهٔ افزونه.
			$root = untrailingslashit( plugin_dir_url( dirname( __DIR__, 2 ) . '/moshtaryar.php' ) );
		}

		return $root . '/assets';
	}

	/**
	 * دادهٔ نمودارها برای wp_localize_script؛ قالب‌بندی اعداد/مبلغ سمت PHP انجام می‌شود.
	 *
	 * @return array
	 */
	private function charts_payload() {
		$provider = $this->provider();

		$labels   = $provider->get_segment_labels();
		$fallback = array(
			'label' => __( 'نامشخص', 'moshtaryar' ),
			'color' => '#8c8f94',
		);

		$segments = array();
		foreach ( $provider->get_segment_distribution() as $row ) {
			$meta = isset( $labels[ $row['segment'] ] ) ? $labels[ $row['segment'] ] : $fallback;

			$segments[] = array(
				'segment' => (string) $row['segment'],
				'label'   => (string) $meta['label'],
				'color'   => (string) $meta['color'],
				'count'   => (int) $row['count'],
			);
		}

		$monthly_rows = $provider->get_monthly_sales( 12 );
		$monthly      = array();
		foreach ( $monthly_rows as $row ) {
			$monthly[] = array(
				'label' => (string) $row['label'],
				'total' => (float) $row['total'],
				'price' => moshtaryar_price( (float) $row['total'] ),
			);
		}

		return array(
			'segments' => $segments,
			'monthly'  => $monthly,
			'axis'     => $provider->get_monthly_axis( $monthly_rows ),
			'i18n'     => array(
				'confirm_recalc' => __( 'بازمحاسبهٔ کامل ممکن است بسته به تعداد سفارش‌ها چند لحظه طول بکشد. ادامه می‌دهید؟', 'moshtaryar' ),
				'no_data'        => __( 'داده‌ای برای نمایش وجود ندارد.', 'moshtaryar' ),
				'no_sales'       => __( 'در ۱۲ ماه اخیر فروشی ثبت نشده است.', 'moshtaryar' ),
				'zero'           => __( 'بدون فروش', 'moshtaryar' ),
			),
		);
	}

	/**
	 * رندر صفحهٔ داشبورد.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازهٔ دسترسی به این بخش را ندارید.', 'moshtaryar' ) );
		}

		$provider = $this->provider();
		$kpis     = $provider->get_kpis();

		// کارت‌های KPI با مقدار آمادهٔ نمایش.
		$kpi_cards = array(
			array(
				'key'   => 'total',
				'label' => __( 'کل مشتریان', 'moshtaryar' ),
				'value' => moshtaryar_fa_num( $kpis['total'] ),
				'icon'  => 'users',
			),
			array(
				'key'   => 'active',
				'label' => __( 'فعال در ۳۰ روز اخیر', 'moshtaryar' ),
				'value' => moshtaryar_fa_num( $kpis['active_30d'] ),
				'icon'  => 'pulse',
			),
			array(
				'key'   => 'avg',
				'label' => __( 'میانگین ارزش مشتری', 'moshtaryar' ),
				'value' => moshtaryar_price( (float) $kpis['avg_value'] ),
				'icon'  => 'banknote',
			),
			array(
				'key'   => 'repeat',
				'label' => __( 'نرخ تکرار خرید', 'moshtaryar' ),
				'value' => $provider->format_percent( $kpis['repeat_rate'] ),
				'icon'  => 'repeat',
			),
		);

		// توزیع سگمنت‌ها با برچسب، رنگ و درصدِ از پیش محاسبه‌شده.
		$labels     = $provider->get_segment_labels();
		$fallback   = array(
			'label' => __( 'نامشخص', 'moshtaryar' ),
			'color' => '#8c8f94',
		);
		$dist       = $provider->get_segment_distribution();
		$total_dist = 0;
		foreach ( $dist as $row ) {
			$total_dist += (int) $row['count'];
		}

		$dist_view = array();
		foreach ( $dist as $row ) {
			$meta    = isset( $labels[ $row['segment'] ] ) ? $labels[ $row['segment'] ] : $fallback;
			$percent = $total_dist > 0 ? ( (int) $row['count'] / $total_dist ) * 100 : 0;

			$dist_view[] = array(
				'segment' => (string) $row['segment'],
				'label'   => (string) $meta['label'],
				'color'   => (string) $meta['color'],
				'count'   => (int) $row['count'],
				'percent' => $provider->format_percent( $percent ),
			);
		}

		$this->view(
			'dashboard',
			array(
				'kpi_cards'       => $kpi_cards,
				'dist_view'       => $dist_view,
				'total_customers' => (int) $kpis['total'],
				'last_calc'       => $provider->get_last_recalc(),
				'notice'          => $this->current_notice(),
			)
		);
	}

	/**
	 * رندر صفحهٔ جدول مشتریان.
	 */
	public function render_customers() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازهٔ دسترسی به این بخش را ندارید.', 'moshtaryar' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- فیلترهای نمایشی GET، بدون تغییر داده.
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$segment = isset( $_GET['segment'] ) ? sanitize_key( wp_unslash( $_GET['segment'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'last_order_date';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';
		$paged   = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		// phpcs:enable

		$provider = $this->provider();
		$result   = $provider->get_customers(
			array(
				'search'  => $search,
				'segment' => $segment,
				'orderby' => $orderby,
				'order'   => $order,
				'paged'   => $paged,
			)
		);

		$labels   = $provider->get_segment_labels();
		$sortable = $provider->sortable_columns();
		$fallback = array(
			'label' => __( 'نامشخص', 'moshtaryar' ),
			'color' => '#8c8f94',
		);

		// سطرهای آمادهٔ نمایش به‌همراه متادیتای سگمنت و نام جایگزین.
		$rows_view = array();
		foreach ( $result['rows'] as $row ) {
			$meta = isset( $labels[ $row->segment ] ) ? $labels[ $row->segment ] : $fallback;

			$rows_view[] = array(
				'row'          => $row,
				'badge_label'  => (string) $meta['label'],
				'badge_color'  => (string) $meta['color'],
				'display_name' => '' !== $row->display_name ? $row->display_name : ( '' !== $row->billing_email ? $row->billing_email : __( 'بدون نام', 'moshtaryar' ) ),
			);
		}

		// سرستون‌ها به‌همراه نشانی مرتب‌سازی و جهت فعلی.
		$columns = array(
			'display_name'    => array(
				'label'    => __( 'نام', 'moshtaryar' ),
				'sortable' => true,
			),
			'billing_email'   => array(
				'label'    => __( 'ایمیل', 'moshtaryar' ),
				'sortable' => false,
			),
			'segment'         => array(
				'label'    => __( 'سگمنت', 'moshtaryar' ),
				'sortable' => false,
			),
			'last_order_date' => array(
				'label'    => __( 'آخرین خرید', 'moshtaryar' ),
				'sortable' => true,
			),
			'order_count'     => array(
				'label'    => __( 'تعداد سفارش', 'moshtaryar' ),
				'sortable' => true,
			),
			'total_spent'     => array(
				'label'    => __( 'مجموع خرید', 'moshtaryar' ),
				'sortable' => true,
			),
			'avg_order_value' => array(
				'label'    => __( 'میانگین سبد', 'moshtaryar' ),
				'sortable' => true,
			),
		);

		foreach ( $columns as $key => &$column ) {
			$column['url'] = '';
			$column['dir'] = '';

			if ( empty( $column['sortable'] ) || ! isset( $sortable[ $key ] ) ) {
				continue;
			}

			$column['url'] = add_query_arg(
				array(
					'page'    => 'moshtaryar-customers',
					's'       => $search,
					'segment' => $segment,
					'orderby' => $key,
					'order'   => ( $orderby === $key && 'asc' === $order ) ? 'desc' : 'asc',
				),
				admin_url( 'admin.php' )
			);

			if ( $orderby === $key ) {
				$column['dir'] = ( 'asc' === $order ) ? 'asc' : 'desc';
			}
		}
		unset( $column );

		$pages = max( 1, (int) $result['pages'] );
		$paged = min( max( 1, $paged ), $pages );

		$pagination = array(
			'info' => sprintf(
				/* translators: 1: شمارهٔ صفحه، 2: تعداد کل صفحات. */
				__( 'صفحهٔ %1$s از %2$s', 'moshtaryar' ),
				moshtaryar_fa_num( $paged ),
				moshtaryar_fa_num( $pages )
			),
			'prev' => $paged > 1 ? $this->customers_page_url( $search, $segment, $orderby, $order, $paged - 1 ) : '',
			'next' => $paged < $pages ? $this->customers_page_url( $search, $segment, $orderby, $order, $paged + 1 ) : '',
		);

		$this->view(
			'customers-table',
			array(
				'rows_view'  => $rows_view,
				'columns'    => $columns,
				'labels'     => $labels,
				'total'      => (int) $result['total'],
				'pages'      => $pages,
				'pagination' => $pagination,
				'filters'    => array(
					'search'  => $search,
					'segment' => $segment,
					'orderby' => $orderby,
					'order'   => $order,
				),
			)
		);
	}

	/**
	 * نشانی صفحهٔ مشتریان با فیلترهای فعلی.
	 *
	 * @param string $search  عبارت جست‌وجو.
	 * @param string $segment نامک سگمنت.
	 * @param string $orderby ستون مرتب‌سازی.
	 * @param string $order   جهت.
	 * @param int    $paged   شمارهٔ صفحه.
	 * @return string
	 */
	private function customers_page_url( $search, $segment, $orderby, $order, $paged ) {
		return add_query_arg(
			array(
				'page'    => 'moshtaryar-customers',
				's'       => $search,
				'segment' => $segment,
				'orderby' => $orderby,
				'order'   => $order,
				'paged'   => $paged,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * کنش بازمحاسبهٔ کامل: nonce + capability، سپس ریدایرکت با پیام نتیجه.
	 */
	public function handle_recalc() {
		check_admin_referer( 'moshtaryar_recalc', '_mynonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازهٔ دسترسی به این بخش را ندارید.', 'moshtaryar' ) );
		}

		$redirect = admin_url( 'admin.php?page=moshtaryar-dashboard' );

		if ( ! class_exists( 'Moshtaryar_RFM_Engine' ) || ! method_exists( 'Moshtaryar_RFM_Engine', 'full_recalc' ) ) {
			wp_safe_redirect( add_query_arg( 'moshtaryar_notice', 'recalc_fail', $redirect ) );
			exit;
		}

		wp_raise_memory_limit( 'admin' );

		$result = Moshtaryar_RFM_Engine::full_recalc();

		wp_safe_redirect(
			add_query_arg(
				array(
					'moshtaryar_notice' => 'recalc_ok',
					'c'                 => isset( $result['customers'] ) ? absint( $result['customers'] ) : 0,
					'o'                 => isset( $result['orders'] ) ? absint( $result['orders'] ) : 0,
					't'                 => isset( $result['seconds'] ) ? round( (float) $result['seconds'], 1 ) : 0,
				),
				$redirect
			)
		);
		exit;
	}

	/**
	 * کنش خروجی CSV: همان فیلترهای صفحه، همهٔ نتایج، با BOM و اعداد لاتین (بخش ۱۳ SPEC).
	 */
	public function handle_export_csv() {
		check_admin_referer( 'moshtaryar_export_csv', '_mynonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازهٔ دسترسی به این بخش را ندارید.', 'moshtaryar' ) );
		}

		$search  = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		$segment = isset( $_POST['segment'] ) ? sanitize_key( wp_unslash( $_POST['segment'] ) ) : '';

		$provider = $this->provider();
		$result   = $provider->get_customers(
			array(
				'search'   => $search,
				'segment'  => $segment,
				'orderby'  => 'last_order_date',
				'order'    => 'desc',
				'per_page' => 0, // همهٔ نتایجِ فیلترشده، بدون صفحه‌بندی.
			)
		);

		$labels   = $provider->get_segment_labels();
		$fallback = __( 'نامشخص', 'moshtaryar' );

		$columns = apply_filters(
			'moshtaryar_csv_columns',
			array(
				'display_name'    => __( 'نام', 'moshtaryar' ),
				'billing_email'   => __( 'ایمیل', 'moshtaryar' ),
				'segment'         => __( 'سگمنت', 'moshtaryar' ),
				'last_order_date' => __( 'آخرین خرید', 'moshtaryar' ),
				'order_count'     => __( 'تعداد سفارش', 'moshtaryar' ),
				'total_spent'     => __( 'مجموع خرید', 'moshtaryar' ),
				'avg_order_value' => __( 'میانگین سبد', 'moshtaryar' ),
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="moshtaryar-customers-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$handle = fopen( 'php://output', 'w' );
		fwrite( $handle, "\xEF\xBB\xBF" ); // BOM برای نمایش درست فارسی در اکسل.

		// پارامتر escape صریح است تا روی PHP 8.4 اخطار Deprecated صادر نشود.
		fputcsv( $handle, array_values( $columns ), ',', '"', '\\' );

		foreach ( $result['rows'] as $row ) {
			$values = array();
			foreach ( array_keys( $columns ) as $key ) {
				$values[] = $this->csv_value( $key, $row, $labels, $fallback, $provider );
			}
			fputcsv( $handle, $values, ',', '"', '\\' );
		}

		fclose( $handle );
		exit;
	}

	/**
	 * مقدار یک ستون CSV؛ اعداد لاتین می‌مانند و تاریخ و سگمنت فارسی‌اند (بخش ۱۳ SPEC).
	 *
	 * @param string                   $key       کلید ستون.
	 * @param object                   $row       سطر جدول مشتریان.
	 * @param array                    $labels    برچسب سگمنت‌ها.
	 * @param string                   $fallback  برچسب سگمنت ناشناخته.
	 * @param Moshtaryar_Data_Provider $provider  ارائه‌دهندهٔ داده.
	 * @return string|int|float
	 */
	private function csv_value( $key, $row, $labels, $fallback, $provider ) {
		switch ( $key ) {
			case 'segment':
				return isset( $labels[ $row->segment ] ) ? (string) $labels[ $row->segment ]['label'] : $fallback;
			case 'last_order_date':
				return ! empty( $row->last_order_date ) ? $provider->jalali_date_latin( $row->last_order_date, 'Y/m/d' ) : '';
			case 'order_count':
				return (int) $row->order_count;
			case 'total_spent':
			case 'avg_order_value':
				return (float) $row->{$key};
			default:
				return isset( $row->{$key} ) ? (string) $row->{$key} : '';
		}
	}

	/**
	 * پیام وضعیت پس از ریدایرکت کنش‌ها.
	 *
	 * @return array{type:string, message:string}|null
	 */
	private function current_notice() {
		if ( empty( $_GET['moshtaryar_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_key( wp_unslash( $_GET['moshtaryar_notice'] ) );

		if ( 'recalc_fail' === $slug ) {
			return array(
				'type'    => 'error',
				'message' => __( 'بازمحاسبه انجام نشد؛ موتور تحلیل (RFM) در دسترس نیست.', 'moshtaryar' ),
			);
		}

		if ( 'recalc_ok' !== $slug ) {
			return null;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$customers = isset( $_GET['c'] ) ? absint( $_GET['c'] ) : 0;
		$orders    = isset( $_GET['o'] ) ? absint( $_GET['o'] ) : 0;
		$seconds   = isset( $_GET['t'] ) ? (float) $_GET['t'] : 0;
		// phpcs:enable

		return array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: 1: تعداد مشتریان، 2: تعداد سفارش‌ها، 3: مدت زمان به ثانیه. */
				__( 'بازمحاسبهٔ کامل انجام شد: %1$s مشتری از روی %2$s سفارش در %3$s ثانیه.', 'moshtaryar' ),
				moshtaryar_fa_num( $customers ),
				moshtaryar_fa_num( $orders ),
				moshtaryar_fa_num( round( $seconds, 1 ) )
			),
		);
	}

	/**
	 * بارگذاری نمای صفحه با متغیرهای کنترل‌شده.
	 *
	 * @param string $name نام نما (بدون پسوند).
	 * @param array  $args متغیرهای نما.
	 */
	private function view( $name, $args ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- متغیرهای داخلی کنترل‌شده برای نما.
		extract( $args );

		include __DIR__ . '/views/' . $name . '.php';
	}
}
