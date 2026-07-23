<?php
/**
 * One recoverable production pilot: Sangens L Ceramic (3 simple products ->
 * 1 variable product with 3 variations). Default mode is read-only.
 *
 * Run with WP-CLI:
 *   wp eval-file run_sangens_l_pilot.php
 * Execute only after a fresh backup:
 *   wp eval-file run_sangens_l_pilot.php -- --execute --confirm=sangens-l-ceramic
 */

if ( ! defined( 'ABSPATH' ) ) {
	$bootstrap_path = '/var/www/html/wp-load.php';
	if ( ! is_readable( $bootstrap_path ) ) {
		throw new RuntimeException( 'WordPress bootstrap is unavailable.' );
	}
	require_once $bootstrap_path;
}

function hws_sangens_l_pilot_log( string $message ): void {
	echo $message . PHP_EOL;
}

function hws_sangens_l_pilot_attribute( string $taxonomy, array $term_ids, bool $variation ): WC_Product_Attribute {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
	$attribute->set_name( $taxonomy );
	$attribute->set_options( array_values( array_unique( array_map( 'intval', $term_ids ) ) ) );
	$attribute->set_visible( true );
	$attribute->set_variation( $variation );
	return $attribute;
}

function hws_sangens_l_pilot_source( int $id ): WC_Product {
	$product = wc_get_product( $id );
	if ( ! $product || ! $product->is_type( 'simple' ) || 'publish' !== $product->get_status() ) {
		throw new RuntimeException( "Expected published simple source product {$id}." );
	}
	return $product;
}

$cli_args = isset( $args ) && is_array( $args ) ? $args : ( $argv ?? [] );
$execute  = in_array( '--execute', $cli_args, true );
$confirmed = in_array( '--confirm=sangens-l-ceramic', $cli_args, true );
$source_ids = [ 249595, 249596, 249597 ];
$merge_key = 'sangens-l-ceramic-v1';
$target_slug = 'sangens-l-ceramic';
$sources = array_map( 'hws_sangens_l_pilot_source', $source_ids );

$report = [
	'mode' => $execute ? 'execute' : 'dry-run',
	'merge_key' => $merge_key,
	'target_slug' => $target_slug,
	'sources' => [],
	'parent_id' => null,
	'variation_ids' => [],
];

$taxonomy_terms = [ 'pa_power' => [], 'pa_steam-room-volume' => [] ];
$skus = [];
foreach ( $sources as $source ) {
	$source_terms = [];
	foreach ( array_keys( $taxonomy_terms ) as $taxonomy ) {
		$terms = wp_get_object_terms( $source->get_id(), $taxonomy, [ 'fields' => 'all' ] );
		if ( is_wp_error( $terms ) || 1 !== count( $terms ) ) {
			throw new RuntimeException( "Expected one {$taxonomy} term for product {$source->get_id()}." );
		}
		$term = $terms[0];
		$taxonomy_terms[ $taxonomy ][ (int) $term->term_id ] = $term;
		$source_terms[ $taxonomy ] = $term;
	}
	$sku = $source->get_sku();
	if ( '' === $sku || in_array( $sku, $skus, true ) ) {
		throw new RuntimeException( "Expected distinct non-empty source SKU for product {$source->get_id()}." );
	}
	$skus[] = $sku;
	$report['sources'][] = [
		'id' => $source->get_id(),
		'slug' => $source->get_slug(),
		'sku' => $sku,
		'price' => $source->get_price(),
		'power' => $source_terms['pa_power']->slug,
		'volume' => $source_terms['pa_steam-room-volume']->slug,
	];
}

$existing = get_posts(
	[
		'post_type' => 'product',
		'post_status' => 'any',
		'posts_per_page' => 1,
		'fields' => 'ids',
		'meta_key' => '_hws_merge_key',
		'meta_value' => $merge_key,
	]
);
if ( ! empty( $existing ) ) {
	$report['parent_id'] = (int) $existing[0];
}

if ( $execute && ! $confirmed ) {
	throw new RuntimeException( 'Execution requires --confirm=sangens-l-ceramic.' );
}
if ( ! $execute || ! empty( $existing ) ) {
	hws_sangens_l_pilot_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	exit( 0 );
}

$base = $sources[0];
$parent = new WC_Product_Variable();
$parent->set_name( 'Электрическая печь для бани и сауны Sangens L Ceramic' );
$parent->set_slug( $target_slug );
$parent->set_status( 'draft' );
$parent->set_catalog_visibility( 'visible' );
$parent->set_description( $base->get_description() );
$parent->set_short_description( $base->get_short_description() );
$parent->set_image_id( $base->get_image_id() );
$parent->set_gallery_image_ids( $base->get_gallery_image_ids() );
$parent->set_category_ids( $base->get_category_ids() );
$parent_id = $parent->save();

$parent_attributes = [];
foreach ( $base->get_attributes() as $attribute ) {
	if ( $attribute instanceof WC_Product_Attribute && ! in_array( $attribute->get_name(), array_keys( $taxonomy_terms ), true ) ) {
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$parent_attributes[ $attribute->get_name() ] = $attribute;
	}
}
foreach ( $taxonomy_terms as $taxonomy => $terms ) {
	$term_ids = array_keys( $terms );
	wp_set_object_terms( $parent_id, $term_ids, $taxonomy );
	$parent_attributes[ $taxonomy ] = hws_sangens_l_pilot_attribute( $taxonomy, $term_ids, true );
}
$parent->set_attributes( $parent_attributes );
$parent->set_default_attributes(
	[
		'pa_power' => reset( $taxonomy_terms['pa_power'] )->slug,
		'pa_steam-room-volume' => reset( $taxonomy_terms['pa_steam-room-volume'] )->slug,
	]
);
$parent->save();

$redirects = get_option( 'hws_product_redirects', [] );
$redirects = is_array( $redirects ) ? $redirects : [];
foreach ( $sources as $source ) {
	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $parent_id );
	$variation->set_status( 'publish' );
	$variation->set_regular_price( $source->get_regular_price() );
	$variation->set_sale_price( $source->get_sale_price() );
	$variation->set_price( $source->get_price() );
	$variation->set_image_id( $source->get_image_id() );
	$source_power = wp_get_object_terms( $source->get_id(), 'pa_power', [ 'fields' => 'all' ] )[0];
	$source_volume = wp_get_object_terms( $source->get_id(), 'pa_steam-room-volume', [ 'fields' => 'all' ] )[0];
	$variation->set_attributes( [ 'pa_power' => $source_power->slug, 'pa_steam-room-volume' => $source_volume->slug ] );
	$variation_id = $variation->save();
	update_post_meta( $variation_id, '_hws_merge_source_product_id', $source->get_id() );
	$report['variation_ids'][ $source->get_id() ] = $variation_id;
	$redirects[ $source->get_slug() ] = $target_slug;
}

update_post_meta( $parent_id, '_hws_merge_key', $merge_key );
update_post_meta( $parent_id, '_hws_merge_source_ids', wp_json_encode( $source_ids ) );
WC_Product_Variable::sync( $parent_id );
wc_delete_product_transients( $parent_id );
update_option( 'hws_product_redirects', $redirects, false );

foreach ( $sources as $source ) {
	$variation_id = $report['variation_ids'][ $source->get_id() ];
	if ( ! wp_trash_post( $source->get_id() ) ) {
		throw new RuntimeException( "Could not move source {$source->get_id()} to Trash." );
	}
	$variation = wc_get_product( $variation_id );
	$variation->set_sku( $source->get_sku() );
	$variation->save();
}
$parent = wc_get_product( $parent_id );
$parent->set_status( 'publish' );
$parent->save();
$report['parent_id'] = $parent_id;
hws_sangens_l_pilot_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
