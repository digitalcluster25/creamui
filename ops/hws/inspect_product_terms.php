<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$product_id = isset( $args[0] ) ? (int) $args[0] : 0;
if ( $product_id <= 0 ) {
	echo "Usage: wp eval-file inspect_product_terms.php <product_id>\n";
	exit( 1 );
}

$taxonomies = [
	'pa_equipment-type',
	'pa_fuel-type',
	'pa_usage-class',
	'pa_room-type',
	'pa_series',
];

foreach ( $taxonomies as $taxonomy ) {
	$terms = wp_get_object_terms(
		$product_id,
		$taxonomy,
		[
			'fields' => 'all',
		]
	);

	if ( is_wp_error( $terms ) ) {
		echo $taxonomy . ': ERROR ' . $terms->get_error_message() . "\n";
		continue;
	}

	$out = [];
	foreach ( $terms as $term ) {
		$out[] = [
			'slug' => $term->slug,
			'name' => $term->name,
		];
	}

	echo $taxonomy . ': ' . wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
