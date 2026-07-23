<?php
/**
 * Recoverable EasySteam cladding batches. Each selected family contains four
 * simple products that differ only by the existing "тип облицовки" attribute.
 *
 * wp eval-file run_easysteam_anapa_k_pilot.php -- --group=sochi-k
 * wp eval-file run_easysteam_anapa_k_pilot.php -- --group=sochi-k --execute --confirm=easysteam-sochi-k
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

function hws_easysteam_anapa_k_attribute( string $taxonomy, array $term_ids ): WC_Product_Attribute {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
	$attribute->set_name( $taxonomy );
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

function hws_easysteam_anapa_k_term( string $taxonomy, string $slug ): WP_Term {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( "Expected existing {$taxonomy} term {$slug}." );
	}
	return $term;
}

$cli_args = isset( $args ) && is_array( $args ) ? $args : ( $argv ?? [] );
$execute = in_array( '--execute', $cli_args, true );
$group = '';
foreach ( $cli_args as $arg ) {
	if ( 0 === strpos( (string) $arg, '--group=' ) ) {
		$group = substr( (string) $arg, 8 );
	}
}
$groups = [
	'anapa-k' => [
		'name' => 'Дровяная печь EasySteam Анапа К',
		'slug' => 'easysteam-anapa-k',
		'sources' => [ 249002 => 'bez-oblicovki', 249003 => '3-storonniy-kamennyy-kozhuh', 249004 => 'polnyy-kamennyy-kozhuh', 249005 => 'nabornyy-kamennyy-kozhuh' ],
	],
	'sochi-k' => [
		'name' => 'Дровяная печь EasySteam Сочи К',
		'slug' => 'easysteam-sochi-k',
		'sources' => [ 249006 => 'bez-oblicovki', 249007 => '3-storonniy-kamennyy-kozhuh', 249008 => 'polnyy-kamennyy-kozhuh', 249009 => 'nabornyy-kamennyy-kozhuh' ],
	],
	'gelendzhik-k' => [
		'name' => 'Дровяная печь EasySteam Геленджик К',
		'slug' => 'easysteam-gelendzhik-k',
		'sources' => [ 249010 => 'bez-oblicovki', 249011 => '3-storonniy-kamennyy-kozhuh', 249012 => 'polnyy-kamennyy-kozhuh', 249013 => 'nabornyy-kamennyy-kozhuh' ],
	],
	'domna-k' => [
		'name' => 'Дровяная печь EasySteam Домна К',
		'slug' => 'easysteam-domna-k',
		'variation_taxonomies' => [ 'pa_cladding-type', 'pa_series' ],
		'sources' => [ 249014 => 'bez-oblicovki', 249015 => '3-storonniy-kamennyy-kozhuh', 249016 => 'polnyy-kamennyy-kozhuh', 249017 => 'nabornyy-kamennyy-kozhuh', 249018 => 'bez-oblicovki', 249019 => '3-storonniy-kamennyy-kozhuh', 249020 => 'polnyy-kamennyy-kozhuh', 249021 => 'nabornyy-kamennyy-kozhuh', 249022 => 'bez-oblicovki', 249023 => 'polnyy-kamennyy-kozhuh' ],
	],
	'vivarte' => [
		'name' => 'Электрическая печь EasySteam VIVARTE',
		'slug' => 'easysteam-vivarte',
		'variation_taxonomies' => [ 'pa_power', 'pa_cladding-material' ],
		'sources' => [ 248950 => '', 248951 => '', 248998 => '', 248999 => '', 249000 => '', 249001 => '' ],
		'source_variations' => [
			248950 => [ 'pa_power' => '6-kvt', 'pa_cladding-material' => 'steklo' ],
			248951 => [ 'pa_power' => '6-kvt', 'pa_cladding-material' => 'kamen' ],
			248998 => [ 'pa_power' => '9-kvt', 'pa_cladding-material' => 'steklo' ],
			248999 => [ 'pa_power' => '9-kvt', 'pa_cladding-material' => 'kamen' ],
			249000 => [ 'pa_power' => '12-kvt', 'pa_cladding-material' => 'steklo' ],
			249001 => [ 'pa_power' => '12-kvt', 'pa_cladding-material' => 'kamen' ],
		],
	],
];
if ( ! isset( $groups[ $group ] ) ) {
	throw new RuntimeException( 'Execution requires one supported --group: ' . implode( ', ', array_keys( $groups ) ) . '.' );
}
$config = $groups[ $group ];
$confirmed = in_array( '--confirm=easysteam-' . $group, $cli_args, true );
$merge_key = 'easysteam-' . $group . '-v1';
$target_slug = $config['slug'];
$source_map = $config['sources'];
$variation_taxonomies = $config['variation_taxonomies'] ?? [ 'pa_cladding-type' ];
$sources = [];
$source_variations = [];
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
	$source_variations[ $id ] = $config['source_variations'][ $id ] ?? [ 'pa_cladding-type' => $casing ];
	foreach ( $variation_taxonomies as $taxonomy ) {
		if ( isset( $source_variations[ $id ][ $taxonomy ] ) ) {
			continue;
		}
		$terms = wp_get_object_terms( $id, $taxonomy, [ 'fields' => 'all' ] );
		if ( is_wp_error( $terms ) || 1 !== count( $terms ) ) {
			throw new RuntimeException( "Expected one {$taxonomy} term for product {$id}." );
		}
		$source_variations[ $id ][ $taxonomy ] = $terms[0]->slug;
	}
	$report['sources'][] = [ 'id' => $id, 'slug' => $source->get_slug(), 'sku' => $sku, 'price' => $source->get_price(), 'variation_attributes' => $source_variations[ $id ] ];
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
$parent->set_name( $config['name'] );
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
	if ( $attribute instanceof WC_Product_Attribute && ! in_array( $attribute->get_name(), $variation_taxonomies, true ) ) {
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$parent_attributes[ $attribute->get_name() ] = $attribute;
	}
}
$default_attributes = [];
foreach ( $variation_taxonomies as $taxonomy ) {
	$terms = [];
	foreach ( $source_variations as $variation_values ) {
		$slug = $variation_values[ $taxonomy ];
		$terms[ $slug ] = hws_easysteam_anapa_k_term( $taxonomy, $slug );
	}
	wp_set_object_terms( $parent_id, array_map( static fn( WP_Term $term ): int => $term->term_id, $terms ), $taxonomy );
	$parent_attributes[ $taxonomy ] = hws_easysteam_anapa_k_attribute( $taxonomy, array_map( static fn( WP_Term $term ): int => $term->term_id, $terms ) );
	$default_attributes[ $taxonomy ] = reset( $source_variations )[ $taxonomy ];
}
$parent->set_attributes( $parent_attributes );
$parent->set_default_attributes( $default_attributes );
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
	$variation->set_attributes( $source_variations[ $id ] );
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
