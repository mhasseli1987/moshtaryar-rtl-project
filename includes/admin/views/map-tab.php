<?php
/**
 * صفحه نقشه حرارتی استان‌ها — مشتریار
 *
 * @package Moshtaryar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array<string, array{name_fa:string, orders:int, customers:int, revenue:float}> $data */
$metric = Moshtaryar_Map_Report::current_metric();

// مرتب‌سازی استان‌ها بر اساس حالت انتخابی.
$rows = $data;
uasort(
	$rows,
	static function ( $a, $b ) use ( $metric ) {
		return $b[ $metric ] <=> $a[ $metric ];
	}
);
?>
<div class="wrap moshtaryar-wrap">

	<h1 class="moshtaryar-title"><?php esc_html_e( 'نقشه حرارتی فروش', 'moshtaryar' ); ?></h1>
	<p class="moshtaryar-subtitle"><?php esc_html_e( 'پراکندگی سفارش‌ها و درآمد فروشگاه به تفکیک استان', 'moshtaryar' ); ?></p>

	<div class="moshtaryar-map-wrap">
		<div class="moshtaryar-map-toolbar">
			<button type="button" class="<?php echo 'orders' === $metric ? 'is-active' : ''; ?>" data-moshtaryar-metric="orders">
				<?php esc_html_e( 'تعداد سفارش', 'moshtaryar' ); ?>
			</button>
			<button type="button" class="<?php echo 'revenue' === $metric ? 'is-active' : ''; ?>" data-moshtaryar-metric="revenue">
				<?php esc_html_e( 'درآمد', 'moshtaryar' ); ?>
			</button>
		</div>

		<?php if ( empty( $data ) ) : ?>
			<p><?php esc_html_e( 'هنوز سفارشی ثبت نشده است. پس از تکمیل اولین سفارش‌ها، نقشه پر می‌شود.', 'moshtaryar' ); ?></p>
		<?php endif; ?>

		<svg id="moshtaryar-iran-svg" viewBox="0 0 1000 880" role="img" aria-label="<?php esc_attr_e( 'نقشه استان‌های ایران', 'moshtaryar' ); ?>"></svg>
		<div id="moshtaryar-map-tip" aria-hidden="true"></div>
		<div id="moshtaryar-map-legend"></div>
	</div>

	<table class="moshtaryar-map-table widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'استان', 'moshtaryar' ); ?></th>
				<th><?php esc_html_e( 'تعداد سفارش', 'moshtaryar' ); ?></th>
				<th><?php esc_html_e( 'تعداد مشتری', 'moshtaryar' ); ?></th>
				<th><?php esc_html_e( 'درآمد', 'moshtaryar' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['name_fa'] ); ?></td>
					<td><?php echo esc_html( moshtaryar_fa_num( $row['orders'] ) ); ?></td>
					<td><?php echo esc_html( moshtaryar_fa_num( $row['customers'] ) ); ?></td>
					<td><?php echo esc_html( moshtaryar_fa_num( round( $row['revenue'] ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

</div>
