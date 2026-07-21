<?php
/**
 * Apply the taxonomy cleanup from Trello card “Перестройка таксономии”.
 *
 * Safe default: dry-run. Apply only on the staging WordPress container with:
 *   php apply_taxonomy_restructure.php run
 */

declare(strict_types=1);

require_once '/var/www/html/wp-load.php';

// The site hides disabled categories through get_terms filters. This migration
// must see the real taxonomy while it changes it; visibility is restored below.
if (class_exists('HWS_Cat_Toggle')) {
    remove_filter('get_terms_args', ['HWS_Cat_Toggle', 'pre_filter_terms_args'], 10);
    remove_filter('get_terms', ['HWS_Cat_Toggle', 'filter_terms'], 10);
    remove_action('pre_get_posts', ['HWS_Cat_Toggle', 'filter_products']);
    remove_filter('woocommerce_product_query_tax_query', ['HWS_Cat_Toggle', 'filter_wc_tax_query']);
}

$mode = $argv[1] ?? 'dry-run';
$apply = $mode === 'run';
$log = static function (string $message): void {
    echo $message . PHP_EOL;
};

$active = [
    'sauna-stoves' => 'Печи для сауны',
    'russian-bath-stoves' => 'Печи для русской бани',
    'steam-generators-and-hammam' => 'Парогенераторы и хаммам',
    'commercial' => 'Коммерческие решения',
];
$hidden = [
    'control-units' => 'Пульты и автоматика',
    'accessories' => 'Аксессуары',
    'chimneys-and-installation' => 'Дымоходы и монтаж',
    'stones-and-cladding' => 'Камни и облицовка',
    'water-tanks-and-heat-exchangers' => 'Баки и теплообменники',
    'spa-systems' => 'SPA-системы',
    'ready-saunas' => 'Готовые сауны',
];
$moves = [
    251808 => 'accessories',
    251596 => 'accessories',
    251580 => 'accessories',
    249643 => 'sauna-stoves',
];
$duplicate_id = 251727;

function hws_target_category_for_product(int $product_id): ?string {
    $types = wp_get_post_terms($product_id, 'pa_equipment-type', ['fields' => 'slugs']);
    $rooms = wp_get_post_terms($product_id, 'pa_room-type', ['fields' => 'slugs']);
    $usage = wp_get_post_terms($product_id, 'pa_usage-class', ['fields' => 'slugs']);
    $title = (string) get_the_title($product_id);

    if (in_array('control-unit', $types, true)) return 'control-units';
    if (in_array('steam-generator', $types, true)) return 'steam-generators-and-hammam';
    if (in_array('commercial-bath-stove', $types, true) || in_array('commercial-bath-sauna-stove', $types, true)) return 'commercial';
    if (in_array('water-tank', $types, true) || in_array('heat-exchanger', $types, true) || in_array('economizer', $types, true)) return 'water-tanks-and-heat-exchangers';
    if (in_array('chimney', $types, true) || in_array('gas-burner', $types, true) || in_array('convection-element', $types, true) || in_array('mounting-element', $types, true)) return 'chimneys-and-installation';
    if (in_array('heater-stones', $types, true) || in_array('natural-stone-product', $types, true)) return 'stones-and-cladding';
    if (in_array('bathing-accessory', $types, true) || in_array('aroma-and-steam', $types, true) || in_array('pouring-device', $types, true) || in_array('wood-storage', $types, true)) return 'accessories';
    if (in_array('steam-room-equipment', $types, true)) {
        return str_contains($title, 'SteamRock') ? 'steam-generators-and-hammam' : 'accessories';
    }
    if (in_array('commercial-bath-stove', $types, true) || in_array('commercial-bath-sauna-stove', $types, true)) return 'commercial';
    if (in_array('wood-bath-stove', $types, true) || in_array('electric-bath-stove', $types, true) || in_array('steam-thermal-stove', $types, true)) return 'russian-bath-stoves';
    if (in_array('bath-sauna-stove', $types, true)) {
        if (in_array('kommercheskoe-ispolzovanie', $usage, true)) return 'commercial';
        if (in_array('russian-bath', $rooms, true)) return 'russian-bath-stoves';
        return 'sauna-stoves';
    }
    return null;
}

$backup_dir = '/var/www/html/data/audit';
if ($apply && !is_dir($backup_dir)) {
    wp_mkdir_p($backup_dir);
}

$terms = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
]);
$term_snapshot = [];
$child_product_ids = [];
foreach ($terms as $term) {
    $term_snapshot[] = [
        'term_id' => (int) $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'parent' => (int) $term->parent,
        'count' => (int) $term->count,
    ];
    if ($term->parent) {
        $child_product_ids = array_merge(
            $child_product_ids,
            array_map('intval', get_objects_in_term((int) $term->term_id, 'product_cat')),
        );
    }
}

$products = [];
$product_ids_to_backup = array_values(array_unique(array_merge(
    array_keys($moves + [$duplicate_id => '']),
    $child_product_ids,
    get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']),
)));
foreach ($product_ids_to_backup as $product_id) {
    $product = wc_get_product((int) $product_id);
    if (!$product) {
        $log("ERROR: product {$product_id} not found");
        continue;
    }
    $products[] = [
        'id' => (int) $product_id,
        'name' => $product->get_name(),
        'sku' => $product->get_sku(),
        'status' => get_post_status((int) $product_id),
        'categories' => wp_get_post_terms((int) $product_id, 'product_cat', ['fields' => 'all']),
    ];
}

$menu_snapshot = [];
$locations = get_nav_menu_locations();
$footer_menu_id = (int) ($locations['FOOTER_PRODUCTS'] ?? 0);
if ($footer_menu_id) {
    foreach (wp_get_nav_menu_items($footer_menu_id) ?: [] as $item) {
        $menu_snapshot[] = [
            'id' => (int) $item->ID,
            'title' => $item->title,
            'url' => $item->url,
        ];
    }
}

if ($apply) {
    file_put_contents(
        $backup_dir . '/taxonomy-restructure-' . gmdate('Ymd-His') . '.json',
        wp_json_encode(['terms' => $term_snapshot, 'products' => $products, 'footer_products' => $menu_snapshot], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    );
}

$term_ids = [];
foreach ($active + $hidden as $slug => $name) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $log(($apply ? 'CREATE' : 'WOULD CREATE') . ": {$slug}");
        if ($apply) {
            $created = wp_insert_term($name, 'product_cat', ['slug' => $slug, 'parent' => 0]);
            if (is_wp_error($created)) {
                $log("ERROR: {$slug}: " . $created->get_error_message());
                continue;
            }
            $term_ids[$slug] = (int) $created['term_id'];
        }
    } else {
        $term_ids[$slug] = (int) $term->term_id;
    }
}

$disabled = get_option('hws_disabled_cats', []);
$disabled = is_array($disabled) ? array_map('intval', $disabled) : [];
if ($apply && class_exists('HWS_Cat_Toggle')) {
    foreach (array_keys($hidden) as $slug) {
        if (isset($term_ids[$slug])) {
            $disabled[] = $term_ids[$slug];
        }
    }
    update_option('hws_disabled_cats', array_values(array_unique($disabled)));
}

foreach ($moves as $product_id => $target_slug) {
    if (!wc_get_product((int) $product_id)) {
        continue;
    }
    $log(($apply ? 'MOVE' : 'WOULD MOVE') . ": product {$product_id} → {$target_slug}");
    if ($apply) {
        $result = wp_set_object_terms((int) $product_id, [$target_slug], 'product_cat', false);
        if (is_wp_error($result) || !has_term($target_slug, 'product_cat', (int) $product_id)) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'term was not assigned';
            $log("ERROR: product {$product_id} → {$target_slug}: {$message}");
            exit(1);
        }
    }
}

if (wc_get_product($duplicate_id)) {
    $log(($apply ? 'TRASH' : 'WOULD TRASH') . ": duplicate product {$duplicate_id}");
    if ($apply) {
        wp_trash_post($duplicate_id);
    }
}

// The target catalog is flat. Products from every child category are first
// assigned to its direct parent; only then is the now-empty child removed.
foreach ($terms as $term) {
    if (!$term->parent) {
        continue;
    }
    $parent = get_term((int) $term->parent, 'product_cat');
    if (!$parent || is_wp_error($parent)) {
        $log("ERROR: parent missing for {$term->slug}");
        exit(1);
    }
    $object_ids = array_map('intval', get_objects_in_term((int) $term->term_id, 'product_cat'));
    $log(($apply ? 'FLATTEN' : 'WOULD FLATTEN') . ": {$term->slug} → {$parent->slug} (" . count($object_ids) . ' products)');
    if (!$apply) {
        continue;
    }
    foreach ($object_ids as $object_id) {
        $result = wp_set_object_terms($object_id, [(int) $parent->term_id], 'product_cat', false);
        if (is_wp_error($result) || !has_term((int) $parent->term_id, 'product_cat', $object_id)) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'parent category was not assigned';
            $log("ERROR: product {$object_id} → {$parent->slug}: {$message}");
            exit(1);
        }
    }
    if (get_objects_in_term((int) $term->term_id, 'product_cat')) {
        $log("ERROR: child {$term->slug} still has products");
        exit(1);
    }
    $deleted = wp_delete_term((int) $term->term_id, 'product_cat');
    if (is_wp_error($deleted) || !$deleted) {
        $message = is_wp_error($deleted) ? $deleted->get_error_message() : 'term was not deleted';
        $log("ERROR: child {$term->slug}: {$message}");
        exit(1);
    }
    $disabled = array_values(array_diff($disabled, [(int) $term->term_id]));
}

if ($apply && class_exists('HWS_Cat_Toggle')) {
    update_option('hws_disabled_cats', array_values(array_unique($disabled)));
}

// Reclassify published products from their structured WooCommerce attributes.
// This prevents the flattening step from leaving stoves or controls inside an
// unrelated hidden parent category. Ready-made saunas are intentionally hidden
// and excluded from automatic reassignment.
foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']) as $product_id) {
    $current_slugs = wp_get_post_terms((int) $product_id, 'product_cat', ['fields' => 'slugs']);
    if (in_array('ready-saunas', $current_slugs, true)) {
        continue;
    }
    $target_slug = hws_target_category_for_product((int) $product_id);
    if (!$target_slug || $current_slugs === [$target_slug]) {
        continue;
    }
    $log(($apply ? 'CLASSIFY' : 'WOULD CLASSIFY') . ": product {$product_id} → {$target_slug}");
    if (!$apply) {
        continue;
    }
    $result = wp_set_object_terms((int) $product_id, [$target_slug], 'product_cat', false);
    if (is_wp_error($result) || !has_term($target_slug, 'product_cat', (int) $product_id)) {
        $message = is_wp_error($result) ? $result->get_error_message() : 'target category was not assigned';
        $log("ERROR: product {$product_id} → {$target_slug}: {$message}");
        exit(1);
    }
}

// Merge the legacy transliterated SPA category into the canonical hidden slug.
foreach (['spa-sistemy' => 'spa-systems'] as $legacy_slug => $target_slug) {
    $legacy_term = get_term_by('slug', $legacy_slug, 'product_cat');
    if (!$legacy_term || !isset($term_ids[$target_slug])) {
        continue;
    }
    $object_ids = array_map('intval', get_objects_in_term((int) $legacy_term->term_id, 'product_cat'));
    $log(($apply ? 'MERGE' : 'WOULD MERGE') . ": {$legacy_slug} → {$target_slug} (" . count($object_ids) . ' products)');
    if (!$apply) {
        continue;
    }
    foreach ($object_ids as $object_id) {
        $result = wp_set_object_terms($object_id, [$term_ids[$target_slug]], 'product_cat', false);
        if (is_wp_error($result) || !has_term($target_slug, 'product_cat', $object_id)) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'canonical category was not assigned';
            $log("ERROR: product {$object_id} → {$target_slug}: {$message}");
            exit(1);
        }
    }
    $deleted = wp_delete_term((int) $legacy_term->term_id, 'product_cat');
    if (is_wp_error($deleted) || !$deleted) {
        $message = is_wp_error($deleted) ? $deleted->get_error_message() : 'legacy category was not deleted';
        $log("ERROR: legacy category {$legacy_slug}: {$message}");
        exit(1);
    }
    $disabled = array_values(array_diff($disabled, [(int) $legacy_term->term_id]));
    if (class_exists('HWS_Cat_Toggle')) {
        update_option('hws_disabled_cats', array_values(array_unique($disabled)));
    }
}

if ($footer_menu_id) {
    $allowed_urls = array_map(static fn (string $slug): string => '/product-category/' . $slug . '/', array_keys($active));
    $allowed_urls[] = 'https://hws.shopping/product-category/';
    foreach (wp_get_nav_menu_items($footer_menu_id) ?: [] as $item) {
        $is_hidden_category = false;
        foreach (array_keys($hidden) as $slug) {
            if (str_contains($item->url, '/product-category/' . $slug)) {
                $is_hidden_category = true;
                break;
            }
        }
        if ($is_hidden_category) {
            $log(($apply ? 'REMOVE' : 'WOULD REMOVE') . ": footer item {$item->title}");
            if ($apply) {
                wp_delete_post((int) $item->ID, true);
            }
        }
    }
    $existing_urls = array_map(static fn ($item): string => untrailingslashit((string) $item->url), wp_get_nav_menu_items($footer_menu_id) ?: []);
    foreach ($active as $slug => $label) {
        $url = 'https://hws.shopping/product-category/' . $slug . '/';
        if (!in_array(untrailingslashit($url), $existing_urls, true)) {
            $log(($apply ? 'ADD' : 'WOULD ADD') . ": footer {$label}");
            if ($apply) {
                wp_update_nav_menu_item($footer_menu_id, 0, [
                    'menu-item-title' => $label,
                    'menu-item-url' => $url,
                    'menu-item-status' => 'publish',
                ]);
            }
        }
    }
}

$log($apply ? 'DONE: taxonomy restructure applied' : 'DRY-RUN ONLY: no changes made');
