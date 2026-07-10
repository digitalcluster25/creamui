<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$product_id = isset( $args[0] ) ? (int) $args[0] : 0;
if ( $product_id <= 0 ) {
	echo "Usage: wp eval-file inspect_vvd_product.php <product_id>\n";
	exit( 1 );
}

$post = get_post( $product_id );
if ( ! $post ) {
	echo "Product not found\n";
	exit( 1 );
}

$thumb_id = get_post_thumbnail_id( $product_id );
$gallery = (string) get_post_meta( $product_id, '_product_image_gallery', true );
$sku = (string) get_post_meta( $product_id, '_sku', true );

echo 'post_name=' . $post->post_name . PHP_EOL;
echo 'sku=' . $sku . PHP_EOL;
echo 'thumb_id=' . ( $thumb_id ? (string) $thumb_id : '' ) . PHP_EOL;
echo 'gallery=' . $gallery . PHP_EOL;
