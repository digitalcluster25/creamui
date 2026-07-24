<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$run = isset( $args[0] ) && 'run' === trim( (string) $args[0] );

function hws_gelendzhik_swatch_text( string $value ): string {
	$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
}

function hws_gelendzhik_swatch_map( string $url ): array {
	$response = wp_remote_get( $url, [ 'timeout' => 30 ] );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return [];
	}

	$html = wp_remote_retrieve_body( $response );
	$dom  = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );
	$has_class = "contains(concat(' ', normalize-space(@class), ' '), ' %s ')";
	$map = [];

	foreach ( $xpath->query( sprintf( "//*[ %s ]", sprintf( $has_class, 'product__params-item' ) ) ) as $group ) {
		$title = $xpath->query( sprintf( ".//*[ %s ]", sprintf( $has_class, 'radio-group__title' ) ), $group )->item( 0 );
		if ( ! $title || false === mb_stripos( hws_gelendzhik_swatch_text( $title->textContent ), 'кожух' ) ) {
			continue;
		}
		foreach ( $xpath->query( sprintf( ".//*[ %s ]", sprintf( $has_class, 'radio-group__item' ) ), $group ) as $item ) {
			$label_node = $xpath->query( sprintf( ".//*[ %s ]", sprintf( $has_class, 'radio-group__item-text' ) ), $item )->item( 0 );
			$image_node = $xpath->query( './/img[1]', $item )->item( 0 );
			if ( ! $label_node || ! $image_node ) {
				continue;
			}
			$label = hws_gelendzhik_swatch_text( $label_node->textContent );
			$src = trim( (string) $image_node->getAttribute( 'src' ) );
			if ( '' !== $label && '' !== $src ) {
				$map[ $label ] = esc_url_raw( wp_http_validate_url( $src ) ? $src : 'https://easysteam.ru' . $src );
			}
		}
	}

	return $map;
}

$products = get_posts( [
	'post_type' => 'product',
	'post_status' => 'any',
	'posts_per_page' => -1,
	'fields' => 'ids',
	'meta_query' => [ [ 'key' => '_hws_source_url', 'value' => 'https://easysteam.ru/products/show/', 'compare' => 'LIKE' ] ],
] );
$report = [ 'products' => count( $products ), 'updated_products' => 0, 'updated_swatches' => 0, 'failed_products' => 0, 'dry_run' => ! $run ];

foreach ( $products as $product_id ) {
	$source_url = (string) get_post_meta( $product_id, '_hws_source_url', true );
	$swatches = hws_gelendzhik_swatch_map( $source_url );
	if ( empty( $swatches ) ) {
		$report['failed_products']++;
		continue;
	}
	$payload = json_decode( (string) get_post_meta( $product_id, '_hws_source_payload', true ), true );
	if ( ! is_array( $payload ) ) {
		$report['failed_products']++;
		continue;
	}
	$changed = 0;
	foreach ( ( $payload['option_groups'] ?? [] ) as &$group ) {
		if ( false === mb_stripos( (string) ( $group['name'] ?? '' ), 'кожух' ) ) {
			continue;
		}
		foreach ( ( $group['values'] ?? [] ) as &$value ) {
			$name = hws_gelendzhik_swatch_text( (string) ( $value['name'] ?? '' ) );
			if ( isset( $swatches[ $name ] ) && $swatches[ $name ] !== ( $value['swatch_image'] ?? '' ) ) {
				$value['swatch_image'] = $swatches[ $name ];
				$changed++;
			}
		}
		unset( $value );
	}
	unset( $group );
	if ( 0 === $changed ) {
		continue;
	}
	$report['updated_products']++;
	$report['updated_swatches'] += $changed;
	if ( $run ) {
		update_post_meta( $product_id, '_hws_source_payload', wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		wc_delete_product_transients( $product_id );
	}
}

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
