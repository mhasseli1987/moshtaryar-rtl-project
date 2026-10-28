<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * زمان‌بندی بازمحاسبهٔ شبانه — روزی ساعت ۰۲:۰۰ به وقت سایت.
 *
 * @package Moshtaryar
 */
class Moshtaryar_Cron {

	const EVENT = 'moshtaryar_daily_recalc';

	/**
	 * ثبت رویداد تکرارشونده با گارد تکرار نبودن.
	 */
	public static function schedule() {
		if ( wp_next_scheduled( self::EVENT ) ) {
			return;
		}

		// نزدیک‌ترین ساعت ۰۲:۰۰ آینده به وقت محلی سایت.
		$now  = current_time( 'timestamp' );
		$next = strtotime( gmdate( 'Y-m-d 02:00:00', $now ), $now );
		if ( $next <= $now ) {
			$next = strtotime( '+1 day', $next );
		}

		wp_schedule_event( $next, 'daily', self::EVENT );
	}

	/**
	 * لغو رویداد هنگام غیرفعال‌سازی.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::EVENT );
	}
}

add_action( Moshtaryar_Cron::EVENT, array( 'Moshtaryar_RFM_Engine', 'full_recalc' ) );
