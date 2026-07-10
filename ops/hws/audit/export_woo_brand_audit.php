<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'hws_audit_log' ) ) {
	function hws_audit_log( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
			return;
		}
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_audit_error' ) ) {
	function hws_audit_error( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( $message );
			return;
		}
		throw new RuntimeException( $message );
	}
}

function hws_audit_normalize_text( string $value ): string {
	$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( (string) $value );
}

function hws_audit_slugify_brand( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = mb_strtolower( $value, 'UTF-8' );
	$map   = [
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
		'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	];
	$value = strtr( $value, $map );
	return sanitize_title( $value );
}

function hws_audit_deepest_product_cat_slug( int $product_id ): ?string {
	$terms = wp_get_object_terms(
		$product_id,
		'product_cat',
		[
			'fields' => 'all',
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}

	$deepest       = null;
	$deepest_depth = -1;

	foreach ( $terms as $term ) {
		$depth   = count( get_ancestors( (int) $term->term_id, 'product_cat', 'taxonomy' ) );
		$term_id = (int) $term->term_id;
		if ( $depth > $deepest_depth ) {
			$deepest       = $term;
			$deepest_depth = $depth;
			continue;
		}
		if ( $depth === $deepest_depth && $deepest && $term_id < (int) $deepest->term_id ) {
			$deepest = $term;
		}
	}

	return $deepest ? (string) $deepest->slug : null;
}

function hws_audit_collect_category_slugs( int $product_id ): array {
	$terms = wp_get_object_terms(
		$product_id,
		'product_cat',
		[
			'fields' => 'slugs',
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	$slugs = array_map(
		static function ( $slug ): string {
			return (string) $slug;
		},
		$terms
	);

	sort( $slugs );
	return array_values( array_unique( $slugs ) );
}

function hws_audit_collect_attribute_keys( WC_Product $product ): array {
	$attributes = $product->get_attributes();
	$keys       = [];

	foreach ( $attributes as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		$name = (string) $attribute->get_name();
		if ( '' !== $name ) {
			$keys[] = $name;
		}
	}

	sort( $keys );
	return array_values( array_unique( $keys ) );
}

function hws_audit_collect_brand_slugs( int $product_id ): array {
	if ( taxonomy_exists( 'product_brand' ) ) {
		$terms = wp_get_object_terms(
			$product_id,
			'product_brand',
			[
				'fields' => 'slugs',
			]
		);
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$slugs = array_map(
				static function ( $slug ): string {
					return (string) $slug;
				},
				$terms
			);
			sort( $slugs );
			return array_values( array_unique( $slugs ) );
		}
	}

	$meta_slug = hws_audit_slugify_brand( (string) get_post_meta( $product_id, '_hws_source_brand', true ) );
	return $meta_slug ? [ $meta_slug ] : [];
}

function hws_audit_get_brand_products( string $brand_slug ): array {
	$query_args = [
		'limit'  => -1,
		'return' => 'ids',
		'status' => [ 'publish', 'draft', 'pending', 'private' ],
		'type'   => [ 'simple', 'variable' ],
	];

	if ( taxonomy_exists( 'product_brand' ) ) {
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => [ $brand_slug ],
			],
		];
		$product_ids            = wc_get_products( $query_args );
		if ( ! empty( $product_ids ) ) {
			return array_map( 'intval', $product_ids );
		}
	}

	$product_ids = wc_get_products( $query_args );
	$matched     = [];
	foreach ( $product_ids as $product_id ) {
		$meta_brand = hws_audit_slugify_brand( (string) get_post_meta( (int) $product_id, '_hws_source_brand', true ) );
		if ( $meta_brand && $meta_brand === $brand_slug ) {
			$matched[] = (int) $product_id;
		}
	}

	return $matched;
}

function hws_audit_collect_product_row( WC_Product $product ): array {
	$product_id        = $product->get_id();
	$post              = get_post( $product_id );
	$gallery_ids       = $product->get_gallery_image_ids();
	$variation_ids     = $product->is_type( 'variable' ) ? $product->get_children() : [];
	$brand_slugs       = hws_audit_collect_brand_slugs( $product_id );
	$price_on_request  = 'yes' === (string) get_post_meta( $product_id, '_hws_price_on_request', true );
	$source_brand_meta = hws_audit_normalize_text( (string) get_post_meta( $product_id, '_hws_source_brand', true ) );
	$source_url        = (string) get_post_meta( $product_id, '_hws_source_url', true );

	return [
		'product_id'                     => $product_id,
		'sku'                            => (string) $product->get_sku(),
		'slug'                           => (string) $product->get_slug(),
		'status'                         => $post ? (string) $post->post_status : '',
		'title'                          => hws_audit_normalize_text( (string) $product->get_name() ),
		'brand'                          => ! empty( $brand_slugs ) ? $brand_slugs[0] : hws_audit_slugify_brand( $source_brand_meta ),
		'brand_slugs'                    => $brand_slugs,
		'source_brand_meta'              => $source_brand_meta,
		'source_url'                     => $source_url,
		'primary_category'               => hws_audit_deepest_product_cat_slug( $product_id ),
		'category_slugs'                 => hws_audit_collect_category_slugs( $product_id ),
		'image_count'                    => ( $product->get_image_id() ? 1 : 0 ) + count( $gallery_ids ),
		'featured_image_id'              => (int) $product->get_image_id(),
		'gallery_image_ids'              => array_map( 'intval', $gallery_ids ),
		'has_short_description'          => '' !== hws_audit_normalize_text( (string) $product->get_short_description() ),
		'has_full_description'           => '' !== hws_audit_normalize_text( (string) $product->get_description() ),
		'attribute_keys'                 => hws_audit_collect_attribute_keys( $product ),
		'price'                          => (string) $product->get_price(),
		'regular_price'                  => (string) $product->get_regular_price(),
		'price_on_request'               => $price_on_request,
		'product_type'                   => (string) $product->get_type(),
		'variation_count'                => count( $variation_ids ),
		'variation_ids'                  => array_map( 'intval', $variation_ids ),
		'imported_variation_count_meta'  => (int) get_post_meta( $product_id, '_hws_imported_variation_count', true ),
		'possible_variation_count_meta'  => (int) get_post_meta( $product_id, '_hws_possible_variation_count', true ),
	];
}

function hws_audit_output_dir(): string {
	$override = getenv( 'HWS_AUDIT_OUTPUT_DIR' );
	if ( is_string( $override ) && '' !== trim( $override ) ) {
		return rtrim( trim( $override ), '/' );
	}
	return rtrim( ABSPATH, '/' ) . '/data/audit/woo';
}

function hws_audit_write_json( string $brand_slug, array $rows ): string {
	$base_dir = hws_audit_output_dir();
	if ( ! is_dir( $base_dir ) && ! wp_mkdir_p( $base_dir ) ) {
		hws_audit_error( 'Cannot create output directory: ' . $base_dir );
	}

	$output_path = $base_dir . '/' . $brand_slug . '.json';
	$payload     = [
		'brand'        => $brand_slug,
		'generated_at' => gmdate( 'c' ),
		'total'        => count( $rows ),
		'products'     => array_values( $rows ),
	];
	$json        = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $json ) {
		hws_audit_error( 'Cannot encode JSON for brand ' . $brand_slug );
	}
	if ( false === file_put_contents( $output_path, $json . PHP_EOL ) ) {
		hws_audit_error( 'Cannot write output: ' . $output_path );
	}

	return $output_path;
}

$cli_args   = isset( $args ) && is_array( $args ) ? $args : [];
$brand_slug = isset( $cli_args[0] ) ? sanitize_title( (string) $cli_args[0] ) : sanitize_title( (string) getenv( 'HWS_AUDIT_BRAND' ) );

if ( '' === $brand_slug ) {
	hws_audit_error( 'Usage: wp eval-file export_woo_brand_audit.php <brand-slug> or set HWS_AUDIT_BRAND' );
}

$product_ids = hws_audit_get_brand_products( $brand_slug );
$rows        = [];

foreach ( $product_ids as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		continue;
	}
	$rows[] = hws_audit_collect_product_row( $product );
}

usort(
	$rows,
	static function ( array $a, array $b ): int {
		return strcmp( (string) $a['sku'], (string) $b['sku'] ) ?: ( $a['product_id'] <=> $b['product_id'] );
	}
);

$output_path = hws_audit_write_json( $brand_slug, $rows );
hws_audit_log( 'brand=' . $brand_slug . ' total=' . count( $rows ) . ' output=' . $output_path );
