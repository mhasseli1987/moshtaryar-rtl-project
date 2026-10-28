<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * استریم خروجی CSV با BOM اکسل — اعداد لاتین، متن‌های فارسی.
 *
 * @package Moshtaryar
 */
class Moshtaryar_Export {

	/**
	 * ارسال CSV به خروجی و پایان درخواست.
	 *
	 * @param string              $filename نام فایل (بدون مسیر).
	 * @param array<string,string> $columns  عنوان => کلید یا مقدارساز.
	 * @param array               $rows     سطرها.
	 */
	public static function stream( $filename, $columns, $rows ) {
		$filename = sanitize_file_name( $filename );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$handle = fopen( 'php://output', 'w' );
		fwrite( $handle, "\xEF\xBB\xBF" ); // BOM برای نمایش درست فارسی در اکسل.

		// escape صریح برای سازگاری با PHP 8.4.
		fputcsv( $handle, array_values( $columns ), ',', '"', '\\' );

		foreach ( (array) $rows as $row ) {
			fputcsv( $handle, array_values( (array) $row ), ',', '"', '\\' );
		}

		fclose( $handle );
		exit;
	}
}
