<?php
/**
 * Plugin Name: HWS Commerce Info
 * Description: Brand-specific payment and delivery information for WooCommerce product pages.
 * Version: 0.1.0
 * Author: HWS
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HWS_Commerce_Info {
    private const OPTION = 'hws_commerce_info_by_brand';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'maybe_save_settings']);
        add_action('woocommerce_single_product_summary', [__CLASS__, 'render_product_box'], 11);
        add_action('wp_head', [__CLASS__, 'render_styles']);
    }

    public static function activate(): void {
        $settings = self::settings();
        $brands = self::brand_terms();

        foreach ($brands as $brand) {
            if (!isset($settings[$brand->term_id])) {
                $settings[$brand->term_id] = self::default_brand_settings();
            }
        }

        update_option(self::OPTION, $settings, false);
    }

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Оплата и доставка по брендам',
            'Оплата и доставка',
            'manage_woocommerce',
            'hws-commerce-info',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function maybe_save_settings(): void {
        if (!is_admin() || !isset($_POST['hws_commerce_info_action'])) {
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав.');
        }

        check_admin_referer('hws_commerce_info_save');

        $incoming = isset($_POST['hws_commerce_info']) && is_array($_POST['hws_commerce_info'])
            ? wp_unslash($_POST['hws_commerce_info'])
            : [];

        $settings = [];
        foreach (self::brand_terms() as $brand) {
            $row = $incoming[$brand->term_id] ?? [];
            $settings[$brand->term_id] = [
                'enabled' => !empty($row['enabled']) ? 1 : 0,
                'delivery_title' => sanitize_text_field($row['delivery_title'] ?? 'Доставка'),
                'delivery_text' => sanitize_textarea_field($row['delivery_text'] ?? ''),
                'payment_title' => sanitize_text_field($row['payment_title'] ?? 'Оплата'),
                'payment_text' => sanitize_textarea_field($row['payment_text'] ?? ''),
                'warranty_title' => sanitize_text_field($row['warranty_title'] ?? 'Гарантия'),
                'warranty_text' => sanitize_textarea_field($row['warranty_text'] ?? ''),
                'note' => sanitize_textarea_field($row['note'] ?? ''),
            ];
        }

        update_option(self::OPTION, $settings, false);
        wp_safe_redirect(add_query_arg(['page' => 'hws-commerce-info', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    public static function render_admin_page(): void {
        $settings = self::settings();
        $brands = self::brand_terms();
        ?>
        <div class="wrap">
            <h1>Оплата и доставка по брендам</h1>
            <p>Эти условия выводятся на карточке товара рядом с ценой. Для каждого производителя можно задать отдельные сроки, способы оплаты и примечание.</p>
            <?php if (!empty($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Условия сохранены.</p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('hws_commerce_info_save'); ?>
                <input type="hidden" name="hws_commerce_info_action" value="save">

                <?php if (!$brands) : ?>
                    <p>Бренды пока не найдены.</p>
                <?php endif; ?>

                <?php foreach ($brands as $brand) :
                    $row = $settings[$brand->term_id] ?? self::default_brand_settings();
                    ?>
                    <div class="postbox" style="padding: 18px 20px; max-width: 980px;">
                        <h2 style="margin-top:0;"><?php echo esc_html($brand->name); ?></h2>
                        <p>
                            <label>
                                <input type="checkbox" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][enabled]" value="1" <?php checked(!empty($row['enabled'])); ?>>
                                Показывать блок на товарах этого бренда
                            </label>
                        </p>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label>Заголовок доставки</label></th>
                                <td><input class="regular-text" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][delivery_title]" value="<?php echo esc_attr($row['delivery_title']); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Текст доставки</label></th>
                                <td><textarea class="large-text" rows="2" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][delivery_text]"><?php echo esc_textarea($row['delivery_text']); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Заголовок оплаты</label></th>
                                <td><input class="regular-text" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][payment_title]" value="<?php echo esc_attr($row['payment_title']); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Текст оплаты</label></th>
                                <td><textarea class="large-text" rows="2" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][payment_text]"><?php echo esc_textarea($row['payment_text']); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Заголовок гарантии</label></th>
                                <td><input class="regular-text" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][warranty_title]" value="<?php echo esc_attr($row['warranty_title'] ?? 'Гарантия'); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Текст гарантии</label></th>
                                <td><textarea class="large-text" rows="2" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][warranty_text]" placeholder="Например: Гарантия до 3-х лет. Зависит от режима использования: бытовое или коммерческое."><?php echo esc_textarea($row['warranty_text'] ?? ''); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Примечание</label></th>
                                <td><textarea class="large-text" rows="2" name="hws_commerce_info[<?php echo esc_attr($brand->term_id); ?>][note]"><?php echo esc_textarea($row['note']); ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>

                <?php submit_button('Сохранить условия'); ?>
            </form>
        </div>
        <?php
    }

    public static function render_product_box(): void {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $brand = self::product_brand($product->get_id());
        if (!$brand) {
            return;
        }

        $settings = self::settings();
        $row = $settings[$brand->term_id] ?? self::default_brand_settings();
        if (empty($row['enabled'])) {
            return;
        }

        $delivery_text = trim((string) ($row['delivery_text'] ?? ''));
        $payment_text = trim((string) ($row['payment_text'] ?? ''));
        $warranty_text = trim((string) ($row['warranty_text'] ?? ''));
        $note = trim((string) ($row['note'] ?? ''));

        if (!$delivery_text && !$payment_text && !$warranty_text && !$note) {
            return;
        }
        ?>
        <section class="hws-commerce-info" aria-label="Оплата и доставка">
            <?php if ($delivery_text) : ?>
                <div class="hws-commerce-info__item">
                    <span class="hws-commerce-info__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h9A2.5 2.5 0 0 1 17 6.5V8h1.4c.7 0 1.35.33 1.76.9l1.44 2.02c.26.37.4.8.4 1.25V17h-2.05a2.75 2.75 0 0 1-5.4 0h-5.1a2.75 2.75 0 0 1-5.4 0H3V6.5Zm2 0V15h.55a2.75 2.75 0 0 1 4.9 0H15V6.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5Zm12 3.5v5h.55a2.75 2.75 0 0 1 2.45-1.5V12.2a.2.2 0 0 0-.04-.12L18.52 10H17ZM6.75 16a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm10.5 0a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z"/></svg>
                    </span>
                    <div>
                        <strong><?php echo esc_html($row['delivery_title'] ?: 'Доставка'); ?></strong>
                        <span><?php echo esc_html($delivery_text); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($payment_text) : ?>
                <div class="hws-commerce-info__item">
                    <span class="hws-commerce-info__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v2h16V7H4Zm0 5v5h16v-5H4Zm2 2h5v2H6v-2Z"/></svg>
                    </span>
                    <div>
                        <strong><?php echo esc_html($row['payment_title'] ?: 'Оплата'); ?></strong>
                        <span><?php echo esc_html($payment_text); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($warranty_text) : ?>
                <div class="hws-commerce-info__item">
                    <span class="hws-commerce-info__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 2.5 19.5 5.5V11c0 5.05-3.18 8.6-7.5 10.5C7.68 19.6 4.5 16.05 4.5 11V5.5L12 2.5Zm3.7 6.6-4.7 4.7-2.2-2.2-1.1 1.1 3.3 3.3 5.8-5.8-1.1-1.1Z"/></svg>
                    </span>
                    <div>
                        <strong><?php echo esc_html($row['warranty_title'] ?: 'Гарантия'); ?></strong>
                        <span><?php echo esc_html($warranty_text); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($note) : ?>
                <p class="hws-commerce-info__note"><?php echo esc_html($note); ?></p>
            <?php endif; ?>
        </section>
        <?php
    }

    public static function render_styles(): void {
        if (!is_product()) {
            return;
        }
        ?>
        <style>
            .hws-commerce-info {
                display: grid;
                gap: 14px;
                margin: 24px 0 22px;
                padding: 22px 24px;
                border: 1px solid rgba(40, 40, 40, 0.14);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.28);
            }
            .hws-commerce-info__item {
                display: grid;
                grid-template-columns: 34px minmax(0, 1fr);
                gap: 14px;
                align-items: start;
            }
            .hws-commerce-info__icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background: rgba(62, 87, 89, 0.12);
                color: #3e5759;
            }
            .hws-commerce-info__icon svg {
                width: 20px;
                height: 20px;
                fill: currentColor;
            }
            .hws-commerce-info strong {
                display: block;
                margin-bottom: 3px;
                color: #282828;
                font-size: 15px;
                line-height: 1.25;
                font-weight: 700;
            }
            .hws-commerce-info span,
            .hws-commerce-info__note {
                color: rgba(40, 40, 40, 0.76);
                font-size: 14px;
                line-height: 1.45;
            }
            .hws-commerce-info__note {
                margin: 4px 0 0;
                padding-top: 12px;
                border-top: 1px solid rgba(40, 40, 40, 0.1);
            }
        </style>
        <?php
    }

    /**
     * Публичный геттер для других плагинов (например, hws-graphql-bridge),
     * чтобы не дублировать логику чтения опции/дефолтов.
     *
     * @param int $brand_term_id
     * @return array{enabled: bool, delivery_title: string, delivery_text: string,
     *               payment_title: string, payment_text: string,
     *               warranty_title: string, warranty_text: string, note: string}
     */
    public static function get_settings_for_brand(int $brand_term_id): array {
        $settings = self::settings();
        $row = $settings[$brand_term_id] ?? self::default_brand_settings();
        return [
            'enabled'        => !empty($row['enabled']),
            'delivery_title' => (string) ($row['delivery_title'] ?? ''),
            'delivery_text'  => (string) ($row['delivery_text'] ?? ''),
            'payment_title'  => (string) ($row['payment_title'] ?? ''),
            'payment_text'   => (string) ($row['payment_text'] ?? ''),
            'warranty_title' => (string) ($row['warranty_title'] ?? ''),
            'warranty_text'  => (string) ($row['warranty_text'] ?? ''),
            'note'           => (string) ($row['note'] ?? ''),
        ];
    }

    private static function default_brand_settings(): array {
        return [
            'enabled' => 1,
            'delivery_title' => 'Доставка',
            'delivery_text' => 'Срок поставки: от 1 до 12 недель.',
            'payment_title' => 'Оплата',
            'payment_text' => 'Наличными, картой или на расчетный счет.',
            'warranty_title' => 'Гарантия',
            'warranty_text' => '',
            'note' => 'Точные сроки и условия подтвердит менеджер перед оплатой.',
        ];
    }

    private static function settings(): array {
        $settings = get_option(self::OPTION, []);
        return is_array($settings) ? $settings : [];
    }

    private static function brand_terms(): array {
        $taxonomy = taxonomy_exists('product_brand') ? 'product_brand' : '';
        if (!$taxonomy) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);

        return is_array($terms) && !is_wp_error($terms) ? $terms : [];
    }

    private static function product_brand(int $product_id): ?WP_Term {
        if (!taxonomy_exists('product_brand')) {
            return null;
        }

        $brands = wp_get_post_terms($product_id, 'product_brand');
        if (!is_array($brands) || is_wp_error($brands) || empty($brands[0])) {
            return null;
        }

        return $brands[0];
    }
}

HWS_Commerce_Info::init();
register_activation_hook(__FILE__, ['HWS_Commerce_Info', 'activate']);

