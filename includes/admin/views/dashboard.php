<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * نمای داشبورد مشتریار.
 *
 * @package Moshtaryar
 *
 * @var array      $kpi_cards       کارت‌های شاخص (key, label, value, icon).
 * @var array      $dist_view       سگمنت‌های آمادهٔ نمایش.
 * @var int        $total_customers تعداد کل مشتریان.
 * @var int        $last_calc       زمان آخرین بازمحاسبه (۰ یعنی هرگز).
 * @var array|null $notice          پیام وضعیت پس از کنش.
 */
?>
<div class="wrap moshtaryar-wrap">
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible moshtaryar-notice">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="moshtaryar-page-head">
		<div class="moshtaryar-page-head__text">
			<h1 class="moshtaryar-title"><?php esc_html_e( 'داشبورد مشتریار', 'moshtaryar' ); ?></h1>
			<p class="moshtaryar-subtitle">
				<?php if ( $last_calc > 0 ) : ?>
					<?php
					printf(
						/* translators: %s: تاریخ و ساعت آخرین بازمحاسبه. */
						esc_html__( 'آخرین بازمحاسبه: %s', 'moshtaryar' ),
						esc_html( moshtaryar_fa_date( (int) $last_calc, 'Y/m/d H:i' ) )
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'هنوز بازمحاسبه‌ای انجام نشده است.', 'moshtaryar' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="moshtaryar-recalc-form">
			<input type="hidden" name="action" value="moshtaryar_recalc" />
			<?php wp_nonce_field( 'moshtaryar_recalc', '_mynonce' ); ?>
			<button type="submit" class="button button-primary moshtaryar-btn-recalc"><?php esc_html_e( 'بازمحاسبهٔ کامل', 'moshtaryar' ); ?></button>
		</form>
	</div>

	<div class="moshtaryar-kpis">
		<?php foreach ( $kpi_cards as $card ) : ?>
			<div class="moshtaryar-kpi moshtaryar-kpi--<?php echo esc_attr( $card['key'] ); ?>">
				<span class="moshtaryar-kpi__icon" aria-hidden="true">
					<?php if ( 'users' === $card['key'] ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M3.5 20c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><path d="M16 5.5a3.5 3.5 0 0 1 0 6"/><path d="M17.5 15.5c2 .7 3 2.4 3 4.5"/></svg>
					<?php elseif ( 'pulse' === $card['key'] ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-7 4 14 3-7h4"/></svg>
					<?php elseif ( 'banknote' === $card['key'] ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="10" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.6 12h.01"/><path d="M17.4 12h.01"/></svg>
					<?php else : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2.5 L21 6.5 L17 10.5"/><path d="M3 11 v-1 a4 4 0 0 1 4 -4 h14"/><path d="M7 21.5 L3 17.5 L7 13.5"/><path d="M21 13 v1 a4 4 0 0 1 -4 4 H3"/></svg>
					<?php endif; ?>
				</span>
				<span class="moshtaryar-kpi__value"><?php echo esc_html( $card['value'] ); ?></span>
				<span class="moshtaryar-kpi__label"><?php echo esc_html( $card['label'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( 0 === $total_customers ) : ?>
		<div class="moshtaryar-empty">
			<span class="moshtaryar-empty__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
			</span>
			<h2 class="moshtaryar-empty__title"><?php esc_html_e( 'هنوز داده‌ای برای تحلیل وجود ندارد', 'moshtaryar' ); ?></h2>
			<p class="moshtaryar-empty__text"><?php esc_html_e( 'با زدن «بازمحاسبهٔ کامل»، سفارش‌های فروشگاه بررسی و سگمنت مشتریان ساخته می‌شود.', 'moshtaryar' ); ?></p>
		</div>
	<?php else : ?>
		<div class="moshtaryar-charts">
			<section class="moshtaryar-card moshtaryar-card--donut">
				<h2 class="moshtaryar-card__title"><?php esc_html_e( 'توزیع سگمنت‌ها', 'moshtaryar' ); ?></h2>
				<div class="moshtaryar-donut-layout">
					<div class="moshtaryar-donut-box">
						<div id="moshtaryar-donut" class="moshtaryar-donut" role="img" aria-label="<?php esc_attr_e( 'نمودار دونات توزیع سگمنت‌های مشتریان', 'moshtaryar' ); ?>"></div>
						<div class="moshtaryar-donut-center" aria-hidden="true">
							<span class="moshtaryar-donut-total"><?php echo esc_html( moshtaryar_fa_num( $total_customers ) ); ?></span>
							<span class="moshtaryar-donut-total-label"><?php esc_html_e( 'مشتری', 'moshtaryar' ); ?></span>
						</div>
					</div>
					<ul class="moshtaryar-legend" id="moshtaryar-legend">
						<?php foreach ( $dist_view as $item ) : ?>
							<li class="moshtaryar-legend__item">
								<span class="moshtaryar-badge__dot" style="background:<?php echo esc_attr( $item['color'] ); ?>;"></span>
								<span class="moshtaryar-legend__label"><?php echo esc_html( $item['label'] ); ?></span>
								<span class="moshtaryar-legend__count"><?php echo esc_html( moshtaryar_fa_num( $item['count'] ) ); ?></span>
								<span class="moshtaryar-legend__pct"><?php echo esc_html( $item['percent'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>

			<section class="moshtaryar-card moshtaryar-card--bars">
				<h2 class="moshtaryar-card__title"><?php esc_html_e( 'فروش ۱۲ ماه اخیر', 'moshtaryar' ); ?></h2>
				<div id="moshtaryar-bars" class="moshtaryar-bars-box" role="img" aria-label="<?php esc_attr_e( 'نمودار میله‌ای فروش ۱۲ ماه اخیر', 'moshtaryar' ); ?>"></div>
			</section>
		</div>
	<?php endif; ?>
</div>
