<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'hws_repair_log' ) ) {
	function hws_repair_log( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
			return;
		}
		echo $message . PHP_EOL;
	}
}

if ( ! function_exists( 'hws_repair_warn' ) ) {
	function hws_repair_warn( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( $message );
			return;
		}
		hws_repair_log( 'WARN: ' . $message );
	}
}

function hws_repair_audit_dir(): string {
	$override = getenv( 'HWS_AUDIT_DIR' );
	if ( is_string( $override ) && '' !== trim( $override ) ) {
		return rtrim( trim( $override ), '/' );
	}
	return rtrim( ABSPATH, '/' ) . '/data/audit';
}

function hws_repair_load_json( string $path ): array {
	if ( ! file_exists( $path ) ) {
		throw new RuntimeException( 'File not found: ' . $path );
	}
	$data = json_decode( file_get_contents( $path ), true );
	if ( ! is_array( $data ) ) {
		throw new RuntimeException( 'Invalid JSON in: ' . $path );
	}
	return $data;
}

function hws_repair_find_product_by_sku( string $sku ): int {
	global $wpdb;
	$post_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
			$sku
		)
	);
	return $post_id;
}

function hws_repair_ensure_term( string $taxonomy, string $slug, string $name ): ?int {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		hws_repair_warn( "Taxonomy does not exist: $taxonomy" );
		return null;
	}

	$existing = get_term_by( 'slug', $slug, $taxonomy );
	if ( $existing ) {
		return (int) $existing->term_id;
	}

	$existing = get_term_by( 'name', $name, $taxonomy );
	if ( $existing ) {
		return (int) $existing->term_id;
	}

	$result = wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
	if ( is_wp_error( $result ) ) {
		hws_repair_warn( "Cannot create term $slug in $taxonomy: " . $result->get_error_message() );
		return null;
	}
	return (int) $result['term_id'];
}

function hws_repair_apply_attributes( int $product_id, array $attribute_values, bool $dry_run ): int {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		hws_repair_warn( "Product not found: $product_id" );
		return 0;
	}

	$existing_attrs = $product->get_attributes();
	$changed        = 0;

	foreach ( $attribute_values as $taxonomy => $raw_value ) {
		$taxonomy = (string) $taxonomy;
		$value    = (string) $raw_value;

		if ( '' === $taxonomy || '' === $value ) {
			continue;
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			hws_repair_warn( "Skip unknown taxonomy: $taxonomy" );
			continue;
		}

		$slug    = sanitize_title( $value );
		$term_id = hws_repair_ensure_term( $taxonomy, $slug, $value );
		if ( ! $term_id ) {
			continue;
		}

		if ( ! $dry_run ) {
			wp_set_object_terms( $product_id, [ $term_id ], $taxonomy );
		}

		$attr = new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attr->set_name( $taxonomy );
		$attr->set_options( [ $term_id ] );
		$attr->set_visible( true );
		$attr->set_variation( false );

		$existing_attrs[ $taxonomy ] = $attr;
		$changed++;
	}

	if ( $changed > 0 && ! $dry_run ) {
		$product->set_attributes( $existing_attrs );
		$result = $product->save();
		if ( is_wp_error( $result ) ) {
			hws_repair_warn( "Save failed for product $product_id: " . $result->get_error_message() );
			return 0;
		}
	}

	return $changed;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$cli_args   = isset( $args ) && is_array( $args ) ? $args : [];
$env_brand  = (string) getenv( 'HWS_REPAIR_BRAND' );
$brand_slug = sanitize_title( $env_brand !== '' ? $env_brand : (string) ( $cli_args[0] ?? '' ) );
$dry_run    = in_array( 'dry-run', $cli_args, true );

if ( '' === $brand_slug ) {
	throw new RuntimeException( 'Usage: wp eval-file repair_brand_attributes.php <brand-slug> [dry-run]' );
}

$audit_dir   = hws_repair_audit_dir();
$diff_path   = $audit_dir . '/diff/' . $brand_slug . '.json';
$source_path = $audit_dir . '/source/' . $brand_slug . '.json';

$diff   = hws_repair_load_json( $diff_path );
$source = hws_repair_load_json( $source_path );

// Build source index: sku → attribute_values
$source_rows = $source['products'] ?? [];
$source_by_sku = [];
foreach ( $source_rows as $row ) {
	$sku = strtoupper( trim( (string) ( $row['source_sku'] ?? '' ) ) );
	if ( $sku ) {
		$source_by_sku[ $sku ] = $row;
	}
}

// Find broken products with missing_attributes
$to_repair = array_filter(
	$diff['matched_but_broken'] ?? [],
	static function ( array $entry ): bool {
		return in_array( 'missing_attributes', (array) $entry['reasons'], true );
	}
);

hws_repair_log(
	sprintf(
		'brand=%s dry_run=%s total_broken=%d to_repair=%d',
		$brand_slug,
		$dry_run ? 'yes' : 'no',
		count( $diff['matched_but_broken'] ?? [] ),
		count( $to_repair )
	)
);

$repaired = 0;
$skipped  = 0;

foreach ( $to_repair as $entry ) {
	$sku        = strtoupper( trim( (string) ( $entry['sku'] ?? '' ) ) );
	$product_id = (int) ( $entry['woo_product_id'] ?? 0 );
	$source_row = $source_by_sku[ $sku ] ?? null;

	if ( ! $source_row ) {
		hws_repair_warn( "SKU not in source manifest: $sku" );
		$skipped++;
		continue;
	}

	$attribute_values = (array) ( $source_row['attribute_values'] ?? [] );
	if ( empty( $attribute_values ) ) {
		hws_repair_warn( "No attribute_values in source for $sku" );
		$skipped++;
		continue;
	}

	if ( $product_id <= 0 ) {
		$product_id = hws_repair_find_product_by_sku( $sku );
	}

	if ( $product_id <= 0 ) {
		hws_repair_warn( "Cannot find WP product for SKU: $sku" );
		$skipped++;
		continue;
	}

	$changed = hws_repair_apply_attributes( $product_id, $attribute_values, $dry_run );
	$prefix  = $dry_run ? 'DRY ' : '';
	hws_repair_log( "{$prefix}REPAIR $sku (id=$product_id) attributes_applied=$changed" );
	$repaired++;
}

hws_repair_log( "Done. repaired=$repaired skipped=$skipped dry_run=" . ( $dry_run ? 'yes' : 'no' ) );
