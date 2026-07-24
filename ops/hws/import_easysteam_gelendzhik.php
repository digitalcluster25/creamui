<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'hws_gelendzhik_log' ) ) {
	function hws_gelendzhik_log( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
			return;
		}
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_gelendzhik_error' ) ) {
	function hws_gelendzhik_error( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( $message );
			return;
		}
		throw new RuntimeException( $message );
	}
}

$payload_path = $args[0] ?? null;
if ( ! $payload_path ) {
	hws_gelendzhik_error( 'Usage: wp eval-file import_easysteam_gelendzhik.php <payload.json> [dry-run|run] [limit] [source-rub|convert-usd]' );
}

$mode       = isset( $args[1] ) ? trim( (string) $args[1] ) : 'dry-run';
$dry_run    = 'run' !== $mode;
$limit      = isset( $args[2] ) ? (int) $args[2] : 0;
$price_mode = isset( $args[3] ) ? trim( (string) $args[3] ) : 'source-rub';

$payload_raw = file_get_contents( $payload_path );
if ( false === $payload_raw ) {
	hws_gelendzhik_error( 'Cannot read payload: ' . $payload_path );
}

$payload = json_decode( $payload_raw, true );
if ( ! is_array( $payload ) ) {
	hws_gelendzhik_error( 'Invalid JSON payload: ' . $payload_path );
}

function hws_gelendzhik_normalize_text( string $value ): string {
	$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( $value );
}

function hws_gelendzhik_slugify( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = sanitize_title( $value );
	return trim( $value );
}

function hws_gelendzhik_attribute_map(): array {
	return [
		'Тип топлива'                    => [ 'taxonomy' => 'pa_fuel-type', 'label' => 'Тип топлива' ],
		'Марка стали'                    => [ 'taxonomy' => 'pa_steel-grade', 'label' => 'Марка стали' ],
		'Защита топки'                   => [ 'taxonomy' => 'pa_firebox-protection', 'label' => 'Защита топки' ],
		'Материал кожуха'                => [ 'taxonomy' => 'pa_cladding-material', 'label' => 'Материал кожуха' ],
		'Сторона дверки'                 => [ 'taxonomy' => 'pa_door-side', 'label' => 'Сторона дверки' ],
		'Сторона входа в каменку'        => [ 'taxonomy' => 'pa_stone-entry-side', 'label' => 'Сторона входа в каменку' ],
		'Сторона подключения дымохода'   => [ 'taxonomy' => 'pa_chimney-connection-side', 'label' => 'Сторона подключения дымохода' ],
	];
}

function hws_gelendzhik_attribute_name_from_taxonomy( string $taxonomy ): string {
	foreach ( hws_gelendzhik_attribute_map() as $name => $config ) {
		if ( $config['taxonomy'] === $taxonomy ) {
			return $name;
		}
	}
	return $taxonomy;
}

function hws_gelendzhik_find_product_id( array $product ): int {
	$source_url = (string) ( $product['source_url'] ?? '' );
	$slug       = (string) ( $product['slug'] ?? '' );
	global $wpdb;
	if ( '' !== $source_url ) {
		$product_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hws_source_url' AND meta_value = %s LIMIT 1",
				$source_url
			)
		);
		if ( $product_id > 0 ) {
			return $product_id;
		}
	}
	if ( '' !== $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post ) {
			return (int) $post->ID;
		}
	}
	return 0;
}

function hws_gelendzhik_find_variation_id_by_sku( string $sku ): int {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
			$sku
		)
	);
}

function hws_gelendzhik_ensure_brand_term( string $brand_name ): int {
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

function hws_gelendzhik_ensure_global_attribute( string $taxonomy, string $label ): void {
	global $wpdb;
	if ( taxonomy_exists( $taxonomy ) ) {
		return;
	}

	$attribute_name = preg_replace( '/^pa_/', '', $taxonomy );
	$existing_id    = wc_attribute_taxonomy_id_by_name( $taxonomy );
	if ( $existing_id <= 0 ) {
		$created = wc_create_attribute(
			[
				'name'         => $label,
				'slug'         => $attribute_name,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			]
		);
		if ( is_wp_error( $created ) ) {
			hws_gelendzhik_error( 'Cannot create attribute ' . $taxonomy . ': ' . $created->get_error_message() );
		}
	}

	register_taxonomy(
		$taxonomy,
		apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy, [ 'product' ] ),
		apply_filters(
			'woocommerce_taxonomy_args_' . $taxonomy,
			[
				'hierarchical' => true,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
			]
		)
	);
	delete_transient( 'wc_attribute_taxonomies' );
	WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
}

function hws_gelendzhik_ensure_term( string $taxonomy, string $value ): int {
	$slug = hws_gelendzhik_slugify( $value );
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}

	$existing = term_exists( $value, $taxonomy );
	if ( is_array( $existing ) && ! empty( $existing['term_id'] ) ) {
		return (int) $existing['term_id'];
	}

	$created = wp_insert_term(
		$value,
		$taxonomy,
		[
			'slug' => $slug,
		]
	);
	if ( is_wp_error( $created ) ) {
		hws_gelendzhik_error( 'Cannot create term ' . $taxonomy . ':' . $value . ' — ' . $created->get_error_message() );
	}

	return (int) $created['term_id'];
}

function hws_gelendzhik_product_cat_ids( array $payload ): array {
	$category_slug = (string) ( $payload['scope']['category_slug'] ?? '' );
	if ( '' === $category_slug ) {
		return [];
	}
	$term = get_term_by( 'slug', $category_slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		hws_gelendzhik_error( 'Missing product_cat slug: ' . $category_slug );
	}
	return [ (int) $term->term_id ];
}

function hws_gelendzhik_store_price( int $price_rub, string $price_mode ): string {
	if ( 'convert-usd' === $price_mode ) {
		global $wpdb;
		$rate = (float) $wpdb->get_var(
			"SELECT meta_value FROM {$wpdb->postmeta}
			 WHERE meta_key = '_hws_usd_rub_rate' AND meta_value > 0
			 ORDER BY post_id DESC LIMIT 1"
		);
		if ( $rate <= 0 ) {
			hws_gelendzhik_error( 'USD/RUB rate is missing for convert-usd mode.' );
		}
		return number_format( $price_rub / $rate, 2, '.', '' );
	}
	return (string) $price_rub;
}

function hws_gelendzhik_build_report( array $payload, int $limit = 0 ): array {
	$products          = $payload['products'] ?? [];
	$report            = [
		'mode'                    => 'dry-run',
		'products_total'          => count( $products ),
		'products_checked'        => 0,
		'variants_total'          => 0,
		'variants_checked'        => 0,
		'duplicate_articles'      => [],
		'products'                => [],
		'missing_prices'          => [],
		'missing_articles'        => [],
		'missing_short_desc'      => [],
		'missing_raw_tabs'        => [],
	];
	$seen_articles = [];

	if ( $limit > 0 ) {
		$products = array_slice( $products, 0, $limit );
	}

	foreach ( $products as $product ) {
		$product_id       = hws_gelendzhik_find_product_id( $product );
		$variants         = $product['variants'] ?? [];
		$variant_preview  = [];
		$report['products_checked']++;
		$report['variants_total'] += count( $variants );

		foreach ( $variants as $variant ) {
			$report['variants_checked']++;
			$article = (string) ( $variant['manufacturer_article'] ?? '' );
			$price   = (int) ( $variant['price_rub'] ?? 0 );
			if ( '' === $article || 'missing manufacturer article' === $article ) {
				$report['missing_articles'][] = [
					'product' => $product['title'] ?? '',
					'options' => $variant['source_options'] ?? [],
				];
			} else {
				if ( isset( $seen_articles[ $article ] ) ) {
					$report['duplicate_articles'][] = [
						'article' => $article,
						'first'   => $seen_articles[ $article ],
						'second'  => $product['title'] ?? '',
					];
				}
				$seen_articles[ $article ] = (string) ( $product['title'] ?? '' );
			}
			if ( $price <= 0 ) {
				$report['missing_prices'][] = [
					'product' => $product['title'] ?? '',
					'article' => $article,
					'options' => $variant['source_options'] ?? [],
				];
			}
			$variant_preview[] = [
				'article'      => $article,
				'existing_id'  => $article ? hws_gelendzhik_find_variation_id_by_sku( $article ) : 0,
				'status'       => $variant['status'] ?? '',
				'price_rub'    => $price,
				'attributes'   => $variant['normalized_attributes'] ?? [],
			];
		}

		if ( '' === hws_gelendzhik_normalize_text( (string) ( $product['short_description'] ?? '' ) ) ) {
			$report['missing_short_desc'][] = (string) ( $product['title'] ?? '' );
		}
		if (
			'' === hws_gelendzhik_normalize_text( (string) ( $product['raw_tabs']['purpose'] ?? '' ) ) &&
			'' === hws_gelendzhik_normalize_text( (string) ( $product['raw_tabs']['advantage'] ?? '' ) )
		) {
			$report['missing_raw_tabs'][] = (string) ( $product['title'] ?? '' );
		}

		$report['products'][] = [
			'title'             => $product['title'] ?? '',
			'source_url'        => $product['source_url'] ?? '',
			'existing_product'  => $product_id,
			'action'            => $product_id > 0 ? 'update' : 'create',
			'variants'          => count( $variants ),
			'variant_preview'   => array_slice( $variant_preview, 0, 5 ),
		];
	}

	return $report;
}

function hws_gelendzhik_build_source_payload( array $product ): string {
	$option_groups = [];

	foreach ( ( $product['option_groups'] ?? [] ) as $group_index => $group ) {
		$group_name = hws_gelendzhik_normalize_text( (string) ( $group['name'] ?? '' ) );
		if ( '' === $group_name || 'Серия' === $group_name ) {
			continue;
		}

		$values = [];
		foreach ( ( $group['values'] ?? [] ) as $value_index => $value ) {
			$value_name = hws_gelendzhik_normalize_text( (string) ( $value['label'] ?? '' ) );
			if ( '' === $value_name ) {
				continue;
			}

			$values[] = [
				'name'       => $value_name,
				'delta_price'=> (int) ( $value['price_delta_rub'] ?? 0 ),
				'is_default' => ! empty( $value['checked'] ),
				'sort_order' => $value_index,
				'image'      => (string) ( $value['image'] ?? '' ),
				'additional_image' => (string) ( $value['additional_image'] ?? '' ),
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

function hws_gelendzhik_sync_parent_attributes( int $product_id, array $product ): void {
	$attribute_map   = hws_gelendzhik_attribute_map();
	$raw_attributes  = $product['parent_attributes'] ?? [];
	$parent_attrs    = [];

	foreach ( $raw_attributes as $attribute_name => $values ) {
		if ( empty( $attribute_map[ $attribute_name ] ) ) {
			continue;
		}
		$taxonomy = $attribute_map[ $attribute_name ]['taxonomy'];
		$label    = $attribute_map[ $attribute_name ]['label'];
		hws_gelendzhik_ensure_global_attribute( $taxonomy, $label );
		$term_ids = [];
		foreach ( $values as $value ) {
			$value = hws_gelendzhik_normalize_text( (string) $value );
			if ( '' === $value ) {
				continue;
			}
			$term_ids[] = hws_gelendzhik_ensure_term( $taxonomy, $value );
		}
		if ( empty( $term_ids ) ) {
			continue;
		}
		wp_set_object_terms( $product_id, $term_ids, $taxonomy );
		$attr = new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attr->set_name( $taxonomy );
		$attr->set_options( array_values( array_unique( array_map( 'intval', $term_ids ) ) ) );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$parent_attrs[ $taxonomy ] = $attr;
	}

	$wc_product = wc_get_product( $product_id );
	$existing   = $wc_product ? $wc_product->get_attributes() : [];
	foreach ( $existing as $name => $attribute ) {
		if ( ! isset( $parent_attrs[ $name ] ) ) {
			$parent_attrs[ $name ] = $attribute;
		}
	}
	if ( $wc_product ) {
		$wc_product->set_attributes( $parent_attrs );
		$wc_product->save();
	}
}

function hws_gelendzhik_sync_variant( int $parent_id, array $variant, string $price_mode ): int {
	$article = (string) ( $variant['manufacturer_article'] ?? '' );
	if ( '' === $article || 'missing manufacturer article' === $article ) {
		hws_gelendzhik_error( 'Cannot import variation without manufacturer article.' );
	}

	$variation_id = hws_gelendzhik_find_variation_id_by_sku( $article );
	$wc_variant   = $variation_id > 0 ? wc_get_product( $variation_id ) : null;
	if ( ! $wc_variant || ! $wc_variant->is_type( 'variation' ) ) {
		$wc_variant = new WC_Product_Variation();
		$wc_variant->set_parent_id( $parent_id );
	}

	$store_price = hws_gelendzhik_store_price( (int) ( $variant['price_rub'] ?? 0 ), $price_mode );
	$attrs       = [];
	foreach ( ( $variant['normalized_attributes'] ?? [] ) as $attribute_name => $value ) {
		$map = hws_gelendzhik_attribute_map();
		if ( empty( $map[ $attribute_name ] ) ) {
			continue;
		}
		$taxonomy            = $map[ $attribute_name ]['taxonomy'];
		$value               = hws_gelendzhik_normalize_text( (string) $value );
		hws_gelendzhik_ensure_global_attribute( $taxonomy, $attribute_name );
		hws_gelendzhik_ensure_term( $taxonomy, $value );
		$attrs[ $taxonomy ] = hws_gelendzhik_slugify( $value );
	}

	$wc_variant->set_status( 'publish' );
	$wc_variant->set_sku( $article );
	$wc_variant->set_regular_price( $store_price );
	$wc_variant->set_price( $store_price );
	$wc_variant->set_attributes( $attrs );
	$variation_id = $wc_variant->save();

	update_post_meta( $variation_id, '_hws_source_price_rub', (int) ( $variant['price_rub'] ?? 0 ) );
	update_post_meta( $variation_id, '_hws_price_currency', 'RUB' );
	update_post_meta( $variation_id, '_hws_source_image', (string) ( $variant['image'] ?? '' ) );
	update_post_meta( $variation_id, '_hws_source_options', wp_json_encode( $variant['source_options'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	update_post_meta( $variation_id, '_hws_source_api_param_list', (string) ( $variant['api_param_list'] ?? '' ) );
	update_post_meta( $variation_id, '_hws_price_mode', $price_mode );

	return $variation_id;
}

function hws_gelendzhik_import_product( array $payload, array $product, string $price_mode ): array {
	$product_id = hws_gelendzhik_find_product_id( $product );
	$wc_product = $product_id > 0 ? wc_get_product( $product_id ) : null;
	if ( ! $wc_product || ! $wc_product->is_type( 'variable' ) ) {
		$wc_product = new WC_Product_Variable();
	}

	$wc_product->set_name( (string) ( $product['title'] ?? '' ) );
	$wc_product->set_slug( (string) ( $product['slug'] ?? '' ) );
	$wc_product->set_status( 'publish' );
	$wc_product->set_catalog_visibility( 'visible' );
	$wc_product->set_short_description( (string) ( $product['short_description'] ?? '' ) );
	$wc_product->set_category_ids( hws_gelendzhik_product_cat_ids( $payload ) );
	$product_id = $wc_product->save();

	update_post_meta( $product_id, '_hws_source_url', (string) ( $product['source_url'] ?? '' ) );
	update_post_meta( $product_id, '_hws_price_currency', 'RUB' );
	update_post_meta( $product_id, '_hws_source_product_id', (string) ( $product['source_product_id'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_base_article', (string) ( $product['base_article'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_base_price_rub', (int) ( $product['base_price_rub'] ?? 0 ) );
	update_post_meta( $product_id, '_hws_source_base_image', (string) ( $product['base_image'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_brand', (string) ( $product['brand'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_series', (string) ( $product['series'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_raw_purpose', (string) ( $product['raw_tabs']['purpose'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_raw_advantage', (string) ( $product['raw_tabs']['advantage'] ?? '' ) );
	update_post_meta( $product_id, '_hws_source_characteristics_json', wp_json_encode( $product['characteristics'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	update_post_meta( $product_id, '_hws_source_skipped_tabs', wp_json_encode( $product['skipped_tabs'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	update_post_meta( $product_id, '_hws_source_payload', hws_gelendzhik_build_source_payload( $product ) );

	$brand_term_id = hws_gelendzhik_ensure_brand_term( (string) ( $product['brand'] ?? '' ) );
	if ( $brand_term_id > 0 ) {
		wp_set_object_terms( $product_id, [ $brand_term_id ], 'product_brand', false );
	}

	hws_gelendzhik_sync_parent_attributes( $product_id, $product );

	$default_attributes = [];
	foreach ( ( $product['default_variant_attributes'] ?? [] ) as $attribute_name => $value ) {
		$map = hws_gelendzhik_attribute_map();
		if ( empty( $map[ $attribute_name ] ) ) {
			continue;
		}
		$default_attributes[ $map[ $attribute_name ]['taxonomy'] ] = hws_gelendzhik_slugify( (string) $value );
	}
	$wc_product = wc_get_product( $product_id );
	if ( $wc_product ) {
		$wc_product->set_default_attributes( $default_attributes );
		$wc_product->save();
	}

	$variation_ids = [];
	foreach ( ( $product['variants'] ?? [] ) as $variant ) {
		$variation_ids[] = hws_gelendzhik_sync_variant( $product_id, $variant, $price_mode );
	}
	WC_Product_Variable::sync( $product_id );
	wc_delete_product_transients( $product_id );

	return [
		'product_id'     => $product_id,
		'variation_ids'  => $variation_ids,
	];
}

$report = hws_gelendzhik_build_report( $payload, $limit );
if ( $dry_run ) {
	hws_gelendzhik_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	return;
}

$products = $payload['products'] ?? [];
if ( $limit > 0 ) {
	$products = array_slice( $products, 0, $limit );
}

$result = [
	'mode'     => 'run',
	'products' => [],
];

foreach ( $products as $product ) {
	$result['products'][] = array_merge(
		[
			'title'      => $product['title'] ?? '',
			'source_url' => $product['source_url'] ?? '',
		],
		hws_gelendzhik_import_product( $payload, $product, $price_mode )
	);
}

hws_gelendzhik_log( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
