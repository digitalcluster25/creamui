<?php
/**
 * Plugin Name: HWS Commerce Info
 * Description: Условия оплаты/доставки/гарантии и инфографика по бренду/категории.
 * Version: 0.4.0
 * Author: HWS
 */

defined('ABSPATH') || exit;

final class HWS_Commerce_Info {

    private const OPTION      = 'hws_commerce_conditions';
    private const OPTION_INFO = 'hws_infographic_configs';

    private const SERVICES = [
        'delivery'     => 'Доставка',
        'payment'      => 'Оплата',
        'warranty'     => 'Гарантия',
        'postwarranty' => 'Постгарантийное обслуживание',
    ];

    private const ICONS = [
        'delivery'     => '<svg viewBox="0 0 24 24"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h9A2.5 2.5 0 0 1 17 6.5V8h1.4c.7 0 1.35.33 1.76.9l1.44 2.02c.26.37.4.8.4 1.25V17h-2.05a2.75 2.75 0 0 1-5.4 0h-5.1a2.75 2.75 0 0 1-5.4 0H3V6.5Zm2 0V15h.55a2.75 2.75 0 0 1 4.9 0H15V6.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5Zm12 3.5v5h.55a2.75 2.75 0 0 1 2.45-1.5V12.2a.2.2 0 0 0-.04-.12L18.52 10H17ZM6.75 16a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm10.5 0a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z"/></svg>',
        'payment'      => '<svg viewBox="0 0 24 24"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v2h16V7H4Zm0 5v5h16v-5H4Zm2 2h5v2H6v-2Z"/></svg>',
        'warranty'     => '<svg viewBox="0 0 24 24"><path d="M12 2.5 19.5 5.5V11c0 5.05-3.18 8.6-7.5 10.5C7.68 19.6 4.5 16.05 4.5 11V5.5L12 2.5Zm3.7 6.6-4.7 4.7-2.2-2.2-1.1 1.1 3.3 3.3 5.8-5.8-1.1-1.1Z"/></svg>',
        'postwarranty' => '<svg viewBox="0 0 24 24"><path d="M3 3h7v2H5v14h14v-5h2v7H3V3Zm11 0h7v7h-2V6.41l-9.29 9.3-1.42-1.42L17.59 5H14V3Z"/></svg>',
    ];

    // ─── Boot ────────────────────────────────────────────────────────────

    public static function init(): void {
        add_action('admin_menu',                         [__CLASS__, 'admin_menu']);
        add_action('admin_post_hws_save_conditions',     [__CLASS__, 'handle_save']);
        add_action('admin_post_hws_save_infographic',    [__CLASS__, 'handle_save_infographic']);
        add_action('wp_ajax_hws_cat_attrs',              [__CLASS__, 'ajax_cat_attrs']);
        add_action('wp_ajax_hws_attr_values',            [__CLASS__, 'ajax_attr_values']);
        add_action('admin_enqueue_scripts',              [__CLASS__, 'admin_css']);
        add_action('admin_footer',                       [__CLASS__, 'admin_footer_js']);
        add_action('woocommerce_single_product_summary', [__CLASS__, 'render_product_box'], 11);
        add_action('wp_head',                            [__CLASS__, 'render_styles']);
        add_action('graphql_register_types',             [__CLASS__, 'register_graphql']);
        add_filter('graphql_connection_max_query_amount', fn() => 1000);
    }

    // ─── Admin menu ──────────────────────────────────────────────────────

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce', 'Оплата и доставка', 'Оплата и доставка',
            'manage_woocommerce', 'hws-commerce-info', [__CLASS__, 'render_admin_page']
        );
        add_submenu_page(
            'woocommerce', 'Инфографика товаров', 'Инфографика',
            'manage_woocommerce', 'hws-infographic', [__CLASS__, 'render_infographic_page']
        );
    }

    // ─── Admin CSS ───────────────────────────────────────────────────────

    public static function admin_css(string $hook): void {
        $pages = ['woocommerce_page_hws-commerce-info', 'woocommerce_page_hws-infographic'];
        if (!in_array($hook, $pages, true)) return;
        ?>
        <style>
        .hws-wrap { max-width: 960px; }
        .hws-card { border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 16px; background: #fff; }
        .hws-card__head { display:flex;align-items:center;justify-content:space-between;
            padding:14px 18px;background:#f6f7f7;border-bottom:1px solid #c3c4c7;
            border-radius:4px 4px 0 0;gap:12px; }
        .hws-card__head--new { background:#f0f6fc; }
        .hws-card__selects { display:flex;gap:10px;flex-wrap:wrap;flex:1; }
        .hws-card__selects select { min-width:170px; }
        .hws-card__actions { display:flex;gap:8px;flex-shrink:0; }
        .hws-card__body { padding:18px; }
        .hws-card__body table { width:100%; }
        .hws-card__body th { width:200px;vertical-align:top;padding-top:10px;font-size:13px; }
        .hws-card__body td { padding:4px 0; }
        .hws-card__body textarea { width:100%;box-sizing:border-box; }
        /* Infographic */
        .hws-info-blocks { margin-top:12px; }
        .hws-info-block { display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap; }
        .hws-info-block input[type=text] { flex:1;min-width:100px; }
        .hws-info-block select { min-width:180px; }
        .hws-info-block__label { font-size:12px;color:#666;white-space:nowrap; }
        .hws-add-block-btn { margin-top:8px; }
        .hws-empty-state { padding:32px;text-align:center;color:#888;
            border:1px dashed #c3c4c7;border-radius:4px; }
        #hws-add-btn,#hws-add-info-btn { margin-bottom:20px; }
        </style>
        <?php
    }

    // ─── Admin footer JS ─────────────────────────────────────────────────

    public static function admin_footer_js(): void {
        $screen = get_current_screen();
        if (!$screen) return;

        // ── Commerce Info JS ──────────────────────────────────────────
        if ($screen->id === 'woocommerce_page_hws-commerce-info') {
            ?>
            <script type="text/html" id="hws-new-tpl">
                <div class="hws-card" data-new="1">
                    <?php self::render_card_html('__IDX__', null); ?>
                </div>
            </script>
            <script>
            (function($){
                var allCats = <?php echo wp_json_encode(self::cats_by_parent()); ?>;
                var newIdx  = <?php echo count(self::conditions()); ?>;

                function buildSubcatOptions(parentId, selectedId) {
                    var opts = '<option value="0">— Уровень категории —</option>';
                    (allCats[parentId] || []).forEach(function(c) {
                        opts += '<option value="' + c.id + '"' + (c.id == selectedId ? ' selected' : '') + '>' + c.name + '</option>';
                    });
                    return opts;
                }

                function initCard(card) {
                    card.find('.hws-cat-select').on('change', function() {
                        card.find('.hws-subcat-select').html(buildSubcatOptions($(this).val(), 0));
                    });
                }

                $('.hws-card').each(function() { initCard($(this)); });

                $('#hws-add-btn').on('click', function() {
                    var uid  = 'new_' + (newIdx++);
                    var html = $('#hws-new-tpl').html().replace(/__IDX__/g, uid);
                    var card = $(html).filter('.hws-card');
                    initCard(card);
                    $('#hws-empty-state').hide();
                    $('#hws-conditions-list').prepend(card);
                    card.find('.hws-card__head select').first().focus();
                    $('#hws-save-btn').show();
                });

                $(document).on('click', '.hws-delete-btn', function() {
                    var card = $(this).closest('.hws-card');
                    if (card.data('new')) {
                        card.remove();
                    } else {
                        if (!confirm('Удалить условие?')) return;
                        card.find('.hws-delete-flag').val('1');
                        card.closest('form').submit();
                    }
                });
            })(jQuery);
            </script>
            <?php
        }

        // ── Infographic JS ────────────────────────────────────────────
        if ($screen->id === 'woocommerce_page_hws-infographic') {
            $info_configs = self::infographic_configs();
            ?>
            <script type="text/html" id="hws-info-config-tpl">
                <div class="hws-card" data-new="1">
                    <?php self::render_info_card_html('__CIDX__', null); ?>
                </div>
            </script>
            <script type="text/html" id="hws-info-block-tpl">
                <?php self::render_info_block_html('__CIDX__', '__BIDX__', null); ?>
            </script>
            <script>
            (function($){
                var allCats   = <?php echo wp_json_encode(self::cats_by_parent()); ?>;
                var ajaxUrl   = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce     = <?php echo wp_json_encode(wp_create_nonce('hws_infographic')); ?>;
                var newCIdx   = <?php echo count($info_configs); ?>;

                function buildSubcatOptions(parentId, selectedId) {
                    var opts = '<option value="0">— Уровень категории —</option>';
                    (allCats[parentId] || []).forEach(function(c) {
                        opts += '<option value="' + c.id + '"' + (c.id == selectedId ? ' selected' : '') + '>' + c.name + '</option>';
                    });
                    return opts;
                }

                function resolvedCatId(card) {
                    var sub = card.find('.hws-subcat-select').val();
                    return (sub && sub !== '0') ? sub : card.find('.hws-cat-select').val();
                }

                function loadAttrs(card, catId, callback) {
                    if (!catId || catId == '0') { callback({}); return; }
                    $.get(ajaxUrl, { action: 'hws_cat_attrs', cat_id: catId, _ajax_nonce: nonce }, function(r) {
                        if (r.success) callback(r.data);
                    });
                }

function updateAttrDropdowns(card, attrs) {
                    card.find('.hws-attr-select').each(function() {
                        var current = $(this).val();
                        var html = '<option value="">— Выберите атрибут —</option>';
                        $.each(attrs, function(slug, name) {
                            html += '<option value="' + slug + '"' + (slug === current ? ' selected' : '') + '>' + name + '</option>';
                        });
                        $(this).html(html);
                    });
                }

                function initInfoCard(card) {
                    card.find('.hws-cat-select').on('change', function() {
                        card.find('.hws-subcat-select').html(buildSubcatOptions($(this).val(), 0));
                        var catId  = resolvedCatId(card);
                        loadAttrs(card, catId, function(attrs) { updateAttrDropdowns(card, attrs); });
                    });

                    card.find('.hws-subcat-select').on('change', function() {
                        var catId = resolvedCatId(card);
                        loadAttrs(card, catId, function(attrs) { updateAttrDropdowns(card, attrs); });
                    });

                    card.find('.hws-add-block-btn').on('click', function() {
                        var cidx  = card.data('cidx');
                        var bidx  = card.find('.hws-info-block').length;
                        var html  = $('#hws-info-block-tpl').html()
                            .replace(/__CIDX__/g, cidx)
                            .replace(/__BIDX__/g, bidx);
                        var block = $(html);
                        card.find('.hws-info-blocks').append(block);
                        // load attrs into new block
                        var catId = resolvedCatId(card);
                        loadAttrs(card, catId, function(attrs) {
                            var html2 = '<option value="">— Атрибут —</option>';
                            $.each(attrs, function(slug, name) { html2 += '<option value="' + slug + '">' + name + '</option>'; });
                            block.find('.hws-attr-select').html(html2);
                        });
                        // attr change handler
                        initBlockAttrChange(block);
                    });

                    card.find('.hws-info-block').each(function() { initBlockAttrChange($(this)); });

                    // load attrs for existing card on init
                    var catId = resolvedCatId(card);
                    if (catId && catId !== '0') {
                        loadAttrs(card, catId, function(attrs) { updateAttrDropdowns(card, attrs); });
                    }
                }

                function initBlockAttrChange(block) {
                    block.find('.hws-attr-select').on('change', function() {
                        var labelInput = block.find('.hws-block-label');
                        if (!labelInput.val()) {
                            labelInput.val($(this).find('option:selected').text());
                        }
                    });
                }

                $('.hws-card[data-cidx]').each(function() { initInfoCard($(this)); });

                $('#hws-add-info-btn').on('click', function() {
                    var cidx = newCIdx++;
                    var html = $('#hws-info-config-tpl').html().replace(/__CIDX__/g, cidx);
                    var card = $(html).filter('.hws-card');
                    card.attr('data-cidx', cidx);
                    initInfoCard(card);
                    $('#hws-info-empty').hide();
                    $('#hws-info-list').prepend(card);
                    $('#hws-info-save-btn').show();
                });

                $(document).on('click', '.hws-info-delete-config', function() {
                    var card = $(this).closest('.hws-card');
                    if (card.data('new')) { card.remove(); return; }
                    if (!confirm('Удалить конфигурацию?')) return;
                    card.find('.hws-info-delete-flag').val('1');
                    card.closest('form').submit();
                });

                $(document).on('click', '.hws-info-delete-block', function() {
                    $(this).closest('.hws-info-block').remove();
                });
            })(jQuery);
            </script>
            <?php
        }
    }

    // ─── Commerce Info admin page ─────────────────────────────────────────

    public static function render_admin_page(): void {
        $conditions = self::conditions();
        ?>
        <div class="wrap hws-wrap" id="hws-conditions-wrap">
            <h1>Оплата, доставка и гарантия</h1>
            <p>Условия применяются по наиболее точному совпадению: бренд + подкатегория → бренд + категория → бренд.</p>
            <?php if (!empty($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Сохранено.</p></div>
            <?php endif; ?>
            <button type="button" id="hws-add-btn" class="button button-primary">+ Добавить условие</button>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hws_save_conditions'); ?>
                <input type="hidden" name="action" value="hws_save_conditions">
                <div id="hws-conditions-list">
                    <?php if (empty($conditions)) : ?>
                        <div class="hws-empty-state" id="hws-empty-state">
                            Условия не добавлены. Нажмите «+ Добавить условие».
                        </div>
                    <?php else : ?>
                        <?php foreach ($conditions as $idx => $cond) : ?>
                            <div class="hws-card">
                                <?php self::render_card_html((string)$idx, $cond); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="hws-save-btn" style="margin-top:12px;<?php echo empty($conditions) ? 'display:none' : ''; ?>">
                    <?php submit_button('Сохранить', 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_card_html(string $idx, ?array $cond): void {
        $brands   = self::brand_terms();
        $top_cats = self::top_cats();
        $sel_brand = (int)($cond['brand_id']       ?? 0);
        $sel_cat   = (int)($cond['category_id']    ?? 0);
        $sel_sub   = (int)($cond['subcategory_id'] ?? 0);
        $prefix    = "hws_conditions[{$idx}]";
        $is_new    = ($cond === null);
        ?>
        <div class="hws-card__head <?php echo $is_new ? 'hws-card__head--new' : ''; ?>">
            <div class="hws-card__selects">
                <select name="<?php echo esc_attr($prefix); ?>[brand_id]" required>
                    <option value="">— Бренд —</option>
                    <?php foreach ($brands as $b) : ?>
                        <option value="<?php echo esc_attr($b->term_id); ?>" <?php selected($sel_brand, $b->term_id); ?>>
                            <?php echo esc_html($b->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="<?php echo esc_attr($prefix); ?>[category_id]" class="hws-cat-select">
                    <option value="0">— Любая категория —</option>
                    <?php foreach ($top_cats as $c) : ?>
                        <option value="<?php echo esc_attr($c->term_id); ?>" <?php selected($sel_cat, $c->term_id); ?>>
                            <?php echo esc_html($c->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="<?php echo esc_attr($prefix); ?>[subcategory_id]" class="hws-subcat-select">
                    <option value="0">— Любая подкатегория —</option>
                    <?php if ($sel_cat) {
                        foreach (self::child_cats($sel_cat) as $s) {
                            printf('<option value="%d"%s>%s</option>', $s->term_id, selected($sel_sub, $s->term_id, false), esc_html($s->name));
                        }
                    } ?>
                </select>
            </div>
            <div class="hws-card__actions">
                <input type="hidden" name="<?php echo esc_attr($prefix); ?>[_delete]" class="hws-delete-flag" value="0">
                <button type="button" class="button hws-delete-btn">Удалить</button>
            </div>
        </div>
        <div class="hws-card__body">
            <table>
                <?php foreach (self::SERVICES as $key => $title) : ?>
                    <tr>
                        <th><strong><?php echo esc_html($title); ?></strong></th>
                        <td>
                            <textarea name="<?php echo esc_attr("{$prefix}[{$key}_text]"); ?>" rows="2"
                                      placeholder="Оставьте пустым — сервис не выводится"><?php
                                echo esc_textarea($cond["{$key}_text"] ?? '');
                            ?></textarea>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th>Примечание</th>
                    <td>
                        <textarea name="<?php echo esc_attr("{$prefix}[note]"); ?>" rows="2"
                                  placeholder="Дополнительный текст внизу блока"><?php
                            echo esc_textarea($cond['note'] ?? '');
                        ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public static function handle_save(): void {
        if (!current_user_can('manage_woocommerce')) wp_die('Нет прав.');
        check_admin_referer('hws_save_conditions');
        $incoming = isset($_POST['hws_conditions']) && is_array($_POST['hws_conditions'])
            ? wp_unslash($_POST['hws_conditions']) : [];
        $saved = [];
        foreach ($incoming as $row) {
            if (!empty($row['_delete'])) continue;
            $brand_id = absint($row['brand_id'] ?? 0);
            if (!$brand_id) continue;
            $saved[] = [
                'brand_id'          => $brand_id,
                'category_id'       => absint($row['category_id']    ?? 0),
                'subcategory_id'    => absint($row['subcategory_id'] ?? 0),
                'delivery_text'     => sanitize_textarea_field($row['delivery_text']     ?? ''),
                'payment_text'      => sanitize_textarea_field($row['payment_text']      ?? ''),
                'warranty_text'     => sanitize_textarea_field($row['warranty_text']     ?? ''),
                'postwarranty_text' => sanitize_textarea_field($row['postwarranty_text'] ?? ''),
                'note'              => sanitize_textarea_field($row['note']              ?? ''),
            ];
        }
        update_option(self::OPTION, $saved, false);
        wp_safe_redirect(add_query_arg(['page' => 'hws-commerce-info', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    // ─── Infographic admin page ───────────────────────────────────────────

    public static function render_infographic_page(): void {
        $configs = self::infographic_configs();
        ?>
        <div class="wrap hws-wrap">
            <h1>Инфографика товаров</h1>
            <p>Блоки инфографики под галереей товара. Применяются по совпадению: бренд + подкатегория → бренд + категория → бренд.</p>
            <?php if (!empty($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Сохранено.</p></div>
            <?php endif; ?>
            <button type="button" id="hws-add-info-btn" class="button button-primary">+ Добавить конфигурацию</button>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hws_save_infographic'); ?>
                <input type="hidden" name="action" value="hws_save_infographic">
                <div id="hws-info-list">
                    <?php if (empty($configs)) : ?>
                        <div class="hws-empty-state" id="hws-info-empty">
                            Конфигурации не добавлены. Нажмите «+ Добавить конфигурацию».
                        </div>
                    <?php else : ?>
                        <?php foreach ($configs as $idx => $cfg) : ?>
                            <div class="hws-card" data-cidx="<?php echo esc_attr($idx); ?>">
                                <?php self::render_info_card_html((string)$idx, $cfg); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="hws-info-save-btn" style="margin-top:12px;<?php echo empty($configs) ? 'display:none' : ''; ?>">
                    <?php submit_button('Сохранить', 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_info_card_html(string $cidx, ?array $cfg): void {
        $brands   = self::brand_terms();
        $top_cats = self::top_cats();
        $sel_brand = (int)($cfg['brand_id']       ?? 0);
        $sel_cat   = (int)($cfg['category_id']    ?? 0);
        $sel_sub   = (int)($cfg['subcategory_id'] ?? 0);
        $prefix    = "hws_info[{$cidx}]";
        $is_new    = ($cfg === null);
        $blocks    = $cfg['blocks'] ?? [];
        ?>
        <div class="hws-card__head <?php echo $is_new ? 'hws-card__head--new' : ''; ?>">
            <div class="hws-card__selects">
                <select name="<?php echo esc_attr($prefix); ?>[brand_id]">
                    <option value="0">— Любой бренд —</option>
                    <?php foreach ($brands as $b) : ?>
                        <option value="<?php echo esc_attr($b->term_id); ?>" <?php selected($sel_brand, $b->term_id); ?>>
                            <?php echo esc_html($b->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="<?php echo esc_attr($prefix); ?>[category_id]" class="hws-cat-select">
                    <option value="0">— Любая категория —</option>
                    <?php foreach ($top_cats as $c) : ?>
                        <option value="<?php echo esc_attr($c->term_id); ?>" <?php selected($sel_cat, $c->term_id); ?>>
                            <?php echo esc_html($c->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="<?php echo esc_attr($prefix); ?>[subcategory_id]" class="hws-subcat-select">
                    <option value="0">— Любая подкатегория —</option>
                    <?php if ($sel_cat) {
                        foreach (self::child_cats($sel_cat) as $s) {
                            printf('<option value="%d"%s>%s</option>', $s->term_id, selected($sel_sub, $s->term_id, false), esc_html($s->name));
                        }
                    } ?>
                </select>
            </div>
            <div class="hws-card__actions">
                <input type="hidden" name="<?php echo esc_attr($prefix); ?>[_delete]" class="hws-info-delete-flag" value="0">
                <button type="button" class="button hws-info-delete-config">Удалить</button>
            </div>
        </div>
        <div class="hws-card__body">
            <p style="margin:0 0 8px;font-weight:600;font-size:13px;">Блоки инфографики</p>
            <div class="hws-info-blocks">
                <?php foreach ($blocks as $bidx => $block) : ?>
                    <?php self::render_info_block_html($cidx, (string)$bidx, $block); ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button hws-add-block-btn">+ Добавить блок</button>
        </div>
        <?php
    }

    private static function render_info_block_html(string $cidx, string $bidx, ?array $block): void {
        $prefix    = "hws_info[{$cidx}][blocks][{$bidx}]";
        $attr_slug = $block['attr_slug'] ?? '';
        $label     = self::normalize_block_label((string) ($block['label'] ?? ''), (string) $attr_slug);
        ?>
        <div class="hws-info-block">
            <span class="hws-info-block__label">Атрибут</span>
            <select name="<?php echo esc_attr($prefix); ?>[attr_slug]" class="hws-attr-select">
                <option value="">— Выберите атрибут —</option>
                <?php if ($attr_slug) : ?>
                    <option value="<?php echo esc_attr($attr_slug); ?>" selected>
                        <?php echo esc_html(self::attribute_label($attr_slug)); ?>
                    </option>
                <?php endif; ?>
            </select>
            <span class="hws-info-block__label">Метка</span>
            <input type="text" class="hws-block-label" name="<?php echo esc_attr($prefix); ?>[label]"
                   value="<?php echo esc_attr($label); ?>" placeholder="напр. Мощность">
            <button type="button" class="button button-small hws-info-delete-block">×</button>
        </div>
        <?php
    }

    public static function handle_save_infographic(): void {
        if (!current_user_can('manage_woocommerce')) wp_die('Нет прав.');
        check_admin_referer('hws_save_infographic');
        $incoming = isset($_POST['hws_info']) && is_array($_POST['hws_info'])
            ? wp_unslash($_POST['hws_info']) : [];
        $saved = [];
        foreach ($incoming as $row) {
            if (!empty($row['_delete'])) continue;
            $blocks_raw = $row['blocks'] ?? [];
            $blocks = [];
            foreach ($blocks_raw as $b) {
                $attr_slug = sanitize_text_field((string) ($b['attr_slug'] ?? ''));
                $lbl = self::normalize_block_label(
                    sanitize_text_field($b['label'] ?? ''),
                    $attr_slug
                );
                if (empty($b['attr_slug']) && $lbl === '') continue;
                $blocks[] = [
                    'attr_slug' => $attr_slug,
                    'label'     => $lbl,
                ];
            }
            if (empty($blocks)) continue;
            $saved[] = [
                'brand_id'       => absint($row['brand_id']       ?? 0),
                'category_id'    => absint($row['category_id']    ?? 0),
                'subcategory_id' => absint($row['subcategory_id'] ?? 0),
                'blocks'         => $blocks,
            ];
        }
        update_option(self::OPTION_INFO, $saved, false);
        wp_safe_redirect(add_query_arg(['page' => 'hws-infographic', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    // ─── AJAX ────────────────────────────────────────────────────────────

    public static function ajax_cat_attrs(): void {
        check_ajax_referer('hws_infographic');
        $cat_id = absint($_GET['cat_id'] ?? 0);
        if (!$cat_id) { wp_send_json_success([]); return; }

        $term = get_term($cat_id, 'product_cat');
        if (!$term || is_wp_error($term)) { wp_send_json_success([]); return; }

        $product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'fields'         => 'ids',
            'tax_query'      => [['taxonomy' => 'product_cat', 'terms' => $cat_id, 'include_children' => true]],
        ]);

        $attrs = [];
        foreach ($product_ids as $pid) {
            $p = wc_get_product($pid);
            if (!$p) continue;
            foreach ($p->get_attributes() as $attr) {
                if (!$attr instanceof WC_Product_Attribute) continue;
                $name = trim((string) $attr->get_name());
                if ($name === '' || isset($attrs[$name])) continue;
                $attrs[$name] = self::attribute_label($name);
            }

            foreach (self::product_characteristics($pid) as $spec_label => $spec_value) {
                if ($spec_value === '') {
                    continue;
                }

                $spec_key = self::spec_key($spec_label);
                if (isset($attrs[$spec_key])) {
                    continue;
                }

                $attrs[$spec_key] = $spec_label;
            }
        }
        if (!empty($attrs)) {
            natcasesort($attrs);
        }
        wp_send_json_success($attrs);
    }

    public static function ajax_attr_values(): void {
        check_ajax_referer('hws_infographic');
        $slug = sanitize_key($_GET['attr_slug'] ?? '');
        if (!$slug || !taxonomy_exists($slug)) { wp_send_json_success([]); return; }
        $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
        $values = (!is_wp_error($terms) && is_array($terms))
            ? array_map(fn($t) => $t->name, $terms) : [];
        wp_send_json_success($values);
    }

    // ─── Frontend ────────────────────────────────────────────────────────

    public static function render_product_box(): void {
        if (!is_product()) return;
        global $product;
        if (!$product instanceof WC_Product) return;
        $cond = self::resolve_condition($product->get_id());
        if (!$cond) return;
        $items = [];
        foreach (self::SERVICES as $key => $title) {
            $text = trim($cond["{$key}_text"] ?? '');
            if ($text !== '') $items[] = ['key' => $key, 'title' => $title, 'text' => $text];
        }
        $note = trim($cond['note'] ?? '');
        if (!$items && !$note) return;
        ?>
        <section class="hws-commerce-info" aria-label="Условия покупки">
            <?php foreach ($items as $item) : ?>
                <div class="hws-commerce-info__item">
                    <span class="hws-commerce-info__icon" aria-hidden="true"><?php echo self::ICONS[$item['key']]; ?></span>
                    <div>
                        <strong><?php echo esc_html($item['title']); ?></strong>
                        <span><?php echo esc_html($item['text']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($note) : ?>
                <p class="hws-commerce-info__note"><?php echo esc_html($note); ?></p>
            <?php endif; ?>
        </section>
        <?php
    }

    public static function render_styles(): void {
        if (!is_product()) return;
        ?>
        <style>
        .hws-commerce-info{display:grid;gap:14px;margin:24px 0 22px;padding:22px 24px;border:1px solid rgba(40,40,40,.14);border-radius:18px;background:rgba(255,255,255,.28)}
        .hws-commerce-info__item{display:grid;grid-template-columns:34px minmax(0,1fr);gap:14px;align-items:start}
        .hws-commerce-info__icon{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:rgba(62,87,89,.12);color:#3e5759}
        .hws-commerce-info__icon svg{width:20px;height:20px;fill:currentColor}
        .hws-commerce-info strong{display:block;margin-bottom:3px;color:#282828;font-size:15px;line-height:1.25;font-weight:700}
        .hws-commerce-info span,.hws-commerce-info__note{color:rgba(40,40,40,.76);font-size:14px;line-height:1.45}
        .hws-commerce-info__note{margin:4px 0 0;padding-top:12px;border-top:1px solid rgba(40,40,40,.1)}
        </style>
        <?php
    }

    // ─── GraphQL ─────────────────────────────────────────────────────────

    public static function register_graphql(): void {
        // Commerce info type
        register_graphql_object_type('HwsCommerceInfo', [
            'fields' => [
                'deliveryTitle'     => ['type' => 'String'],
                'deliveryText'      => ['type' => 'String'],
                'paymentTitle'      => ['type' => 'String'],
                'paymentText'       => ['type' => 'String'],
                'warrantyTitle'     => ['type' => 'String'],
                'warrantyText'      => ['type' => 'String'],
                'postwarrantyTitle' => ['type' => 'String'],
                'postwarrantyText'  => ['type' => 'String'],
                'note'              => ['type' => 'String'],
            ],
        ]);

        // Highlight type
        register_graphql_object_type('HwsHighlight', [
            'fields' => [
                'value' => ['type' => 'String'],
                'label' => ['type' => 'String'],
            ],
        ]);

        foreach (['SimpleProduct', 'VariableProduct'] as $type) {
            register_graphql_field($type, 'hwsCommerceInfo', [
                'type'    => 'HwsCommerceInfo',
                'resolve' => [__CLASS__, 'resolve_graphql'],
            ]);
            register_graphql_field($type, 'hwsHighlights', [
                'type'    => ['list_of' => 'HwsHighlight'],
                'resolve' => [__CLASS__, 'resolve_highlights_graphql'],
            ]);
        }
    }

    public static function resolve_graphql($product): ?array {
        $id   = (int)($product->databaseId ?? 0);
        $cond = self::resolve_condition($id);
        if (!$cond) return null;
        return [
            'deliveryTitle'     => self::SERVICES['delivery'],
            'deliveryText'      => $cond['delivery_text']     ?: null,
            'paymentTitle'      => self::SERVICES['payment'],
            'paymentText'       => $cond['payment_text']      ?: null,
            'warrantyTitle'     => self::SERVICES['warranty'],
            'warrantyText'      => $cond['warranty_text']     ?: null,
            'postwarrantyTitle' => self::SERVICES['postwarranty'],
            'postwarrantyText'  => $cond['postwarranty_text'] ?: null,
            'note'              => $cond['note']              ?: null,
        ];
    }

    public static function resolve_highlights_graphql($product): array {
        $id  = (int)($product->databaseId ?? 0);
        $cfg = self::resolve_infographic($id);
        if (!$cfg || empty($cfg['blocks'])) return [];

        $wc = wc_get_product($id);
        if (!$wc) return [];

        $result = [];
        foreach ($cfg['blocks'] as $block) {
            $slug  = trim((string) ($block['attr_slug'] ?? ''));
            $label = self::normalize_block_label((string) ($block['label'] ?? ''), $slug);
            if ($slug === '') continue;
            $value = self::product_attribute_value($wc, $slug);
            if ($value === '') continue;
            $result[] = [
                'value' => $value,
                'label' => $label ?: self::attribute_label($slug),
            ];
        }
        return $result;
    }

    private static function attribute_label(string $attr_name): string {
        $attr_name = trim($attr_name);
        if ($attr_name === '') return '';

        if (self::is_spec_key($attr_name)) {
            return self::spec_label_from_key($attr_name);
        }

        $label = wc_attribute_label($attr_name);
        return is_string($label) && $label !== '' ? $label : $attr_name;
    }

    private static function product_attribute_value(WC_Product $product, string $attr_name): string {
        $attr_name = trim($attr_name);
        if ($attr_name === '') return '';

        if (self::is_spec_key($attr_name)) {
            $specs = self::product_characteristics($product->get_id());
            return $specs[self::spec_label_from_key($attr_name)] ?? '';
        }

        $direct = $product->get_attribute($attr_name);
        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $needle = array_unique(array_filter([
            $attr_name,
            sanitize_title($attr_name),
            wc_sanitize_taxonomy_name($attr_name),
        ]));

        foreach ($product->get_attributes() as $key => $attr) {
            if (!$attr instanceof WC_Product_Attribute) {
                continue;
            }

            $haystack = array_unique(array_filter([
                (string) $key,
                (string) $attr->get_name(),
                sanitize_title((string) $key),
                sanitize_title((string) $attr->get_name()),
                wc_sanitize_taxonomy_name((string) $key),
                wc_sanitize_taxonomy_name((string) $attr->get_name()),
            ]));

            if (!array_intersect($needle, $haystack)) {
                continue;
            }

            $resolved = $product->get_attribute((string) $key);
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }

            $options = $attr->get_options();
            if (!is_array($options) || empty($options)) {
                return '';
            }

            if ($attr->is_taxonomy()) {
                $names = [];
                foreach ($options as $term_id) {
                    $term = get_term((int) $term_id, (string) $attr->get_name());
                    if ($term && !is_wp_error($term)) {
                        $names[] = $term->name;
                    }
                }
                return implode(', ', array_filter($names, 'strlen'));
            }

            return implode(', ', array_filter(array_map('strval', $options), 'strlen'));
        }

        return '';
    }

    private static function product_characteristics(int $product_id): array {
        $raw = get_post_meta($product_id, '_hws_source_characteristics_json', true);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $result = [];
                foreach ($decoded as $label => $value) {
                    $label = trim((string) $label);
                    $value = trim((string) $value);
                    if ($label === '' || $value === '') {
                        continue;
                    }
                    $result[$label] = $value;
                }
                if ($result) {
                    return $result;
                }
            }
        }

        $html = get_post_meta($product_id, '_hws_specs_html', true);
        if (!is_string($html) || $html === '') {
            return [];
        }

        $rows = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $th = $tr->getElementsByTagName('th');
            $td = $tr->getElementsByTagName('td');

            if ($th->length < 1 || $td->length < 1) {
                continue;
            }

            $label = trim((string) $th->item(0)->textContent);
            $value = trim((string) $td->item(0)->textContent);
            if ($label === '' || $value === '') {
                continue;
            }

            $rows[$label] = $value;
        }

        return $rows;
    }

    private static function is_spec_key(string $value): bool {
        return str_starts_with($value, 'spec:');
    }

    private static function spec_key(string $label): string {
        return 'spec:' . trim($label);
    }

    private static function spec_label_from_key(string $key): string {
        return trim(substr($key, 5));
    }

    private static function normalize_block_label(string $label, string $attr_slug): string {
        $label = trim($label);
        if ($label === '' || !self::is_spec_key($attr_slug)) {
            return $label;
        }

        $prefix = 'Характеристика · ';
        if (str_starts_with($label, $prefix)) {
            return trim(substr($label, strlen($prefix)));
        }

        return $label;
    }

    // ─── Resolution ──────────────────────────────────────────────────────

    private static function resolve_condition(int $product_id): ?array {
        $brand = self::product_brand($product_id);
        if (!$brand) return null;
        $brand_id = $brand->term_id;
        $cat_ids  = (array) wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        foreach ($cat_ids as $cid) {
            $cat_ids = array_merge($cat_ids, get_ancestors((int)$cid, 'product_cat'));
        }
        $cat_ids = array_unique($cat_ids);

        return self::best_match(self::conditions(), $brand_id, $cat_ids);
    }

    private static function resolve_infographic(int $product_id): ?array {
        $brand = self::product_brand($product_id);
        $brand_id = $brand ? $brand->term_id : 0;
        $cat_ids  = (array) wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        foreach ($cat_ids as $cid) {
            $cat_ids = array_merge($cat_ids, get_ancestors((int)$cid, 'product_cat'));
        }
        $cat_ids = array_unique($cat_ids);

        return self::best_match(self::infographic_configs(), $brand_id, $cat_ids);
    }

    private static function best_match(array $items, int $brand_id, array $cat_ids): ?array {
        $best = null;
        $best_score = -1;
        foreach ($items as $item) {
            $item_brand = (int)($item['brand_id'] ?? 0);
            if ($item_brand && $item_brand !== $brand_id) continue;

            $c_cat = (int)($item['category_id']    ?? 0);
            $c_sub = (int)($item['subcategory_id'] ?? 0);

            if ($c_sub && in_array($c_sub, $cat_ids, true)) {
                $score = 3;
            } elseif ($c_cat && !$c_sub && in_array($c_cat, $cat_ids, true)) {
                $score = 2;
            } elseif (!$c_cat && !$c_sub) {
                $score = $item_brand ? 1 : 0;
            } else {
                continue;
            }

            if ($score > $best_score) { $best_score = $score; $best = $item; }
        }
        return $best;
    }

    // ─── Data helpers ─────────────────────────────────────────────────────

    private static function conditions(): array {
        $data = get_option(self::OPTION, []);
        return is_array($data) ? array_values($data) : [];
    }

    private static function infographic_configs(): array {
        $data = get_option(self::OPTION_INFO, []);
        return is_array($data) ? array_values($data) : [];
    }

    private static function brand_terms(): array {
        if (!taxonomy_exists('product_brand')) return [];
        $terms = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]);
        return (!is_wp_error($terms) && is_array($terms)) ? $terms : [];
    }

    private static function top_cats(): array {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0,
                            'exclude_tree' => self::uncategorized_id()]);
        return (!is_wp_error($terms) && is_array($terms)) ? $terms : [];
    }

    private static function child_cats(int $parent_id): array {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $parent_id]);
        return (!is_wp_error($terms) && is_array($terms)) ? $terms : [];
    }

    private static function cats_by_parent(): array {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_wp_error($terms) || !is_array($terms)) return [];
        $map = [];
        foreach ($terms as $t) {
            if ((int)$t->parent === 0) continue;
            $map[$t->parent][] = ['id' => $t->term_id, 'name' => $t->name];
        }
        return $map;
    }

    private static function uncategorized_id(): int {
        $term = get_term_by('slug', 'uncategorized', 'product_cat');
        return $term ? (int)$term->term_id : 0;
    }

    private static function product_brand(int $product_id): ?WP_Term {
        if (!taxonomy_exists('product_brand')) return null;
        $brands = wp_get_post_terms($product_id, 'product_brand');
        if (!is_array($brands) || is_wp_error($brands) || empty($brands[0])) return null;
        return $brands[0];
    }

    public static function get_settings_for_brand(int $brand_term_id, int $product_id = 0): array {
        $cond = $product_id ? self::resolve_condition($product_id) : self::best_match(self::conditions(), $brand_term_id, []);
        if (!$cond) return ['enabled' => false, 'delivery_text' => '', 'payment_text' => '', 'warranty_text' => '', 'postwarranty_text' => '', 'note' => ''];
        return array_merge(['enabled' => true], $cond);
    }
}

HWS_Commerce_Info::init();
