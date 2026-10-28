<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * نمای جدول مشتریان مشتریار.
 *
 * @package Moshtaryar
 *
 * @var array  $rows_view  سطرهای آمادهٔ نمایش.
 * @var array  $columns    سرستون‌ها (label, sortable, url, dir).
 * @var array  $labels     برچسب سگمنت‌ها برای فیلتر کشویی.
 * @var int    $total      تعداد کل نتایج.
 * @var int    $pages      تعداد کل صفحات.
 * @var array  $pagination اطلاعات صفحه‌بندی (info, prev, next).
 * @var array  $filters    فیلترهای فعلی (search, segment, orderby, order).
 */
?>
<div class="wrap moshtaryar-wrap">
	<h1 class="moshtaryar-title"><?php esc_html_e( 'مشتریان فروشگاه', 'moshtaryar' ); ?></h1>

	<div class="moshtaryar-toolbar">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="moshtaryar-filters">
			<input type="hidden" name="page" value="moshtaryar-customers" />
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $filters['orderby'] ); ?>" />
			<input type="hidden" name="order" value="<?php echo esc_attr( $filters['order'] ); ?>" />
			<input type="search" class="moshtaryar-search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'جست‌وجو بر اساس نام یا ایمیل…', 'moshtaryar' ); ?>" />
			<select name="segment" class="moshtaryar-select">
				<option value=""><?php esc_html_e( 'همه سگمنت‌ها', 'moshtaryar' ); ?></option>
				<?php foreach ( $labels as $slug => $meta ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $filters['segment'], $slug ); ?>><?php echo esc_html( $meta['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'اعمال', 'moshtaryar' ); ?></button>
			<?php if ( '' !== $filters['search'] || '' !== $filters['segment'] ) : ?>
				<a class="moshtaryar-clear" href="<?php echo esc_url( admin_url( 'admin.php?page=moshtaryar-customers' ) ); ?>"><?php esc_html_e( 'پاک‌کردن فیلترها', 'moshtaryar' ); ?></a>
			<?php endif; ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="moshtaryar-csv-form">
			<input type="hidden" name="action" value="moshtaryar_export_csv" />
			<input type="hidden" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" />
			<input type="hidden" name="segment" value="<?php echo esc_attr( $filters['segment'] ); ?>" />
			<?php wp_nonce_field( 'moshtaryar_export_csv', '_mynonce' ); ?>
			<button type="submit" class="button"><?php esc_html_e( 'خروجی CSV', 'moshtaryar' ); ?></button>
		</form>
	</div>

	<p class="moshtaryar-result-count">
		<?php
		printf(
			/* translators: %s: تعداد مشتریان. */
			esc_html__( '%s مشتری', 'moshtaryar' ),
			esc_html( moshtaryar_fa_num( $total ) )
		);
		?>
	</p>

	<div class="moshtaryar-table-scroll">
		<table class="wp-list-table widefat fixed striped moshtaryar-table">
			<thead>
				<tr>
					<?php foreach ( $columns as $key => $column ) : ?>
						<th scope="col" class="moshtaryar-col--<?php echo esc_attr( $key ); ?>">
							<?php if ( ! empty( $column['url'] ) ) : ?>
								<a href="<?php echo esc_url( $column['url'] ); ?>">
									<?php echo esc_html( $column['label'] ); ?>
									<?php if ( $column['dir'] ) : ?>
										<span class="moshtaryar-sort" aria-hidden="true"><?php echo 'asc' === $column['dir'] ? '↑' : '↓'; ?></span>
									<?php endif; ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $column['label'] ); ?>
							<?php endif; ?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows_view ) ) : ?>
					<tr>
						<td colspan="<?php echo esc_attr( count( $columns ) ); ?>">
							<?php
							echo '' === $filters['search'] && '' === $filters['segment']
								? esc_html__( 'هنوز مشتری‌ای ثبت نشده است؛ ابتدا «بازمحاسبهٔ کامل» را از داشبورد اجرا کنید.', 'moshtaryar' )
								: esc_html__( 'مشتری‌ای مطابق این فیلترها پیدا نشد.', 'moshtaryar' );
							?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows_view as $item ) : ?>
						<?php $row = $item['row']; ?>
						<tr>
							<td class="moshtaryar-cell-name">
								<?php if ( (int) $row->user_id > 0 ) : ?>
									<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $row->user_id ) ); ?>">
										<?php echo esc_html( $item['display_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $item['display_name'] ); ?>
									<span class="moshtaryar-guest"><?php esc_html_e( '(مهمان)', 'moshtaryar' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $row->billing_email ); ?></td>
							<td>
								<span class="moshtaryar-badge">
									<span class="moshtaryar-badge__dot" style="background:<?php echo esc_attr( $item['badge_color'] ); ?>;"></span>
									<?php echo esc_html( $item['badge_label'] ); ?>
								</span>
							</td>
							<td>
								<?php
								echo ! empty( $row->last_order_date )
									? esc_html( moshtaryar_fa_date( $row->last_order_date, 'Y/m/d' ) )
									: '—';
								?>
							</td>
							<td><?php echo esc_html( moshtaryar_fa_num( (int) $row->order_count ) ); ?></td>
							<td><?php echo esc_html( moshtaryar_price( (float) $row->total_spent ) ); ?></td>
							<td><?php echo esc_html( moshtaryar_price( (float) $row->avg_order_value ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $pages > 1 ) : ?>
		<nav class="moshtaryar-pagination" aria-label="<?php esc_attr_e( 'صفحه‌بندی فهرست مشتریان', 'moshtaryar' ); ?>">
			<span class="moshtaryar-pagination__info"><?php echo esc_html( $pagination['info'] ); ?></span>
			<?php if ( $pagination['prev'] ) : ?>
				<a class="button" href="<?php echo esc_url( $pagination['prev'] ); ?>"><?php esc_html_e( 'قبلی', 'moshtaryar' ); ?></a>
			<?php endif; ?>
			<?php if ( $pagination['next'] ) : ?>
				<a class="button" href="<?php echo esc_url( $pagination['next'] ); ?>"><?php esc_html_e( 'بعدی', 'moshtaryar' ); ?></a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</div>
