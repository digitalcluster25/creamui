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
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
			$sku
		)
	);
}

function hws_repair_sideload_image( string $url, int $product_id ): int {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, $product_id, null, 'id' );

	if ( is_wp_error( $attachment_id ) ) {
		hws_repair_warn( 'Sideload failed for ' . $url . ': ' . $attachment_id->get_error_message() );
		return 0;
	}

	return (int) $attachment_id;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$cli_args   = isset( $args ) && is_array( $args ) ? $args : [];
$env_brand  = (string) getenv( 'HWS_REPAIR_BRAND' );
$brand_slug = sanitize_title( $env_brand !== '' ? $env_brand : (string) ( $cli_args[0] ?? '' ) );
$dry_run    = in_array( 'dry-run', $cli_args, true );

if ( '' === $brand_slug ) {
	throw new RuntimeException( 'Usage: wp eval-file repair_brand_media.php <brand-slug> [dry-run]' );
}

$audit_dir   = hws_repair_audit_dir();
$diff_path   = $audit_dir . '/diff/' . $brand_slug . '.json';
$source_path = $audit_dir . '/source/' . $brand_slug . '.json';

$diff   = hws_repair_load_json( $diff_path );
$source = hws_repair_load_json( $source_path );

$source_by_sku = [];
foreach ( $source['products'] ?? [] as $row ) {
	$sku = strtoupper( trim( (string) ( $row['source_sku'] ?? '' ) ) );
	if ( $sku ) {
		$source_by_sku[ $sku ] = $row;
	}
}

$to_repair = array_filter(
	$diff['matched_but_broken'] ?? [],
	static function ( array $entry ): bool {
		return in_array( 'missing_image', (array) $entry['reasons'], true );
	}
);

hws_repair_log(
	sprintf(
		'brand=%s dry_run=%s total_broken=%d media_broken=%d',
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

	$image_url = (string) ( $source_row['main_image'] ?? '' );
	if ( '' === $image_url ) {
		hws_repair_warn( "Source has no main_image for $sku, skipping" );
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

	$prefix = $dry_run ? 'DRY ' : '';

	if ( ! $dry_run ) {
		$attachment_id = hws_repair_sideload_image( $image_url, $product_id );
		if ( ! $attachment_id ) {
			$skipped++;
			continue;
		}

		set_post_thumbnail( $product_id, $attachment_id );
		wc_delete_product_transients( $product_id );

		hws_repair_log( "REPAIR $sku (id=$product_id) image=$attachment_id url=$image_url" );
	} else {
		hws_repair_log( "DRY REPAIR $sku (id=$product_id) would download url=$image_url" );
	}

	$repaired++;
}

hws_repair_log( "Done. repaired=$repaired skipped=$skipped dry_run=" . ( $dry_run ? 'yes' : 'no' ) );
