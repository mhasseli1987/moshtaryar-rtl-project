<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * موتور RFM: تجمیع سفارش‌های ووکامرس (سازگار HPOS و کلاسیک)، امتیازدهی،
 * سگمنت‌بندی و ذخیره در جدول‌های اختصاصی.
 *
 * @package Moshtaryar
 */
class Moshtaryar_RFM_Engine {

	/**
	 * تعداد سفارش در هر دستهٔ پردازش — برای فروشگاه‌های بزرگ.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 500;

	/**
	 * بازمحاسبهٔ کامل همهٔ مشتریان و بازسازی هر دو جدول.
	 *
	 * @return array{customers:int, orders:int, seconds:float}
	 */
	public static function full_recalc() {
		global $wpdb;

		$start     = microtime( true );
		$statuses  = self::counted_statuses();
		$customers = array(); // کلید => آمار مشتری.
		$order_count = 0;

		// تجمیع دسته‌ای سفارش‌ها؛ هر دو مسیر خروجی یکسان می‌دهند.
		if ( self::hpos_enabled() ) {
			$iterate = array( __CLASS__, 'fetch_batch_hpos' );
		} else {
			$iterate = array( __CLASS__, 'fetch_batch_classic' );
		}

		$offset = 0;
		while ( true ) {
			$batch = call_user_func( $iterate, $statuses, $offset, self::BATCH_SIZE );
			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $batch as $row ) {
				$order_count++;
				self::accumulate( $customers, $row );
			}

			if ( count( $batch ) < self::BATCH_SIZE ) {
				break;
			}
			$offset += self::BATCH_SIZE;
		}

		// ادغام مهمان‌هایی که بعداً ثبت‌نام کرده‌اند با کاربر هم‌ایمیل.
		self::merge_guests_with_users( $customers );

		$now         = current_time( 'mysql' );
		$now_ts      = current_time( 'timestamp' );
		$now_user_ts = $now_ts;

		// بازسازی کامل جدول‌ها.
		$customers_table = $wpdb->prefix . 'moshtaryar_customers';
		$orders_table    = $wpdb->prefix . 'moshtaryar_orders';
		$wpdb->query( "TRUNCATE TABLE {$customers_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$orders_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$customer_rows = array();
		foreach ( $customers as $key => $c ) {
			$customer_rows[ $key ] = self::build_customer_row( $c, $now, $now_user_ts );
		}
		self::insert_customer_rows( array_values( $customer_rows ) );

		// نگاشت هویت → id پس از درج، برای ارجاع سفارش‌ها به مشتری.
		$identity_map = array();
		$map_results  = $wpdb->get_results( "SELECT id, user_id, billing_email FROM {$customers_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( (array) $map_results as $map_row ) {
			$key = ( (int) $map_row->user_id > 0 ) ? 'u:' . (int) $map_row->user_id : 'e:' . (string) $map_row->billing_email;
			$identity_map[ $key ] = (int) $map_row->id;
		}

		// سفارش‌های آماری با ارجاع به id مشتری ذخیره می‌شوند.
		$order_rows = array();
		foreach ( $customers as $key => $c ) {
			$customer_id = isset( $identity_map[ $key ] ) ? $identity_map[ $key ] : 0;
			foreach ( $c['orders'] as $o ) {
				$order_rows[] = array(
					'order_id'       => (int) $o['order_id'],
					'customer_id'    => $customer_id,
					'user_id'        => (int) $c['user_id'],
					'date_created'   => $o['date_created'],
					'total_amount'   => (float) $o['total'],
					'shipping_state' => $o['state'],
				);
			}
		}
		self::insert_order_rows( $order_rows );

		update_option( 'moshtaryar_last_recalc', $now_ts );
		do_action( 'moshtaryar_after_full_recalc' );

		return array(
			'customers' => count( $customer_rows ),
			'orders'    => $order_count,
			'seconds'   => round( microtime( true ) - $start, 2 ),
		);
	}

	/**
	 * بازمحاسبهٔ لحظه‌ای فقط مشتریِ یک سفارش (هوک تغییر وضعیت).
	 *
	 * @param int $order_id شناسهٔ سفارش.
	 */
	public static function recalc_customer( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = (int) $order->get_customer_id();
		$email   = strtolower( sanitize_email( $order->get_billing_email() ) );

		// همهٔ سفارش‌های شمارش‌شدهٔ همین هویت (سازگار HPOS).
		$args = array(
			'limit'  => -1,
			'status' => self::counted_statuses(),
			'return' => 'objects',
		);
		if ( $user_id > 0 ) {
			$args['customer_id'] = $user_id;
		} elseif ( $email ) {
			$args['billing_email'] = $email;
		} else {
			return; // بدون هویت قابل شمارش نیست.
		}
		$orders = wc_get_orders( $args );

		$customers_table = $wpdb->prefix . 'moshtaryar_customers';
		$orders_table    = $wpdb->prefix . 'moshtaryar_orders';

		// اگر سفارش از وضعیت شمارش‌شده خارج شده، سطر آماری‌اش حذف می‌شود.
		if ( ! in_array( $order->get_status(), array_map( array( __CLASS__, 'strip_prefix' ), self::counted_statuses() ), true )
			&& ! in_array( 'wc-' . $order->get_status(), self::counted_statuses(), true ) ) {
			$wpdb->delete( $orders_table, array( 'order_id' => (int) $order_id ), array( '%d' ) );
		}

		if ( empty( $orders ) ) {
			// دیگر سفارش شمارش‌شده‌ای ندارد؛ ردیف مشتری هم حذف می‌شود.
			if ( $user_id > 0 ) {
				$wpdb->delete( $customers_table, array( 'user_id' => $user_id ), array( '%d' ) );
			} elseif ( $email ) {
				$wpdb->delete( $customers_table, array( 'billing_email' => $email ), array( '%s' ) );
			}
			do_action( 'moshtaryar_after_customer_recalc', 0 );

			return;
		}

		// تجمیع دستی همان قالب مسیر کامل.
		$c = array(
			'user_id'       => $user_id,
			'email'         => $email,
			'display_name'  => self::order_display_name( $order, $user_id ),
			'last_date'     => null,
			'count'         => 0,
			'total'         => 0.0,
			'orders'        => array(),
		);

		foreach ( $orders as $o ) {
			$date = $o->get_date_created();
			$row  = array(
				'order_id' => (int) $o->get_id(),
				'date_created' => $date ? $date->date( 'Y-m-d H:i:s' ) : null,
				'total'    => (float) $o->get_total(),
				'state'    => self::normalize_state( $o->get_shipping_state() ),
			);
			$c['orders'][] = $row;
			$c['count']++;
			$c['total'] += $row['total'];
			if ( null === $c['last_date'] || ( $row['date_created'] && $row['date_created'] > $c['last_date'] ) ) {
				$c['last_date'] = $row['date_created'];
			}
		}

		$now    = current_time( 'mysql' );
		$row    = self::build_customer_row( $c, $now, current_time( 'timestamp' ) );
		$row_id = self::upsert_customer_row( $row, $user_id, $email );

		// به‌روزرسانی سطر سفارش جاری و ارجاع مشتری.
		foreach ( $c['orders'] as $o ) {
			$wpdb->replace(
				$orders_table,
				array(
					'order_id'       => $o['order_id'],
					'customer_id'    => $row_id,
					'user_id'        => $user_id,
					'date_created'   => $o['date_created'],
					'total_amount'   => $o['total'],
					'shipping_state' => $o['state'],
				),
				array( '%d', '%d', '%d', '%s', '%f', '%s' )
			);
		}

		do_action( 'moshtaryar_after_customer_recalc', $row_id );
	}

	/* ---------------------------------------------------------------------
	 * تجمیع و دسته‌بندی
	 * ------------------------------------------------------------------- */

	/**
	 * افزودن یک سطر سفارش به گروه مشتری مربوطه.
	 *
	 * @param array     $customers ارایهٔ گروه‌ها (ارجاع).
	 * @param object    $row       سطر سفارش از SQL.
	 */
	private static function accumulate( &$customers, $row ) {
		$user_id = (int) $row->user_id;
		$email   = strtolower( sanitize_email( (string) $row->billing_email ) );

		// هویت: کاربر ثبت‌شده یا ایمیل مهمان.
		$key = ( $user_id > 0 ) ? 'u:' . $user_id : 'e:' . $email;
		if ( $user_id <= 0 && '' === $email ) {
			$key = 'g:' . (int) $row->order_id; // مهمانِ بی‌ایمیل؛ هر سفارش جدا.
		}

		if ( ! isset( $customers[ $key ] ) ) {
			$customers[ $key ] = array(
				'user_id'      => $user_id,
				'email'        => $email,
				'display_name' => (string) $row->display_name,
				'last_date'    => null,
				'count'        => 0,
				'total'        => 0.0,
				'orders'       => array(),
			);
		}

		$c = &$customers[ $key ];
		if ( '' === $c['display_name'] && '' !== (string) $row->display_name ) {
			$c['display_name'] = (string) $row->display_name;
		}

		$c['count']++;
		$c['total'] += (float) $row->total_amount;
		if ( null === $c['last_date'] || ( $row->date_created && $row->date_created > $c['last_date'] ) ) {
			$c['last_date'] = $row->date_created;
		}
		$c['orders'][] = array(
			'order_id'     => (int) $row->order_id,
			'date_created' => $row->date_created,
			'total'        => (float) $row->total_amount,
			'state'        => self::normalize_state( (string) $row->shipping_state ),
		);
		unset( $c );
	}

	/**
	 * ادغام گروه مهمان با کاربری که همان ایمیل را دارد.
	 *
	 * @param array $customers آرایهٔ گروه‌ها (ارجاع).
	 */
	private static function merge_guests_with_users( &$customers ) {
		foreach ( $customers as $key => $c ) {
			if ( 0 !== strpos( $key, 'e:' ) || '' === $c['email'] ) {
				continue;
			}

			$user = get_user_by( 'email', $c['email'] );
			if ( ! $user ) {
				continue;
			}

			$user_key = 'u:' . (int) $user->ID;
			if ( ! isset( $customers[ $user_key ] ) ) {
				// کاربر هنوز سفارشی ندارد؛ گروه مهمان هویت کاربر را می‌گیرد.
				$customers[ $user_key ] = $c;
				$customers[ $user_key ]['user_id'] = (int) $user->ID;
			} else {
				// ادغام سفارش‌های مهمان با گروه کاربر.
				$customers[ $user_key ]['count']  += $c['count'];
				$customers[ $user_key ]['total']  += $c['total'];
				$customers[ $user_key ]['orders']  = array_merge( $customers[ $user_key ]['orders'], $c['orders'] );
				if ( $c['last_date'] && $c['last_date'] > $customers[ $user_key ]['last_date'] ) {
					$customers[ $user_key ]['last_date'] = $c['last_date'];
				}
			}

			unset( $customers[ $key ] );
		}
	}

	/**
	 * ساخت سطر کامل مشتری با امتیازها و سگمنت.
	 *
	 * @param array  $c            آمار تجمیعی مشتری.
	 * @param string $now          زمان محلی برای segment_updated_at.
	 * @param int    $now_ts       timestamp محلی برای محاسبهٔ recency.
	 * @return array
	 */
	private static function build_customer_row( $c, $now, $now_ts ) {
		$recency_days = 0;
		if ( $c['last_date'] ) {
			$recency_days = max( 0, (int) floor( ( $now_ts - strtotime( $c['last_date'] ) ) / DAY_IN_SECONDS ) );
		}

		$r = self::score_r( $recency_days );
		$f = self::score_f( $c['count'] );
		$m = self::score_m( $c['total'] );

		return array(
			'id'                 => null, // پس از درج پر می‌شود.
			'user_id'            => (int) $c['user_id'],
			'billing_email'      => (string) $c['email'],
			'display_name'       => (string) $c['display_name'],
			'last_order_date'    => $c['last_date'],
			'recency_days'       => $recency_days,
			'order_count'        => (int) $c['count'],
			'total_spent'        => (float) $c['total'],
			'avg_order_value'    => $c['count'] > 0 ? $c['total'] / $c['count'] : 0.0,
			'r_score'            => $r,
			'f_score'            => $f,
			'm_score'            => $m,
			'segment'            => self::segment_for( $r, $f, $m ),
			'segment_updated_at' => $now,
		);
	}

	/* ---------------------------------------------------------------------
	 * امتیازدهی و سگمنت — قواعد قطعی بخش ۷-۳ و ۷-۴ SPEC (فیلترپذیر)
	 * ------------------------------------------------------------------- */

	/**
	 * وضعیت‌های شمارش‌شده.
	 *
	 * @return string[]
	 */
	public static function counted_statuses() {
		return apply_filters( 'moshtaryar_counted_statuses', array( 'wc-completed' ) );
	}

	/**
	 * امتیاز Recency از روی روزهای سپری‌شده.
	 *
	 * @param int $days روز از آخرین خرید.
	 * @return int ۱ تا ۵.
	 */
	public static function score_r( $days ) {
		$t = apply_filters( 'moshtaryar_r_thresholds', array( 30, 90, 180, 365 ) );
		if ( $days <= $t[0] ) {
			return 5;
		}
		if ( $days <= $t[1] ) {
			return 4;
		}
		if ( $days <= $t[2] ) {
			return 3;
		}
		if ( $days <= $t[3] ) {
			return 2;
		}

		return 1;
	}

	/**
	 * امتیاز Frequency از روی تعداد سفارش.
	 *
	 * @param int $count تعداد سفارش‌های شمارش‌شده.
	 * @return int ۱ تا ۵.
	 */
	public static function score_f( $count ) {
		$t = apply_filters( 'moshtaryar_f_thresholds', array( 2, 4, 6, 10 ) );
		if ( $count >= $t[3] ) {
			return 5;
		}
		if ( $count >= $t[2] ) {
			return 4;
		}
		if ( $count >= $t[1] ) {
			return 3;
		}
		if ( $count >= $t[0] ) {
			return 2;
		}

		return 1;
	}

	/**
	 * امتیاز Monetary از روی مجموع خرید.
	 *
	 * @param float $total مجموع مبلغ.
	 * @return int ۱ تا ۵.
	 */
	public static function score_m( $total ) {
		$t = apply_filters(
			'moshtaryar_m_thresholds',
			get_option( 'moshtaryar_m_thresholds', array( 1000000, 5000000, 20000000, 50000000 ) )
		);
		if ( $total >= $t[3] ) {
			return 5;
		}
		if ( $total >= $t[2] ) {
			return 4;
		}
		if ( $total >= $t[1] ) {
			return 3;
		}
		if ( $total >= $t[0] ) {
			return 2;
		}

		return 1;
	}

	/**
	 * تعیین سگمنت — قواعد به‌ترتیب، اولین منطبق برنده.
	 *
	 * @param int $r امتیاز R.
	 * @param int $f امتیاز F.
	 * @param int $m امتیاز M.
	 * @return string نامک سگمنت.
	 */
	public static function segment_for( $r, $f, $m ) {
		$rules = apply_filters(
			'moshtaryar_segment_rules',
			array(
				array( 'segment' => 'champions',       'test' => function ( $r, $f, $m ) { return $r >= 4 && $f >= 4 && $m >= 4; } ),
				array( 'segment' => 'at_risk',         'test' => function ( $r, $f, $m ) { return $r <= 2 && $f >= 3; } ),
				array( 'segment' => 'loyal',           'test' => function ( $r, $f, $m ) { return $f >= 4; } ),
				array( 'segment' => 'potential_loyal', 'test' => function ( $r, $f, $m ) { return $r >= 4 && 3 === $f; } ),
				array( 'segment' => 'new',             'test' => function ( $r, $f, $m ) { return $r >= 4 && $f <= 2; } ),
				array( 'segment' => 'needs_attention', 'test' => function ( $r, $f, $m ) { return 3 === $r; } ),
				array( 'segment' => 'hibernating',     'test' => function ( $r, $f, $m ) { return 2 === $f; } ),
				array( 'segment' => 'lost',            'test' => function ( $r, $f, $m ) { return true; } ),
			)
		);

		foreach ( $rules as $rule ) {
			if ( call_user_func( $rule['test'], $r, $f, $m ) ) {
				return $rule['segment'];
			}
		}

		return 'lost';
	}

	/* ---------------------------------------------------------------------
	 * خواندن سفارش‌ها — HPOS و کلاسیک، دسته‌ای و صفحه‌بندی‌شده
	 * ------------------------------------------------------------------- */

	/**
	 * آیا HPOS (جدول سفارش اختصاصی) فعال است؟
	 *
	 * @return bool
	 */
	public static function hpos_enabled() {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_enabled' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_enabled();
	}

	/**
	 * یک دسته سفارش از جدول‌های HPOS (wc_orders + wc_order_addresses).
	 *
	 * @param string[] $statuses وضعیت‌های شمارش‌شده.
	 * @param int      $offset   آفست.
	 * @param int      $limit    اندازهٔ دسته.
	 * @return array
	 */
	private static function fetch_batch_hpos( $statuses, $offset, $limit ) {
		global $wpdb;

		$orders_table    = $wpdb->prefix . 'wc_orders';
		$addresses_table = $wpdb->prefix . 'wc_order_addresses';
		$placeholders    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- نام جدول‌ها ثابت داخلی است.
			"SELECT o.id AS order_id, o.customer_id AS user_id, o.total_amount, o.date_created_gmt,
				b.email AS billing_email, CONCAT_WS(' ', b.first_name, b.last_name) AS display_name,
				s.state AS shipping_state
			 FROM {$orders_table} o
			 LEFT JOIN {$addresses_table} b ON b.order_id = o.id AND b.address_type = 'billing'
			 LEFT JOIN {$addresses_table} s ON s.order_id = o.id AND s.address_type = 'shipping'
			 WHERE o.type = 'shop_order' AND o.status IN ({$placeholders})
			 ORDER BY o.id ASC
			 LIMIT %d OFFSET %d",
			array_merge( $statuses, array( $limit, $offset ) )
		);

		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// تاریخ GMT به وقت محلی سایت برگردانده می‌شود تا گروه‌بندی ماهیانه درست باشد.
		foreach ( (array) $rows as $row ) {
			$row->date_created = $row->date_created_gmt ? get_date_from_gmt( $row->date_created_gmt, 'Y-m-d H:i:s' ) : null;
			$row->user_id      = max( 0, (int) $row->user_id );
		}

		return (array) $rows;
	}

	/**
	 * یک دسته سفارش از ساختار کلاسیک (wp_posts + wp_postmeta).
	 *
	 * @param string[] $statuses وضعیت‌های شمارش‌شده.
	 * @param int      $offset   آفست.
	 * @param int      $limit    اندازهٔ دسته.
	 * @return array
	 */
	private static function fetch_batch_classic( $statuses, $offset, $limit ) {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- کوئری ثابت روی جداول هسته.
			"SELECT p.ID AS order_id, p.post_date AS date_created,
				COALESCE( CAST( cu.meta_value AS SIGNED ), 0 ) AS user_id,
				COALESCE( be.meta_value, '' ) AS billing_email,
				CAST( ot.meta_value AS DECIMAL(26,8) ) AS total_amount,
				CONCAT_WS(' ', bf.meta_value, bl.meta_value) AS display_name,
				COALESCE( st.meta_value, '' ) AS shipping_state
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} cu ON cu.post_id = p.ID AND cu.meta_key = '_customer_user'
			 LEFT JOIN {$wpdb->postmeta} be ON be.post_id = p.ID AND be.meta_key = '_billing_email'
			 LEFT JOIN {$wpdb->postmeta} ot ON ot.post_id = p.ID AND ot.meta_key = '_order_total'
			 LEFT JOIN {$wpdb->postmeta} bf ON bf.post_id = p.ID AND bf.meta_key = '_billing_first_name'
			 LEFT JOIN {$wpdb->postmeta} bl ON bl.post_id = p.ID AND bl.meta_key = '_billing_last_name'
			 LEFT JOIN {$wpdb->postmeta} st ON st.post_id = p.ID AND st.meta_key = '_shipping_state'
			 WHERE p.post_type = 'shop_order' AND p.post_status IN ({$placeholders})
			 ORDER BY p.ID ASC
			 LIMIT %d OFFSET %d",
			array_merge( $statuses, array( $limit, $offset ) )
		);

		return (array) $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* ---------------------------------------------------------------------
	 * نوشتن در جدول‌ها
	 * ------------------------------------------------------------------- */

	/**
	 * درج گروهی سطرهای مشتریان (چندسطری در هر کوئری).
	 *
	 * @param array $rows سطرها.
	 */
	private static function insert_customer_rows( $rows ) {
		global $wpdb;

		$table = $wpdb->prefix . 'moshtaryar_customers';
		$chunk = array();

		foreach ( $rows as $row ) {
			$chunk[] = $row;
			if ( count( $chunk ) >= 50 ) {
				self::flush_customer_chunk( $table, $chunk );
				$chunk = array();
			}
		}
		if ( $chunk ) {
			self::flush_customer_chunk( $table, $chunk );
		}
	}

	/**
	 * اجرای درج یک دسته سطر مشتری.
	 *
	 * @param string $table نام جدول.
	 * @param array  $chunk سطرها.
	 */
	private static function flush_customer_chunk( $table, $chunk ) {
		global $wpdb;

		$values   = array();
		$params   = array();
		$row_keys = array( 'user_id', 'billing_email', 'display_name', 'last_order_date', 'recency_days', 'order_count', 'total_spent', 'avg_order_value', 'r_score', 'f_score', 'm_score', 'segment', 'segment_updated_at' );

		foreach ( $chunk as $row ) {
			$placeholders = array( '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%d', '%d', '%d', '%s', '%s' );
			$values[]     = '(' . implode( ',', $placeholders ) . ')';
			foreach ( $row_keys as $key ) {
				$params[] = null === $row[ $key ] ? null : $row[ $key ];
			}
		}

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- درج گروهی با placeholders کامل.
				"INSERT INTO {$table} (user_id, billing_email, display_name, last_order_date, recency_days, order_count, total_spent, avg_order_value, r_score, f_score, m_score, segment, segment_updated_at)
				 VALUES " . implode( ',', $values ),
				$params
			)
		);
	}

	/**
	 * درج گروهی سطرهای سفارش آماری.
	 *
	 * @param array $rows سطرها.
	 */
	private static function insert_order_rows( $rows ) {
		global $wpdb;

		$table = $wpdb->prefix . 'moshtaryar_orders';
		$chunk = array();

		$flush = function ( $chunk ) use ( $table ) {
			global $wpdb;
			$values = array();
			$params = array();
			foreach ( $chunk as $r ) {
				$values[] = '(%d,%d,%d,%s,%f,%s)';
				$params[] = $r['order_id'];
				$params[] = $r['customer_id'];
				$params[] = $r['user_id'];
				$params[] = $r['date_created'];
				$params[] = $r['total_amount'];
				$params[] = $r['shipping_state'];
			}
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"INSERT INTO {$table} (order_id, customer_id, user_id, date_created, total_amount, shipping_state)
					 VALUES " . implode( ',', $values ) . '
					 ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), total_amount = VALUES(total_amount)',
					$params
				)
			);
		};

		foreach ( $rows as $row ) {
			$chunk[] = $row;
			if ( count( $chunk ) >= 50 ) {
				$flush( $chunk );
				$chunk = array();
			}
		}
		if ( $chunk ) {
			$flush( $chunk );
		}
	}

	/**
	 * درج/به‌روزرسانی سطر یک مشتری و بازگرداندن id.
	 *
	 * @param array  $row     سطر مشتری.
	 * @param int    $user_id شناسهٔ کاربر.
	 * @param string $email   ایمیل.
	 * @return int
	 */
	private static function upsert_customer_row( $row, $user_id, $email ) {
		global $wpdb;

		$table = $wpdb->prefix . 'moshtaryar_customers';

		$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			"SELECT id FROM {$table} WHERE user_id = %d AND billing_email = %s",
			$user_id,
			$email
		) );

		$data = array(
			'user_id'            => $row['user_id'],
			'billing_email'      => $row['billing_email'],
			'display_name'       => $row['display_name'],
			'last_order_date'    => $row['last_order_date'],
			'recency_days'       => $row['recency_days'],
			'order_count'        => $row['order_count'],
			'total_spent'        => $row['total_spent'],
			'avg_order_value'    => $row['avg_order_value'],
			'r_score'            => $row['r_score'],
			'f_score'            => $row['f_score'],
			'm_score'            => $row['m_score'],
			'segment'            => $row['segment'],
			'segment_updated_at' => $row['segment_updated_at'],
		);
		$format = array( '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%d', '%d', '%d', '%s', '%s' );

		if ( $existing_id > 0 ) {
			$wpdb->update( $table, $data, array( 'id' => $existing_id ), $format, array( '%d' ) );

			return $existing_id;
		}

		$wpdb->insert( $table, $data, $format );

		return (int) $wpdb->insert_id;
	}

	/* ---------------------------------------------------------------------
	 * ابزارهای کوچک
	 * ------------------------------------------------------------------- */

	/**
	 * نام نمایشی مشتری از سفارش یا کاربر.
	 *
	 * @param WC_Order $order   سفارش.
	 * @param int      $user_id شناسهٔ کاربر.
	 * @return string
	 */
	private static function order_display_name( $order, $user_id ) {
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		if ( '' !== $name ) {
			return $name;
		}
		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				return $user->display_name;
			}
		}

		return (string) $order->get_billing_email();
	}

	/**
	 * نرمال‌سازی نامک استان؛ خالی یا ناشناخته به unknown.
	 *
	 * @param string $state نامک خام.
	 * @return string
	 */
	private static function normalize_state( $state ) {
		$state = strtoupper( trim( (string) $state ) );
		if ( '' === $state ) {
			return 'unknown';
		}

		return $state;
	}

	/**
	 * حذف پیشوند wc- از وضعیت.
	 *
	 * @param string $status وضعیت.
	 * @return string
	 */
	private static function strip_prefix( $status ) {
		return ( 0 === strpos( $status, 'wc-' ) ) ? substr( $status, 3 ) : $status;
	}
}
