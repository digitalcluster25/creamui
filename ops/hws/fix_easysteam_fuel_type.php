<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// One-off: assign pa_fuel-type=drova to EasySteam wood stove products that
// have no fuel type term assigned. These are products where the EasySteam
// page had no "вид топлива" option group (pure wood stoves with no fuel
// choice), so the importer never extracted a fuel type.
//
// Targets only products in russian-bath-stoves with pa_equipment-type=wood-bath-stove
// or commercial-bath-stove that have zero pa_fuel-type terms.
//
// Run: HWS_FIX_MODE=run wp --allow-root eval-file fix_easysteam_fuel_type.php --path=/var/www/html

$dry_run = 'run' !== ( getenv( 'HWS_FIX_MODE' ) ?: 'dry-run' );

function esfuel_log( string $msg ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $msg );
	} else {
		echo $msg . PHP_EOL;
	}
}

// Equipment types that are definitively wood-burning (bath stoves without
// a fuel option group on their product page → all wood).
// bath-sauna-stove: Ялта residential wood stoves.
// commercial-bath-stove: Анапа К / Сочи К / Геленджик К / Домна К (all wood).
// commercial-bath-sauna-stove: Ялта К commercial (all wood).
$wood_equipment_types = [
	'wood-bath-stove',
	'bath-sauna-stove',
	'commercial-bath-stove',
	'commercial-bath-sauna-stove',
];

// Get term IDs for the wood equipment types.
$wood_equip_term_ids = [];
foreach ( $wood_equipment_types as $slug ) {
	$term = get_term_by( 'slug', $slug, 'pa_equipment-type' );
	if ( $term && ! is_wp_error( $term ) ) {
		$wood_equip_term_ids[] = (int) $term->term_id;
	} else {
		esfuel_log( "WARN: pa_equipment-type term not found: {$slug}" );
	}
}

if ( empty( $wood_equip_term_ids ) ) {
	esfuel_log( 'No equipment type terms found — abort.' );
	exit;
}

$easysteam_brand = get_term_by( 'slug', 'easysteam', 'product_brand' );
if ( ! $easysteam_brand || is_wp_error( $easysteam_brand ) ) {
	esfuel_log( 'EasySteam brand term not found — abort.' );
	exit;
}

// Get all EasySteam products with wood equipment types, then filter no-fuel in PHP
// (WP_Query NOT EXISTS on taxonomy is unreliable with other tax_query conditions).
$candidates = get_posts( [
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'tax_query' => [
		'relation' => 'AND',
		[
			'taxonomy' => 'product_brand',
			'terms'    => [ (int) $easysteam_brand->term_id ],
		],
		[
			'taxonomy' => 'pa_equipment-type',
			'terms'    => $wood_equip_term_ids,
			'operator' => 'IN',
		],
	],
] );

// Filter to those with no pa_fuel-type assigned.
$products = array_values( array_filter( $candidates, static function ( int $pid ): bool {
	$terms = wp_get_object_terms( $pid, 'pa_fuel-type', [ 'fields' => 'ids' ] );
	return is_array( $terms ) && empty( $terms );
} ) );

esfuel_log( 'EasySteam wood products without pa_fuel-type: ' . count( $products ) );

$drova_term = get_term_by( 'slug', 'drova', 'pa_fuel-type' );
if ( ! $drova_term || is_wp_error( $drova_term ) ) {
	esfuel_log( 'WARN: pa_fuel-type term "drova" not found, will create it' );
	if ( ! $dry_run ) {
		$created = wp_insert_term( 'Дрова', 'pa_fuel-type', [ 'slug' => 'drova' ] );
		$drova_id = is_array( $created ) ? (int) $created['term_id'] : 0;
	} else {
		$drova_id = 0;
	}
} else {
	$drova_id = (int) $drova_term->term_id;
}

esfuel_log( 'pa_fuel-type:drova term_id=' . $drova_id );

$assigned = 0;
foreach ( $products as $pid ) {
	$title = get_the_title( $pid );
	$series = wp_get_object_terms( $pid, 'pa_series', [ 'fields' => 'names' ] );
	$series_str = is_array( $series ) ? implode( ', ', $series ) : '';
	esfuel_log( ( $dry_run ? '[DRY] ' : '' ) . "ASSIGN drova → post_id={$pid} \"{$title}\" (series: {$series_str})" );
	if ( ! $dry_run && $drova_id > 0 ) {
		wp_set_object_terms( $pid, [ $drova_id ], 'pa_fuel-type', false );

		// Ensure pa_fuel-type is listed in _product_attributes meta.
		$pa = (array) get_post_meta( $pid, '_product_attributes', true );
		if ( empty( $pa['pa_fuel-type'] ) ) {
			$pa['pa_fuel-type'] = [
				'name'         => 'pa_fuel-type',
				'value'        => '',
				'position'     => 99,
				'is_visible'   => 1,
				'is_variation' => 0,
				'is_taxonomy'  => 1,
			];
			update_post_meta( $pid, '_product_attributes', $pa );
		}
		$assigned++;
	} else {
		$assigned++;
	}
}

esfuel_log( sprintf(
	'Done. assigned=%d dry_run=%s',
	$assigned,
	$dry_run ? 'yes' : 'no'
) );
