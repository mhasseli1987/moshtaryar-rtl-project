<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ارائه‌دهندهٔ دادهٔ صفحات ادمین مشتریار.
 *
 * تنها نقطهٔ دسترسی صفحات پیشخوان به داده‌ها؛ همهٔ کوئری‌ها پارامتردارند و
 * ورودی‌های مرتب‌سازی/فیلتر فقط از فهرست مجاز عبور می‌کنند.
 *
 * وابستگی (قرارداد بخش ۱۵ SPEC): توابع moshtaryar_fa_num، moshtaryar_price و
 * moshtaryar_fa_date باید در includes/functions.php هسته تعریف شده باشند.
 *
 * @package Moshtaryar
 */
class Moshtaryar_Data_Provider {

	/**
	 * نمونهٔ کلاس (برای فراخوانی استاتیک).
	 *
	 * @var Moshtaryar_Data_Provider|null
	 */
	private static $instance = null;

	/**
	 * دسترسی سراسری.
	 *
	 * @return Moshtaryar_Data_Provider
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * نام جدول مشتریان.
	 *
	 * @return string
	 */
	private function customers_table() {
		global $wpdb;
		return $wpdb->prefix . 'moshtaryar_customers';
	}

	/**
	 * نام جدول سفارش‌های آماری.
	 *
	 * @return string
	 */
	private function orders_table() {
		global $wpdb;
		return $wpdb->prefix . 'moshtaryar_orders';
	}

	/**
	 * آیا جدول‌های افزونه ساخته شده‌اند؟ (پیش از فعال‌سازی از خطای SQL جلوگیری می‌کند)
	 *
	 * @return bool
	 */
	private function tables_ready() {
		global $wpdb;

		static $ready = null;
		if ( null !== $ready ) {
			return $ready;
		}

		$table = $this->customers_table();
		$ready = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );

		return $ready;
	}

	/**
	 * شاخص‌های کلیدی داشبورد.
	 *
	 * @return array{total:int, active_30d:int, avg_value:float, repeat_rate:float}
	 */
	public function get_kpis() {
		global $wpdb;

		$defaults = array(
			'total'       => 0,
			'active_30d'  => 0,
			'avg_value'   => 0.0,
			'repeat_rate' => 0.0,
		);

		if ( ! $this->tables_ready() ) {
			return apply_filters( 'moshtaryar_kpis', $defaults );
		}

		// مقایسه با زمان محلی سایت، هم‌سو با ذخیرهٔ تاریخ سفارش‌ها.
		$active_since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS );

		$row = $wpdb->get_row( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- نام جدول ثابت داخلی است.
			"SELECT COUNT( * ) AS total,
				COALESCE( SUM( CASE WHEN last_order_date >= %s THEN 1 ELSE 0 END ), 0 ) AS active_30d,
				COALESCE( AVG( total_spent ), 0 ) AS avg_value,
				COALESCE( SUM( CASE WHEN order_count >= 2 THEN 1 ELSE 0 END ), 0 ) AS repeaters
			FROM {$this->customers_table()}",
			$active_since
		) );

		if ( ! $row ) {
			return apply_filters( 'moshtaryar_kpis', $defaults );
		}

		$total = (int) $row->total;

		$kpis = array(
			'total'       => $total,
			'active_30d'  => (int) $row->active_30d,
			'avg_value'   => (float) $row->avg_value,
			'repeat_rate' => $total > 0 ? round( ( (int) $row->repeaters / $total ) * 100, 1 ) : 0.0,
		);

		return apply_filters( 'moshtaryar_kpis', $kpis );
	}

	/**
	 * برچسب و رنگ سگمنت‌ها؛ از فیلتر رسمی بخش ۷-۴ SPEC می‌گذرد تا با هسته هم‌خوان بماند.
	 *
	 * @return array<string, array{label:string, color:string}>
	 */
	public function get_segment_labels() {
		return apply_filters(
			'moshtaryar_segment_labels',
			array(
				'champions'       => array(
					'label' => __( 'قهرمان', 'moshtaryar' ),
					'color' => '#16a34a',
				),
				'loyal'           => array(
					'label' => __( 'وفادار', 'moshtaryar' ),
					'color' => '#0ea5e9',
				),
				'potential_loyal' => array(
					'label' => __( 'بالقوه وفادار', 'moshtaryar' ),
					'color' => '#6366f1',
				),
				'new'             => array(
					'label' => __( 'تازه‌وارد', 'moshtaryar' ),
					'color' => '#22d3ee',
				),
				'needs_attention' => array(
					'label' => __( 'نیازمند توجه', 'moshtaryar' ),
					'color' => '#f59e0b',
				),
				'at_risk'         => array(
					'label' => __( 'در خطر رفتن', 'moshtaryar' ),
					'color' => '#f97316',
				),
				'hibernating'     => array(
					'label' => __( 'خوابیده', 'moshtaryar' ),
					'color' => '#a855f7',
				),
				'lost'            => array(
					'label' => __( 'از دست رفته', 'moshtaryar' ),
					'color' => '#ef4444',
				),
			)
		);
	}

	/**
	 * توزیع سگمنت‌ها با ترتیب ثابت بخش ۷-۴؛ مقادیر ناشناخته در انتها با نامک unknown.
	 *
	 * @return array<int, array{segment:string, count:int}>
	 */
	public function get_segment_distribution() {
		global $wpdb;

		if ( ! $this->tables_ready() ) {
			return array();
		}

		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- کوئری ثابت بدون ورودی کاربر.
			"SELECT segment, COUNT( * ) AS count FROM {$this->customers_table()} GROUP BY segment"
		);

		$by_segment = array();
		foreach ( (array) $results as $row ) {
			$by_segment[ (string) $row->segment ] = (int) $row->count;
		}

		$rows = array();
		foreach ( array_keys( $this->get_segment_labels() ) as $slug ) {
			if ( ! empty( $by_segment[ $slug ] ) ) {
				$rows[] = array(
					'segment' => $slug,
					'count'   => $by_segment[ $slug ],
				);
				unset( $by_segment[ $slug ] );
			}
		}

		foreach ( $by_segment as $slug => $count ) {
			$rows[] = array(
				'segment' => '' === $slug ? 'unknown' : $slug,
				'count'   => $count,
			);
		}

		return $rows;
	}

	/**
	 * روند فروش ۱۲ ماه منتهی به ماه جاری، قدیمی‌ترین ماه اول.
	 *
	 * @param int $months تعداد ماه‌ها.
	 * @return array<int, array{label:string, total:float}>
	 */
	public function get_monthly_sales( $months = 12 ) {
		global $wpdb;

		if ( ! $this->tables_ready() ) {
			return array();
		}

		$months = max( 1, min( 24, absint( $months ) ) );

		// ابتدای پنجره: اولِ ماهِ (months - 1) ماه قبل، بر پایهٔ تاریخ محلی سایت.
		$first_of_month = strtotime( gmdate( 'Y-m-01', current_time( 'timestamp' ) ) );
		$start_ts       = strtotime( '-' . ( $months - 1 ) . ' months', $first_of_month );

		$results = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- نام جدول ثابت داخلی است.
			"SELECT DATE_FORMAT( date_created, '%%Y-%%m' ) AS ym, SUM( total_amount ) AS total
				FROM {$this->orders_table()}
				WHERE date_created IS NOT NULL AND date_created >= %s
				GROUP BY ym
				ORDER BY ym ASC",
			gmdate( 'Y-m-d 00:00:00', $start_ts )
		) );

		$totals = array();
		foreach ( (array) $results as $row ) {
			$totals[ $row->ym ] = (float) $row->total;
		}

		$rows = array();
		for ( $i = $months - 1; $i >= 0; $i-- ) {
			$ts     = strtotime( '-' . $i . ' months', $first_of_month );
			$ym     = gmdate( 'Y-m', $ts );
			$rows[] = array(
				'label' => $this->month_label( $ts ),
				'total' => isset( $totals[ $ym ] ) ? $totals[ $ym ] : 0.0,
			);
		}

		return apply_filters( 'moshtaryar_monthly_sales', $rows );
	}

	/**
	 * محور عمودی نمودار میله‌ای: سقف گردشده و چهار برچسب آمادهٔ فارسی.
	 *
	 * @param array<int, array{label:string, total:float}> $rows خروجی get_monthly_sales.
	 * @return array{max:float, labels:string[]}
	 */
	public function get_monthly_axis( $rows ) {
		$max = 0.0;
		foreach ( (array) $rows as $row ) {
			$max = max( $max, (float) $row['total'] );
		}

		if ( $max <= 0 ) {
			return array(
				'max'    => 0.0,
				'labels' => array(),
			);
		}

		$base  = pow( 10, floor( log10( $max ) ) );
		$ratio = $max / $base;
		$step  = $ratio <= 1 ? 1 : ( $ratio <= 2 ? 2 : ( $ratio <= 5 ? 5 : 10 ) );

		$labels = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$labels[] = $this->format_compact( $step * $base * $i / 4 );
		}

		return array(
			'max'    => (float) ( $step * $base ),
			'labels' => $labels,
		);
	}

	/**
	 * جدول مشتریان با جست‌وجو، فیلتر سگمنت، مرتب‌سازی مجاز و صفحه‌بندی.
	 *
	 * @param array $args {search, segment, orderby, order, paged, per_page} — per_page=0 یعنی همهٔ نتایج.
	 * @return array{rows:array, total:int, pages:int}
	 */
	public function get_customers( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'search'   => '',
				'segment'  => '',
				'orderby'  => 'last_order_date',
				'order'    => 'desc',
				'paged'    => 1,
				'per_page' => null,
			)
		);

		if ( ! $this->tables_ready() ) {
			return array(
				'rows'  => array(),
				'total' => 0,
				'pages' => 1,
			);
		}

		$per_page = null === $args['per_page']
			? max( 1, absint( apply_filters( 'moshtaryar_customers_per_page', 20 ) ) )
			: max( 0, absint( $args['per_page'] ) );

		$orderby = $this->resolve_sort_column( $args['orderby'] );
		$order   = ( 'asc' === strtolower( (string) $args['order'] ) ) ? 'ASC' : 'DESC';

		// فیلتر سگمنت فقط با نامک معتبر از فهرست سگمنت‌ها.
		$segment = (string) $args['segment'];
		$labels  = $this->get_segment_labels();
		if ( '' !== $segment && ! isset( $labels[ $segment ] ) ) {
			$segment = '';
		}

		$where  = array();
		$params = array();

		$search = (string) $args['search'];
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '( display_name LIKE %s OR billing_email LIKE %s )';
			$params[] = $like;
			$params[] = $like;
		}
		if ( '' !== $segment ) {
			$where[]  = 'segment = %s';
			$params[] = $segment;
		}
		$where_sql = empty( $where ) ? '' : ' WHERE ' . implode( ' AND ', $where );

		$table = $this->customers_table();

		$count_sql = "SELECT COUNT( * ) FROM {$table}" . $where_sql;
		$total     = empty( $params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- کوئری بدون هیچ ورودی کاربر.
			? (int) $wpdb->get_var( $count_sql )
			: (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

		$data_sql = "SELECT id, user_id, billing_email, display_name, last_order_date, order_count, total_spent, avg_order_value, segment
			FROM {$table}" . $where_sql . " ORDER BY {$orderby} {$order}";

		if ( $per_page > 0 ) {
			$paged     = max( 1, absint( $args['paged'] ) );
			$data_sql .= ' LIMIT %d OFFSET %d';
			$params[]  = $per_page;
			$params[]  = ( $paged - 1 ) * $per_page;
		}

		$results = empty( $params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- کوئری بدون هیچ ورودی کاربر (حالت CSV کامل).
			? $wpdb->get_results( $data_sql )
			: $wpdb->get_results( $wpdb->prepare( $data_sql, $params ) );

		return array(
			'rows'  => (array) $results,
			'total' => $total,
			'pages' => $per_page > 0 ? max( 1, (int) ceil( $total / $per_page ) ) : 1,
		);
	}

	/**
	 * ستون‌های مجاز مرتب‌سازی جدول مشتریان (فیلترپذیر).
	 *
	 * @return array<string, string> کلید => نام ستون SQL.
	 */
	public function sortable_columns() {
		return apply_filters(
			'moshtaryar_customers_sortable_columns',
			array(
				'display_name'    => 'display_name',
				'last_order_date' => 'last_order_date',
				'order_count'     => 'order_count',
				'total_spent'     => 'total_spent',
				'avg_order_value' => 'avg_order_value',
			)
		);
	}

	/**
	 * زمان آخرین بازمحاسبهٔ کامل.
	 *
	 * @return int
	 */
	public function get_last_recalc() {
		return (int) get_option( 'moshtaryar_last_recalc', 0 );
	}

	/**
	 * درصد فارسی‌شده با یک رقم اعشار («۱۲٫۵٪»).
	 *
	 * @param float $value مقدار درصد.
	 * @return string
	 */
	public function format_percent( $value ) {
		// قالب لاتین پیش‌فرض می‌شود تا رفتار رقم اعشار به فرمت‌دهی وابسته نباشد.
		$latin = number_format( round( (float) $value, 1 ), 1 );

		return str_replace( '.', '٫', moshtaryar_fa_num( $latin ) ) . '٪';
	}

	/**
	 * تاریخ جلالی با ارقام لاتین — برای CSV مطابق بخش ۱۳ SPEC.
	 *
	 * @param string|int $datetime تاریخ.
	 * @param string     $format   قالب.
	 * @return string
	 */
	public function jalali_date_latin( $datetime, $format = 'Y/m/d' ) {
		return $this->to_latin_digits( moshtaryar_fa_date( $datetime, $format ) );
	}

	/**
	 * تبدیل ارقام فارسی به لاتین.
	 *
	 * @param string $text متن ورودی.
	 * @return string
	 */
	public function to_latin_digits( $text ) {
		return strtr(
			(string) $text,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٫' => '.',
				'٬' => '',
			)
		);
	}

	/**
	 * برچسب جلالی ماه («مهر ۱۴۰۵») از مهرِ زمانی اولِ ماه.
	 *
	 * @param int $month_start_ts زمان اولِ ماه.
	 * @return string
	 */
	private function month_label( $month_start_ts ) {
		$names = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );

		// خروجی helpers ممکن است ارقام فارسی داشته باشد؛ برای تجزیه لاتین می‌شود.
		$parts = explode( '-', $this->to_latin_digits( moshtaryar_fa_date( $month_start_ts, 'Y-n' ) ) );

		if ( 2 !== count( $parts ) ) {
			return gmdate( 'Y-m', $month_start_ts );
		}

		$year  = (int) $parts[0];
		$month = (int) $parts[1];

		if ( $year <= 0 || $month < 1 || $month > 12 ) {
			return gmdate( 'Y-m', $month_start_ts );
		}

		return $names[ $month - 1 ] . ' ' . moshtaryar_fa_num( $year );
	}

	/**
	 * عدد فشرده برای محور نمودار («۴۵م»، «۶۲۵ه»، «۱٫۲۵م»).
	 *
	 * @param float $value مقدار.
	 * @return string
	 */
	private function format_compact( $value ) {
		$value = (float) $value;

		if ( $value >= 1000000 ) {
			$scaled = $value / 1000000;
			$latin  = abs( $scaled - round( $scaled ) ) < 0.001 ? number_format( round( $scaled ) ) : number_format( round( $scaled, 2 ), 2 );
			$suffix = 'م';
		} elseif ( $value >= 1000 ) {
			$scaled = $value / 1000;
			$latin  = abs( $scaled - round( $scaled ) ) < 0.001 ? number_format( round( $scaled ) ) : number_format( round( $scaled, 1 ), 1 );
			$suffix = 'ه';
		} else {
			$latin  = number_format( round( $value ) );
			$suffix = '';
		}

		return str_replace( '.', '٫', moshtaryar_fa_num( $latin ) ) . $suffix;
	}

	/**
	 * اعتبارسنجی ستون مرتب‌سازی از فهرست مجاز.
	 *
	 * @param string $orderby ورودی کاربر.
	 * @return string نام ستون SQL امن.
	 */
	private function resolve_sort_column( $orderby ) {
		$allowed = $this->sortable_columns();
		$key     = isset( $allowed[ $orderby ] ) ? (string) $orderby : 'last_order_date';
		$column  = (string) $allowed[ $key ];

		// حتی اگر فیلتری ستونی تزریق کرده باشد، فقط نام ستون معتبر پذیرفته می‌شود.
		if ( ! preg_match( '/^[a-z0-9_]+$/', $column ) ) {
			$column = 'last_order_date';
		}

		return $column;
	}
}
