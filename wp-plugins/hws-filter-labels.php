<?php
/**
 * Plugin Name: HWS Catalog Filters
 * Description: Настройка фильтров каталога по атрибутам продуктов.
 * Version: 3.0.0
 */

defined('ABSPATH') || exit;

final class HWS_Catalog_Filters {

    private const OPTION_FILTERS = 'hws_catalog_filter_config';
    private const META_SUBCAT    = '_hws_filter_subcat_label';
    private const META_BRAND     = '_hws_filter_brand_label';

    public static function init(): void {
        add_action('admin_menu',               [__CLASS__, 'admin_menu']);
        add_action('wp_ajax_hws_cf_get_attrs', [__CLASS__, 'ajax_get_attrs']);
        add_action('wp_ajax_hws_cf_save',      [__CLASS__, 'ajax_save']);
        add_action('graphql_register_types',   [__CLASS__, 'register_graphql']);
    }

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Фильтры каталога',
            'Фильтры каталога',
            'manage_woocommerce',
            'hws-catalog-filters',
            [__CLASS__, 'render_page']
        );
    }

    public static function ajax_get_attrs(): void {
        check_ajax_referer('hws_catalog_filters');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Forbidden', 403);

        $cat_id = absint($_POST['cat_id'] ?? 0);
        if (!$cat_id) wp_send_json_error('No category');

        wp_send_json_success(self::global_product_attributes());
    }

    private static function global_product_attributes(): array {
        global $wpdb;

        $result = [];
        $taxonomies = function_exists('wc_get_attribute_taxonomies') ? wc_get_attribute_taxonomies() : [];
        foreach ((array) $taxonomies as $taxonomy) {
            $name = (string) ($taxonomy->attribute_name ?? '');
            if ($name === '') {
                continue;
            }

            $result[] = [
                'slug'  => 'pa_' . $name,
                'label' => (string) (($taxonomy->attribute_label ?? '') ?: $name),
            ];
        }

        if (empty($result)) {
            $rows = $wpdb->get_results("SELECT attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies ORDER BY attribute_label ASC");
            foreach ((array) $rows as $row) {
                $name = (string) ($row->attribute_name ?? '');
                if ($name === '') {
                    continue;
                }

                $result[] = [
                    'slug'  => 'pa_' . $name,
                    'label' => (string) (($row->attribute_label ?? '') ?: $name),
                ];
            }
        }

        usort($result, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $result;
    }

    public static function ajax_save(): void {
        check_ajax_referer('hws_catalog_filters');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Forbidden', 403);

        $cat_id = absint($_POST['cat_id'] ?? 0);
        $raw    = is_array($_POST['filters'] ?? null) ? $_POST['filters'] : [];

        $filters = [];
        foreach ($raw as $item) {
            $slug = sanitize_text_field($item['slug'] ?? '');
            $type = in_array($item['type'] ?? '', ['multicheck', 'input'], true) ? $item['type'] : 'multicheck';
            if (str_starts_with($slug, 'pa_')) {
                $filters[] = ['slug' => $slug, 'type' => $type];
            }
        }

        $config = get_option(self::OPTION_FILTERS, []);
        if (!is_array($config)) $config = [];

        if (empty($filters)) {
            unset($config[(string) $cat_id]);
        } else {
            $config[(string) $cat_id] = $filters;
        }

        update_option(self::OPTION_FILTERS, $config, false);
        if (function_exists('hws_revalidate')) hws_revalidate();
        wp_send_json_success();
    }

    public static function render_page(): void {
        $config = get_option(self::OPTION_FILTERS, []);
        if (!is_array($config)) $config = [];

        // Normalize legacy string format → {slug, type}
        foreach ($config as $cid => $items) {
            $config[$cid] = array_map(function ($item) {
                if (is_string($item)) return ['slug' => $item, 'type' => 'multicheck'];
                return $item;
            }, $items);
        }

        $cats = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
        ]);
        ?>
        <div class="wrap" style="max-width:1000px">
            <h1>Фильтры каталога</h1>

            <?php if (!is_wp_error($cats)) foreach ($cats as $cat):
                $saved = $config[(string) $cat->term_id] ?? [];
                $saved_count = count($saved);
            ?>
            <div class="hws-cat-card"
                 data-cat-id="<?php echo (int) $cat->term_id; ?>"
                 data-saved='<?php echo wp_json_encode($saved); ?>'
                 style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:16px;margin-bottom:12px">

                <!-- Header -->
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                    <strong style="font-size:15px"><?php echo esc_html($cat->name); ?></strong>
                    <span style="color:#999;font-size:12px">(<?php echo (int) $cat->count; ?> тов.)</span>
                    <span class="hws-saved-indicator" style="font-size:12px;color:<?php echo $saved_count ? '#2a7e2e' : '#999'; ?>">
                        <?php echo $saved_count ? "✓ $saved_count фильтров" : 'не настроено'; ?>
                    </span>
                </div>

                <!-- Load button -->
                <button class="button hws-load-attrs" type="button" style="margin-bottom:12px">Загрузить атрибуты</button>

                <!-- Two-panel editor (hidden until attrs loaded) -->
                <div class="hws-editor" style="display:none;gap:24px;align-items:flex-start">

                    <!-- Available attrs -->
                    <div style="flex:1;min-width:200px">
                        <div style="font-weight:600;font-size:13px;margin-bottom:8px;color:#555">Доступные атрибуты</div>
                        <div class="hws-available" style="border:1px solid #ddd;border-radius:4px;padding:8px;min-height:80px;display:flex;flex-direction:column;gap:4px">
                            <!-- populated by JS -->
                        </div>
                    </div>

                    <!-- Active filters -->
                    <div style="flex:1;min-width:200px">
                        <div style="font-weight:600;font-size:13px;margin-bottom:8px;color:#555">Активные фильтры</div>
                        <div class="hws-active" style="border:1px solid #ddd;border-radius:4px;padding:8px;min-height:80px;display:flex;flex-direction:column;gap:6px">
                            <!-- populated by JS -->
                        </div>
                    </div>

                    <!-- Save button -->
                    <div style="padding-top:28px">
                        <button class="button button-primary hws-save-cat" type="button">Сохранить</button>
                        <div class="hws-status" style="margin-top:6px;font-size:12px;min-height:16px;text-align:center"></div>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <style>
        .hws-attr-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            background: #f0f0f0;
            border: 1px solid #ddd;
            text-align: left;
            transition: background .15s;
        }
        .hws-attr-chip:hover { background: #e5e5e5; }
        .hws-attr-chip.is-available { background: #f0f6ff; border-color: #b3d4ff; color: #0a6ecf; }
        .hws-attr-chip.is-available:hover { background: #dceeff; }
        .hws-active-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        .hws-active-row .hws-type-sel {
            font-size: 12px;
            padding: 2px 4px;
            border: 1px solid #ccc;
            border-radius: 3px;
            background: #fff;
        }
        .hws-active-row .hws-remove {
            margin-left: auto;
            cursor: pointer;
            color: #999;
            font-size: 16px;
            line-height: 1;
            border: none;
            background: none;
            padding: 0 2px;
        }
        .hws-active-row .hws-remove:hover { color: #d63638; }
        .hws-editor { display: flex !important; }
        </style>

        <script>
        (function($){
            var nonce   = <?php echo wp_json_encode(wp_create_nonce('hws_catalog_filters')); ?>;
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;

            function escHtml(s) {
                return $('<span>').text(s).html();
            }

            function renderAvailable($card, allAttrs, activeFilters) {
                var activeSlugs = activeFilters.map(function(f){ return f.slug; });
                var $avail = $card.find('.hws-available').empty();
                allAttrs.forEach(function(attr) {
                    if (activeSlugs.indexOf(attr.slug) !== -1) return;
                    $avail.append(
                        $('<button class="hws-attr-chip is-available" type="button">')
                            .data('slug', attr.slug)
                            .data('label', attr.label)
                            .html('+ ' + escHtml(attr.label) + ' <small style="opacity:.6">(' + escHtml(attr.slug) + ')</small>')
                    );
                });
                if ($avail.children().length === 0) {
                    $avail.append('<span style="color:#999;font-size:13px">Все атрибуты добавлены</span>');
                }
            }

            function renderActive($card, allAttrs, activeFilters) {
                var $active = $card.find('.hws-active').empty();
                if (activeFilters.length === 0) {
                    $active.append('<span style="color:#999;font-size:13px">Нет активных фильтров</span>');
                    return;
                }
                activeFilters.forEach(function(filter) {
                    var attr = allAttrs.find(function(a){ return a.slug === filter.slug; }) || { label: filter.slug };
                    var $row = $('<div class="hws-active-row">')
                        .data('slug', filter.slug);
                    var $label = $('<span style="font-size:13px;flex:1">').text(attr.label + ' (' + filter.slug + ')');
                    var $type = $('<select class="hws-type-sel">')
                        .append('<option value="multicheck">Мультивыбор</option>')
                        .append('<option value="input">Ввод текста/числа</option>')
                        .val(filter.type || 'multicheck');
                    var $rm = $('<button class="hws-remove" type="button" title="Удалить">×</button>');
                    $row.append($label).append($type).append($rm);
                    $active.append($row);
                });
            }

            function getActiveFilters($card) {
                var filters = [];
                $card.find('.hws-active-row').each(function() {
                    filters.push({
                        slug: $(this).data('slug'),
                        type: $(this).find('.hws-type-sel').val()
                    });
                });
                return filters;
            }

            // Load attrs button
            $(document).on('click', '.hws-load-attrs', function(){
                var $card = $(this).closest('.hws-cat-card');
                var catId = String($card.data('cat-id'));
                var $btn  = $(this).prop('disabled', true).text('Загрузка…');

                $.post(ajaxUrl, {
                    action: 'hws_cf_get_attrs',
                    cat_id: catId,
                    _ajax_nonce: nonce
                }, function(r){
                    $btn.prop('disabled', false).text('Обновить атрибуты');
                    if (!r.success) return;

                    var allAttrs = r.data;
                    var saved    = $card.data('saved') || [];
                    // normalize legacy string format
                    var activeFilters = saved.map(function(s){
                        return typeof s === 'string' ? {slug: s, type: 'multicheck'} : s;
                    });

                    $card.data('all-attrs', allAttrs);
                    $card.data('active-filters', activeFilters);
                    renderAvailable($card, allAttrs, activeFilters);
                    renderActive($card, allAttrs, activeFilters);
                    $card.find('.hws-editor').css('display', 'flex');
                });
            });

            // Add attr to active
            $(document).on('click', '.hws-available .hws-attr-chip', function(){
                var $card   = $(this).closest('.hws-cat-card');
                var allAttrs = $card.data('all-attrs') || [];
                var activeFilters = getActiveFilters($card);
                var slug    = $(this).data('slug');
                var label   = $(this).data('label');
                if (activeFilters.find(function(f){ return f.slug === slug; })) return;
                activeFilters.push({ slug: slug, type: 'multicheck' });
                $card.data('active-filters', activeFilters);
                renderAvailable($card, allAttrs, activeFilters);
                renderActive($card, allAttrs, activeFilters);
            });

            // Remove attr from active
            $(document).on('click', '.hws-active-row .hws-remove', function(){
                var $card = $(this).closest('.hws-cat-card');
                var allAttrs = $card.data('all-attrs') || [];
                var activeFilters = getActiveFilters($card);
                var slug = $(this).closest('.hws-active-row').data('slug');
                activeFilters = activeFilters.filter(function(f){ return f.slug !== slug; });
                $card.data('active-filters', activeFilters);
                renderAvailable($card, allAttrs, activeFilters);
                renderActive($card, allAttrs, activeFilters);
            });

            // Save
            $(document).on('click', '.hws-save-cat', function(){
                var $card  = $(this).closest('.hws-cat-card');
                var catId  = String($card.data('cat-id'));
                var filters = getActiveFilters($card);
                var $btn   = $(this).prop('disabled', true).text('Сохранение…');
                var $stat  = $card.find('.hws-status');
                var $ind   = $card.find('.hws-saved-indicator');

                $.post(ajaxUrl, {
                    action:  'hws_cf_save',
                    cat_id:  catId,
                    filters: filters,
                    _ajax_nonce: nonce
                }, function(r){
                    $btn.prop('disabled', false).text('Сохранить');
                    if (r.success) {
                        $card.data('saved', filters);
                        $ind.text(filters.length ? '✓ ' + filters.length + ' фильтров' : 'не настроено')
                            .css('color', filters.length ? '#2a7e2e' : '#999');
                        $stat.text('✓ Сохранено').css('color', '#2a7e2e');
                        setTimeout(function(){ $stat.text(''); }, 2000);
                    } else {
                        $stat.text('Ошибка').css('color', '#d63638');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    // Returns normalized filter list: [{slug, type}]
    public static function get_filters_for_category(int $cat_id): array {
        $config = get_option(self::OPTION_FILTERS, []);
        $items  = is_array($config) ? ($config[(string) $cat_id] ?? []) : [];
        // backward compat: normalize string → {slug, type}
        return array_map(function ($item) {
            if (is_string($item)) return ['slug' => $item, 'type' => 'multicheck'];
            return $item;
        }, $items);
    }

    public static function register_graphql(): void {
        register_graphql_field('ProductCategory', 'hwsFilterSubcatLabel', [
            'type'    => 'String',
            'resolve' => fn($term) => get_term_meta($term->databaseId, self::META_SUBCAT, true) ?: null,
        ]);
        register_graphql_field('ProductCategory', 'hwsFilterBrandLabel', [
            'type'    => 'String',
            'resolve' => fn($term) => get_term_meta($term->databaseId, self::META_BRAND, true) ?: null,
        ]);

        register_graphql_object_type('HwsCatalogFilter', [
            'description' => 'Фильтр каталога: атрибут + тип UI',
            'fields'      => [
                'slug' => ['type' => 'String'],
                'type' => ['type' => 'String'], // multicheck | input
            ],
        ]);

        register_graphql_field('ProductCategory', 'hwsCatalogFilters', [
            'type'        => ['list_of' => 'HwsCatalogFilter'],
            'description' => 'Настроенные фильтры для каталога этой категории',
            'resolve'     => function ($term) {
                return self::get_filters_for_category((int) $term->databaseId) ?: null;
            },
        ]);
    }
}

HWS_Catalog_Filters::init();
