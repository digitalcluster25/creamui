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

$cli_args = isset( $args ) && is_array( $args ) ? $args : [];

$payload_path = $cli_args[0] ?? getenv( 'HWS_PAYLOAD_PATH' ) ?: null;
if ( ! $payload_path ) {
	hws_import_error( 'Usage: wp eval-file import_vvd_wave.php <payload.json> [dry-run|run] [limit] [series] [download-media|no-media] or set HWS_PAYLOAD_PATH env' );
}

$mode            = isset( $cli_args[1] ) ? trim( (string) $cli_args[1] ) : ( getenv( 'HWS_IMPORT_MODE' ) ?: 'dry-run' );
$dry_run         = 'run' !== $mode;
$limit           = isset( $cli_args[2] ) ? (int) $cli_args[2] : (int) ( getenv( 'HWS_IMPORT_LIMIT' ) ?: 0 );
$series_filter   = isset( $cli_args[3] ) ? trim( (string) $cli_args[3] ) : (string) ( getenv( 'HWS_IMPORT_SERIES' ) ?: '' );
$media_mode      = isset( $cli_args[4] ) ? trim( (string) $cli_args[4] ) : (string) ( getenv( 'HWS_IMPORT_MEDIA' ) ?: 'no-media' );
$download_media  = 'download-media' === $media_mode;

$payload_raw = file_get_contents( $payload_path );
if ( false === $payload_raw ) {
	hws_import_error( 'Cannot read payload: ' . $payload_path );
}

$payload = json_decode( $payload_raw, true );
if ( ! is_array( $payload ) ) {
	hws_import_error( 'Invalid JSON payload: ' . $payload_path );
}

function hws_normalize_text( string $value ): string {
	$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( (string) $value );
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
	return sanitize_title( $value );
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

function hws_assign_attribute_terms( int $product_id, array $attrs, bool $dry_run ): array {
	$product_attributes = [];
	$position = 0;

	foreach ( $attrs as $taxonomy => $values ) {
		if ( empty( $values ) || ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$term_ids = [];
		foreach ( $values as $value ) {
			$value = hws_normalize_text( (string) $value );
			if ( '' === $value ) {
				continue;
			}
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

function hws_sideload_image_cached( string $url, int $post_id, bool $set_thumbnail = false ): int {
	static $cache = [];

	if ( isset( $cache[ $url ] ) ) {
		$attachment_id = (int) $cache[ $url ];
		if ( $attachment_id > 0 && $set_thumbnail ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
		return $attachment_id;
	}

	$attachment_id = hws_sideload_image( $url, $post_id, $set_thumbnail );
	if ( $attachment_id > 0 ) {
		$cache[ $url ] = $attachment_id;
	}
	return $attachment_id;
}

function hws_calculate_rate(): float {
	$products = wc_get_products(
		[
			'limit'  => 20,
			'return' => 'ids',
			'status' => 'publish',
		]
	);
	foreach ( $products as $product_id ) {
		$rate = (float) get_post_meta( $product_id, '_hws_usd_rub_rate', true );
		if ( $rate > 0 ) {
			return $rate;
		}
	}
	return 71.209;
}

function hws_vvd_find_spec_value( array $specs, array $labels ): ?string {
	foreach ( $specs as $spec ) {
		$name = hws_normalize_text( (string) ( $spec['name'] ?? '' ) );
		foreach ( $labels as $label ) {
			if ( mb_strtolower( $name ) === mb_strtolower( $label ) ) {
				$value = hws_normalize_text( (string) ( $spec['value'] ?? '' ) );
				if ( '' !== $value ) {
					return $value;
				}
			}
		}
	}
	return null;
}

function hws_vvd_build_specs_html( array $specs ): string {
	$html = '<table class="shop_attributes hws-specs"><tbody>';
	foreach ( $specs as $spec ) {
		$label = esc_html( hws_normalize_text( (string) ( $spec['name'] ?? '' ) ) );
		$value = esc_html( hws_normalize_text( (string) ( $spec['value'] ?? '' ) ) );
		if ( '' === $label || '' === $value ) {
			continue;
		}
		$html .= '<tr><th>' . $label . '</th><td>' . $value . '</td></tr>';
	}
	$html .= '</tbody></table>';
	return $html;
}

function hws_vvd_build_sku( array $product ): string {
	$current_offer = $product['current_offer'] ?? [];
	$offer_id      = hws_normalize_text( (string) ( $current_offer['offer_id'] ?? '' ) );
	$url = (string) ( $product['source_url'] ?? '' );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$slug = trim( basename( $path ), '/' );
	if ( '' !== $offer_id && '' !== $slug ) {
		return 'VVD-' . strtoupper( preg_replace( '/[^A-Z0-9]+/i', '-', $slug ) ) . '-' . $offer_id;
	}
	if ( '' !== $offer_id ) {
		return 'VVD-' . $offer_id;
	}
	if ( '' !== $slug ) {
		return 'VVD-' . strtoupper( preg_replace( '/[^A-Z0-9]+/i', '-', $slug ) );
	}

	return 'VVD-' . strtoupper( substr( md5( wp_json_encode( $product ) ), 0, 12 ) );
}

function hws_vvd_build_variation_groups( array $option_groups ): array {
	$groups = [];

	foreach ( $option_groups as $position => $group ) {
		$group_id = hws_normalize_text( (string) ( $group['id'] ?? '' ) );
		if ( '' === $group_id ) {
			continue;
		}

		$values = [];
		foreach ( $group['values'] ?? [] as $value ) {
			$name = hws_normalize_text( (string) ( $value['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$values[] = [
				'name'       => $name,
				'is_default' => ! empty( $value['is_default'] ),
			];
		}

		if ( empty( $values ) ) {
			continue;
		}

		$groups[ $group_id ] = [
			'id'       => $group_id,
			'label'    => hws_normalize_text( (string) ( $group['name'] ?? '' ) ),
			'position' => $position,
			'values'   => $values,
		];
	}

	return $groups;
}

function hws_vvd_merge_product_attributes( array $taxonomy_attributes, array $variation_groups ): array {
	$product_attributes = $taxonomy_attributes;
	$position = count( $product_attributes );

	foreach ( $variation_groups as $group_id => $group ) {
		$values = array_values(
			array_filter(
				array_map(
					static function ( array $value ): string {
						return (string) ( $value['name'] ?? '' );
					},
					$group['values'] ?? []
				)
			)
		);

		if ( empty( $values ) ) {
			continue;
		}

		$product_attributes[ $group_id ] = [
			'name'         => $group_id,
			'value'        => implode( ' | ', $values ),
			'position'     => $position++,
			'is_visible'   => 0,
			'is_variation' => 1,
			'is_taxonomy'  => 0,
		];
	}

	return $product_attributes;
}

function hws_vvd_default_variation_attributes( array $variation_groups ): array {
	$defaults = [];

	foreach ( $variation_groups as $group_id => $group ) {
		$default_value = '';
		foreach ( $group['values'] ?? [] as $value ) {
			if ( ! empty( $value['is_default'] ) ) {
				$default_value = (string) $value['name'];
				break;
			}
		}
		if ( '' === $default_value && ! empty( $group['values'][0]['name'] ) ) {
			$default_value = (string) $group['values'][0]['name'];
		}
		if ( '' !== $default_value ) {
			$defaults[ $group_id ] = $default_value;
		}
	}

	return $defaults;
}

function hws_vvd_clear_variations( int $product_id ): void {
	$variation_ids = get_posts(
		[
			'post_type'   => 'product_variation',
			'post_parent' => $product_id,
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_status' => [ 'publish', 'private' ],
		]
	);

	foreach ( $variation_ids as $variation_id ) {
		wp_delete_post( (int) $variation_id, true );
	}
}

function hws_vvd_build_variation_sku( string $parent_sku, string $offer_id ): string {
	$base = preg_replace( '/-\d+$/', '', $parent_sku );
	return $base . '-' . $offer_id;
}

function hws_vvd_sync_variations( int $product_id, string $parent_sku, array $variation_groups, array $offers, float $rate, bool $download_media ): array {
	hws_vvd_clear_variations( $product_id );

	$imported = 0;
	$min_price = null;
	$max_price = null;

	foreach ( $offers as $index => $offer ) {
		$offer_id = hws_normalize_text( (string) ( $offer['offer_id'] ?? '' ) );
		if ( '' === $offer_id ) {
			continue;
		}

		$selection_map = [];
		foreach ( $offer['selections'] ?? [] as $selection ) {
			$group_id = hws_normalize_text( (string) ( $selection['group_id'] ?? '' ) );
			$value_name = hws_normalize_text( (string) ( $selection['value_name'] ?? '' ) );
			if ( '' === $group_id || '' === $value_name || ! isset( $variation_groups[ $group_id ] ) ) {
				continue;
			}
			$selection_map[ $group_id ] = $value_name;
		}

		if ( ! empty( $variation_groups ) && count( $selection_map ) < count( $variation_groups ) ) {
			continue;
		}

		$variation_id = wp_insert_post(
			[
				'post_title'   => 'Variation ' . $offer_id,
				'post_status'  => 'publish',
				'post_parent'  => $product_id,
				'post_type'    => 'product_variation',
				'menu_order'   => $index,
				'post_content' => '',
			],
			true
		);

		if ( is_wp_error( $variation_id ) ) {
			hws_import_warn( 'Variation create failed for offer ' . $offer_id . ': ' . $variation_id->get_error_message() );
			continue;
		}

		$price_rub = (int) preg_replace( '/[^\d]/', '', (string) ( $offer['price_number_rub'] ?? '' ) );
		if ( $price_rub <= 0 ) {
			$price_rub = (int) preg_replace( '/[^\d]/', '', (string) ( $offer['price_text'] ?? '' ) );
		}
		$price_usd = $price_rub > 0 ? round( $price_rub / $rate ) : 0;
		$price_on_request = $price_usd <= 0;

		update_post_meta( $variation_id, '_sku', hws_vvd_build_variation_sku( $parent_sku, $offer_id ) );
		update_post_meta( $variation_id, '_regular_price', $price_on_request ? '' : $price_usd );
		update_post_meta( $variation_id, '_price', $price_on_request ? '' : $price_usd );
		update_post_meta( $variation_id, '_stock_status', 'instock' );
		update_post_meta( $variation_id, '_manage_stock', 'no' );
		update_post_meta( $variation_id, '_hws_price_on_request', $price_on_request ? 'yes' : 'no' );
		update_post_meta( $variation_id, '_hws_source_offer_id', $offer_id );
		update_post_meta( $variation_id, '_hws_source_offer_url', (string) ( $offer['detail_page_url'] ?? '' ) );

		foreach ( $selection_map as $group_id => $value_name ) {
			update_post_meta( $variation_id, 'attribute_' . $group_id, $value_name );
		}

		if ( $download_media ) {
			$image_url = (string) ( $offer['primary_image_url'] ?? '' );
			if ( '' !== $image_url ) {
				hws_sideload_image_cached( $image_url, $variation_id, true );
			}
		}

		if ( ! $price_on_request ) {
			$min_price = null === $min_price ? $price_usd : min( $min_price, $price_usd );
			$max_price = null === $max_price ? $price_usd : max( $max_price, $price_usd );
		}

		$imported++;
	}

	return [
		'imported'  => $imported,
		'min_price' => $min_price,
		'max_price' => $max_price,
	];
}

function hws_vvd_attribute_values_for_product( array $series_payload, array $product ): array {
	$attrs    = [];
	$defaults = $series_payload['defaultAttributes'] ?? [];
	foreach ( $defaults as $taxonomy => $value ) {
		if ( '' !== hws_normalize_text( (string) $value ) ) {
			$attrs[ $taxonomy ] = [ (string) $value ];
		}
	}

	$specs = $product['specs'] ?? [];
	$volume = hws_vvd_find_spec_value( $specs, [ 'Объём парного помещения, м³', 'Объем парного помещения, м³' ] );
	if ( $volume ) {
		$attrs['pa_steam-room-volume'] = [ $volume ];
	}

	$power = hws_vvd_find_spec_value( $specs, [ 'Номинальная потребляемая мощность', 'Номинальная мощность', 'Мощность' ] );
	if ( ! $power ) {
		foreach ( $product['option_groups'] ?? [] as $group ) {
			$group_name = mb_strtolower( hws_normalize_text( (string) ( $group['name'] ?? '' ) ) );
			if ( false !== mb_strpos( $group_name, 'мощност' ) ) {
				$power = hws_normalize_text( (string) ( $group['selected'] ?? '' ) );
				if ( $power ) {
					break;
				}
			}
		}
	}
	if ( $power ) {
		$attrs['pa_power'] = [ $power ];
	}

	$materials = [];
	$cladding_spec = hws_vvd_find_spec_value( $specs, [ 'Наружная облицовка печи', 'Облицовочный материал' ] );
	if ( $cladding_spec ) {
		$materials[] = $cladding_spec;
	}
	foreach ( $product['option_groups'] ?? [] as $group ) {
		$group_name = mb_strtolower( hws_normalize_text( (string) ( $group['name'] ?? '' ) ) );
		if ( false === mb_strpos( $group_name, 'облицов' ) && false === mb_strpos( $group_name, 'материал' ) ) {
			continue;
		}
		foreach ( $group['values'] ?? [] as $value ) {
			$materials[] = hws_normalize_text( (string) ( $value['name'] ?? '' ) );
		}
	}
	$materials = array_values( array_unique( array_filter( $materials ) ) );
	if ( ! empty( $materials ) ) {
		$attrs['pa_cladding-material'] = $materials;
	}

	$voltage = null;
	$title = hws_normalize_text( (string) ( $product['title'] ?? '' ) );
	if ( preg_match( '/(220\s*\/\s*380\s*В|220\s*В|380\s*В)/u', $title, $matches ) ) {
		$voltage = $matches[1];
	}
	if ( ! $voltage ) {
		$voltage = hws_vvd_find_spec_value( $specs, [ 'Номинальное напряжение' ] );
	}
	if ( $voltage ) {
		$attrs['pa_voltage'] = [ $voltage ];
	}

	$fuel = 'электричество';
	if ( false !== mb_strpos( mb_strtolower( (string) ( $series_payload['target']['primaryCategoryPath'] ?? '' ) ), 'steam-generators' ) ) {
		$fuel = 'электричество';
	}
	$attrs['pa_fuel-type'] = [ $fuel ];

	foreach ( $attrs as $taxonomy => $values ) {
		$attrs[ $taxonomy ] = array_values( array_unique( array_filter( array_map( 'hws_normalize_text', $values ) ) ) );
	}

	return $attrs;
}

$rate = hws_calculate_rate();
hws_import_log( 'Using catalog USD/RUB rate for VVD: ' . $rate );

$processed = 0;
$created   = 0;
$updated   = 0;
$seen_skus = [];

foreach ( $payload['series'] ?? [] as $series_payload ) {
	$series_name = (string) ( $series_payload['seriesName'] ?? '' );
	if ( $series_filter && $series_name !== $series_filter ) {
		continue;
	}

	hws_import_log( 'Series: ' . $series_name );

	foreach ( $series_payload['products'] ?? [] as $product ) {
		if ( ! empty( $product['error'] ) ) {
			hws_import_warn( 'Skip errored payload product: ' . ( $product['source_url'] ?? 'unknown' ) . ' (' . $product['error'] . ')' );
			continue;
		}

		$sku = hws_vvd_build_sku( $product );
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
		if ( '' === $title ) {
			hws_import_warn( 'Skip product without title: ' . ( $product['source_url'] ?? 'unknown' ) );
			continue;
		}

		$slug = 'vvd-' . sanitize_title( preg_replace( '/^VVD-/i', '', $sku ) );
		$product_id = hws_find_existing_product_id( $sku, $slug );
		$is_update = $product_id > 0;
		$offers = is_array( $product['offers'] ?? null ) ? $product['offers'] : [];
		$has_real_variations = count( $offers ) > 1;
		$product_type_label = $has_real_variations ? 'variable' : 'simple';

		$current_offer = $product['current_offer'] ?? [];
		$base_price_rub = (int) preg_replace( '/[^\d]/', '', (string) ( $current_offer['price_number_rub'] ?? '' ) );
		if ( $base_price_rub <= 0 ) {
			$base_price_rub = (int) preg_replace( '/[^\d]/', '', (string) ( $current_offer['price_text'] ?? '' ) );
		}
		$base_price_usd = $base_price_rub > 0 ? round( $base_price_rub / $rate ) : 0;

		$description = trim( (string) ( $product['full_description_html'] ?? '' ) );
		if ( '' === $description ) {
			$description = wpautop( esc_html( hws_normalize_text( (string) ( $product['full_description_text'] ?? '' ) ) ) );
		}
		$short_description = hws_normalize_text( (string) ( $product['short_description'] ?? '' ) );
		if ( '' === $short_description ) {
			$short_description = hws_normalize_text( (string) ( $product['full_description_text'] ?? '' ) );
		}

		$category_ids = hws_category_chain_ids( (string) ( $series_payload['target']['primaryCategoryPath'] ?? '' ) );
		$brand_name   = (string) ( $payload['brand']['name'] ?? 'VVD' );
		$brand_slug   = (string) ( $payload['brand']['slug'] ?? 'vvd' );
		$brand_term_id = hws_ensure_term_by_slug( 'product_brand', $brand_slug, $brand_name );
		$attrs         = hws_vvd_attribute_values_for_product( $series_payload, $product );
		$specs         = $product['specs'] ?? [];
		$specs_html    = hws_vvd_build_specs_html( $specs );

		$payload_json = wp_json_encode(
			[
				'source_sku'        => $sku,
				'name'              => $title,
				'display_name'      => $title,
				'series'            => $series_name,
				'slug'              => $slug,
				'short_description' => $short_description,
				'description'       => $description,
				'option_groups'     => $product['option_groups'] ?? [],
				'offers'            => $offers,
				'documents'         => $product['documents'] ?? [],
				'videos'            => $product['videos'] ?? [],
				'gallery'           => $product['gallery'] ?? [],
				'current_offer'     => $current_offer,
				'raw_data'          => [
					'breadcrumbs' => $product['breadcrumbs'] ?? [],
					'specs'       => $specs,
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
			'post_status'  => 'publish',
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
		wp_set_object_terms( $product_id, $product_type_label, 'product_type', false );

		$product_attributes = hws_assign_attribute_terms( $product_id, $attrs, false );
		$variation_groups = hws_vvd_build_variation_groups( $product['option_groups'] ?? [] );

		// Narrow variation_groups to only those group_ids that appear in at
		// least one offer's selections. Groups in the option tree that no
		// offer actually references are catalog-navigation artefacts and must
		// not be treated as variation axes — otherwise the strict "all groups
		// must be covered" check in hws_vvd_sync_variations() skips every offer.
		if ( ! empty( $variation_groups ) && ! empty( $offers ) ) {
			$active_gids = [];
			foreach ( $offers as $offer ) {
				foreach ( $offer['selections'] ?? [] as $sel ) {
					$gid = hws_normalize_text( (string) ( $sel['group_id'] ?? '' ) );
					if ( '' !== $gid && isset( $variation_groups[ $gid ] ) ) {
						$active_gids[ $gid ] = true;
					}
				}
			}
			if ( ! empty( $active_gids ) ) {
				$variation_groups = array_intersect_key( $variation_groups, $active_gids );
			} else {
				// Offers have no selection data at all → cannot map to variation
				// axes. Treat the product as simple.
				$variation_groups   = [];
				$product_type_label = 'simple';
				wp_set_object_terms( $product_id, 'simple', 'product_type', false );
			}
		}

		if ( 'variable' === $product_type_label && ! empty( $variation_groups ) ) {
			$product_attributes = hws_vvd_merge_product_attributes( $product_attributes, $variation_groups );
			update_post_meta( $product_id, '_default_attributes', hws_vvd_default_variation_attributes( $variation_groups ) );
		} else {
			delete_post_meta( $product_id, '_default_attributes' );
			hws_vvd_clear_variations( $product_id );
		}

		$price_on_request = $base_price_usd <= 0;
		update_post_meta( $product_id, '_sku', $sku );
		// Some VVD "Про" items are configure-to-order and expose no price on
		// the source page. Store empty price (not 0, so Woo does not render
		// "Free"/"$0") and flag them as price-on-request.
		update_post_meta( $product_id, '_price', $price_on_request ? '' : $base_price_usd );
		update_post_meta( $product_id, '_regular_price', $price_on_request ? '' : $base_price_usd );
		update_post_meta( $product_id, '_hws_price_on_request', $price_on_request ? 'yes' : 'no' );
		update_post_meta( $product_id, '_stock_status', 'instock' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_hws_source_payload', $payload_json );
		update_post_meta( $product_id, '_hws_specs_html', $specs_html );
		update_post_meta( $product_id, '_hws_specs_groups', $specs );
		update_post_meta( $product_id, '_hws_source_brand', 'VVD' );
		update_post_meta( $product_id, '_hws_source_supplier', 'VVD' );
		update_post_meta( $product_id, '_hws_source_url', (string) ( $product['source_url'] ?? '' ) );
		update_post_meta( $product_id, '_hws_usd_rub_rate', $rate );
		update_post_meta( $product_id, '_hws_price_currency', 'USD' );
		update_post_meta( $product_id, '_hws_base_price_rub', $base_price_rub );
		update_post_meta( $product_id, '_product_attributes', $product_attributes );
		update_post_meta(
			$product_id,
			'_hws_possible_variation_count',
			array_reduce(
				$product['option_groups'] ?? [],
				static function ( int $carry, array $group ): int {
					$count = count( $group['values'] ?? [] );
					return $carry * max( 1, $count );
				},
				1
			)
		);
		update_post_meta( $product_id, '_hws_imported_variation_count', 0 );

		if ( 'variable' === $product_type_label && ! empty( $variation_groups ) && ! empty( $offers ) ) {
			$variation_result = hws_vvd_sync_variations( $product_id, $sku, $variation_groups, $offers, $rate, $download_media );
			update_post_meta( $product_id, '_hws_imported_variation_count', (int) $variation_result['imported'] );

			$min_variation_price = $variation_result['min_price'];
			$max_variation_price = $variation_result['max_price'];
			$parent_price_on_request = null === $min_variation_price || null === $max_variation_price;

			update_post_meta( $product_id, '_price', $parent_price_on_request ? '' : $min_variation_price );
			update_post_meta( $product_id, '_regular_price', $parent_price_on_request ? '' : $min_variation_price );
			update_post_meta( $product_id, '_hws_price_on_request', $parent_price_on_request ? 'yes' : 'no' );
			update_post_meta( $product_id, '_min_variation_price', $parent_price_on_request ? '' : $min_variation_price );
			update_post_meta( $product_id, '_max_variation_price', $parent_price_on_request ? '' : $max_variation_price );
			update_post_meta( $product_id, '_min_variation_regular_price', $parent_price_on_request ? '' : $min_variation_price );
			update_post_meta( $product_id, '_max_variation_regular_price', $parent_price_on_request ? '' : $max_variation_price );
			wc_delete_product_transients( $product_id );
		}

		if ( $download_media ) {
			$image_ids = [];
			foreach ( $product['gallery'] ?? [] as $index => $image ) {
				$url = (string) ( $image['url'] ?? '' );
				if ( '' === $url ) {
					continue;
				}
				$attachment_id = hws_sideload_image_cached( $url, $product_id, 0 === $index );
				if ( $attachment_id > 0 ) {
					$image_ids[] = $attachment_id;
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
