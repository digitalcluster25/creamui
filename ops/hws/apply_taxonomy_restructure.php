<?php
/**
 * Apply the taxonomy cleanup from Trello card “Перестройка таксономии”.
 *
 * Safe default: dry-run. Apply only on the staging WordPress container with:
 *   php apply_taxonomy_restructure.php run
 */

declare(strict_types=1);

require_once '/var/www/html/wp-load.php';

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

$backup_dir = '/var/www/html/data/audit';
if ($apply && !is_dir($backup_dir)) {
    wp_mkdir_p($backup_dir);
}

$terms = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
]);
$term_snapshot = [];
foreach ($terms as $term) {
    $term_snapshot[] = [
        'term_id' => (int) $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'parent' => (int) $term->parent,
        'count' => (int) $term->count,
    ];
}

$products = [];
foreach (array_keys($moves + [$duplicate_id => '']) as $product_id) {
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

foreach ($moves as $product_id => $target_slug) {
    if (!wc_get_product((int) $product_id)) {
        continue;
    }
    $log(($apply ? 'MOVE' : 'WOULD MOVE') . ": product {$product_id} → {$target_slug}");
    if ($apply && isset($term_ids[$target_slug])) {
        wp_set_object_terms((int) $product_id, [$term_ids[$target_slug]], 'product_cat', false);
    }
}

if (wc_get_product($duplicate_id)) {
    $log(($apply ? 'TRASH' : 'WOULD TRASH') . ": duplicate product {$duplicate_id}");
    if ($apply) {
        wp_trash_post($duplicate_id);
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
