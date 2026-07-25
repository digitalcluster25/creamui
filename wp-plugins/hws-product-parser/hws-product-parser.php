<?php
/**
 * Plugin Name: HWS Product Parser
 * Description: Parser status and field mapping console for confirmed supplier product sources.
 * Version: 0.1.0
 * Author: HWS
 */

defined('ABSPATH') || exit;

final class HWS_Product_Parser {

    private const MENU_SLUG = 'hws-product-parser';
    private const OPTION    = 'hws_product_parser_state';
    private const NONCE     = 'hws_product_parser_action';
    private const STATE_VERSION = '0.2.0';

    private const MANUFACTURERS = [
        'easysteam' => [
            'label'      => 'EasySteam',
            'categories' => ['gelendzhik'],
        ],
    ];

    private const CATEGORIES = [
        'gelendzhik' => [
            'manufacturer' => 'easysteam',
            'label'        => 'Геленджик',
            'url'          => 'https://easysteam.ru/products/stoves/pechi/gelendzhik',
        ],
    ];

    private const PRODUCT_FIELDS = [
        'title'             => ['source' => 'Название карточки', 'target' => 'WooCommerce product name'],
        'article'           => ['source' => 'Артикул offer', 'target' => 'SKU / variation SKU'],
        'price'             => ['source' => 'Цена offer', 'target' => 'Variation regular price'],
        'offer_image'       => ['source' => 'Фото offer', 'target' => 'Variation image'],
        'short_description' => ['source' => 'Описание', 'target' => 'Short description'],
        'long_description'  => ['source' => 'Назначение + Преимущества', 'target' => 'Long description source'],
        'characteristics'   => ['source' => 'Информация о товаре / характеристики', 'target' => 'Structured attributes + specs'],
        'fuel_type'         => ['source' => 'Вид топлива', 'target' => 'Тип топлива'],
        'purpose'           => ['source' => 'Назначение', 'target' => 'Назначение'],
        'steam_volume'      => ['source' => 'Объем парной', 'target' => 'Объем парной'],
        'model'             => ['source' => 'Модель', 'target' => 'Модель'],
        'jacket_material'   => ['source' => 'Вид кожуха / Варианты кожуха', 'target' => 'Материал кожуха'],
        'steel_grade'       => ['source' => 'Марка стали', 'target' => 'Марка стали'],
        'door_side'         => ['source' => 'Исполнение дверки', 'target' => 'Сторона дверки'],
        'stone_side'        => ['source' => 'Боковой вход в каменку', 'target' => 'Сторона входа в каменку'],
        'chimney_side'      => ['source' => 'Боковое подключение дымохода', 'target' => 'Сторона подключения дымохода'],
    ];

    private const MANUFACTURER_MAPPING = [
        ['source' => 'EasySteam', 'target' => 'Product brand'],
        ['source' => 'Печи для русской бани / Геленджик', 'target' => 'WooCommerce category scope'],
        ['source' => 'Карточки товаров производителя', 'target' => 'WooCommerce variable products'],
    ];

    private const CATEGORY_MAPPING = [
        ['source' => 'https://easysteam.ru/products/stoves/pechi/gelendzhik', 'target' => 'Category source URL'],
        ['source' => '18 карточек товаров', 'target' => 'Products in category'],
        ['source' => 'Опции radio', 'target' => 'Real offers / variations'],
        ['source' => 'Артикул + цена + фото offer', 'target' => 'Variation SKU + price + image'],
        ['source' => 'Описание', 'target' => 'Short description'],
        ['source' => 'Назначение + Преимущества', 'target' => 'Long description source'],
        ['source' => 'Информация о товаре', 'target' => 'Attributes, filters, specs table'],
    ];

    private const BLOCKED_TABS = [
        'Печь в разрезе',
        'Схема работы печи',
        'Документация',
        'Видео',
        'Для проектов',
    ];

    public static function init(): void {
        add_action('admin_init', [__CLASS__, 'maybe_migrate_state']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_hws_product_parser_parse', [__CLASS__, 'handle_parse']);
    }

    public static function admin_menu(): void {
        add_menu_page(
            'HWS Parser',
            'HWS Parser',
            'manage_woocommerce',
            self::MENU_SLUG,
            [__CLASS__, 'render_page'],
            'dashicons-database-import',
            56
        );

        add_submenu_page(
            self::MENU_SLUG,
            'HWS Parser',
            'HWS Parser',
            'manage_woocommerce',
            self::MENU_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    public static function maybe_migrate_state(): void {
        $state = get_option(self::OPTION, []);
        if (!is_array($state)) {
            update_option(self::OPTION, ['version' => self::STATE_VERSION], false);
            return;
        }
        if (($state['version'] ?? '') === self::STATE_VERSION) {
            return;
        }

        $state['version']  = self::STATE_VERSION;
        $state['statuses'] = [];
        update_option(self::OPTION, $state, false);
    }

    public static function handle_parse(): void {
        check_admin_referer(self::NONCE);
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Forbidden', 403);
        }

        $manufacturer = self::sanitize_manufacturer(wp_unslash($_POST['manufacturer'] ?? 'easysteam'));
        $category     = self::sanitize_category(wp_unslash($_POST['category'] ?? ''));
        $product      = self::sanitize_product_id(wp_unslash($_POST['product'] ?? ''));
        $scope        = sanitize_key(wp_unslash($_POST['scope'] ?? ''));
        $field        = sanitize_key(wp_unslash($_POST['field'] ?? ''));
        $message      = 'parsed';

        if ($category === '') {
            $category = 'gelendzhik';
        }

        try {
            if ($scope === 'manufacturer') {
                self::parse_manufacturer($manufacturer);
            } elseif ($scope === 'category') {
                self::parse_category($manufacturer, $category);
            } elseif ($scope === 'product') {
                self::parse_product($manufacturer, $category, $product, null);
            } elseif ($scope === 'field') {
                self::parse_product($manufacturer, $category, $product, $field);
            } else {
                throw new RuntimeException('Unknown parse scope.');
            }
        } catch (Throwable $e) {
            $message = 'error:' . rawurlencode($e->getMessage());
        }

        $redirect = add_query_arg(
            [
                'page'         => self::MENU_SLUG,
                'manufacturer' => $manufacturer,
                'category'     => $category,
                'product'      => $product,
                'hws_message'  => $message,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function render_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Forbidden', 403);
        }

        $manufacturer = self::sanitize_manufacturer(wp_unslash($_GET['manufacturer'] ?? 'easysteam'));
        $category     = self::sanitize_category(wp_unslash($_GET['category'] ?? ''));
        $product      = self::sanitize_product_id(wp_unslash($_GET['product'] ?? ''));
        $state        = self::state();
        $products     = self::products_for_category($state, $manufacturer, $category ?: 'gelendzhik');

        if ($product !== '' && !isset($products[$product])) {
            $product = '';
        }

        ?>
        <div class="wrap hws-parser-wrap">
            <h1>HWS Parser</h1>
            <?php self::render_notice(); ?>
            <?php self::render_styles(); ?>
            <?php self::render_filters($manufacturer, $category, $product, $products); ?>
            <?php self::render_scope_actions($manufacturer, $category, $product); ?>

            <?php if ($category === '' && $product === ''): ?>
                <?php self::render_mapping_table('Производитель', self::MANUFACTURER_MAPPING, self::status_key('manufacturer', $manufacturer)); ?>
            <?php endif; ?>

            <?php if ($product === ''): ?>
                <?php self::render_mapping_table('Категория: Геленджик', self::CATEGORY_MAPPING, self::status_key('category', $manufacturer, 'gelendzhik')); ?>
                <?php self::render_products_table($manufacturer, 'gelendzhik', $products); ?>
            <?php else: ?>
                <?php self::render_product_detail($manufacturer, 'gelendzhik', $product, $products[$product]); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_notice(): void {
        $message = sanitize_text_field(wp_unslash($_GET['hws_message'] ?? ''));
        if ($message === '') {
            return;
        }

        if (str_starts_with($message, 'error:')) {
            $text = rawurldecode(substr($message, 6));
            echo '<div class="notice notice-error"><p>' . esc_html($text) . '</p></div>';
            return;
        }

        echo '<div class="notice notice-success"><p>Статус парсинга обновлен.</p></div>';
    }

    private static function render_styles(): void {
        ?>
        <style>
        .hws-parser-wrap { max-width: 1180px; }
        .hws-parser-panel { background:#fff;border:1px solid #c3c4c7;border-radius:4px;margin:16px 0;padding:16px; }
        .hws-parser-filters { display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap; }
        .hws-parser-filters label { display:block;font-weight:600;margin-bottom:4px; }
        .hws-parser-filters select { min-width:220px; }
        .hws-parser-actions { display:flex;gap:8px;flex-wrap:wrap;margin-top:12px; }
        .hws-parser-status { display:inline-flex;align-items:center;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600; }
        .hws-parser-status--ok { background:#edfaef;color:#0a6b24; }
        .hws-parser-status--empty { background:#f6f7f7;color:#646970; }
        .hws-parser-meta { color:#646970;font-size:12px; }
        .hws-parser-table th, .hws-parser-table td { vertical-align:top; }
        .hws-parser-table code { white-space:normal; }
        .hws-parser-products td:first-child { width:35%; }
        .hws-parser-field-actions { display:flex;gap:6px;flex-wrap:wrap; }
        .hws-parser-accordion { margin:0; }
        .hws-parser-accordion summary { cursor:pointer;font-weight:600;color:#2271b1; }
        .hws-parser-accordion__body { margin-top:12px; }
        .hws-parser-store-link { margin:8px 0;color:#646970; }
        </style>
        <?php
    }

    private static function render_filters(string $manufacturer, string $category, string $product, array $products): void {
        ?>
        <form class="hws-parser-panel hws-parser-filters" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>">
            <div>
                <label for="hws-parser-manufacturer">Производитель</label>
                <select id="hws-parser-manufacturer" name="manufacturer">
                    <option value="easysteam" selected>EasySteam</option>
                </select>
            </div>
            <div>
                <label for="hws-parser-category">Категория производителя</label>
                <select id="hws-parser-category" name="category">
                    <option value="" <?php selected($category, ''); ?>>Все категории EasySteam</option>
                    <option value="gelendzhik" <?php selected($category, 'gelendzhik'); ?>>Геленджик</option>
                </select>
            </div>
            <div>
                <label for="hws-parser-product">Товар</label>
                <select id="hws-parser-product" name="product">
                    <option value="" <?php selected($product, ''); ?>>Все товары</option>
                    <?php foreach ($products as $product_id => $item): ?>
                        <option value="<?php echo esc_attr($product_id); ?>" <?php selected($product, $product_id); ?>>
                            <?php echo esc_html($item['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="button button-primary" type="submit">Показать</button>
        </form>
        <?php
    }

    private static function render_scope_actions(string $manufacturer, string $category, string $product): void {
        $scope = 'manufacturer';
        if ($product !== '') {
            $scope = 'product';
        } elseif ($category !== '') {
            $scope = 'category';
        }
        ?>
        <div class="hws-parser-panel">
            <strong>Парсинг выбранного уровня</strong>
            <div class="hws-parser-actions">
                <?php self::render_action_button('Спарсить с нуля', $scope, $manufacturer, $category ?: 'gelendzhik', $product); ?>
            </div>
        </div>
        <?php
    }

    private static function render_mapping_table(string $title, array $rows, string $status_key): void {
        $status = self::status($status_key);
        ?>
        <div class="hws-parser-panel">
            <h2><?php echo esc_html($title); ?></h2>
            <p>
                <?php self::render_status_badge($status); ?>
                <span class="hws-parser-meta"><?php echo esc_html(self::status_date($status)); ?></span>
            </p>
            <table class="widefat striped hws-parser-table">
                <thead><tr><th>Поле у производителя</th><th>Поле магазина</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['source']); ?></td>
                        <td><?php echo esc_html($row['target']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_products_table(string $manufacturer, string $category, array $products): void {
        ?>
        <div class="hws-parser-panel">
            <h2>Товары для парсинга</h2>
            <table class="widefat striped hws-parser-table hws-parser-products">
                <thead><tr><th>Товар у производителя</th><th>URL</th><th>Статус</th><th>Действие</th></tr></thead>
                <tbody>
                <?php if (!$products): ?>
                    <tr><td colspan="4">Товары еще не спарсены. Запустите парсинг категории.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $product_id => $item): ?>
                    <?php
                    $status = self::product_status($manufacturer, $category, $product_id);
                    $parsed = self::parsed_product($manufacturer, $category, $product_id);
                    $product_url = add_query_arg(
                        [
                            'page'         => self::MENU_SLUG,
                            'manufacturer' => $manufacturer,
                            'category'     => $category,
                            'product'      => $product_id,
                        ],
                        admin_url('admin.php')
                    );
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($item['title']); ?></a></td>
                        <td><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noreferrer"><?php echo esc_html($item['url']); ?></a></td>
                        <td><?php self::render_status_badge($status); ?><br><span class="hws-parser-meta"><?php echo esc_html(self::status_date($status)); ?></span></td>
                        <td><?php self::render_action_button('Спарсить товар с нуля', 'product', $manufacturer, $category, $product_id); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <details class="hws-parser-accordion">
                                <summary>Показать поля товара</summary>
                                <div class="hws-parser-accordion__body">
                                    <?php self::render_product_fields_table($manufacturer, $category, $product_id, $parsed); ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_product_detail(string $manufacturer, string $category, string $product_id, array $item): void {
        $parsed = self::parsed_product($manufacturer, $category, $product_id);
        ?>
        <div class="hws-parser-panel">
            <h2><?php echo esc_html($item['title']); ?></h2>
            <p>Источник: <a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noreferrer"><?php echo esc_html($item['url']); ?></a></p>
            <?php self::render_store_product_link($parsed); ?>
            <?php self::render_product_fields_table($manufacturer, $category, $product_id, $parsed); ?>
        </div>
        <div class="hws-parser-panel">
            <h2>Не импортировать по ТЗ</h2>
            <p><?php echo esc_html(implode(', ', self::BLOCKED_TABS)); ?></p>
        </div>
        <?php
    }

    private static function render_product_fields_table(string $manufacturer, string $category, string $product_id, array $parsed): void {
        ?>
        <table class="widefat striped hws-parser-table">
            <thead>
                <tr>
                    <th>Поле у производителя</th>
                    <th>Поле магазина</th>
                    <th>Текущее значение парсера</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (self::PRODUCT_FIELDS as $field => $mapping): ?>
                <?php $status = self::status(self::status_key('field', $manufacturer, $category, $product_id, $field)); ?>
                <tr>
                    <td><?php echo esc_html($mapping['source']); ?></td>
                    <td><?php echo esc_html($mapping['target']); ?></td>
                    <td><?php echo esc_html(self::format_value($parsed[$field] ?? null)); ?></td>
                    <td><?php self::render_status_badge($status); ?><br><span class="hws-parser-meta"><?php echo esc_html(self::status_date($status)); ?></span></td>
                    <td class="hws-parser-field-actions">
                        <?php self::render_action_button('Перепарсить поле', 'field', $manufacturer, $category, $product_id, $field); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_store_product_link(array $parsed): void {
        $url = self::store_product_url($parsed);
        echo '<p class="hws-parser-store-link">Товар на сайте HWS: ';
        if ($url !== '') {
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noreferrer">' . esc_html($url) . '</a>';
        } else {
            echo 'не найден';
        }
        echo '</p>';
    }

    private static function render_action_button(string $label, string $scope, string $manufacturer, string $category, string $product = '', string $field = ''): void {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
            <?php wp_nonce_field(self::NONCE); ?>
            <input type="hidden" name="action" value="hws_product_parser_parse">
            <input type="hidden" name="scope" value="<?php echo esc_attr($scope); ?>">
            <input type="hidden" name="manufacturer" value="<?php echo esc_attr($manufacturer); ?>">
            <input type="hidden" name="category" value="<?php echo esc_attr($category); ?>">
            <input type="hidden" name="product" value="<?php echo esc_attr($product); ?>">
            <input type="hidden" name="field" value="<?php echo esc_attr($field); ?>">
            <button class="button" type="submit"><?php echo esc_html($label); ?></button>
        </form>
        <?php
    }

    private static function render_status_badge(array $status): void {
        $parsed = !empty($status['parsed']);
        $class  = $parsed ? 'hws-parser-status--ok' : 'hws-parser-status--empty';
        $label  = $parsed ? 'parsed' : 'not parsed';
        echo '<span class="hws-parser-status ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }

    private static function parse_manufacturer(string $manufacturer): void {
        $all_parsed = true;
        foreach (self::MANUFACTURERS[$manufacturer]['categories'] as $category) {
            self::parse_category($manufacturer, $category);
            $status = self::status(self::status_key('category', $manufacturer, $category));
            $all_parsed = $all_parsed && !empty($status['parsed']);
        }
        self::mark_status(self::status_key('manufacturer', $manufacturer), $all_parsed);
    }

    private static function parse_category(string $manufacturer, string $category): void {
        if (!isset(self::CATEGORIES[$category]) || self::CATEGORIES[$category]['manufacturer'] !== $manufacturer) {
            throw new RuntimeException('Category is not allowed by parser manifest.');
        }

        $html     = self::fetch_html(self::CATEGORIES[$category]['url']);
        $products = self::extract_category_products($html);

        $state = self::state();
        $state['products'][$manufacturer][$category] = $products;
        update_option(self::OPTION, $state, false);

        self::mark_status(
            self::status_key('category', $manufacturer, $category),
            count($products) === 18,
            'products=' . count($products)
        );
    }

    private static function parse_product(string $manufacturer, string $category, string $product_id, ?string $only_field): void {
        $products = self::products_for_category(self::state(), $manufacturer, $category);
        if (!isset($products[$product_id])) {
            self::parse_category($manufacturer, $category);
            $products = self::products_for_category(self::state(), $manufacturer, $category);
        }
        if (!isset($products[$product_id])) {
            throw new RuntimeException('Product is not found in parsed category list.');
        }
        if ($only_field !== null && !isset(self::PRODUCT_FIELDS[$only_field])) {
            throw new RuntimeException('Field is not allowed by parser mapping.');
        }

        $html   = self::fetch_html($products[$product_id]['url']);
        $parsed = self::extract_product_fields($html);
        $fields = $only_field === null ? array_keys(self::PRODUCT_FIELDS) : [$only_field];
        $state  = self::state();

        foreach ($fields as $field) {
            $state['parsed_products'][$manufacturer][$category][$product_id][$field] = $parsed[$field] ?? null;
        }
        update_option(self::OPTION, $state, false);

        foreach ($fields as $field) {
            self::mark_status(
                self::status_key('field', $manufacturer, $category, $product_id, $field),
                self::field_is_valid($field, $parsed[$field] ?? null)
            );
        }
        if ($only_field === null) {
            self::mark_status(
                self::status_key('product', $manufacturer, $category, $product_id),
                self::product_has_parsed_data($parsed),
                'fields=' . count(array_filter($parsed, static fn($value): bool => self::value_is_not_empty($value)))
            );
        }
    }

    private static function extract_category_products(string $html): array {
        $products = [];
        if (!preg_match_all('~<div class=["\'][^"\']*(?<![\w-])product-card(?![\w-])[^"\']*["\'][\s\S]*?<a href=["\']([^"\']+)["\'][^>]*class=["\'][^"\']*product-card__link[^"\']*["\'][^>]*>[\s\S]*?<div class=["\'][^"\']*product-card__title[^"\']*["\'][^>]*>(.*?)</div>\s*(?:<div class=["\'][^"\']*product-card__text[^"\']*["\'][^>]*>(.*?)</div>)?~isu', $html, $matches, PREG_SET_ORDER)) {
            return $products;
        }

        foreach ($matches as $match) {
            $url = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!str_contains($url, '/products/show/') && !str_contains($url, '/products/product/')) {
                continue;
            }
            $title = trim(wp_strip_all_tags($match[2]));
            $subtitle = trim(wp_strip_all_tags($match[3] ?? ''));
            if ($subtitle !== '') {
                $title .= ' — ' . $subtitle;
            }
            if ($title === '' || mb_strlen($title) < 4) {
                continue;
            }
            $absolute = self::absolute_url($url);
            $id       = self::product_id_from_url($absolute);
            $products[$id] = [
                'title' => $title,
                'url'   => $absolute,
            ];
        }

        uasort($products, static fn(array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));

        return $products;
    }

    private static function extract_product_fields(string $html): array {
        $text_blocks = self::extract_tab_texts($html);
        $chars       = self::extract_characteristics($html);
        $options     = self::extract_options($html);

        return [
            'title'             => self::extract_first_text($html, ['~<h1[^>]*>(.*?)</h1>~isu']),
            'article'           => self::extract_attr($html, 'data-product-offer'),
            'price'             => self::extract_price($html),
            'offer_image'       => self::extract_image($html),
            'short_description' => $text_blocks['Описание'] ?? '',
            'long_description'  => trim(($text_blocks['Назначение'] ?? '') . "\n\n" . ($text_blocks['Преимущества'] ?? '')),
            'characteristics'   => $chars,
            'fuel_type'         => self::normalize_fuel_type(self::first_present($options, $chars, ['Вид топлива', 'Тип топлива'])),
            'purpose'           => self::first_present($options, $chars, ['Назначение']),
            'steam_volume'      => self::extract_steam_volume($chars),
            'model'             => self::first_present($options, $chars, ['Модель']),
            'jacket_material'   => self::first_present($options, $chars, ['Вид кожуха', 'Варианты кожуха', 'Материал кожуха']),
            'steel_grade'       => self::first_present($options, $chars, ['Марка стали']),
            'door_side'         => self::first_present($options, $chars, ['Исполнение дверки', 'Сторона дверки']),
            'stone_side'        => self::first_present($options, $chars, ['Боковой вход в каменку']),
            'chimney_side'      => self::first_present($options, $chars, ['Боковое подключение дымохода']),
        ];
    }

    private static function extract_tab_texts(string $html): array {
        $texts = [];

        if (preg_match('~<div class=["\'][^"\']*product__description-title[^"\']*["\'][^>]*>\s*Описание\s*</div>\s*<div[^>]*>(.*?)</div>~isu', $html, $match)) {
            $texts['Описание'] = trim(wp_strip_all_tags($match[1]));
        }
        if (preg_match('~<div class=["\'][^"\']*tab-pane[^"\']*["\'][^>]*id=["\']prod-purpose["\'][^>]*>(.*?)(?=<div class=["\'][^"\']*tab-pane[^"\']*["\'][^>]*id=|</div>\s*</div>\s*</div>)~isu', $html, $match)) {
            $texts['Назначение'] = trim(wp_strip_all_tags($match[1]));
        }
        if (preg_match('~<div class=["\'][^"\']*tab-pane[^"\']*["\'][^>]*id=["\']prod-advantage["\'][^>]*>(.*?)(?=<div class=["\'][^"\']*tab-pane[^"\']*["\'][^>]*id=|</div>\s*</div>\s*</div>)~isu', $html, $match)) {
            $texts['Преимущества'] = trim(wp_strip_all_tags($match[1]));
        }

        return $texts;
    }

    private static function extract_characteristics(string $html): array {
        $result = [];
        if (!preg_match_all('~<tr[^>]*>\s*<t[hd][^>]*>(.*?)</t[hd]>\s*<t[hd][^>]*>(.*?)</t[hd]>\s*</tr>~isu', $html, $rows, PREG_SET_ORDER)) {
            return $result;
        }

        foreach ($rows as $row) {
            $key = trim(wp_strip_all_tags($row[1]));
            $val = trim(wp_strip_all_tags($row[2]));
            if ($key !== '' && $val !== '') {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    private static function extract_options(string $html): array {
        $options = [];
        if (!preg_match_all('~<div class=["\'][^"\']*(?<![\w-])radio-group(?![\w-])[^"\']*["\'][^>]*>(.*?)(?=<div class=["\'][^"\']*(?<![\w-])radio-group(?![\w-])[^"\']*["\']|<button[^>]*class=["\'][^"\']*js-btn-item-cart-add|<div class=["\'][^"\']*product-tabs|$)~isu', $html, $items, PREG_SET_ORDER)) {
            return $options;
        }

        foreach ($items as $item) {
            $block = $item[1];
            $name  = self::extract_first_text($block, ['~class=["\'][^"\']*radio-group__title[^"\']*["\'][^>]*>(.*?)</~isu']);
            if ($name === '') {
                continue;
            }
            preg_match_all('~class=["\'][^"\']*radio-group__item-text[^"\']*["\'][^>]*>(.*?)</~isu', $block, $values);
            $clean_values = array_values(array_filter(array_map(static fn($value): string => trim(wp_strip_all_tags($value)), $values[1] ?? [])));
            if ($clean_values) {
                $options[$name] = implode(', ', array_unique($clean_values));
            }
        }

        return $options;
    }

    private static function extract_price(string $html): string {
        if (preg_match('~data-product-offer-price=["\']([^"\']+)["\']~isu', $html, $match)) {
            return trim($match[1]);
        }
        if (preg_match('~class=["\'][^"\']*price[^"\']*["\'][^>]*>(.*?)</~isu', $html, $match)) {
            return preg_replace('~[^\d.,]~u', '', wp_strip_all_tags($match[1]));
        }
        return '';
    }

    private static function extract_image(string $html): string {
        if (preg_match('~<img(?=[^>]+class=["\'][^"\']*(?:js-product-main-image|product__image)[^"\']*["\'])(?=[^>]+src=["\']([^"\']+)["\'])[^>]*>~isu', $html, $match)) {
            return self::absolute_url(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if (preg_match('~class=["\'][^"\']*js-product-main-image-wrap[^"\']*["\'][^>]+href=["\']([^"\']+)["\']~isu', $html, $match)) {
            return self::absolute_url(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return '';
    }

    private static function extract_steam_volume(array $chars): string {
        $direct = self::first_present([], $chars, ['Объем парной', 'Объём парной']);
        if ($direct !== '') {
            return $direct;
        }

        $min = $chars['Минимальный объем парной'] ?? $chars['Минимальный объём парной'] ?? '';
        $max = $chars['Максимальный объем парной'] ?? $chars['Максимальный объём парной'] ?? '';
        if ($min !== '' && $max !== '') {
            return trim($min . ' - ' . $max);
        }
        if ($max !== '') {
            return 'до ' . $max;
        }
        if ($min !== '') {
            return 'от ' . $min;
        }

        return '';
    }

    private static function normalize_fuel_type(string $raw): string {
        $raw = mb_strtolower($raw);
        $values = [];

        if (str_contains($raw, 'дрова')) {
            $values[] = 'дрова';
        }
        if (str_contains($raw, 'подготовка') || str_contains($raw, 'ггу')) {
            $values[] = 'подготовка под газ';
        }
        if (str_contains($raw, 'сабк') || preg_match('~(^|[,\s])газ([,\s]|$)~u', $raw)) {
            $values[] = 'газ';
        }
        if (str_contains($raw, 'газ, дрова') || str_contains($raw, 'газ + дрова')) {
            $values[] = 'газ + дрова';
        }

        return implode(', ', array_values(array_unique($values)));
    }

    private static function extract_attr(string $html, string $attr): string {
        if (preg_match('~' . preg_quote($attr, '~') . '=["\']([^"\']+)["\']~isu', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return '';
    }

    private static function extract_first_text(string $html, array $patterns): string {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                return trim(wp_strip_all_tags($match[1]));
            }
        }
        return '';
    }

    private static function first_present(array $options, array $chars, array $keys): string {
        foreach ($keys as $key) {
            if (!empty($options[$key])) {
                return $options[$key];
            }
            if (!empty($chars[$key])) {
                return $chars[$key];
            }
        }
        return '';
    }

    private static function fetch_html(string $url): string {
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'HWS Product Parser/0.1',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('HTTP ' . $code . ' while fetching source.');
        }

        $body = wp_remote_retrieve_body($response);
        if (!is_string($body) || trim($body) === '') {
            throw new RuntimeException('Empty source response.');
        }

        return $body;
    }

    private static function state(): array {
        $state = get_option(self::OPTION, []);
        if (!is_array($state)) {
            return [];
        }
        return $state;
    }

    private static function products_for_category(array $state, string $manufacturer, string $category): array {
        $products = $state['products'][$manufacturer][$category] ?? [];
        return is_array($products) ? $products : [];
    }

    private static function parsed_product(string $manufacturer, string $category, string $product_id): array {
        $parsed = self::state()['parsed_products'][$manufacturer][$category][$product_id] ?? [];
        return is_array($parsed) ? $parsed : [];
    }

    private static function product_status(string $manufacturer, string $category, string $product_id): array {
        $status = self::status(self::status_key('product', $manufacturer, $category, $product_id));
        if (!empty($status['parsed'])) {
            return $status;
        }

        $parsed = self::parsed_product($manufacturer, $category, $product_id);
        if (self::product_has_parsed_data($parsed)) {
            $status['parsed'] = true;
            $status['note'] = 'derived from parsed product data';
            return $status;
        }

        return $status;
    }

    private static function status(string $key): array {
        $status = self::state()['statuses'][$key] ?? [];
        return is_array($status) ? $status : ['parsed' => false, 'date' => ''];
    }

    private static function product_has_parsed_data(array $parsed): bool {
        return self::field_is_valid('title', $parsed['title'] ?? '')
            && self::field_is_valid('article', $parsed['article'] ?? '')
            && self::field_is_valid('price', $parsed['price'] ?? '');
    }

    private static function value_is_not_empty($value): bool {
        if (is_array($value)) {
            return count($value) > 0;
        }
        return trim((string) $value) !== '';
    }

    private static function field_is_valid(string $field, $value): bool {
        if (is_array($value)) {
            return count($value) > 0;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        if ($field === 'price') {
            return (float) preg_replace('~[^\d.]~', '', str_replace(',', '.', $value)) > 0;
        }
        if ($field === 'offer_image') {
            return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
        }
        if ($field === 'fuel_type') {
            $allowed = ['дрова', 'подготовка под газ', 'газ', 'газ + дрова'];
            $parts = array_filter(array_map('trim', explode(',', $value)));
            return $parts !== [] && count(array_diff($parts, $allowed)) === 0;
        }
        if ($field === 'steam_volume') {
            return preg_match('~\d~', $value) === 1;
        }

        return true;
    }

    private static function mark_status(string $key, bool $parsed, string $note = ''): void {
        $state = self::state();
        $state['statuses'][$key] = [
            'parsed' => $parsed,
            'date'   => current_time('mysql'),
            'note'   => $note,
        ];
        update_option(self::OPTION, $state, false);
    }

    private static function store_product_url(array $parsed): string {
        $sku = trim((string) ($parsed['article'] ?? ''));
        if ($sku === '' || !function_exists('wc_get_product_id_by_sku')) {
            return '';
        }

        $product_id = (int) wc_get_product_id_by_sku($sku);
        if ($product_id <= 0 || !function_exists('wc_get_product')) {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        if (method_exists($product, 'is_type') && $product->is_type('variation') && method_exists($product, 'get_parent_id')) {
            $parent_id = (int) $product->get_parent_id();
            if ($parent_id > 0) {
                $product_id = $parent_id;
            }
        }

        $url = get_permalink($product_id);
        return is_string($url) ? $url : '';
    }

    private static function status_key(string $scope, string $manufacturer, string $category = '', string $product = '', string $field = ''): string {
        return implode(':', array_filter([$scope, $manufacturer, $category, $product, $field], static fn(string $part): bool => $part !== ''));
    }

    private static function status_date(array $status): string {
        return !empty($status['date']) ? (string) $status['date'] : 'даты нет';
    }

    private static function format_value($value): string {
        if (is_array($value)) {
            if (!$value) {
                return '';
            }
            $pairs = [];
            foreach ($value as $key => $item) {
                $pairs[] = $key . ': ' . $item;
            }
            return implode('; ', array_slice($pairs, 0, 12));
        }
        return is_scalar($value) ? (string) $value : '';
    }

    private static function sanitize_manufacturer($value): string {
        $value = sanitize_key((string) $value);
        return isset(self::MANUFACTURERS[$value]) ? $value : 'easysteam';
    }

    private static function sanitize_category($value): string {
        $value = sanitize_key((string) $value);
        return isset(self::CATEGORIES[$value]) ? $value : '';
    }

    private static function sanitize_product_id($value): string {
        return preg_replace('~[^a-zA-Z0-9_-]~', '', (string) $value);
    }

    private static function product_id_from_url(string $url): string {
        $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
        $last = basename($path);
        $slug = sanitize_title($last);
        return $slug !== '' ? $slug : substr(md5($url), 0, 12);
    }

    private static function absolute_url(string $url): string {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        return 'https://easysteam.ru/' . ltrim($url, '/');
    }
}

HWS_Product_Parser::init();
