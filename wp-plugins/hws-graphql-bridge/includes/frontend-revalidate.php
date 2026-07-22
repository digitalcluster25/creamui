<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hws_graphql_bridge_get_frontend_revalidate_url(): string {
	$base = getenv( 'HWS_FRONTEND_URL' );
	if ( ! is_string( $base ) || '' === trim( $base ) ) {
		$base = 'https://hws.shopping';
	}

	return rtrim( $base, '/' ) . '/api/revalidate-catalog';
}

function hws_graphql_bridge_trigger_frontend_revalidate(): void {
	$url    = hws_graphql_bridge_get_frontend_revalidate_url();
	$secret = getenv( 'NEXT_REVALIDATE_SECRET' );

	$args = [
		'timeout'  => 5,
		'blocking' => false,
		'headers'  => [
			'content-type' => 'application/json',
		],
		'body'     => wp_json_encode(
			[
				'source' => 'wordpress',
			]
		),
	];

	if ( is_string( $secret ) && '' !== trim( $secret ) ) {
		$args['headers']['x-revalidate-secret'] = $secret;
	}

	wp_remote_post( $url, $args );
}

function hws_graphql_bridge_schedule_frontend_revalidate( $term_id = 0 ): void {
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		hws_graphql_bridge_trigger_frontend_revalidate();
		return;
	}

	add_action(
		'shutdown',
		static function () {
			hws_graphql_bridge_trigger_frontend_revalidate();
		},
		99
	);
}

function hws_graphql_bridge_schedule_menu_revalidate(): void {
	hws_graphql_bridge_trigger_frontend_revalidate();
}

add_action( 'created_product_cat', 'hws_graphql_bridge_schedule_frontend_revalidate', 20, 1 );
add_action( 'edited_product_cat', 'hws_graphql_bridge_schedule_frontend_revalidate', 20, 1 );
add_action( 'delete_product_cat', 'hws_graphql_bridge_schedule_frontend_revalidate', 20, 1 );
add_action( 'save_post_product', 'hws_graphql_bridge_schedule_frontend_revalidate', 20, 1 );
add_action( 'wp_update_nav_menu', 'hws_graphql_bridge_schedule_menu_revalidate', 20 );
add_action( 'wp_update_nav_menu_item', 'hws_graphql_bridge_schedule_menu_revalidate', 20 );
add_action( 'wp_delete_nav_menu', 'hws_graphql_bridge_schedule_menu_revalidate', 20 );
