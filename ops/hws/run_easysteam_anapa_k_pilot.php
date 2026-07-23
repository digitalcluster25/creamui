<?php
/**
 * Recoverable EasySteam pilot: four "Анапа К" simple products become one
 * variable product. Default mode only validates and prints the proposed move.
 *
 * wp eval-file run_easysteam_anapa_k_pilot.php
 * wp eval-file run_easysteam_anapa_k_pilot.php -- --execute --confirm=easysteam-anapa-k
 */

if ( ! defined( 'ABSPATH' ) ) {
	$bootstrap_path = '/var/www/html/wp-load.php';
	if ( ! is_readable( $bootstrap_path ) ) {
		throw new RuntimeException( 'WordPress bootstrap is unavailable.' );
	}
	require_once $bootstrap_path;
}

function hws_easysteam_anapa_k_log( string $message ): void {
	echo $message . PHP_EOL;
}

function hws_easysteam_anapa_k_attribute( array $term_ids ): WC_Product_Attribute {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_cladding-type' ) );
	$attribute->set_name( 'pa_cladding-type' );
	$attribute->set_options( array_values( array_unique( array_map( 'intval', $term_ids ) ) ) );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	return $attribute;
}

function hws_easysteam_anapa_k_source( int $id ): WC_Product {
	$product = wc_get_product( $id );
	if ( ! $product || ! $product->is_type( 'simple' ) || 'publish' !== $product->get_status() ) {
		throw new RuntimeException( "Expected published simple source product {$id}." );
	}
	return $product;
}

function hws_easysteam_anapa_k_term( string $slug ): WP_Term {
	$term = get_term_by( 'slug', $slug, 'pa_cladding-type' );
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( "Expected existing pa_cladding-type term {$slug}." );
	}
	return $term;
}

$cli_args = isset( $args ) && is_array( $args ) ? $args : ( $argv ?? [] );
$execute = in_array( '--execute', $cli_args, true );
$confirmed = in_array( '--confirm=easysteam-anapa-k', $cli_args, true );
$merge_key = 'easysteam-anapa-k-v1';
$target_slug = 'easysteam-anapa-k';
$source_map = [
	249002 => 'bez-oblicovki',
	249003 => '3-storonniy-kamennyy-kozhuh',
	249004 => 'polnyy-kamennyy-kozhuh',
	249005 => 'nabornyy-kamennyy-kozhuh',
];
$sources = [];
$skus = [];
$report = [ 'mode' => $execute ? 'execute' : 'dry-run', 'merge_key' => $merge_key, 'target_slug' => $target_slug, 'sources' => [], 'parent_id' => null, 'variation_ids' => [] ];

foreach ( $source_map as $id => $casing ) {
	$source = hws_easysteam_anapa_k_source( $id );
	$sku = $source->get_sku();
	if ( '' === $sku || in_array( $sku, $skus, true ) ) {
		throw new RuntimeException( "Expected distinct non-empty source SKU for product {$id}." );
	}
	$skus[] = $sku;
	$sources[ $id ] = $source;
	$report['sources'][] = [ 'id' => $id, 'slug' => $source->get_slug(), 'sku' => $sku, 'price' => $source->get_price(), 'cladding_type' => $casing ];
}

$existing = get_posts( [ 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_hws_merge_key', 'meta_value' => $merge_key ] );
if ( ! empty( $existing ) ) {
	$report['parent_id'] = (int) $existing[0];
}
if ( $execute && ! $confirmed ) {
	throw new RuntimeException( 'Execution requires --confirm=easysteam-anapa-k.' );
}
if ( ! $execute || ! empty( $existing ) ) {
	hws_easysteam_anapa_k_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	exit( 0 );
}

$base = reset( $sources );
$parent = new WC_Product_Variable();
$parent->set_name( 'Дровяная печь EasySteam Анапа К' );
$parent->set_slug( $target_slug );
$parent->set_status( 'draft' );
$parent->set_catalog_visibility( 'visible' );
$parent->set_description( $base->get_description() );
$parent->set_short_description( $base->get_short_description() );
$parent->set_image_id( $base->get_image_id() );
$parent->set_gallery_image_ids( $base->get_gallery_image_ids() );
$parent->set_category_ids( $base->get_category_ids() );
$parent->set_tag_ids( $base->get_tag_ids() );
$parent_id = $parent->save();

$parent_attributes = [];
foreach ( $base->get_attributes() as $attribute ) {
	if ( $attribute instanceof WC_Product_Attribute && 'pa_cladding-type' !== $attribute->get_name() ) {
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$parent_attributes[ $attribute->get_name() ] = $attribute;
	}
}
$terms = [];
foreach ( $source_map as $cladding_slug ) {
	$terms[ $cladding_slug ] = hws_easysteam_anapa_k_term( $cladding_slug );
}
wp_set_object_terms( $parent_id, array_map( static fn( WP_Term $term ): int => $term->term_id, $terms ), 'pa_cladding-type' );
$parent_attributes['pa_cladding-type'] = hws_easysteam_anapa_k_attribute( array_map( static fn( WP_Term $term ): int => $term->term_id, $terms ) );
$parent->set_attributes( $parent_attributes );
$parent->set_default_attributes( [ 'pa_cladding-type' => 'bez-oblicovki' ] );
$parent->save();

$redirects = get_option( 'hws_product_redirects', [] );
$redirects = is_array( $redirects ) ? $redirects : [];
foreach ( $sources as $id => $source ) {
	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $parent_id );
	$variation->set_status( 'publish' );
	$variation->set_regular_price( $source->get_regular_price() );
	$variation->set_sale_price( $source->get_sale_price() );
	$variation->set_price( $source->get_price() );
	$variation->set_image_id( $source->get_image_id() );
	$variation->set_attributes( [ 'pa_cladding-type' => $source_map[ $id ] ] );
	$variation_id = $variation->save();
	update_post_meta( $variation_id, '_hws_merge_source_product_id', $id );
	$report['variation_ids'][ $id ] = $variation_id;
	$redirects[ $source->get_slug() ] = $target_slug;
}
update_post_meta( $parent_id, '_hws_merge_key', $merge_key );
update_post_meta( $parent_id, '_hws_merge_source_ids', wp_json_encode( array_keys( $source_map ) ) );
WC_Product_Variable::sync( $parent_id );
wc_delete_product_transients( $parent_id );
update_option( 'hws_product_redirects', $redirects, false );

foreach ( $sources as $id => $source ) {
	if ( ! wp_trash_post( $id ) ) {
		throw new RuntimeException( "Could not move source {$id} to Trash." );
	}
	$variation = wc_get_product( $report['variation_ids'][ $id ] );
	$variation->set_sku( $source->get_sku() );
	$variation->save();
}
$parent = wc_get_product( $parent_id );
$parent->set_status( 'publish' );
$parent->save();
$report['parent_id'] = $parent_id;
hws_easysteam_anapa_k_log( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
