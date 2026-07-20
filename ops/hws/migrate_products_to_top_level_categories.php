<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// Move products from nested product_cat terms to their single top-level term.
// Existing product attributes are intentionally left untouched.
// Run with:
//   wp --allow-root eval-file migrate_products_to_top_level_categories.php dry-run
//   HWS_TOP_LEVEL_CATEGORY_MODE=run wp --allow-root eval-file migrate_products_to_top_level_categories.php run

if ( ! function_exists( 'hws_top_level_log' ) ) {
	function hws_top_level_log( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
			return;
		}
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_top_level_warn' ) ) {
	function hws_top_level_warn( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( $message );
			return;
		}
		hws_top_level_log( 'WARN: ' . $message );
	}
}

function hws_top_level_category( WP_Term $term ): ?WP_Term {
	$current = $term;
	$visited = [];

	while ( $current->parent > 0 ) {
		if ( isset( $visited[ $current->term_id ] ) ) {
			return null;
		}
		$visited[ $current->term_id ] = true;
		$parent = get_term( (int) $current->parent, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			return null;
		}
		$current = $parent;
	}

	return $current;
}

function hws_top_level_audit_path(): string {
	$dir = rtrim( ABSPATH, '/' ) . '/data/audit';
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	return $dir . '/top-level-category-migration-' . gmdate( 'Ymd-His' ) . '.json';
}

$cli_args = isset( $args ) && is_array( $args ) ? $args : [];
$mode     = (string) ( $cli_args[0] ?? getenv( 'HWS_TOP_LEVEL_CATEGORY_MODE' ) ?: 'dry-run' );
$dry_run  = 'run' !== $mode;

$product_ids = get_posts(
	[
		'post_type'      => 'product',
		'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

$audit = [
	'mode'       => $dry_run ? 'dry-run' : 'run',
	'generatedAt' => gmdate( 'c' ),
	'productCount' => count( $product_ids ),
	'changed'    => [],
	'skipped'    => [],
];

foreach ( $product_ids as $product_id ) {
	$terms = wp_get_object_terms( (int) $product_id, 'product_cat', [ 'orderby' => 'term_id', 'order' => 'ASC' ] );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		$audit['skipped'][] = [
			'productId' => (int) $product_id,
			'reason'    => 'no_product_category',
		];
		hws_top_level_warn( 'No product category: product_id=' . (int) $product_id );
		continue;
	}

	$roots = [];
	foreach ( $terms as $term ) {
		$root = hws_top_level_category( $term );
		if ( ! $root ) {
			$audit['skipped'][] = [
				'productId' => (int) $product_id,
				'reason'    => 'invalid_category_parent_chain',
			];
			continue 2;
		}
		$roots[ (int) $root->term_id ] = $root;
	}

	if ( count( $roots ) !== 1 ) {
		$audit['skipped'][] = [
			'productId' => (int) $product_id,
			'reason'    => 'multiple_top_level_categories',
			'categories' => array_map( static fn( WP_Term $term ): string => $term->slug, $terms ),
			'roots'     => array_map( static fn( WP_Term $term ): string => $term->slug, $roots ),
		];
		hws_top_level_warn( 'Multiple top-level categories, skipped: product_id=' . (int) $product_id );
		continue;
	}

	$root       = reset( $roots );
	$current_ids = array_map( static fn( WP_Term $term ): int => (int) $term->term_id, $terms );
	$root_id    = (int) $root->term_id;
	if ( count( $current_ids ) === 1 && $current_ids[0] === $root_id ) {
		continue;
	}

	$change = [
		'productId'       => (int) $product_id,
		'title'           => get_the_title( (int) $product_id ),
		'before'          => array_map( static fn( WP_Term $term ): string => $term->slug, $terms ),
		'after'           => [ $root->slug ],
		'rootTermId'      => $root_id,
		'preservedAttrs'  => array_values(
			array_filter(
				get_object_taxonomies( 'product', 'names' ),
				static fn( string $taxonomy ): bool => str_starts_with( $taxonomy, 'pa_' ) && ! empty( wp_get_object_terms( (int) $product_id, $taxonomy, [ 'fields' => 'ids' ] ) )
			)
		),
	];
	$audit['changed'][] = $change;

	if ( ! $dry_run ) {
		$result = wp_set_object_terms( (int) $product_id, [ $root_id ], 'product_cat', false );
		if ( is_wp_error( $result ) ) {
			$audit['skipped'][] = [
				'productId' => (int) $product_id,
				'reason'    => 'category_assignment_failed',
				'error'     => $result->get_error_message(),
			];
			continue;
		}
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( (int) $product_id );
		}
	}
}

$audit['changedCount'] = count( $audit['changed'] );
$audit['skippedCount'] = count( $audit['skipped'] );
$audit_path = hws_top_level_audit_path();
file_put_contents( $audit_path, wp_json_encode( $audit, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

hws_top_level_log(
	sprintf(
		'Done. mode=%s products=%d changed=%d skipped=%d audit=%s',
		$audit['mode'],
		$audit['productCount'],
		$audit['changedCount'],
		$audit['skippedCount'],
		$audit_path
	)
);
