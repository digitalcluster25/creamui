<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'hws_import_log' ) ) {
	function hws_import_log( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
			return;
		}
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_import_warn' ) ) {
	function hws_import_warn( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( $message );
			return;
		}
		hws_import_log( 'WARN: ' . $message );
	}
}

if ( ! function_exists( 'hws_import_error' ) ) {
	function hws_import_error( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( $message );
			return;
		}
		throw new RuntimeException( $message );
	}
}

$payload_path = $args[0] ?? null;
if ( ! $payload_path ) {
	hws_import_error( 'Usage: wp eval-file import_easysteam_wave.php <payload.json> [dry-run|run] [limit] [series] [download-media|no-media]' );
}

$mode          = isset( $args[1] ) ? trim( (string) $args[1] ) : 'dry-run';
$dry_run       = 'run' !== $mode;
$limit         = isset( $args[2] ) ? (int) $args[2] : 0;
$series_filter = isset( $args[3] ) ? trim( (string) $args[3] ) : '';
$media_mode    = isset( $args[4] ) ? trim( (string) $args[4] ) : 'no-media';
$download_media = 'download-media' === $media_mode;

$payload_raw = file_get_contents( $payload_path );
if ( false === $payload_raw ) {
	hws_import_error( 'Cannot read payload: ' . $payload_path );
}

$payload = json_decode( $payload_raw, true );
if ( ! is_array( $payload ) ) {
	hws_import_error( 'Invalid JSON payload: ' . $payload_path );
}

$brand_slug = (string) ( $payload['brand']['slug'] ?? 'easysteam' );
$brand_name = (string) ( $payload['brand']['name'] ?? 'EasySteam' );

function hws_normalize_text( string $value ): string {
	$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( $value );
}

function hws_translit_cyr( string $value ): string {
	$map = [
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
		'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	];
	$value = mb_strtolower( $value, 'UTF-8' );
	return strtr( $value, $map );
}

function hws_slugify_meta( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = hws_translit_cyr( $value );
	$value = sanitize_title( $value );
	return $value;
}

function hws_find_spec_value( array $spec_groups, array $labels ): ?string {
	foreach ( $spec_groups as $group ) {
		foreach ( $group['rows'] ?? [] as $row ) {
			$label = hws_normalize_text( (string) ( $row['label'] ?? '' ) );
			foreach ( $labels as $candidate ) {
				if ( mb_strtolower( $label ) === mb_strtolower( $candidate ) ) {
					return hws_normalize_text( (string) ( $row['value'] ?? '' ) );
				}
			}
		}
	}
	return null;
}

function hws_collect_group_rows( array $spec_groups ): array {
	$rows = [];
	foreach ( $spec_groups as $group ) {
		$title = hws_normalize_text( (string) ( $group['title'] ?? '' ) );
		$section = hws_normalize_text( (string) ( $group['section'] ?? '' ) );
		foreach ( $group['rows'] ?? [] as $row ) {
			$rows[] = [
				'section' => $section,
				'group'   => $title,
				'name'    => hws_normalize_text( (string) ( $row['label'] ?? '' ) ),
				'value'   => hws_normalize_text( (string) ( $row['value'] ?? '' ) ),
			];
		}
	}
	return $rows;
}

function hws_build_specs_html( array $spec_groups ): string {
	$html = '<table class="shop_attributes hws-specs"><tbody>';
	foreach ( $spec_groups as $group ) {
		foreach ( $group['rows'] ?? [] as $row ) {
			$label = esc_html( hws_normalize_text( (string) ( $row['label'] ?? '' ) ) );
			$value = esc_html( hws_normalize_text( (string) ( $row['value'] ?? '' ) ) );
			if ( '' === $label || '' === $value ) {
				continue;
			}
			$html .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
		}
	}
	$html .= '</tbody></table>';
	return $html;
}

function hws_calculate_rate(): float {
	global $wpdb;
	$rate = (float) $wpdb->get_var(
		"SELECT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = '_hws_usd_rub_rate' AND meta_value > 0
		 ORDER BY post_id DESC LIMIT 1"
	);
	return $rate > 0 ? $rate : 71.209;
}

function hws_find_existing_product_id( string $sku, string $slug ): int {
	global $wpdb;
	$product_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
			$sku
		)
	);
	if ( $product_id > 0 ) {
		return $product_id;
	}
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	return $post ? (int) $post->ID : 0;
}

function hws_ensure_term_by_slug( string $taxonomy, string $slug, string $name ): int {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}
	$term = term_exists( $name, $taxonomy );
	if ( is_array( $term ) && ! empty( $term['term_id'] ) ) {
		return (int) $term['term_id'];
	}
	$created = wp_insert_term(
		$name,
		$taxonomy,
		[
			'slug' => $slug,
		]
	);
	if ( is_wp_error( $created ) ) {
		hws_import_warn( 'Cannot create term ' . $taxonomy . ':' . $slug . ' (' . $created->get_error_message() . ')' );
		return 0;
	}
	return (int) $created['term_id'];
}

function hws_category_chain_ids( string $path ): array {
	$slugs = array_values( array_filter( explode( '/', $path ) ) );
	$root_slug = $slugs[0] ?? '';
	if ( '' === $root_slug ) {
		return [];
	}
	$term = get_term_by( 'slug', $root_slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		hws_import_warn( 'Missing top-level product_cat slug: ' . $root_slug );
		return [];
	}
	return [ (int) $term->term_id ];
}

function hws_attribute_values_for_product( array $series_payload, array $product, array $spec_groups ): array {
	$attrs = [];
	$defaults = $series_payload['defaultAttributes'] ?? [];

	foreach ( $defaults as $taxonomy => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$attrs[ $taxonomy ] = [ (string) $value ];
	}

	$volume_min = hws_find_spec_value( $spec_groups, [ 'Минимальный объем парной' ] );
	$volume_max = hws_find_spec_value( $spec_groups, [ 'Максимальный объем парной' ] );
	if ( $volume_min && $volume_max ) {
		$attrs['pa_steam-room-volume'] = [ $volume_min . ' - ' . $volume_max ];
	} elseif ( $volume_max ) {
		$attrs['pa_steam-room-volume'] = [ 'до ' . $volume_max ];
	}

	$power = hws_find_spec_value( $spec_groups, [ 'Номинальная потребляемая мощность', 'Мощность' ] );
	if ( $power ) {
		$attrs['pa_power'] = [ $power ];
	}

	$materials = [];
	foreach ( $product['option_groups'] ?? [] as $group ) {
		$name = mb_strtolower( hws_normalize_text( (string) ( $group['name'] ?? '' ) ) );
		foreach ( $group['values'] ?? [] as $value ) {
			$label = hws_normalize_text( (string) ( $value['name'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}
			if ( false !== mb_strpos( $name, 'кожух' ) || false !== mb_strpos( $name, 'облицов' ) ) {
				$materials[] = $label;
			}
			if ( false !== mb_strpos( $name, 'топлив' ) ) {
				if ( false !== mb_stripos( $label, 'дров' ) ) {
					$attrs['pa_fuel-type'][] = 'дрова';
				}
				if ( false !== mb_stripos( $label, 'газ' ) ) {
					$attrs['pa_fuel-type'][] = false !== mb_stripos( $label, 'дров' ) ? 'газ, дрова' : 'газ';
				}
				if ( false !== mb_stripos( $label, 'электр' ) ) {
					$attrs['pa_fuel-type'][] = 'электричество';
				}
			}
		}
	}

	if ( ! empty( $materials ) ) {
		$attrs['pa_cladding-material'] = array_values( array_unique( $materials ) );
	}

	if ( ! empty( $series_payload['seriesName'] ) ) {
		$attrs['pa_series'] = [ $series_payload['seriesName'] ];
	}

	if ( 'VIVARTE' === ( $series_payload['seriesName'] ?? '' ) ) {
		$attrs['pa_fuel-type'] = [ 'электричество' ];
	}

	foreach ( $attrs as $taxonomy => $values ) {
		$attrs[ $taxonomy ] = array_values( array_unique( array_filter( array_map( 'hws_normalize_text', $values ) ) ) );
	}

	return $attrs;
}

function hws_assign_attribute_terms( int $product_id, array $attrs, bool $dry_run ): array {
	$product_attributes = [];
	$position = 0;

	foreach ( $attrs as $taxonomy => $values ) {
		if ( empty( $values ) ) {
			continue;
		}
		$term_ids = [];
		foreach ( $values as $value ) {
			$slug = hws_slugify_meta( $value );
			$term_id = hws_ensure_term_by_slug( $taxonomy, $slug, $value );
			if ( $term_id > 0 ) {
				$term_ids[] = $term_id;
			}
		}
		if ( empty( $term_ids ) ) {
			continue;
		}
		if ( ! $dry_run ) {
			wp_set_object_terms( $product_id, $term_ids, $taxonomy, false );
		}
		$product_attributes[ $taxonomy ] = [
			'name'         => $taxonomy,
			'value'        => '',
			'position'     => $position++,
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => 1,
		];
	}

	return $product_attributes;
}

function hws_sideload_image( string $url, int $post_id, bool $set_thumbnail = false ): int {
	if ( ! $url ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 20 );
	if ( is_wp_error( $tmp ) ) {
		hws_import_warn( 'Cannot download image ' . $url . ': ' . $tmp->get_error_message() );
		return 0;
	}

	$filename = wp_basename( parse_url( $url, PHP_URL_PATH ) ?: 'image.jpg' );
	$file_array = [
		'name'     => $filename,
		'tmp_name' => $tmp,
	];

	$attachment_id = media_handle_sideload( $file_array, $post_id );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		hws_import_warn( 'Cannot sideload image ' . $url . ': ' . $attachment_id->get_error_message() );
		return 0;
	}

	if ( $set_thumbnail ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}

	return (int) $attachment_id;
}

$rate = hws_calculate_rate();
hws_import_log( 'Brand: ' . $brand_name . ' (' . $brand_slug . ') | USD/RUB rate: ' . $rate );

$processed = 0;
$created = 0;
$updated = 0;
$seen_skus = [];

foreach ( $payload['series'] ?? [] as $series_payload ) {
	$series_name = (string) ( $series_payload['seriesName'] ?? '' );
	if ( $series_filter && $series_name !== $series_filter ) {
		continue;
	}

	hws_import_log( 'Series: ' . $series_name );

	foreach ( $series_payload['products'] ?? [] as $product ) {
		if ( ! empty( $product['error'] ) ) {
			hws_import_warn( 'Skip errored payload product: ' . $product['source_url'] . ' (' . $product['error'] . ')' );
			continue;
		}

		$sku = hws_normalize_text( (string) ( $product['article'] ?? '' ) );
		if ( '' === $sku ) {
			hws_import_warn( 'Skip product without article: ' . ( $product['source_url'] ?? 'unknown' ) );
			continue;
		}
		if ( isset( $seen_skus[ $sku ] ) ) {
			hws_import_warn( 'Skip duplicate SKU in payload: ' . $sku );
			continue;
		}
		$seen_skus[ $sku ] = true;

		$processed++;
		if ( $limit > 0 && $processed > $limit ) {
			break 2;
		}

		$title = hws_normalize_text( (string) ( $product['title'] ?? '' ) );
		$slug = $brand_slug . '-' . sanitize_title( $sku );
		$product_id = hws_find_existing_product_id( $sku, $slug );
		$is_update = $product_id > 0;
		$post_status = 'publish';
		$post_type_label = ! empty( $product['option_groups'] ) ? 'variable' : 'simple';
		$base_price_usd = round( ( (int) ( $product['base_price_rub'] ?? 0 ) ) / $rate );
		$spec_groups = $product['specs_groups'] ?? [];
		$version = hws_find_spec_value( $spec_groups, [ 'Версия' ] );
		$series_intro = (string) ( $series_payload['seriesIntro'] ?? '' );
		$description = (string) ( $product['description'] ?? '' );
		$short_description = (string) ( $product['short_description'] ?? '' );
		if ( '' === trim( $description ) && '' !== trim( $series_intro ) ) {
			$description = $series_intro;
		}
		if ( '' === trim( $short_description ) ) {
			$short_description = $version ? $version : $series_intro;
		}
		$category_ids = hws_category_chain_ids( (string) ( $series_payload['target']['primaryCategoryPath'] ?? '' ) );
		$brand_term_id = hws_ensure_term_by_slug( 'product_brand', $brand_slug, $brand_name );
		$attrs = hws_attribute_values_for_product( $series_payload, $product, $spec_groups );
		$specs_html = hws_build_specs_html( $spec_groups );
		$raw_rows = hws_collect_group_rows( $spec_groups );

		$payload_json = wp_json_encode(
			[
				'source_sku'        => $sku,
				'name'              => $title,
				'display_name'      => $title,
				'series'            => $series_name,
				'model'             => hws_find_spec_value( $spec_groups, [ 'Модель' ] ),
				'slug'              => $slug,
				'short_description' => $short_description,
				'description'       => $description,
				'option_groups'     => $product['option_groups'] ?? [],
				'documents'         => $product['documents'] ?? [],
				'raw_data'          => [
					'detail' => [
						'specs' => $raw_rows,
					],
				],
				'source_url'        => $product['source_url'] ?? '',
			],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		$summary = sprintf(
			'%s %s | sku=%s | price=%s USD | cat=%s',
			$is_update ? 'UPDATE' : 'CREATE',
			$title,
			$sku,
			$base_price_usd,
			$series_payload['target']['primaryCategoryPath'] ?? ''
		);

		if ( $dry_run ) {
			hws_import_log( '[DRY] ' . $summary );
			continue;
		}

		$postarr = [
			'post_type'    => 'product',
			'post_status'  => $post_status,
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => $short_description,
			'post_content' => $description,
		];
		if ( $is_update ) {
			$postarr['ID'] = $product_id;
			$product_id = wp_update_post( $postarr, true );
			if ( is_wp_error( $product_id ) ) {
				hws_import_warn( 'Update failed for ' . $sku . ': ' . $product_id->get_error_message() );
				continue;
			}
			$updated++;
		} else {
			$product_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $product_id ) ) {
				hws_import_warn( 'Create failed for ' . $sku . ': ' . $product_id->get_error_message() );
				continue;
			}
			$created++;
		}

		wp_set_object_terms( $product_id, [ $brand_term_id ], 'product_brand', false );
		wp_set_object_terms( $product_id, $category_ids, 'product_cat', false );
		wp_set_object_terms( $product_id, $post_type_label, 'product_type', false );

		$product_attributes = hws_assign_attribute_terms( $product_id, $attrs, false );

		update_post_meta( $product_id, '_sku', $sku );
		update_post_meta( $product_id, '_price', $base_price_usd );
		update_post_meta( $product_id, '_regular_price', $base_price_usd );
		update_post_meta( $product_id, '_stock_status', 'instock' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_hws_source_payload', $payload_json );
		update_post_meta( $product_id, '_hws_specs_html', $specs_html );
		update_post_meta( $product_id, '_hws_specs_groups', $spec_groups );
		update_post_meta( $product_id, '_hws_usd_rub_rate', $rate );
		update_post_meta( $product_id, '_hws_price_currency', 'USD' );
		update_post_meta( $product_id, '_hws_possible_variation_count', array_reduce( $product['option_groups'] ?? [], static function ( int $carry, array $group ): int {
			$count = count( $group['values'] ?? [] );
			return $carry * max( 1, $count );
		}, 1 ) );
		update_post_meta( $product_id, '_hws_imported_variation_count', 0 );
		update_post_meta( $product_id, '_product_attributes', $product_attributes );

		if ( $download_media ) {
			$image_ids = [];
			if ( ! empty( $product['main_image'] ) ) {
				$thumb_id = hws_sideload_image( (string) $product['main_image'], $product_id, true );
				if ( $thumb_id > 0 ) {
					$image_ids[] = $thumb_id;
				}
			}
			foreach ( $product['option_groups'] ?? [] as $group ) {
				foreach ( $group['values'] ?? [] as $value ) {
					$url = (string) ( $value['image'] ?? '' );
					if ( '' === $url ) {
						continue;
					}
					$image_ids[] = hws_sideload_image( $url, $product_id, false );
				}
			}
			$image_ids = array_values( array_unique( array_filter( array_map( 'intval', $image_ids ) ) ) );
			if ( count( $image_ids ) > 1 ) {
				update_post_meta( $product_id, '_product_image_gallery', implode( ',', array_slice( $image_ids, 1 ) ) );
			}
		}

		hws_import_log( $summary . ' | post_id=' . $product_id );
	}
}

hws_import_log(
	sprintf(
		'Done. processed=%d created=%d updated=%d dry_run=%s',
		$processed,
		$created,
		$updated,
		$dry_run ? 'yes' : 'no'
	)
);
