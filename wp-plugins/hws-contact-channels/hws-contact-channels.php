<?php
/**
 * Plugin Name: HWS Contact Channels
 * Description: Контакты для кнопок мессенджеров на сайте (WhatsApp/Telegram) — редактируются
 *               через админку, не зашиты в код фронта. Только публичные данные (номер/юзернейм),
 *               без секретов — токен Telegram-бота сюда НЕ кладём, он живёт в .env.local фронтенда.
 * Version: 0.1.0
 * Author: HWS
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HWS_Contact_Channels {
    private const OPTION = 'hws_contact_channels';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'maybe_save_settings']);
    }

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Контакты для мессенджеров',
            'Контакты (мессенджеры)',
            'manage_woocommerce',
            'hws-contact-channels',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function maybe_save_settings(): void {
        if (!is_admin() || !isset($_POST['hws_contact_channels_action'])) {
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав.');
        }

        check_admin_referer('hws_contact_channels_save');

        $settings = [
            'whatsapp_number'   => sanitize_text_field(wp_unslash($_POST['whatsapp_number'] ?? '')),
            'telegram_username' => sanitize_text_field(wp_unslash($_POST['telegram_username'] ?? '')),
        ];

        // Нормализуем номер WhatsApp — только цифры (wa.me ждёт международный формат без + и пробелов).
        $settings['whatsapp_number'] = preg_replace('/\D+/', '', $settings['whatsapp_number']);
        // Telegram-юзернейм — без @, t.me/username ждёт чистое имя.
        $settings['telegram_username'] = ltrim($settings['telegram_username'], '@');

        update_option(self::OPTION, $settings, false);
        wp_safe_redirect(add_query_arg(['page' => 'hws-contact-channels', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    public static function render_admin_page(): void {
        $settings = self::settings();
        ?>
        <div class="wrap">
            <h1>Контакты для мессенджеров</h1>
            <p>Эти данные используются в кнопках «Получить товар» на сайте (открывают WhatsApp/Telegram с готовым сообщением). Токен Telegram-бота и chat_id для приёма заявок сюда не вписываются — это секрет, он настраивается отдельно на стороне фронтенда.</p>
            <?php if (!empty($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Сохранено.</p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('hws_contact_channels_save'); ?>
                <input type="hidden" name="hws_contact_channels_action" value="save">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="whatsapp_number">Номер WhatsApp</label></th>
                        <td>
                            <input class="regular-text" id="whatsapp_number" name="whatsapp_number" value="<?php echo esc_attr($settings['whatsapp_number']); ?>" placeholder="79991234567">
                            <p class="description">С кодом страны, без +, пробелов и скобок. Например: 79991234567</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="telegram_username">Telegram (юзернейм)</label></th>
                        <td>
                            <input class="regular-text" id="telegram_username" name="telegram_username" value="<?php echo esc_attr($settings['telegram_username']); ?>" placeholder="hwsdigital_manager">
                            <p class="description">Без @. Куда переходит покупатель при клике «Получить товар через Telegram».</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Сохранить'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Публичный геттер для hws-graphql-bridge.
     *
     * @return array{whatsapp_number: string, telegram_username: string}
     */
    public static function get_settings(): array {
        return self::settings();
    }

    private static function settings(): array {
        $settings = get_option(self::OPTION, []);
        $settings = is_array($settings) ? $settings : [];
        return [
            'whatsapp_number'   => (string) ($settings['whatsapp_number'] ?? ''),
            'telegram_username' => (string) ($settings['telegram_username'] ?? ''),
        ];
    }
}

HWS_Contact_Channels::init();
