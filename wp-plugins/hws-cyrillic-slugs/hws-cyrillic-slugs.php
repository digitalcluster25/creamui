<?php
/**
 * Plugin Name: HWS Cyrillic Slugs
 * Description: Автоматически транслитерирует кириллические slug в латиницу для записей, страниц, товаров и терминов. Включает массовую миграцию старых slug.
 * Version: 0.2.0
 * Author: HWS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hws_cyrillic_slugs_map(): array {
	return [
		'А' => 'A',   'а' => 'a',
		'Б' => 'B',   'б' => 'b',
		'В' => 'V',   'в' => 'v',
		'Г' => 'G',   'г' => 'g',
		'Д' => 'D',   'д' => 'd',
		'Е' => 'E',   'е' => 'e',
		'Ё' => 'Yo',  'ё' => 'yo',
		'Ж' => 'Zh',  'ж' => 'zh',
		'З' => 'Z',   'з' => 'z',
		'И' => 'I',   'и' => 'i',
		'Й' => 'Y',   'й' => 'y',
		'К' => 'K',   'к' => 'k',
		'Л' => 'L',   'л' => 'l',
		'М' => 'M',   'м' => 'm',
		'Н' => 'N',   'н' => 'n',
		'О' => 'O',   'о' => 'o',
		'П' => 'P',   'п' => 'p',
		'Р' => 'R',   'р' => 'r',
		'С' => 'S',   'с' => 's',
		'Т' => 'T',   'т' => 't',
		'У' => 'U',   'у' => 'u',
		'Ф' => 'F',   'ф' => 'f',
		'Х' => 'Kh',  'х' => 'kh',
		'Ц' => 'Ts',  'ц' => 'ts',
		'Ч' => 'Ch',  'ч' => 'ch',
		'Ш' => 'Sh',  'ш' => 'sh',
		'Щ' => 'Sch', 'щ' => 'sch',
		'Ъ' => '',    'ъ' => '',
		'Ы' => 'Y',   'ы' => 'y',
		'Ь' => '',    'ь' => '',
		'Э' => 'E',   'э' => 'e',
		'Ю' => 'Yu',  'ю' => 'yu',
		'Я' => 'Ya',  'я' => 'ya',
	];
}

function hws_cyrillic_slugs_transliterate( string $value ): string {
	return strtr( $value, hws_cyrillic_slugs_map() );
}

/**
 * Для новых сохранений подменяем кириллицу на латиницу до стандартной sanitize_title().
 */
add_filter(
	'sanitize_title',
	function ( $title, $raw_title = '', $context = 'save' ) {
		if ( 'save' !== $context || ! is_string( $title ) || '' === $title ) {
			return $title;
		}

		return hws_cyrillic_slugs_transliterate( $title );
	},
	9,
	3
);

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'Транслитерация URL',
			'Транслитерация URL',
			'manage_options',
			'hws-cyrillic-slugs',
			'hws_cyrillic_slugs_render_admin_page'
		);
	}
);

add_action( 'admin_post_hws_cyrillic_slugs_migrate', 'hws_cyrillic_slugs_handle_migration' );

function hws_cyrillic_slugs_render_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	$report = get_transient( 'hws_cyrillic_slugs_last_report' );
	?>
	<div class="wrap">
		<h1>Транслитерация URL в латиницу</h1>
		<p>Этот инструмент массово обновляет существующие slug у записей, страниц, товаров и таксономий. Новые материалы после активации плагина уже будут получать латинские URL автоматически.</p>
		<p>Перед запуском убедитесь, что у вас есть свежий бэкап. Если установлен Yoast SEO Premium, он поможет обработать редиректы при изменении URL.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="hws_cyrillic_slugs_migrate">
			<?php wp_nonce_field( 'hws_cyrillic_slugs_migrate' ); ?>
			<?php submit_button( 'Запустить миграцию slug' ); ?>
		</form>

		<?php if ( ! empty( $_GET['updated'] ) && is_array( $report ) ) : ?>
			<h2>Отчёт</h2>
			<p>
				Обновлено записей: <strong><?php echo esc_html( (string) ( $report['posts_updated'] ?? 0 ) ); ?></strong>,
				таксономий: <strong><?php echo esc_html( (string) ( $report['terms_updated'] ?? 0 ) ); ?></strong>,
				ошибок: <strong><?php echo esc_html( (string) ( $report['errors_count'] ?? 0 ) ); ?></strong>.
			</p>

			<?php if ( ! empty( $report['post_changes'] ) ) : ?>
				<h3>Изменённые записи</h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Тип</th>
							<th>Старый slug</th>
							<th>Новый slug</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $report['post_changes'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row['id'] ); ?></td>
								<td><?php echo esc_html( $row['type'] ); ?></td>
								<td><code><?php echo esc_html( $row['old'] ); ?></code></td>
								<td><code><?php echo esc_html( $row['new'] ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $report['term_changes'] ) ) : ?>
				<h3>Изменённые таксономии</h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Таксономия</th>
							<th>Старый slug</th>
							<th>Новый slug</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $report['term_changes'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row['id'] ); ?></td>
								<td><?php echo esc_html( $row['taxonomy'] ); ?></td>
								<td><code><?php echo esc_html( $row['old'] ); ?></code></td>
								<td><code><?php echo esc_html( $row['new'] ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $report['errors'] ) ) : ?>
				<h3>Ошибки</h3>
				<ul>
					<?php foreach ( $report['errors'] as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

function hws_cyrillic_slugs_handle_migration(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'hws_cyrillic_slugs_migrate' );

	$report = [
		'posts_updated' => 0,
		'terms_updated' => 0,
		'errors_count'  => 0,
		'post_changes'  => [],
		'term_changes'  => [],
		'errors'        => [],
	];

	$post_types = get_post_types(
		[
			'public'  => true,
			'show_ui' => true,
		],
		'objects'
	);

	foreach ( $post_types as $post_type ) {
		$post_ids = get_posts(
			[
				'post_type'      => $post_type->name,
				'post_status'    => [ 'publish', 'future', 'draft', 'pending', 'private' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || ! is_string( $post->post_name ) || '' === $post->post_name ) {
				continue;
			}

			$old_slug = $post->post_name;
			$new_slug = sanitize_title( $old_slug );
			if ( '' === $new_slug || $new_slug === $old_slug ) {
				continue;
			}

			$new_slug = wp_unique_post_slug(
				$new_slug,
				(int) $post->ID,
				$post->post_status,
				$post->post_type,
				(int) $post->post_parent
			);

			$result = wp_update_post(
				[
					'ID'        => (int) $post->ID,
					'post_name' => $new_slug,
				],
				true
			);

			if ( is_wp_error( $result ) ) {
				$report['errors_count']++;
				$report['errors'][] = sprintf(
					'Пост %d (%s): %s',
					(int) $post->ID,
					$post->post_type,
					$result->get_error_message()
				);
				continue;
			}

			$report['posts_updated']++;
			if ( count( $report['post_changes'] ) < 100 ) {
				$report['post_changes'][] = [
					'id'   => (int) $post->ID,
					'type' => $post->post_type,
					'old'  => $old_slug,
					'new'  => $new_slug,
				];
			}
		}
	}

	$taxonomies = get_taxonomies(
		[
			'public'  => true,
			'show_ui' => true,
		],
		'objects'
	);

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) ) {
			$report['errors_count']++;
			$report['errors'][] = sprintf(
				'Таксономия %s: %s',
				$taxonomy->name,
				$terms->get_error_message()
			);
			continue;
		}

		foreach ( $terms as $term ) {
			if ( ! isset( $term->slug ) || '' === $term->slug ) {
				continue;
			}

			$old_slug = $term->slug;
			$new_slug = sanitize_title( $old_slug );
			if ( '' === $new_slug || $new_slug === $old_slug ) {
				continue;
			}

			$result = wp_update_term(
				(int) $term->term_id,
				$taxonomy->name,
				[
					'slug' => $new_slug,
				]
			);

			if ( is_wp_error( $result ) ) {
				$report['errors_count']++;
				$report['errors'][] = sprintf(
					'Терм %d (%s): %s',
					(int) $term->term_id,
					$taxonomy->name,
					$result->get_error_message()
				);
				continue;
			}

			$report['terms_updated']++;
			if ( count( $report['term_changes'] ) < 100 ) {
				$report['term_changes'][] = [
					'id'       => (int) $term->term_id,
					'taxonomy' => $taxonomy->name,
					'old'      => $old_slug,
					'new'      => $new_slug,
				];
			}
		}
	}

	set_transient( 'hws_cyrillic_slugs_last_report', $report, HOUR_IN_SECONDS );

	wp_safe_redirect(
		add_query_arg(
			[
				'page'    => 'hws-cyrillic-slugs',
				'updated' => '1',
			],
			admin_url( 'tools.php' )
		)
	);
	exit;
}
