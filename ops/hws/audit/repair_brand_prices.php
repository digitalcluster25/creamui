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

function hws_repair_latest_rate(): float {
	global $wpdb;
	$rate = (float) $wpdb->get_var(
		"SELECT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = '_hws_usd_rub_rate' AND meta_value > 0
		 ORDER BY post_id DESC LIMIT 1"
	);
	return $rate > 0 ? $rate : 71.209;
}

function hws_repair_normalize_bool( $value ): bool {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( null === $value ) {
		return false;
	}
	$text = strtolower( trim( (string) $value ) );
	return in_array( $text, [ '1', 'true', 'yes', 'y', 'да' ], true );
}

function hws_repair_source_price_on_request( array $row ): bool {
	foreach ( [ 'price_on_request_expected', 'price_on_request' ] as $key ) {
		if ( array_key_exists( $key, $row ) && null !== $row[ $key ] && '' !== trim( (string) $row[ $key ] ) ) {
			return hws_repair_normalize_bool( $row[ $key ] );
		}
	}

	$price = isset( $row['price'] ) ? (string) $row['price'] : '';
	if ( '' === $price && isset( $row['base_price'] ) ) {
		$price = (string) $row['base_price'];
	}
	if ( '' === $price && isset( $row['base_price_rub'] ) ) {
		$price = (string) $row['base_price_rub'];
	}
	$price = trim( $price );

	if ( '' === $price ) {
		return true;
	}

	return (float) str_replace( ',', '.', $price ) <= 0;
}

$cli_args   = isset( $args ) && is_array( $args ) ? $args : [];
$env_brand  = (string) getenv( 'HWS_REPAIR_BRAND' );
$brand_slug = sanitize_title( $env_brand !== '' ? $env_brand : (string) ( $cli_args[0] ?? '' ) );
$dry_run    = in_array( 'dry-run', $cli_args, true );

if ( '' === $brand_slug ) {
	throw new RuntimeException( 'Usage: wp eval-file repair_brand_prices.php <brand-slug> [dry-run]' );
}

$audit_dir   = hws_repair_audit_dir();
$diff_path   = $audit_dir . '/diff/' . $brand_slug . '.json';
$source_path = $audit_dir . '/source/' . $brand_slug . '.json';

$diff   = hws_repair_load_json( $diff_path );
$source = hws_repair_load_json( $source_path );
$rate   = hws_repair_latest_rate();

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
		return in_array( 'price_issue', (array) $entry['reasons'], true );
	}
);

hws_repair_log(
	sprintf(
		'brand=%s dry_run=%s total_broken=%d price_broken=%d rate=%.3f',
		$brand_slug,
		$dry_run ? 'yes' : 'no',
		count( $diff['matched_but_broken'] ?? [] ),
		count( $to_repair ),
		$rate
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

	if ( $product_id <= 0 ) {
		$product_id = hws_repair_find_product_by_sku( $sku );
	}
	if ( $product_id <= 0 ) {
		hws_repair_warn( "Cannot find WP product for SKU: $sku" );
		$skipped++;
		continue;
	}

	$price_on_request = hws_repair_source_price_on_request( $source_row );
	$base_price_rub   = (int) ( $source_row['base_price_rub'] ?? 0 );
	$base_price_usd   = $base_price_rub > 0 ? round( $base_price_rub / $rate ) : 0;

	$prefix = $dry_run ? 'DRY ' : '';

	if ( ! $dry_run ) {
		if ( $price_on_request ) {
			update_post_meta( $product_id, '_price', '' );
			update_post_meta( $product_id, '_regular_price', '' );
			update_post_meta( $product_id, '_hws_price_on_request', 'yes' );
		} else {
			update_post_meta( $product_id, '_price', $base_price_usd );
			update_post_meta( $product_id, '_regular_price', $base_price_usd );
			update_post_meta( $product_id, '_hws_price_on_request', 'no' );
			update_post_meta( $product_id, '_hws_base_price_rub', $base_price_rub );
			update_post_meta( $product_id, '_hws_price_currency', 'USD' );
			update_post_meta( $product_id, '_hws_usd_rub_rate', $rate );
		}

		clean_post_cache( $product_id );
		wc_delete_product_transients( $product_id );
	}

	hws_repair_log(
		sprintf(
			'%sREPAIR %s (id=%d) price_on_request=%s base_price_rub=%d base_price_usd=%d',
			$prefix,
			$sku,
			$product_id,
			$price_on_request ? 'yes' : 'no',
			$base_price_rub,
			$base_price_usd
		)
	);
	$repaired++;
}

hws_repair_log( "Done. repaired=$repaired skipped=$skipped dry_run=" . ( $dry_run ? 'yes' : 'no' ) );
