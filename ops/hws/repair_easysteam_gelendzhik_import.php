<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'hws_gelendzhik_repair_log' ) ) {
	function hws_gelendzhik_repair_log( string $message ): void {
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_gelendzhik_repair_normalize_text' ) ) {
	function hws_gelendzhik_repair_normalize_text( string $value ): string {
		$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$value = preg_replace( '/\s+/u', ' ', $value );
		return trim( $value );
	}
}

function hws_gelendzhik_repair_build_source_payload( array $product ): string {
	$option_groups = [];

	foreach ( ( $product['option_groups'] ?? [] ) as $group_index => $group ) {
		$group_name = hws_gelendzhik_repair_normalize_text( (string) ( $group['name'] ?? '' ) );
		if ( '' === $group_name || 'Серия' === $group_name ) {
			continue;
		}

		$values = [];
		foreach ( ( $group['values'] ?? [] ) as $value_index => $value ) {
			$value_name = hws_gelendzhik_repair_normalize_text( (string) ( $value['label'] ?? '' ) );
			if ( '' === $value_name ) {
				continue;
			}

			$values[] = [
				'name'       => $value_name,
				'delta_price'=> (int) ( $value['price_delta_rub'] ?? 0 ),
				'is_default' => ! empty( $value['checked'] ),
				'sort_order' => $value_index,
			];
		}

		if ( empty( $values ) ) {
			continue;
		}

		$option_groups[] = [
			'id'     => 'gelendzhik-' . $group_index,
			'name'   => $group_name,
			'values' => $values,
		];
	}

	return wp_json_encode(
		[
			'option_groups' => $option_groups,
		],
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
}

function hws_gelendzhik_repair_ensure_brand_term( string $brand_name ): int {
	if ( '' === $brand_name || ! taxonomy_exists( 'product_brand' ) ) {
		return 0;
	}

	$slug = sanitize_title( $brand_name );
	$term = get_term_by( 'slug', $slug, 'product_brand' );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}

	$term = get_term_by( 'name', $brand_name, 'product_brand' );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}

	$created = wp_insert_term(
		$brand_name,
		'product_brand',
		[
			'slug' => $slug,
		]
	);
	if ( is_wp_error( $created ) ) {
		return 0;
	}

	return (int) ( $created['term_id'] ?? 0 );
}

function hws_gelendzhik_repair_sideload( string $url, int $post_id ): int {
	static $cache = [];
	if ( '' === $url ) {
		return 0;
	}
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 20 );
	if ( is_wp_error( $tmp ) ) {
		hws_gelendzhik_repair_log( 'WARN download failed: ' . $url . ' :: ' . $tmp->get_error_message() );
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
		hws_gelendzhik_repair_log( 'WARN sideload failed: ' . $url . ' :: ' . $attachment_id->get_error_message() );
		return 0;
	}

	$cache[ $url ] = (int) $attachment_id;
	return (int) $attachment_id;
}

$dry_run      = isset( $args[0] ) ? 'run' !== trim( (string) $args[0] ) : true;
$payload_path = isset( $args[1] ) ? (string) $args[1] : '/var/www/html/wp-content/themes/hws-frontend/data/import/easysteam/gelendzhik-import-payload.json';
$payload_map  = [];

if ( is_readable( $payload_path ) ) {
	$payload_raw = file_get_contents( $payload_path );
	$payload     = json_decode( (string) $payload_raw, true );
	if ( is_array( $payload ) ) {
		foreach ( ( $payload['products'] ?? [] ) as $product ) {
			$source_url = (string) ( $product['source_url'] ?? '' );
			if ( '' !== $source_url ) {
				$payload_map[ $source_url ] = $product;
			}
		}
	}
}

$products = get_posts(
	[
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'     => '_hws_source_url',
				'value'   => 'https://easysteam.ru/products/show/',
				'compare' => 'LIKE',
			],
		],
	]
);

$report = [
	'products'              => count( $products ),
	'currency_fixed'        => 0,
	'thumbnail_fixed'       => 0,
	'variation_currency'    => 0,
	'payload_fixed'         => 0,
	'series_removed'        => 0,
	'variation_series_removed' => 0,
	'brand_fixed'           => 0,
];

foreach ( $products as $product_id ) {
	$report['currency_fixed']++;
	if ( ! $dry_run ) {
		update_post_meta( $product_id, '_hws_price_currency', 'RUB' );
	}

	$source_url      = (string) get_post_meta( $product_id, '_hws_source_url', true );
	$payload_product = $payload_map[ $source_url ] ?? null;
	if ( is_array( $payload_product ) ) {
		$report['payload_fixed']++;
		if ( ! $dry_run ) {
			update_post_meta( $product_id, '_hws_source_payload', hws_gelendzhik_repair_build_source_payload( $payload_product ) );
		}
	}

	$thumb_id = get_post_thumbnail_id( $product_id );
	$source_image = (string) get_post_meta( $product_id, '_hws_source_base_image', true );
	if ( ! $thumb_id && $source_image ) {
		$report['thumbnail_fixed']++;
		if ( ! $dry_run ) {
			$attachment_id = hws_gelendzhik_repair_sideload( $source_image, $product_id );
			if ( $attachment_id > 0 ) {
				set_post_thumbnail( $product_id, $attachment_id );
			}
		}
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product_Variable ) {
		continue;
	}

	$source_brand   = (string) get_post_meta( $product_id, '_hws_source_brand', true );
	$brand_term_id  = hws_gelendzhik_repair_ensure_brand_term( $source_brand );
	if ( $brand_term_id > 0 ) {
		$report['brand_fixed']++;
		if ( ! $dry_run ) {
			wp_set_object_terms( $product_id, [ $brand_term_id ], 'product_brand', false );
		}
	}

	$attributes = $product->get_attributes();
	if ( isset( $attributes['pa_series'] ) ) {
		unset( $attributes['pa_series'] );
		$report['series_removed']++;
		if ( ! $dry_run ) {
			$product->set_attributes( $attributes );
			$default_attributes = $product->get_default_attributes();
			unset( $default_attributes['pa_series'] );
			$product->set_default_attributes( $default_attributes );
			$product->save();
			wp_set_object_terms( $product_id, [], 'pa_series' );
		}
	}

	foreach ( $product->get_children() as $variation_id ) {
		$report['variation_currency']++;
		if ( ! $dry_run ) {
			update_post_meta( $variation_id, '_hws_price_currency', 'RUB' );
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}

		$variation_attrs = $variation->get_attributes();
		if ( isset( $variation_attrs['pa_series'] ) ) {
			unset( $variation_attrs['pa_series'] );
			$report['variation_series_removed']++;
			if ( ! $dry_run ) {
				$variation->set_attributes( $variation_attrs );
				$variation->save();
				delete_post_meta( $variation_id, 'attribute_pa_series' );
			}
		}
	}

	if ( ! $dry_run ) {
		WC_Product_Variable::sync( $product_id );
		wc_delete_product_transients( $product_id );
	}
}

hws_gelendzhik_repair_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
