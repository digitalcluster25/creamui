<?php

if ( ! defined( 'ABSPATH' ) ) {
	$bootstrap_path = '/var/www/html/wp-load.php';
	if ( ! is_readable( $bootstrap_path ) ) {
		exit( 1 );
	}
	require_once $bootstrap_path;
}

function hws_pm_log( string $message ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $message );
		return;
	}
	echo $message . PHP_EOL;
}

function hws_pm_normalize_text( string $value ): string {
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$value = wp_strip_all_tags( $value );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( (string) $value );
}

function hws_pm_product_attributes( WC_Product $product ): array {
	$attributes = [];

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}

		$name = (string) $attribute->get_name();
		if ( '' === $name ) {
			continue;
		}

		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $name, [ 'fields' => 'names' ] )
			: $attribute->get_options();
		$values = array_map( 'hws_pm_normalize_text', array_map( 'strval', $values ) );
		sort( $values, SORT_NATURAL | SORT_FLAG_CASE );

		$attributes[ $name ] = [
			'values'    => array_values( array_unique( $values ) ),
			'visible'   => $attribute->get_visible(),
			'variation' => $attribute->get_variation(),
		];
	}

	ksort( $attributes, SORT_NATURAL | SORT_FLAG_CASE );
	return $attributes;
}

function hws_pm_images( WC_Product $product ): array {
	$image_ids = array_filter( array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() ) );
	$images    = [];

	foreach ( $image_ids as $image_id ) {
		$file_path = get_attached_file( (int) $image_id );
		$images[]  = [
			'url'          => (string) wp_get_attachment_url( (int) $image_id ),
			'content_hash' => $file_path && is_readable( $file_path ) ? hash_file( 'sha256', $file_path ) : '',
		];
	}

	return $images;
}

function hws_pm_hash( $value ): string {
	return hash( 'sha256', wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

function hws_pm_product_row( WC_Product $product ): array {
	$product_id          = $product->get_id();
	$title               = hws_pm_normalize_text( $product->get_name() );
	$description         = hws_pm_normalize_text( $product->get_description() );
	$short_description   = hws_pm_normalize_text( $product->get_short_description() );
	$attributes          = hws_pm_product_attributes( $product );
	$images              = hws_pm_images( $product );
	$image_urls          = array_column( $images, 'url' );
	$image_hashes        = array_column( $images, 'content_hash' );
	$content_fingerprint = [
		'title'             => $title,
		'price'             => (string) $product->get_price(),
		'regular_price'     => (string) $product->get_regular_price(),
		'sale_price'        => (string) $product->get_sale_price(),
		'description_hash'  => hash( 'sha256', $description ),
		'short_description' => hash( 'sha256', $short_description ),
		'attributes'        => $attributes,
		'image_hashes'      => $image_hashes,
	];

	return [
		'id'                  => $product_id,
		'url'                 => get_permalink( $product_id ),
		'slug'                => $product->get_slug(),
		'title'               => $title,
		'title_key'           => mb_strtolower( $title, 'UTF-8' ),
		'type'                => $product->get_type(),
		'status'              => get_post_status( $product_id ),
		'parent_id'           => $product->get_parent_id(),
		'sku'                 => (string) $product->get_sku(),
		'price'               => (string) $product->get_price(),
		'regular_price'       => (string) $product->get_regular_price(),
		'sale_price'          => (string) $product->get_sale_price(),
		'description_hash'    => $content_fingerprint['description_hash'],
		'short_description_hash' => $content_fingerprint['short_description'],
		'image_urls'          => $image_urls,
		'image_hashes'        => $image_hashes,
		'attributes'          => $attributes,
		'content_signature'   => hws_pm_hash( $content_fingerprint ),
	];
}

function hws_pm_output_path(): string {
	$directory = rtrim( ABSPATH, '/' ) . '/data/audit/product-merge';
	if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
		throw new RuntimeException( 'Cannot create audit directory: ' . $directory );
	}
	return $directory . '/inventory-' . gmdate( 'Ymd-His' ) . '.json';
}

$product_ids = wc_get_products(
	[
		'limit'  => -1,
		'return' => 'ids',
		'status' => [ 'publish', 'draft', 'pending', 'private' ],
		'type'   => [ 'simple', 'variable' ],
	]
);

$products_by_title = [];
$products          = [];

foreach ( $product_ids as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		continue;
	}

	$row = hws_pm_product_row( $product );
	$products[] = $row;
	$products_by_title[ $row['title_key'] ][] = $row;
}

usort( $products, static function ( array $left, array $right ): int {
	return $left['id'] <=> $right['id'];
} );

$same_title_groups = [];
$exact_duplicates  = [];

foreach ( $products_by_title as $rows ) {
	if ( count( $rows ) < 2 ) {
		continue;
	}

	usort( $rows, static function ( array $left, array $right ): int {
		return $left['id'] <=> $right['id'];
	} );
	$same_title_groups[] = $rows;

	$by_signature = [];
	foreach ( $rows as $row ) {
		$by_signature[ $row['content_signature'] ][] = $row;
	}
	foreach ( $by_signature as $matching_rows ) {
		if ( count( $matching_rows ) > 1 ) {
			$exact_duplicates[] = [
				'title'               => $matching_rows[0]['title'],
				'retain_suggestion'   => $matching_rows[0],
				'delete_candidates'   => array_slice( $matching_rows, 1 ),
				'comparison'          => 'same title, price, descriptions, attributes and image file content',
			];
		}
	}
}

$payload = [
	'generated_at'          => gmdate( 'c' ),
	'mode'                  => 'read-only export',
	'product_count'         => count( $products ),
	'same_title_group_count'=> count( $same_title_groups ),
	'exact_duplicate_groups'=> $exact_duplicates,
	'products'              => $products,
];

$output_path = hws_pm_output_path();
$json        = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( false === $json || false === file_put_contents( $output_path, $json . PHP_EOL ) ) {
	throw new RuntimeException( 'Cannot write inventory: ' . $output_path );
}

hws_pm_log(
	sprintf(
		'Export complete. products=%d same_title_groups=%d exact_duplicate_groups=%d output=%s',
		count( $products ),
		count( $same_title_groups ),
		count( $exact_duplicates ),
		$output_path
	)
);
