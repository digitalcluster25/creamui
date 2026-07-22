<?php

if ( ! defined( 'ABSPATH' ) ) {
	$bootstrap_path = '/var/www/html/wp-load.php';
	if ( ! is_readable( $bootstrap_path ) ) {
		exit( 1 );
	}
	require_once $bootstrap_path;
}

function hws_sangens_pilot_log( string $message ): void {
	echo $message . PHP_EOL;
}

function hws_sangens_pilot_attribute( string $taxonomy, array $term_ids, bool $variation ): WC_Product_Attribute {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
	$attribute->set_name( $taxonomy );
	$attribute->set_options( array_values( array_unique( array_map( 'intval', $term_ids ) ) ) );
	$attribute->set_visible( true );
	$attribute->set_variation( $variation );
	return $attribute;
}

function hws_sangens_pilot_source_product( int $product_id ): WC_Product {
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'simple' ) || 'publish' !== $product->get_status() ) {
		throw new RuntimeException( 'Expected published simple product: ' . $product_id );
	}
	return $product;
}

$cli_args   = isset( $args ) && is_array( $args ) ? $args : ( $argv ?? [] );
$mode       = in_array( 'run', $cli_args, true ) ? 'run' : 'dry-run';
$merge_key  = 'sangens-l-ceramic-v1';
$source_ids = [ 249595, 249596, 249597 ];
$sources    = array_map( 'hws_sangens_pilot_source_product', $source_ids );
$report     = [
	'mode'       => $mode,
	'merge_key'  => $merge_key,
	'sources'    => [],
	'created_id' => null,
	'variation_ids' => [],
];

$power_terms = [];
foreach ( $sources as $source ) {
	$terms = wp_get_object_terms( $source->get_id(), 'pa_power', [ 'fields' => 'all' ] );
	if ( is_wp_error( $terms ) || 1 !== count( $terms ) ) {
		throw new RuntimeException( 'Expected one pa_power term for product: ' . $source->get_id() );
	}
	$term = $terms[0];
	$power_terms[ (int) $term->term_id ] = $term;
	$report['sources'][] = [
		'id'        => $source->get_id(),
		'sku'       => $source->get_sku(),
		'price'     => $source->get_price(),
		'power'     => $term->name,
		'old_slug'  => $source->get_slug(),
	];
}

$existing_ids = get_posts(
	[
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_hws_merge_key',
		'meta_value'     => $merge_key,
	]
);

if ( 'dry-run' === $mode ) {
	if ( ! empty( $existing_ids ) ) {
		$report['created_id'] = (int) $existing_ids[0];
	}
	hws_sangens_pilot_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	exit( 0 );
}

$base = $sources[0];
$parent = ! empty( $existing_ids ) ? wc_get_product( (int) $existing_ids[0] ) : new WC_Product_Variable();
if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
	throw new RuntimeException( 'Expected variable parent for merge key: ' . $merge_key );
}
$parent->set_name( 'Электрическая печь для бани и сауны Sangens L Ceramic' );
$parent->set_slug( 'sangens-l-ceramic' );
$parent->set_status( 'publish' );
$parent->set_catalog_visibility( 'hidden' );
$parent->set_description( $base->get_description() );
$parent->set_short_description( $base->get_short_description() );
$parent->set_image_id( $base->get_image_id() );
$parent->set_gallery_image_ids( $base->get_gallery_image_ids() );
$parent->set_category_ids( $base->get_category_ids() );
$parent_id = $parent->save();

$brand_terms = wp_get_object_terms( $base->get_id(), 'product_brand', [ 'fields' => 'ids' ] );
if ( ! is_wp_error( $brand_terms ) && ! empty( $brand_terms ) ) {
	wp_set_object_terms( $parent_id, $brand_terms, 'product_brand' );
}

$parent_attributes = [];
foreach ( $base->get_attributes() as $attribute ) {
	if ( ! $attribute instanceof WC_Product_Attribute ) {
		continue;
	}
	$name = (string) $attribute->get_name();
	if ( 'pa_power' === $name ) {
		continue;
	}
	$attribute->set_visible( true );
	$attribute->set_variation( false );
	$parent_attributes[ $name ] = $attribute;
}

$power_ids = array_keys( $power_terms );
wp_set_object_terms( $parent_id, $power_ids, 'pa_power' );
$parent_attributes['pa_power'] = hws_sangens_pilot_attribute( 'pa_power', $power_ids, true );
$parent->set_attributes( $parent_attributes );
$parent->set_default_attributes( [ 'pa_power' => $power_terms[ $power_ids[0] ]->slug ] );
$parent->save();

foreach ( $sources as $source ) {
	$existing_variations = get_posts(
		[
			'post_type'      => 'product_variation',
			'post_parent'    => $parent_id,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_hws_merge_source_product_id',
			'meta_value'     => $source->get_id(),
		]
	);
	if ( ! empty( $existing_variations ) ) {
		$report['variation_ids'][] = (int) $existing_variations[0];
		continue;
	}

	$terms = wp_get_object_terms( $source->get_id(), 'pa_power', [ 'fields' => 'all' ] );
	$term  = $terms[0];
	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $parent_id );
	$variation->set_status( 'publish' );
	// SKU остаётся на прежней опубликованной карточке до переключения URL на 301.
	// После этого этапа его перенесёт финальный скрипт без нарушения уникальности WooCommerce.
	$variation->set_regular_price( $source->get_regular_price() );
	$variation->set_sale_price( $source->get_sale_price() );
	$variation->set_price( $source->get_price() );
	$variation->set_image_id( $source->get_image_id() );
	$variation->set_attributes( [ 'pa_power' => $term->slug ] );
	$variation_id = $variation->save();
	update_post_meta( $variation_id, '_hws_merge_source_product_id', $source->get_id() );
	update_post_meta( $variation_id, '_hws_source_sku', $source->get_sku() );
	$report['variation_ids'][] = $variation_id;
}

update_post_meta( $parent_id, '_hws_merge_key', $merge_key );
update_post_meta( $parent_id, '_hws_merge_source_ids', wp_json_encode( $source_ids ) );
WC_Product_Variable::sync( $parent_id );
wc_delete_product_transients( $parent_id );

$report['created_id'] = $parent_id;
hws_sangens_pilot_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
