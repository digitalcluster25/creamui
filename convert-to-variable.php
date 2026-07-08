<?php
/**
 * Convert product 248495 from SimpleProduct to VariableProduct
 * with 24 variations (6 power × 4 facing), real prices in USD.
 */

$product_id = 248495;
$rate = 77.9695;

// === 1. Ensure taxonomy attributes exist ===
$power_values = ['10 кВт','12 кВт','14 кВт','16 кВт','18 кВт','20 кВт'];
$facing_values = ['Серпентинит Бархат','Серпентинит Премиум','Пироксенит чёрный','Талькохлорит'];

// Register taxonomies if not yet (WooCommerce does this, but just in case)
foreach (['pa_power' => 'Мощность', 'pa_facing' => 'Облицовка'] as $tax => $label) {
    if (!taxonomy_exists($tax)) {
        wc_create_attribute(['name' => str_replace('pa_', '', $tax), 'slug' => str_replace('pa_', '', $tax), 'type' => 'select']);
        register_taxonomy($tax, 'product');
    }
}

// Create terms
foreach ($power_values as $v) {
    if (!term_exists($v, 'pa_power')) wp_insert_term($v, 'pa_power');
}
foreach ($facing_values as $v) {
    if (!term_exists($v, 'pa_facing')) wp_insert_term($v, 'pa_facing');
}

// Assign terms to product
wp_set_object_terms($product_id, $power_values, 'pa_power');
wp_set_object_terms($product_id, $facing_values, 'pa_facing');


// === 2. Set product attributes as variation attributes ===
$product_attributes = [
    'pa_power' => [
        'name' => 'pa_power',
        'value' => '',
        'position' => 0,
        'is_visible' => 1,
        'is_variation' => 1,
        'is_taxonomy' => 1,
    ],
    'pa_facing' => [
        'name' => 'pa_facing',
        'value' => '',
        'position' => 1,
        'is_visible' => 1,
        'is_variation' => 1,
        'is_taxonomy' => 1,
    ],
];
update_post_meta($product_id, '_product_attributes', $product_attributes);

// === 3. Change product type to variable ===
wp_remove_object_terms($product_id, 'simple', 'product_type');
wp_set_object_terms($product_id, 'variable', 'product_type');

// === 4. Delete old variations if any ===
$existing = get_posts(['post_type' => 'product_variation', 'post_parent' => $product_id, 'numberposts' => -1, 'fields' => 'ids']);
foreach ($existing as $vid) { wp_delete_post($vid, true); }


// === 5. Create 24 variations ===
$offers = [
    ['1383','10 кВт','Серпентинит Бархат',587500],
    ['1388','10 кВт','Серпентинит Премиум',597100],
    ['1393','10 кВт','Пироксенит чёрный',544800],
    ['1378','10 кВт','Талькохлорит',511700],
    ['1384','12 кВт','Серпентинит Бархат',595100],
    ['1389','12 кВт','Серпентинит Премиум',605110],
    ['1394','12 кВт','Пироксенит чёрный',553200],
    ['1379','12 кВт','Талькохлорит',520000],
    ['1385','14 кВт','Серпентинит Бархат',603600],
    ['1390','14 кВт','Серпентинит Премиум',613300],
    ['1395','14 кВт','Пироксенит чёрный',561700],
    ['1380','14 кВт','Талькохлорит',528600],
    ['1386','16 кВт','Серпентинит Бархат',611700],
    ['1391','16 кВт','Серпентинит Премиум',621300],
    ['1396','16 кВт','Пироксенит чёрный',563300],
    ['1381','16 кВт','Талькохлорит',536700],
    ['1387','18 кВт','Серпентинит Бархат',619700],
    ['1392','18 кВт','Серпентинит Премиум',629400],
    ['1397','18 кВт','Пироксенит чёрный',578400],
    ['1382','18 кВт','Талькохлорит',545300],
    ['1400','20 кВт','Серпентинит Бархат',627800],
    ['1401','20 кВт','Серпентинит Премиум',637300],
    ['1398','20 кВт','Пироксенит чёрный',586900],
    ['1399','20 кВт','Талькохлорит',553600],
];

$min_price = PHP_INT_MAX;
$max_price = 0;


foreach ($offers as $i => $offer) {
    list($sku, $power, $facing, $rub) = $offer;
    $usd = round($rub / $rate, 2);

    if ($usd < $min_price) $min_price = $usd;
    if ($usd > $max_price) $max_price = $usd;

    // Get term slugs
    $power_term = get_term_by('name', $power, 'pa_power');
    $facing_term = get_term_by('name', $facing, 'pa_facing');
    $power_slug = $power_term ? $power_term->slug : sanitize_title($power);
    $facing_slug = $facing_term ? $facing_term->slug : sanitize_title($facing);

    $variation_id = wp_insert_post([
        'post_title' => "Variation #{$i}",
        'post_content' => '',
        'post_status' => 'publish',
        'post_parent' => $product_id,
        'post_type' => 'product_variation',
        'menu_order' => $i,
    ]);

    update_post_meta($variation_id, '_sku', $sku);
    update_post_meta($variation_id, '_regular_price', $usd);
    update_post_meta($variation_id, '_price', $usd);
    update_post_meta($variation_id, '_stock_status', 'instock');
    update_post_meta($variation_id, '_manage_stock', 'no');


    update_post_meta($variation_id, 'attribute_pa_power', $power_slug);
    update_post_meta($variation_id, 'attribute_pa_facing', $facing_slug);

    echo "Created variation $variation_id: SKU=$sku, $power + $facing = \${$usd}\n";
}

// === 6. Update parent price range ===
update_post_meta($product_id, '_price', $min_price);
update_post_meta($product_id, '_regular_price', $min_price);
update_post_meta($product_id, '_min_variation_price', $min_price);
update_post_meta($product_id, '_max_variation_price', $max_price);
update_post_meta($product_id, '_min_variation_regular_price', $min_price);
update_post_meta($product_id, '_max_variation_regular_price', $max_price);

// Clear transients
delete_transient('wc_var_prices_' . $product_id);
wc_delete_product_transients($product_id);

echo "\nDONE. Product $product_id converted to Variable.\n";
echo "Variations: " . count($offers) . "\n";
echo "Price range: \${$min_price} - \${$max_price}\n";
