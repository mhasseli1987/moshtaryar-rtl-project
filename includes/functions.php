<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * توابع کمکی سراسری مشتریار: فارسی‌سازی اعداد، مبلغ و تاریخ جلالی.
 *
 * @package Moshtaryar
 */

/**
 * تبدیل ارقام لاتین یک عدد یا رشته به ارقام فارسی.
 *
 * @param mixed $number عدد یا رشتهٔ عددی.
 * @return string
 */
function moshtaryar_fa_num( $number ) {
	return strtr(
		(string) $number,
		array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		)
	);
}

/**
 * فرمت مبلغ با جداکنندهٔ هزارگان و نماد ارز فروشگاه؛ ارقام فارسی.
 *
 * @param float $amount مبلغ.
 * @return string
 */
function moshtaryar_price( $amount ) {
	$amount = (float) $amount;
	// اعشار فقط وقتی لازم است که مقدار واقعاً کسری باشد.
	$decimals = ( abs( $amount - round( $amount ) ) < 0.005 ) ? 0 : 2;
	$formatted = number_format( $amount, $decimals, '.', ',' );

	$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
	if ( is_rtl() ) {
		return moshtaryar_fa_num( $formatted ) . ' ' . $symbol;
	}

	return $symbol . ' ' . moshtaryar_fa_num( $formatted );
}

/**
 * نمایش تاریخ میلادی/زمانی به صورت جلالی.
 *
 * پشتیبانی از کاراکترهای Y y m n d j F و اجزای زمان H i s.
 *
 * @param string|int $datetime تاریخ (رشتهٔ mysql یا timestamp).
 * @param string     $format   قالب خروجی، پیش‌فرض Y/m/d.
 * @return string
 */
function moshtaryar_fa_date( $datetime, $format = 'Y/m/d' ) {
	if ( empty( $datetime ) ) {
		return '';
	}

	$timestamp = is_numeric( $datetime ) ? (int) $datetime : strtotime( $datetime );
	if ( false === $timestamp ) {
		return '';
	}

	list( $gy, $gm, $gd, $hour, $minute, $second ) = explode( '-', gmdate( 'Y-n-j-G-i-s', $timestamp ) );
	list( $jy, $jm, $jd ) = moshtaryar_gregorian_to_jalali( (int) $gy, (int) $gm, (int) $gd );

	$month_names = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );

	$replacements = array(
		'Y' => (string) $jy,
		'y' => substr( (string) $jy, -2 ),
		'm' => sprintf( '%02d', $jm ),
		'n' => (string) $jm,
		'd' => sprintf( '%02d', $jd ),
		'j' => (string) $jd,
		'F' => $month_names[ $jm - 1 ],
		'H' => sprintf( '%02d', $hour ),
		'i' => sprintf( '%02d', $minute ),
		's' => sprintf( '%02d', $second ),
	);

	$out        = '';
	$format_len = strlen( $format );
	for ( $i = 0; $i < $format_len; $i++ ) {
		$char = $format[ $i ];
		$out .= isset( $replacements[ $char ] ) ? $replacements[ $char ] : $char;
	}

	return $out;
}

/**
 * تبدیل میلادی به جلالی (الگوریتم استاندارد، بدون کتابخانه بیرونی).
 *
 * @param int $gy سال میلادی.
 * @param int $gm ماه میلادی.
 * @param int $gd روز میلادی.
 * @return array{0:int,1:int,2:int} سال، ماه، روز جلالی.
 */
function moshtaryar_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );

	$gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days = 355666 + ( 365 * $gy ) + ( (int) ( ( $gy2 + 3 ) / 4 ) ) - ( (int) ( ( $gy2 + 99 ) / 100 ) )
		+ ( (int) ( ( $gy2 + 399 ) / 400 ) ) + $gd + $g_d_m[ $gm - 1 ];

	$jy  = -1595 + ( 33 * ( (int) ( $days / 12053 ) ) );
	$days %= 12053;

	$jy += 4 * ( (int) ( $days / 1461 ) );
	$days %= 1461;

	if ( $days > 365 ) {
		$jy  += (int) ( ( $days - 1 ) / 365 );
		$days = ( $days - 1 ) % 365;
	}

	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}

	return array( $jy, $jm, $jd );
}
