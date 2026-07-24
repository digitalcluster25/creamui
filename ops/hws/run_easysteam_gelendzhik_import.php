<?php

$mode       = $argv[1] ?? 'dry-run';
$payload    = $argv[2] ?? '/tmp/gelendzhik-import-payload.json';
$price_mode = $argv[3] ?? 'source-rub';
$script     = '/tmp/import_easysteam_gelendzhik.php';

require '/var/www/html/wp-load.php';

ob_start();
$args = [ $payload, $mode, '0', $price_mode ];
require $script;
$raw = trim( ob_get_clean() );

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
	echo $raw . PHP_EOL;
	exit( 0 );
}

if ( 'dry-run' === $mode ) {
	$summary = [
		'mode'               => $data['mode'] ?? 'dry-run',
		'products_total'     => $data['products_total'] ?? 0,
		'products_checked'   => $data['products_checked'] ?? 0,
		'variants_total'     => $data['variants_total'] ?? 0,
		'variants_checked'   => $data['variants_checked'] ?? 0,
		'duplicate_articles' => count( $data['duplicate_articles'] ?? [] ),
		'missing_prices'     => count( $data['missing_prices'] ?? [] ),
		'missing_articles'   => count( $data['missing_articles'] ?? [] ),
		'missing_short_desc' => count( $data['missing_short_desc'] ?? [] ),
		'missing_raw_tabs'   => count( $data['missing_raw_tabs'] ?? [] ),
	];
	foreach ( $summary as $key => $value ) {
		echo $key . '=' . $value . PHP_EOL;
	}
	exit( 0 );
}

$products        = $data['products'] ?? [];
$variation_count = 0;
foreach ( $products as $product ) {
	$variation_count += count( $product['variation_ids'] ?? [] );
}

$summary = [
	'mode'                => $data['mode'] ?? 'run',
	'products_imported'   => count( $products ),
	'variations_imported' => $variation_count,
	'product_ids'         => implode( ',', array_values( array_map( static fn( $product ) => (int) ( $product['product_id'] ?? 0 ), $products ) ) ),
	'first_product_id'    => (int) ( $products[0]['product_id'] ?? 0 ),
];
foreach ( $summary as $key => $value ) {
	echo $key . '=' . $value . PHP_EOL;
}
