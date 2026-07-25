<?php
/**
 * Plugin Name: HWS Description Formatter
 * Description: Форматирует и переводит тексты товаров, атрибуты и вариации через OpenRouter AI.
 * Version: 1.2.12
 * Author: HWS
 */

defined('ABSPATH') || exit;

final class HWS_Desc_Formatter {

    private const FORMAT_META_KEY = '_hws_desc_formatted';
    private const CLEAN_EXCERPT_META_KEY = '_hws_short_desc_cleaned';
    private const TRANSLATE_META_KEY = '_hws_desc_translated';
    private const TRANSLATE_REPORT_META_KEY = '_hws_desc_translate_report';
    private const CATEGORY_META_KEY = '_hws_category_checked';
    private const CATEGORY_REPORT_META_KEY = '_hws_category_report';
    private const OPT_KEY = 'hws_formatter_settings';
    private const LOG_OPT_KEY = 'hws_formatter_activity_log';
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const FORMAT_PROMPT = <<<'PROMPT'
Ты — редактор текстов для интернет-магазина. Тебе передают описание товара в виде сплошного текста или HTML без структуры.

Задача: отформатируй описание в чистый HTML следующего вида:
- Раздели текст на логические блоки (2–5 блоков)
- Каждый блок начинается с <h3>Подзаголовок</h3>
- За ним следует один или несколько <p>абзацев</p>
- Не придумывай информацию, только структурируй имеющийся текст
- Убери лишние переносы строк и артефакты HTML
- Верни ТОЛЬКО готовый HTML без объяснений и markdown-разметки
PROMPT;

    private const TRANSLATE_PRODUCT_PROMPT = <<<'PROMPT'
Ты — переводчик карточек товаров для интернет-магазина.

Тебе передают JSON с полями:
- title
- excerpt
- content
- from_language
- to_language

Задача:
- переведи ВСЕ текстовые поля с языка from_language на язык to_language, включая title и excerpt
- title верни обычной строкой: переводи все описательные и нарицательные слова, даже если они написаны латиницей
- в title сохраняй без перевода только бренды, модели, серии, артикулы, названия технологий/стандартов, единицы измерения, числа и другие явно собственные имена
- не оставляй title целиком без изменений только потому, что он содержит латиницу; например, Steam outlet нужно перевести как обычное описание товара, а EOS и 947005 сохранить
- excerpt переводи полностью; сохраняй без перевода только бренды, модели, артикулы, единицы и собственные имена
- excerpt и content переводи с сохранением HTML-структуры, если она есть
- не добавляй новые факты и не сокращай исходный смысл
- если текст уже частично переведен, переведи оставшиеся слова и не дублируй перевод
- не возвращай объяснения
- не используй markdown
- верни ТОЛЬКО валидный JSON объекта вида:
{
  "title": "...",
  "excerpt": "...",
  "content": "..."
}
PROMPT;

    private const TRANSLATE_ATTRIBUTES_PROMPT = <<<'PROMPT'
Ты — переводчик атрибутов и вариаций товаров для интернет-магазина.

Тебе передают JSON с полями:
- from_language
- to_language
- attributes

Каждый элемент attributes содержит:
- key
- label
- is_taxonomy
- taxonomy
- terms: массив объектов { term_id, name }
- options: массив строк

Задача:
- переведи label
- переведи term.name
- переведи options
- НЕ меняй key, taxonomy, term_id, порядок элементов и структуру JSON
- не придумывай новые значения
- не возвращай объяснения
- верни ТОЛЬКО валидный JSON объекта вида:
{
  "attributes": [
    {
      "key": "...",
      "label": "...",
      "terms": [{ "term_id": 123, "name": "..." }],
      "options": ["..."]
    }
  ]
}
PROMPT;

    private const LANGUAGE_OPTIONS = [
        'ar' => 'Arabic',
        'bg' => 'Bulgarian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'en' => 'English',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'fi' => 'Finnish',
        'fr' => 'French',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sr' => 'Serbian',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'vi' => 'Vietnamese',
        'zh' => 'Chinese',
    ];

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_ajax_hws_fmt_status', [__CLASS__, 'ajax_status']);
        add_action('wp_ajax_hws_fmt_next', [__CLASS__, 'ajax_next']);
        add_action('wp_ajax_hws_fmt_scan', [__CLASS__, 'ajax_scan']);
        add_action('wp_ajax_hws_fmt_completed', [__CLASS__, 'ajax_completed']);
        add_action('wp_ajax_hws_fmt_logs', [__CLASS__, 'ajax_logs']);
        add_action('wp_ajax_hws_fmt_save_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_hws_fmt_category_status', [__CLASS__, 'ajax_category_status']);
        add_action('wp_ajax_hws_fmt_category_next', [__CLASS__, 'ajax_category_next']);
        add_action('wp_ajax_hws_fmt_category_reset', [__CLASS__, 'ajax_category_reset']);
    }

    // Admin UI

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Форматирование описаний',
            'Форматирование описаний',
            'manage_woocommerce',
            'hws-desc-formatter',
            [__CLASS__, 'render_page']
        );
    }

    public static function admin_assets(string $hook): void {
        if ($hook !== 'woocommerce_page_hws-desc-formatter') {
            return;
        }
        ?>
        <style>
        #hws-fmt-wrap { max-width: 1320px; }
        #hws-fmt-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 20px;
            align-items: start;
        }
        #hws-fmt-wrap .hws-section {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border: 1px solid #d9dee8;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(18, 43, 70, 0.04);
        }
        #hws-fmt-wrap h1 { margin-bottom: 18px; }
        #hws-fmt-wrap h2 { margin: 0 0 8px; font-size: 28px; line-height: 1.15; }
        #hws-fmt-wrap .hws-subtitle { margin: 0 0 18px; color: #4f5b6b; font-size: 15px; }
        .hws-settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            gap: 16px;
            max-width: 860px;
            margin-bottom: 18px;
        }
        .hws-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .hws-field input,
        .hws-field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid #c9d2df;
            border-radius: 10px;
            padding: 0 14px;
            background: #fff;
        }
        .hws-kpi {
            display: grid;
            grid-template-columns: repeat(3, minmax(120px, 1fr));
            gap: 12px;
            max-width: 520px;
            margin: 18px 0;
        }
        .hws-kpi-card {
            background: #f7f9fc;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .hws-kpi-card strong { display: block; font-size: 22px; line-height: 1.1; }
        .hws-kpi-card span { color: #5d6979; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .hws-translate-settings-actions { margin: 14px 0 18px; }
        .hws-progress { display: none; margin-top: 16px; }
        .hws-bar-outer {
            height: 18px;
            background: #edf2f8;
            border-radius: 999px;
            overflow: hidden;
            margin: 10px 0 6px;
        }
        .hws-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #3158ff 0%, #5686ff 100%);
            border-radius: 999px;
            transition: width .25s ease;
        }
        .hws-log {
            max-height: 240px;
            overflow-y: auto;
            background: #0f1722;
            color: #d8e1ee;
            border-radius: 12px;
            padding: 12px 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            border: 1px solid #202c3d;
            margin-top: 14px;
        }
        .hws-log-ok { color: #5ce6a5; }
        .hws-log-err { color: #ff8f8f; }
        .hws-log-info { color: #86b8ff; }
        .hws-activity {
            position: sticky;
            top: 48px;
        }
        .hws-activity-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }
        .hws-activity-head h2 { margin: 0; font-size: 22px; }
        .hws-activity-list {
            max-height: 720px;
            overflow-y: auto;
            display: grid;
            gap: 10px;
        }
        .hws-activity-item {
            border: 1px solid #e0e6ef;
            border-left: 4px solid #86b8ff;
            border-radius: 10px;
            background: #fff;
            padding: 10px 12px;
        }
        .hws-activity-item.is-ok { border-left-color: #2a9d62; }
        .hws-activity-item.is-err { border-left-color: #c93535; }
        .hws-activity-meta {
            color: #6b7584;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .hws-activity-message {
            color: #1f2933;
            font-size: 13px;
            line-height: 1.35;
            word-break: break-word;
        }
        .hws-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .hws-chip-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .hws-chip {
            background: #eef3ff;
            color: #3158ff;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .hws-scan {
            display: none;
            margin: 18px 0;
            border: 1px solid #dfe6f1;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .hws-scan-head {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px;
            background: #f7f9fc;
            border-bottom: 1px solid #dfe6f1;
        }
        .hws-scan-pill {
            background: #eef3ff;
            color: #3158ff;
            border-radius: 999px;
            padding: 7px 10px;
            font-weight: 700;
            font-size: 12px;
        }
        .hws-scan-list {
            max-height: 360px;
            overflow: auto;
        }
        .hws-scan-row {
            display: grid;
            grid-template-columns: 90px minmax(220px, 1fr);
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid #edf1f7;
        }
        .hws-scan-row:last-child { border-bottom: 0; }
        .hws-scan-title {
            font-weight: 700;
            color: #1f2933;
            margin-bottom: 8px;
        }
        .hws-scan-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .hws-scan-tag {
            border: 1px solid #d8e0ec;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 12px;
            background: #fff;
            color: #344054;
        }
        .hws-completed {
            margin-top: 18px;
        }
        .hws-completed h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .hws-completed-note {
            color: #667085;
            font-size: 12px;
            margin-top: 6px;
        }
        @media (max-width: 782px) {
            #hws-fmt-layout { grid-template-columns: 1fr; }
            .hws-activity { position: static; }
            .hws-settings-grid,
            .hws-kpi { grid-template-columns: 1fr; }
            .hws-scan-row { grid-template-columns: 1fr; }
        }
        </style>
        <?php
    }

    public static function render_page(): void {
        $settings = self::settings();
        ?>
        <div class="wrap" id="hws-fmt-wrap">
            <h1>Форматирование и перевод карточек товаров</h1>

            <div id="hws-fmt-layout">
                <main>
                    <div class="hws-section">
                        <h2>Настройки</h2>
                        <p class="hws-subtitle">API-ключ и модель для форматирования и перевода.</p>

                        <div class="hws-settings-grid">
                            <div class="hws-field">
                                <label for="hws-api-key">API Key</label>
                                <input
                                    type="password"
                                    id="hws-api-key"
                                    value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>"
                                    placeholder="sk-or-..."
                                >
                            </div>
                            <div class="hws-field">
                                <label for="hws-model">Модель</label>
                                <input
                                    type="text"
                                    id="hws-model"
                                    value="<?php echo esc_attr($settings['model'] ?? 'meta-llama/llama-3.1-8b-instruct:free'); ?>"
                                >
                            </div>
                        </div>

                        <button class="button button-primary" id="hws-save-settings">Сохранить настройки</button>
                        <span id="hws-settings-saved" style="display:none;margin-left:10px;color:#2a7e2e">✓ Сохранено</span>
                    </div>

                    <div class="hws-section">
                        <h2>Массовое форматирование</h2>
                        <p class="hws-subtitle">Структурирует только полное описание товара в чистый HTML.</p>
                        <div class="hws-kpi" id="hws-format-kpi">
                            <div class="hws-kpi-card"><strong id="hws-format-total">0</strong><span>Всего</span></div>
                            <div class="hws-kpi-card"><strong id="hws-format-done">0</strong><span>Готово</span></div>
                            <div class="hws-kpi-card"><strong id="hws-format-left">0</strong><span>Осталось</span></div>
                        </div>
                        <div class="hws-controls">
                            <button class="button button-primary" id="hws-format-start-btn" disabled>▶ Форматировать все</button>
                            <button class="button" id="hws-format-stop-btn" style="display:none">⏹ Остановить</button>
                            <button class="button" id="hws-format-reset-btn" style="display:none">↺ Сбросить отметки</button>
                        </div>
                        <div class="hws-progress" id="hws-format-progress">
                            <div class="hws-bar-outer"><div class="hws-bar-inner" id="hws-format-bar" style="width:0%"></div></div>
                            <span id="hws-format-progress-label">0 / 0</span>
                        </div>
                        <div class="hws-log" id="hws-format-log"></div>
                    </div>

                    <div class="hws-section">
                        <h2>Очистка коротких описаний</h2>
                        <p class="hws-subtitle">Удаляет теги, shortcode, переносы и HTML entities из краткого описания, затем сохраняет одну строку.</p>
                        <div class="hws-kpi" id="hws-clean-excerpt-kpi">
                            <div class="hws-kpi-card"><strong id="hws-clean-excerpt-total">0</strong><span>Всего</span></div>
                            <div class="hws-kpi-card"><strong id="hws-clean-excerpt-done">0</strong><span>Готово</span></div>
                            <div class="hws-kpi-card"><strong id="hws-clean-excerpt-left">0</strong><span>Осталось</span></div>
                        </div>
                        <div class="hws-controls">
                            <button class="button button-primary" id="hws-clean-excerpt-start-btn" disabled>▶ Очистить короткие описания</button>
                            <button class="button" id="hws-clean-excerpt-stop-btn" style="display:none">⏹ Остановить</button>
                            <button class="button" id="hws-clean-excerpt-reset-btn" style="display:none">↺ Сбросить отметки</button>
                        </div>
                        <div class="hws-progress" id="hws-clean-excerpt-progress">
                            <div class="hws-bar-outer"><div class="hws-bar-inner" id="hws-clean-excerpt-bar" style="width:0%"></div></div>
                            <span id="hws-clean-excerpt-progress-label">0 / 0</span>
                        </div>
                        <div class="hws-log" id="hws-clean-excerpt-log"></div>
                    </div>

                    <div class="hws-section">
                        <h2>Распределение товаров по категориям</h2>
                        <p class="hws-subtitle">Модель анализирует карточку и текущую категорию. Правильные категории сохраняются, ошибочные заменяются на подходящую категорию. Если нужной дочерней категории нет, она создаётся в утверждённом дереве каталога.</p>
                        <div class="hws-chip-row">
                            <div class="hws-chip">Текущая категория</div>
                            <div class="hws-chip">Название и описание</div>
                            <div class="hws-chip">Бренд и атрибуты</div>
                            <div class="hws-chip">Создание отсутствующей категории</div>
                        </div>
                        <div class="hws-settings-grid hws-category-filters">
                            <div class="hws-field">
                                <label for="hws-category-brand">Бренд</label>
                                <?php self::render_category_brand_select(); ?>
                            </div>
                            <div class="hws-field">
                                <label for="hws-category-parent">Категория</label>
                                <?php self::render_category_parent_select(); ?>
                            </div>
                            <div class="hws-field">
                                <label for="hws-category-child">Подкатегория</label>
                                <?php self::render_category_child_select(); ?>
                            </div>
                        </div>
                        <div class="hws-kpi" id="hws-category-kpi">
                            <div class="hws-kpi-card"><strong id="hws-category-total">0</strong><span>Всего</span></div>
                            <div class="hws-kpi-card"><strong id="hws-category-done">0</strong><span>Проверено</span></div>
                            <div class="hws-kpi-card"><strong id="hws-category-left">0</strong><span>Осталось</span></div>
                        </div>
                        <div class="hws-controls">
                            <button class="button button-primary" id="hws-category-start-btn">▶ Распределить товары</button>
                            <button class="button" id="hws-category-stop-btn" style="display:none">⏹ Остановить</button>
                            <button class="button" id="hws-category-reset-btn">↺ Проверить заново</button>
                        </div>
                        <div class="hws-progress" id="hws-category-progress">
                            <div class="hws-bar-outer"><div class="hws-bar-inner" id="hws-category-bar" style="width:0%"></div></div>
                            <span id="hws-category-progress-label">0 / 0</span>
                        </div>
                        <div class="hws-log" id="hws-category-log"></div>
                    </div>

                    <div class="hws-section">
                        <h2>Массовый перевод карточек</h2>
                        <p class="hws-subtitle">Переводит тексты карточки, значения атрибутов, термы глобальных атрибутов и значения вариаций.</p>
                        <div class="hws-chip-row">
                            <div class="hws-chip">Название товара</div>
                            <div class="hws-chip">Краткое описание</div>
                            <div class="hws-chip">Полное описание</div>
                            <div class="hws-chip">Термы атрибутов</div>
                            <div class="hws-chip">Значения вариаций</div>
                        </div>
                        <div class="hws-settings-grid">
                            <div class="hws-field">
                                <label for="hws-translate-from">Язык источника</label>
                                <?php self::render_language_select('hws-translate-from', $settings['translate_from'] ?? 'ru'); ?>
                            </div>
                            <div class="hws-field">
                                <label for="hws-translate-to">Язык перевода</label>
                                <?php self::render_language_select('hws-translate-to', $settings['translate_to'] ?? 'en'); ?>
                            </div>
                            <div class="hws-field">
                                <label for="hws-translate-brand">Бренд товаров</label>
                                <?php self::render_brand_select((int) ($settings['translate_brand'] ?? 0)); ?>
                            </div>
                        </div>
                        <div class="hws-translate-settings-actions">
                            <button class="button" id="hws-save-translate-settings">Сохранить настройки перевода</button>
                            <span id="hws-translate-settings-saved" style="display:none;margin-left:10px;color:#2a7e2e">✓ Сохранено</span>
                        </div>
                        <div class="hws-kpi" id="hws-translate-kpi">
                            <div class="hws-kpi-card"><strong id="hws-translate-total">0</strong><span>Всего</span></div>
                            <div class="hws-kpi-card"><strong id="hws-translate-done">0</strong><span>Переведено</span></div>
                            <div class="hws-kpi-card"><strong id="hws-translate-left">0</strong><span>Осталось</span></div>
                        </div>
                        <div class="hws-controls">
                            <button class="button" id="hws-translate-scan-btn">Сканировать</button>
                            <button class="button button-primary" id="hws-translate-start-btn" disabled>▶ Перевести все</button>
                            <button class="button" id="hws-translate-stop-btn" style="display:none">⏹ Остановить</button>
                            <button class="button" id="hws-translate-reset-btn" style="display:none">↺ Сбросить отметки</button>
                        </div>
                        <div class="hws-scan" id="hws-translate-scan">
                            <div class="hws-scan-head" id="hws-translate-scan-summary"></div>
                            <div class="hws-scan-list" id="hws-translate-scan-list"></div>
                        </div>
                        <div class="hws-progress" id="hws-translate-progress">
                            <div class="hws-bar-outer"><div class="hws-bar-inner" id="hws-translate-bar" style="width:0%"></div></div>
                            <span id="hws-translate-progress-label">0 / 0</span>
                        </div>
                        <div class="hws-log" id="hws-translate-log"></div>
                        <div class="hws-completed">
                            <h3>Переведенные товары</h3>
                            <div class="hws-scan" style="display:block">
                                <div class="hws-scan-list" id="hws-translate-completed-list"></div>
                            </div>
                        </div>
                    </div>
                </main>

                <aside class="hws-section hws-activity">
                    <div class="hws-activity-head">
                        <h2>Журнал</h2>
                        <button class="button" id="hws-clear-activity" type="button">Очистить</button>
                    </div>
                    <p class="hws-subtitle">Последние действия сохраняются между перезапусками страницы.</p>
                    <div class="hws-activity-list" id="hws-activity-list"></div>
                </aside>
            </div>
        </div>

        <script>
        (function($){
            var nonce = <?php echo wp_json_encode(wp_create_nonce('hws_formatter')); ?>;
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function renderActivityLog(items) {
                var $list = $('#hws-activity-list');
                $list.empty();
                if (!items || !items.length) {
                    $list.append('<div class="hws-activity-item"><div class="hws-activity-message">Журнал пока пуст.</div></div>');
                    return;
                }
                items.forEach(function(item) {
                    var level = item.level === 'ok' ? 'ok' : (item.level === 'err' ? 'err' : 'info');
                    var meta = escapeHtml(item.time + ' · ' + item.mode);
                    var message = escapeHtml(item.message);
                    $list.append(
                        '<div class="hws-activity-item is-' + level + '">' +
                            '<div class="hws-activity-meta">' + meta + '</div>' +
                            '<div class="hws-activity-message">' + message + '</div>' +
                        '</div>'
                    );
                });
            }

            function loadActivityLog() {
                $.post(ajaxUrl, {
                    action: 'hws_fmt_logs',
                    log_action: 'list',
                    _ajax_nonce: nonce
                }, function(r) {
                    if (r.success) {
                        renderActivityLog(r.data.logs || []);
                    }
                });
            }

            function persistActivity(mode, msg, cls) {
                $.post(ajaxUrl, {
                    action: 'hws_fmt_logs',
                    log_action: 'add',
                    mode: mode,
                    level: cls || 'info',
                    message: msg,
                    _ajax_nonce: nonce
                }, function(r) {
                    if (r.success) {
                        renderActivityLog(r.data.logs || []);
                    }
                });
            }

            function createRunner(mode, config) {
                var running = false;
                var total = 0;
                var done = 0;
                var scanned = !config.scanRequired;
                var scanTotal = 0;

                function log(msg, cls) {
                    var ts = new Date().toLocaleTimeString();
                    $(config.log).append('<div class="hws-log-' + (cls || 'info') + '">[' + ts + '] ' + msg + '</div>');
                    var el = document.querySelector(config.log);
                    el.scrollTop = el.scrollHeight;
                    persistActivity(mode, msg, cls || 'info');
                }

                function updateKpi(d, t) {
                    var left = Math.max(t - d, 0);
                    $(config.totalEl).text(t);
                    $(config.doneEl).text(d);
                    $(config.leftEl).text(left);
                    $(config.startBtn).prop('disabled', config.scanRequired ? (!scanned || scanTotal === 0) : left === 0);
                    if (d > 0) {
                        $(config.resetBtn).show();
                    }
                }

                function updateBar(d, t) {
                    var pct = t > 0 ? Math.round(d / t * 100) : 0;
                    $(config.bar).css('width', pct + '%');
                    $(config.progressLabel).text(d + ' / ' + t);
                }

                function extraPayload() {
                    if (mode !== 'translate') {
                        return {};
                    }
                    return {
                        from_lang: $('#hws-translate-from').val(),
                        to_lang: $('#hws-translate-to').val(),
                        brand_id: $('#hws-translate-brand').val()
                    };
                }

                function loadStatus() {
                    var payload = $.extend({
                        action: 'hws_fmt_status',
                        mode: mode,
                        _ajax_nonce: nonce
                    }, extraPayload());

                    $.post(ajaxUrl, payload, function(r) {
                        if (!r.success) {
                            return;
                        }
                        total = r.data.total;
                        done = r.data.processed;
                        updateKpi(done, total);
                        updateBar(done, total);
                        if (total > 0) {
                            $(config.progressWrap).show();
                        }
                        loadCompleted();
                    });
                }

                function finishRun() {
                    $(config.stopBtn).hide();
                    $(config.startBtn).show();
                    scanned = false;
                    scanTotal = 0;
                    loadStatus();
                }

                function renderScan(data) {
                    var $summary = $(config.scanSummary);
                    var $list = $(config.scanList);
                    $summary.empty();
                    $list.empty();

                    [
                        'Товаров: ' + data.total,
                        'Название: ' + data.summary.title,
                        'Краткое: ' + data.summary.excerpt,
                        'Полное: ' + data.summary.content,
                        'Термы: ' + data.summary.attribute_terms,
                        'Вариации: ' + data.summary.variation_values
                    ].forEach(function(text) {
                        $summary.append('<span class="hws-scan-pill">' + escapeHtml(text) + '</span>');
                    });

                    (data.items || []).forEach(function(item) {
                        var tags = item.parts.map(function(part) {
                            return '<span class="hws-scan-tag">' + escapeHtml(part.type + ' · ' + part.language) + '</span>';
                        }).join('');
                        $list.append(
                            '<div class="hws-scan-row">' +
                                '<div>#' + item.id + '</div>' +
                                '<div><div class="hws-scan-title">' + escapeHtml(item.title) + '</div>' +
                                '<div class="hws-scan-tags">' + tags + '</div></div>' +
                            '</div>'
                        );
                    });

                    if (!data.items || !data.items.length) {
                        $list.append('<div class="hws-scan-row"><div></div><div>Нет товаров для перевода.</div></div>');
                    }

                    $(config.scanWrap).show();
                }

                function renderCompleted(items) {
                    if (!config.completedList) {
                        return;
                    }

                    var $list = $(config.completedList);
                    $list.empty();

                    if (!items || !items.length) {
                        $list.append('<div class="hws-scan-row"><div></div><div>Переведенных товаров пока нет.</div></div>');
                        return;
                    }

                    items.forEach(function(item) {
                        var tags = (item.parts || []).map(function(part) {
                            return '<span class="hws-scan-tag">' + escapeHtml(part.type + ' · ' + part.language) + '</span>';
                        }).join('');
                        var note = item.note ? '<div class="hws-completed-note">' + escapeHtml(item.note) + '</div>' : '';
                        var details = (item.details || []).map(function(detail) {
                            return '<div class="hws-completed-note">' + escapeHtml(detail) + '</div>';
                        }).join('');
                        $list.append(
                            '<div class="hws-scan-row">' +
                                '<div>#' + item.id + '</div>' +
                                '<div><div class="hws-scan-title">' + escapeHtml(item.title) + '</div>' +
                                '<div class="hws-scan-tags">' + tags + '</div>' +
                                '<div class="hws-completed-note">Переведено: ' + escapeHtml(item.translated_at || 'дата не записана') + '</div>' +
                                note + details + '</div>' +
                            '</div>'
                        );
                    });
                }

                function loadCompleted() {
                    if (!config.completedList) {
                        return;
                    }

                    var payload = $.extend({
                        action: 'hws_fmt_completed',
                        mode: mode,
                        _ajax_nonce: nonce
                    }, extraPayload());

                    $.post(ajaxUrl, payload, function(r) {
                        if (r.success) {
                            renderCompleted(r.data.items || []);
                        }
                    });
                }

                function processNext() {
                    if (!running) {
                        return;
                    }

                    var payload = $.extend({
                        action: 'hws_fmt_next',
                        mode: mode,
                        _ajax_nonce: nonce
                    }, extraPayload());

                    $.post(ajaxUrl, payload, function(r) {
                        if (!r.success) {
                            log('Ошибка: ' + (r.data || 'неизвестная'), 'err');
                            running = false;
                            finishRun();
                            return;
                        }

                        var d = r.data;
                        if (d.done) {
                            log('✓ ' + config.finishMessage, 'ok');
                            running = false;
                            finishRun();
                            return;
                        }

                        done++;
                        updateKpi(done, total);
                        updateBar(done, total);

                        (d.details || []).forEach(function(detail) {
                            log('#' + d.id + ' ' + detail, detail.indexOf('Готово:') === 0 ? 'ok' : 'info');
                        });

                        if (d.skipped) {
                            log('→ #' + d.id + ' ' + d.title + ' — пропущен (' + d.skipped_reason + ')', 'info');
                        } else if (d.error) {
                            log('✗ #' + d.id + ' ' + d.title + ' — ' + d.error, 'err');
                        } else {
                            log('✓ #' + d.id + ' ' + d.title, 'ok');
                            loadCompleted();
                        }

                        setTimeout(processNext, 300);
                    }).fail(function() {
                        log('Сетевая ошибка, повтор через 3с…', 'err');
                        setTimeout(processNext, 3000);
                    });
                }

                $(config.startBtn).on('click', function() {
                    if (mode === 'translate' && (!$('#hws-translate-from').val() || !$('#hws-translate-to').val())) {
                        alert('Выбери языки перевода.');
                        return;
                    }
                    if (config.scanRequired && !scanned) {
                        alert('Сначала запусти сканирование.');
                        return;
                    }

                    running = true;
                    $(config.startBtn).hide();
                    $(config.stopBtn).show();
                    $(config.progressWrap).show();
                    $(config.log).empty();
                    log(config.startMessage, 'info');
                    processNext();
                });

                if (config.scanBtn) {
                    $(config.scanBtn).on('click', function() {
                        var payload = $.extend({
                            action: 'hws_fmt_scan',
                            mode: mode,
                            _ajax_nonce: nonce
                        }, extraPayload());

                        $(config.scanBtn).prop('disabled', true).text('Сканирую…');
                        $(config.startBtn).prop('disabled', true);
                        $.post(ajaxUrl, payload, function(r) {
                            if (r.success) {
                                scanned = true;
                                scanTotal = parseInt(r.data.total, 10) || 0;
                                renderScan(r.data);
                                log('Сканирование: найдено товаров ' + r.data.total, 'info');
                                if (scanTotal > 0) {
                                    $(config.startBtn).prop('disabled', false).removeAttr('disabled');
                                } else {
                                    $(config.startBtn).prop('disabled', true);
                                }
                                // Сканер пересобрал очередь и снял устаревшие
                                // отметки. Обновляем KPI до запуска перевода.
                                loadStatus();
                                loadCompleted();
                            } else {
                                log('Ошибка сканирования: ' + (r.data || 'неизвестная'), 'err');
                            }
                        }).always(function() {
                            $(config.scanBtn).prop('disabled', false).text('Сканировать');
                        });
                    });

                    $('#hws-translate-from, #hws-translate-to, #hws-translate-brand').on('change', function() {
                        scanned = false;
                        scanTotal = 0;
                        $(config.scanWrap).hide();
                        loadStatus();
                    });
                }

                $(config.stopBtn).on('click', function() {
                    running = false;
                    log('Остановлено пользователем', 'info');
                    finishRun();
                });

                $(config.resetBtn).on('click', function() {
                    if (!confirm(config.resetConfirm)) {
                        return;
                    }
                    var payload = $.extend({
                        action: 'hws_fmt_status',
                        mode: mode,
                        reset: 1,
                        _ajax_nonce: nonce
                    }, extraPayload());
                    $.post(ajaxUrl, payload, function() {
                        log('Отметки сброшены', 'info');
                        loadStatus();
                        loadCompleted();
                    });
                });

                return { loadStatus: loadStatus, loadCompleted: loadCompleted };
            }

            function saveSettings($button, $saved) {
                $button.prop('disabled', true);
                $.post(ajaxUrl, {
                    action: 'hws_fmt_save_settings',
                    api_key: $('#hws-api-key').val(),
                    model: $('#hws-model').val(),
                    translate_from: $('#hws-translate-from').val(),
                    translate_to: $('#hws-translate-to').val(),
                    translate_brand: $('#hws-translate-brand').val(),
                    _ajax_nonce: nonce
                }, function(r) {
                    if (r.success) {
                        persistActivity('settings', 'Настройки сохранены', 'ok');
                        $saved.stop(true, true).fadeIn().delay(1800).fadeOut();
                    }
                }).always(function() {
                    $button.prop('disabled', false);
                });
            }

            $('#hws-save-settings').on('click', function() {
                saveSettings($(this), $('#hws-settings-saved'));
            });

            $('#hws-save-translate-settings').on('click', function() {
                saveSettings($(this), $('#hws-translate-settings-saved'));
            });

            $('#hws-clear-activity').on('click', function() {
                if (!confirm('Очистить журнал действий?')) {
                    return;
                }
                $.post(ajaxUrl, {
                    action: 'hws_fmt_logs',
                    log_action: 'clear',
                    _ajax_nonce: nonce
                }, function(r) {
                    if (r.success) {
                        renderActivityLog(r.data.logs || []);
                    }
                });
            });

            (function(){
                var running = false;
                var total = 0;
                var done = 0;

                function categoryPayload(action) {
                    return {
                        action: action,
                        _ajax_nonce: nonce,
                        brand: parseInt($('#hws-category-brand').val(), 10) || 0,
                        category: parseInt($('#hws-category-parent').val(), 10) || 0,
                        subcategory: parseInt($('#hws-category-child').val(), 10) || 0
                    };
                }

                function setCategoryFiltersDisabled(disabled) {
                    $('.hws-category-filters select').prop('disabled', disabled);
                }

                function log(msg, cls) {
                    var ts = new Date().toLocaleTimeString();
                    $('#hws-category-log').append('<div class="hws-log-' + (cls || 'info') + '">[' + ts + '] ' + escapeHtml(msg) + '</div>');
                    var el = document.querySelector('#hws-category-log');
                    el.scrollTop = el.scrollHeight;
                    persistActivity('category', msg, cls || 'info');
                }

                function update(){
                    var left = Math.max(total - done, 0);
                    $('#hws-category-total').text(total);
                    $('#hws-category-done').text(done);
                    $('#hws-category-left').text(left);
                    $('#hws-category-bar').css('width', (total ? Math.round(done / total * 100) : 0) + '%');
                    $('#hws-category-progress-label').text(done + ' / ' + total);
                }

                function loadStatus(callback){
                    $.post(ajaxUrl, categoryPayload('hws_fmt_category_status'), function(r){
                        if (r.success) {
                            total = parseInt(r.data.total, 10) || 0;
                            done = parseInt(r.data.processed, 10) || 0;
                            update();
                        }
                    }).always(function(){ if (typeof callback === 'function') callback(); });
                }

                function next(){
                    if (!running) return;
                    $.post(ajaxUrl, categoryPayload('hws_fmt_category_next'), function(r){
                        if (!r.success) {
                            log('Ошибка: ' + (r.data || 'неизвестная ошибка'), 'err');
                            running = false;
                            $('#hws-category-stop-btn').hide();
                            $('#hws-category-start-btn').show();
                            setCategoryFiltersDisabled(false);
                            return;
                        }
                        if (r.data.done) {
                            log('Распределение категорий завершено', 'ok');
                            running = false;
                            $('#hws-category-stop-btn').hide();
                            $('#hws-category-start-btn').show();
                            setCategoryFiltersDisabled(false);
                            loadStatus();
                            return;
                        }
                        done++;
                        update();
                        log('#' + r.data.id + ' ' + r.data.title + ' — ' + r.data.message, r.data.changed ? 'ok' : 'info');
                        setTimeout(next, 250);
                    }).fail(function(){
                        log('Сетевая ошибка, повтор через 3 секунды…', 'err');
                        setTimeout(next, 3000);
                    });
                }

                $('#hws-category-start-btn').on('click', function(){
                    loadStatus(function(){
                        if (total === 0 || done >= total) {
                            log('В выбранном диапазоне нет товаров для обработки', 'info');
                            return;
                        }
                        running = true;
                        setCategoryFiltersDisabled(true);
                        $('#hws-category-start-btn').hide();
                        $('#hws-category-stop-btn').show();
                        $('#hws-category-progress').show();
                        log('Запуск проверки категорий…', 'info');
                        next();
                    });
                });

                $('#hws-category-stop-btn').on('click', function(){
                    running = false;
                    $('#hws-category-stop-btn').hide();
                    $('#hws-category-start-btn').show();
                    setCategoryFiltersDisabled(false);
                    log('Остановлено пользователем', 'info');
                });

                $('#hws-category-reset-btn').on('click', function(){
                    if (!confirm('Сбросить отметки проверки категорий и запустить проверку заново?')) return;
                    $.post(ajaxUrl, categoryPayload('hws_fmt_category_reset'), function(r){
                        if (r.success) {
                            done = 0;
                            log('Отметки проверки категорий сброшены', 'info');
                            loadStatus();
                        }
                    });
                });

                $('#hws-category-parent').on('change', function(){
                    var parentId = String($(this).val() || '');
                    var child = $('#hws-category-child');
                    child.val('');
                    child.find('option[data-parent]').each(function(){
                        $(this).toggle(!parentId || String($(this).data('parent')) === parentId);
                    });
                    child.prop('disabled', !parentId);
                    done = 0;
                    loadStatus();
                });

                $('#hws-category-brand, #hws-category-child').on('change', function(){
                    done = 0;
                    loadStatus();
                });

                loadStatus();
            })();

            var formatRunner = createRunner('format', {
                startBtn: '#hws-format-start-btn',
                stopBtn: '#hws-format-stop-btn',
                resetBtn: '#hws-format-reset-btn',
                progressWrap: '#hws-format-progress',
                progressLabel: '#hws-format-progress-label',
                bar: '#hws-format-bar',
                log: '#hws-format-log',
                totalEl: '#hws-format-total',
                doneEl: '#hws-format-done',
                leftEl: '#hws-format-left',
                startMessage: 'Запуск форматирования…',
                finishMessage: 'Все товары отформатированы',
                resetConfirm: 'Сбросить все отметки форматирования? Все товары будут отформатированы заново.'
            });

            var cleanExcerptRunner = createRunner('clean_excerpt', {
                startBtn: '#hws-clean-excerpt-start-btn',
                stopBtn: '#hws-clean-excerpt-stop-btn',
                resetBtn: '#hws-clean-excerpt-reset-btn',
                progressWrap: '#hws-clean-excerpt-progress',
                progressLabel: '#hws-clean-excerpt-progress-label',
                bar: '#hws-clean-excerpt-bar',
                log: '#hws-clean-excerpt-log',
                totalEl: '#hws-clean-excerpt-total',
                doneEl: '#hws-clean-excerpt-done',
                leftEl: '#hws-clean-excerpt-left',
                startMessage: 'Запуск очистки коротких описаний…',
                finishMessage: 'Короткие описания очищены',
                resetConfirm: 'Сбросить отметки очистки коротких описаний? Все товары будут проверены заново.'
            });

            var translateRunner = createRunner('translate', {
                scanRequired: true,
                scanBtn: '#hws-translate-scan-btn',
                scanWrap: '#hws-translate-scan',
                scanSummary: '#hws-translate-scan-summary',
                scanList: '#hws-translate-scan-list',
                completedList: '#hws-translate-completed-list',
                startBtn: '#hws-translate-start-btn',
                stopBtn: '#hws-translate-stop-btn',
                resetBtn: '#hws-translate-reset-btn',
                progressWrap: '#hws-translate-progress',
                progressLabel: '#hws-translate-progress-label',
                bar: '#hws-translate-bar',
                log: '#hws-translate-log',
                totalEl: '#hws-translate-total',
                doneEl: '#hws-translate-done',
                leftEl: '#hws-translate-left',
                startMessage: 'Запуск перевода карточек, атрибутов и вариаций…',
                finishMessage: 'Все товары переведены',
                resetConfirm: 'Сбросить все отметки перевода? Все товары будут переведены заново.'
            });

            formatRunner.loadStatus();
            cleanExcerptRunner.loadStatus();
            translateRunner.loadStatus();
            loadActivityLog();
        })(jQuery);
        </script>
        <?php
    }

    // AJAX handlers

    public static function ajax_category_status(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $filters = self::category_filters_from_request();
        $total = (int) (new WP_Query(self::category_product_query_args(false, -1, $filters)))->found_posts;
        $processed = (int) (new WP_Query(self::category_product_query_args(true, -1, $filters)))->found_posts;

        wp_send_json_success([
            'total' => $total,
            'processed' => $processed,
        ]);
    }

    public static function ajax_category_reset(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $filters = self::category_filters_from_request();
        $ids = (new WP_Query(self::category_product_query_args(true, -1, $filters)))->posts;
        foreach ($ids as $product_id) {
            delete_post_meta((int) $product_id, self::CATEGORY_META_KEY);
            delete_post_meta((int) $product_id, self::CATEGORY_REPORT_META_KEY);
        }
        wp_send_json_success();
    }

    public static function ajax_category_next(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $settings = self::settings();
        $api_key = (string) ($settings['api_key'] ?? '');
        $model = (string) ($settings['model'] ?? 'meta-llama/llama-3.1-8b-instruct:free');
        if ($mode !== 'clean_excerpt' && $api_key === '') {
            wp_send_json_error('API ключ не задан');
        }

        $filters = self::category_filters_from_request();
        $query = new WP_Query(self::category_product_query_args(false, 1, $filters));
        if (empty($query->posts)) {
            wp_send_json_success(['done' => true]);
        }

        $product_id = (int) $query->posts[0];
        $post = get_post($product_id);
        $product = wc_get_product($product_id);
        if (!$post instanceof WP_Post || !$product) {
            update_post_meta($product_id, self::CATEGORY_META_KEY, 'error');
            wp_send_json_success([
                'done' => false,
                'id' => $product_id,
                'title' => 'unknown',
                'changed' => false,
                'message' => 'товар не найден',
            ]);
        }

        $result = self::classify_and_apply_category($post, $product, $api_key, $model);
        if (is_wp_error($result)) {
            update_post_meta($product_id, self::CATEGORY_META_KEY, 'error');
            wp_send_json_success([
                'done' => false,
                'id' => $product_id,
                'title' => $post->post_title,
                'changed' => false,
                'message' => 'ошибка: ' . $result->get_error_message(),
            ]);
        }

        update_post_meta($product_id, self::CATEGORY_META_KEY, '1');
        update_post_meta($product_id, self::CATEGORY_REPORT_META_KEY, $result);

        wp_send_json_success([
            'done' => false,
            'id' => $product_id,
            'title' => $post->post_title,
            'changed' => !empty($result['changed']),
            'message' => (string) ($result['message'] ?? 'проверено'),
        ]);
    }

    public static function ajax_status(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $mode = self::requested_mode();
        $meta_key = self::meta_key_for_mode($mode);
        $brand_id = self::request_int('brand_id');

        if (!empty($_POST['reset'])) {
            self::reset_meta_marks($mode, $meta_key, $brand_id);
        }

        $total = (int) (new WP_Query(self::product_query_args($mode, '', $brand_id, null)))->found_posts;
        $processed = (int) (new WP_Query(self::product_query_args($mode, $meta_key, $brand_id, true)))->found_posts;

        wp_send_json_success([
            'total' => $total,
            'processed' => $processed,
        ]);
    }

    public static function ajax_next(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $mode = self::requested_mode();
        $meta_key = self::meta_key_for_mode($mode);
        $brand_id = self::request_int('brand_id');
        $settings = self::settings();
        $api_key = $settings['api_key'] ?? '';
        $model = $settings['model'] ?? 'meta-llama/llama-3.1-8b-instruct:free';

        if ($api_key === '') {
            wp_send_json_error('API ключ не задан');
        }

        $query = new WP_Query(self::product_query_args($mode, $meta_key, $brand_id, false, 1));

        if (empty($query->posts)) {
            wp_send_json_success(['done' => true]);
        }

        $product_id = (int) $query->posts[0];
        $post = get_post($product_id);
        if (!$post instanceof WP_Post) {
            update_post_meta($product_id, $meta_key, 'error');
            wp_send_json_success([
                'done' => false,
                'id' => $product_id,
                'title' => 'unknown',
                'error' => 'Товар не найден',
            ]);
        }

        if ($mode === 'format') {
            $result = self::process_format($post, $api_key, $model);
        } elseif ($mode === 'clean_excerpt') {
            $result = self::process_clean_excerpt($post);
        } else {
            $result = self::process_translate(
                $post,
                $api_key,
                $model,
                self::request_string('from_lang', $settings['translate_from'] ?? 'ru'),
                self::request_string('to_lang', $settings['translate_to'] ?? 'en')
            );
        }

        if (!empty($result['mark'])) {
            update_post_meta($product_id, $meta_key, $result['mark']);
        }

        wp_send_json_success([
            'done' => false,
            'id' => $product_id,
            'title' => $post->post_title,
            'skipped' => !empty($result['skipped']),
            'skipped_reason' => $result['skipped_reason'] ?? '',
            'error' => $result['error'] ?? '',
            'details' => $result['details'] ?? [],
        ]);
    }

    public static function ajax_scan(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $mode = self::requested_mode();
        if ($mode !== 'translate') {
            wp_send_json_error('Сканер доступен только для перевода');
        }

        $brand_id = self::request_int('brand_id');
        // Сканирование должно проверять актуальное содержимое, а не старую
        // отметку перевода. Отметка не содержит языковую пару и могла быть
        // записана предыдущим запуском для другого перевода.
        $query = new WP_Query(self::product_query_args($mode, '', $brand_id, null));
        $from_code = self::request_string('from_lang', self::settings()['translate_from'] ?? 'ru');
        $to_code = self::request_string('to_lang', self::settings()['translate_to'] ?? 'en');
        $items = [];
        $summary = [
            'title' => 0,
            'excerpt' => 0,
            'content' => 0,
            'attribute_terms' => 0,
            'variation_values' => 0,
        ];

        foreach ($query->posts as $product_id) {
            $post = get_post((int) $product_id);
            $product = wc_get_product((int) $product_id);
            if (!$post instanceof WP_Post || !$product) {
                continue;
            }

            $scan = self::scan_product_translation($post, $product, $from_code);
            if (empty($scan['parts'])) {
                // Синхронизируем очередь: товар без исходных текстов больше
                // не должен блокировать завершение текущей языковой пары.
                update_post_meta((int) $product_id, self::TRANSLATE_META_KEY, '1');
                continue;
            }

            // Если содержимое снова требует перевода, снимаем устаревшую
            // отметку, чтобы ajax_next обработал этот товар.
            delete_post_meta((int) $product_id, self::TRANSLATE_META_KEY);
            delete_post_meta((int) $product_id, self::TRANSLATE_REPORT_META_KEY);

            foreach ($scan['counts'] as $key => $value) {
                $summary[$key] += $value;
            }
            $items[] = $scan['item'];
        }

        wp_send_json_success([
            'total' => count($items),
            'summary' => $summary,
            'items' => $items,
        ]);
    }

    public static function ajax_completed(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $mode = self::requested_mode();
        if ($mode !== 'translate') {
            wp_send_json_success(['items' => []]);
        }

        $brand_id = self::request_int('brand_id');
        $args = self::product_query_args($mode, '', $brand_id, null, 200);
        $args['meta_query'] = [[
            'key' => self::TRANSLATE_META_KEY,
            'value' => '1',
            'compare' => '=',
        ]];
        $query = new WP_Query($args);
        $items = [];

        foreach ($query->posts as $product_id) {
            $post = get_post((int) $product_id);
            $product = wc_get_product((int) $product_id);
            if (!$post instanceof WP_Post || !$product) {
                continue;
            }

            $report = get_post_meta((int) $product_id, self::TRANSLATE_REPORT_META_KEY, true);
            if (is_array($report)) {
                $items[] = [
                    'id' => (int) $product_id,
                    'title' => $post->post_title,
                    'translated_at' => (string) ($report['translated_at'] ?? ''),
                    'parts' => array_values((array) ($report['parts'] ?? [])),
                    'details' => array_values((array) ($report['details'] ?? [])),
                    'note' => '',
                ];
                continue;
            }

            $scan = self::scan_product_translation($post, $product, self::request_string('from_lang', self::settings()['translate_from'] ?? 'ru'));
            $items[] = [
                'id' => (int) $product_id,
                'title' => $post->post_title,
                'translated_at' => '',
                'parts' => array_values((array) ($scan['parts'] ?? [])),
                'details' => [],
                'note' => 'Старый перевод: точный отчет еще не записывался.',
            ];
        }

        wp_send_json_success(['items' => $items]);
    }

    public static function ajax_logs(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        $action = self::request_string('log_action', 'list');
        if ($action === 'clear') {
            update_option(self::LOG_OPT_KEY, []);
            wp_send_json_success(['logs' => []]);
        }

        if ($action === 'add') {
            self::append_activity_log(
                self::request_string('mode', 'system'),
                self::request_string('level', 'info'),
                self::request_string('message', '')
            );
        }

        wp_send_json_success(['logs' => self::activity_logs()]);
    }

    public static function ajax_save_settings(): void {
        check_ajax_referer('hws_formatter');
        self::assert_permissions();

        update_option(self::OPT_KEY, [
            'api_key' => sanitize_text_field(wp_unslash($_POST['api_key'] ?? '')),
            'model' => sanitize_text_field(wp_unslash($_POST['model'] ?? '')),
            'translate_from' => self::sanitize_language_code($_POST['translate_from'] ?? 'ru'),
            'translate_to' => self::sanitize_language_code($_POST['translate_to'] ?? 'en'),
            'translate_brand' => absint($_POST['translate_brand'] ?? 0),
        ]);

        wp_send_json_success();
    }

    // Product processing

    private static function process_format(WP_Post $post, string $api_key, string $model): array {
        $raw = (string) $post->post_content;
        if (trim(wp_strip_all_tags($raw)) === '') {
            return ['mark' => '1', 'skipped' => true, 'skipped_reason' => 'нет описания'];
        }

        $formatted = self::call_format_openrouter($raw, $api_key, $model);
        if (is_wp_error($formatted)) {
            return ['mark' => 'error', 'error' => $formatted->get_error_message()];
        }

        wp_update_post([
            'ID' => $post->ID,
            'post_content' => $formatted,
        ]);

        return ['mark' => '1'];
    }

    private static function process_clean_excerpt(WP_Post $post): array {
        $raw = (string) $post->post_excerpt;
        $clean = self::clean_excerpt_text($raw);

        if ($clean === '') {
            return [
                'mark' => '1',
                'skipped' => true,
                'skipped_reason' => 'краткое описание пустое',
                'details' => ['Проверка: исходное краткое описание пустое'],
            ];
        }

        $details = [
            'Проверка: было символов ' . mb_strlen($raw) . ', стало ' . mb_strlen($clean),
        ];

        if ($clean !== $raw) {
            wp_update_post([
                'ID' => $post->ID,
                'post_excerpt' => $clean,
            ]);
            self::flush_product_cache($post->ID);
            $details[] = 'Готово: краткое описание очищено и сохранено одной строкой';
        } else {
            $details[] = 'Готово: краткое описание уже было чистой строкой';
        }

        return ['mark' => '1', 'details' => $details];
    }

    private static function clean_excerpt_text(string $text): string {
        $charset = get_bloginfo('charset') ?: 'UTF-8';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, $charset);
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text, true);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, $charset);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    private static function flush_product_cache(int $product_id): void {
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
        }
        clean_post_cache($product_id);
        if (function_exists('hws_revalidate')) {
            hws_revalidate();
        }
        if (function_exists('hws_graphql_bridge_trigger_frontend_revalidate')) {
            hws_graphql_bridge_trigger_frontend_revalidate();
        }
    }

    private static function process_translate(WP_Post $post, string $api_key, string $model, string $from_code, string $to_code): array {
        $from_label = self::language_label($from_code);
        $to_label = self::language_label($to_code);
        $details = [];
        if ($from_label === '' || $to_label === '') {
            return ['mark' => 'error', 'error' => 'Не выбраны языки перевода', 'details' => $details];
        }

        $product_payload = [
            'title' => (string) $post->post_title,
            'excerpt' => (string) $post->post_excerpt,
            'content' => (string) $post->post_content,
            'from_language' => $from_label,
            'to_language' => $to_label,
        ];

        $product = wc_get_product($post->ID);
        $scan = $product ? self::scan_product_translation($post, $product, $from_code) : ['parts' => []];
        $blueprint = $product ? self::build_attributes_blueprint($post->ID, $product) : ['attributes' => []];
        $scope = self::translation_scope_details($product_payload, $blueprint, $product);
        $details[] = 'Проверка: ' . $scope['check'];
        $details[] = 'Нужно перевести: ' . $scope['todo'];

        $has_text = self::payload_has_text($product_payload, ['title', 'excerpt', 'content']);
        $has_attributes = !empty($blueprint['attributes']);

        if (!$has_text && !$has_attributes) {
            self::save_translate_report($post->ID, (array) ($scan['parts'] ?? []), $details);
            return [
                'mark' => '1',
                'skipped' => true,
                'skipped_reason' => 'нет текстов и атрибутов для перевода',
                'details' => $details,
            ];
        }

        if ($has_text) {
            $details[] = 'Статус: отправляю тексты карточки в модель';
            $translated_product = self::call_translate_product_openrouter($product_payload, $api_key, $model);
            if (is_wp_error($translated_product)) {
                return ['mark' => 'error', 'error' => $translated_product->get_error_message(), 'details' => $details];
            }

            wp_update_post([
                'ID' => $post->ID,
                'post_title' => $translated_product['title'],
                'post_excerpt' => $translated_product['excerpt'],
                'post_content' => $translated_product['content'],
            ]);
            $details[] = 'Готово: название, краткое описание и полное описание обновлены';
        } else {
            $details[] = 'Статус: текстовые поля пустые, пропускаю перевод текстов';
        }

        $details[] = 'Статус: проверяю атрибуты и вариации';
        $attributes_result = self::translate_product_attributes($post->ID, $api_key, $model, $from_label, $to_label, $blueprint);
        if (is_wp_error($attributes_result)) {
            return ['mark' => 'error', 'error' => $attributes_result->get_error_message(), 'details' => $details];
        }
        $details[] = 'Готово: ' . $attributes_result['message'];

        self::save_translate_report($post->ID, (array) ($scan['parts'] ?? []), $details);
        return ['mark' => '1', 'details' => $details];
    }

    private static function save_translate_report(int $product_id, array $parts, array $details): void {
        update_post_meta($product_id, self::TRANSLATE_REPORT_META_KEY, [
            'translated_at' => current_time('mysql'),
            'parts' => array_values($parts),
            'details' => array_values($details),
        ]);
    }

    private static function translate_product_attributes(int $product_id, string $api_key, string $model, string $from_label, string $to_label, ?array $blueprint = null): array|\WP_Error {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new \WP_Error('product_missing', 'Товар не найден');
        }

        $blueprint = $blueprint ?? self::build_attributes_blueprint($product_id, $product);
        if (empty($blueprint['attributes'])) {
            return ['message' => 'атрибутов для перевода нет'];
        }

        $translated = self::call_translate_attributes_openrouter([
            'from_language' => $from_label,
            'to_language' => $to_label,
            'attributes' => $blueprint['attributes'],
        ], $api_key, $model);

        if (is_wp_error($translated)) {
            return $translated;
        }

        self::apply_translated_attributes($product_id, $product, $blueprint, $translated['attributes']);
        $scope = self::translation_scope_details([], $blueprint, $product);
        return ['message' => 'атрибуты и вариации обновлены (' . $scope['attributes'] . ')'];
    }

    private static function scan_product_translation(WP_Post $post, WC_Product $product, string $from_code = 'ru'): array {
        $parts = [];
        $counts = [
            'title' => 0,
            'excerpt' => 0,
            'content' => 0,
            'attribute_terms' => 0,
            'variation_values' => 0,
        ];

        $text_fields = [
            'title' => ['Название товара', (string) $post->post_title],
            'excerpt' => ['Краткое описание', (string) $post->post_excerpt],
            'content' => ['Полное описание', (string) $post->post_content],
        ];

        foreach ($text_fields as $key => [$label, $value]) {
            if (trim(wp_strip_all_tags($value)) === '') {
                continue;
            }
            $parts[] = [
                'type' => $label,
                'language' => self::detect_text_language($value),
            ];
            $counts[$key]++;
        }

        $blueprint = self::build_attributes_blueprint($post->ID, $product);
        $term_langs = [];
        $variation_langs = [];

        foreach ((array) ($blueprint['attributes'] ?? []) as $attribute) {
            if (!empty($attribute['is_taxonomy'])) {
                foreach ((array) ($attribute['terms'] ?? []) as $term) {
                    $name = (string) ($term['name'] ?? '');
                    if ($name !== '') {
                        $term_langs[self::detect_text_language($name)] = true;
                        $counts['attribute_terms']++;
                    }
                }
                continue;
            }

            foreach ((array) ($attribute['options'] ?? []) as $option) {
                $option = (string) $option;
                if ($option !== '') {
                    $variation_langs[self::detect_text_language($option)] = true;
                    $counts['variation_values']++;
                }
            }
        }

        if (!empty($term_langs)) {
            $parts[] = [
                'type' => 'Термы атрибутов',
                'language' => implode(', ', array_keys($term_langs)),
            ];
        }

        if (!empty($variation_langs)) {
            $parts[] = [
                'type' => 'Значения вариаций',
                'language' => implode(', ', array_keys($variation_langs)),
            ];
        }

        return [
            'counts' => $counts,
            'parts' => $parts,
            'item' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'parts' => $parts,
            ],
        ];
    }

    private static function text_needs_translation(string $text, string $from_code): bool {
        $text = trim(wp_strip_all_tags($text));
        if ($text === '') {
            return false;
        }

        // Для языков, которые текущий лёгкий детектор не различает,
        // оставляем прежнее безопасное поведение: проверяем любое значение.
        $detected = self::detect_text_language($text);
        if ($from_code === 'ru') {
            return in_array($detected, ['Russian', 'mixed'], true);
        }
        if ($from_code === 'en') {
            return in_array($detected, ['English/Latin', 'mixed'], true);
        }

        return true;
    }

    private static function detect_text_language(string $text): string {
        $text = wp_strip_all_tags($text);
        $cyr = preg_match_all('/[А-Яа-яЁё]/u', $text);
        $lat = preg_match_all('/[A-Za-z]/', $text);
        $total = $cyr + $lat;

        if ($total === 0) {
            return 'не определен';
        }
        if ($cyr / $total > 0.7) {
            return 'Russian';
        }
        if ($lat / $total > 0.7) {
            return 'English/Latin';
        }
        return 'mixed';
    }

    private static function translation_scope_details(array $payload, array $blueprint, ?WC_Product $product): array {
        $text_labels = [
            'title' => 'название',
            'excerpt' => 'краткое описание',
            'content' => 'полное описание',
        ];
        $filled_texts = [];
        foreach ($text_labels as $field => $label) {
            if (trim(wp_strip_all_tags((string) ($payload[$field] ?? ''))) !== '') {
                $filled_texts[] = $label;
            }
        }

        $taxonomy_count = 0;
        $custom_count = 0;
        $term_count = 0;
        $option_count = 0;
        $variation_value_count = 0;

        foreach ((array) ($blueprint['attributes'] ?? []) as $attribute) {
            if (!empty($attribute['is_taxonomy'])) {
                $taxonomy_count++;
                $term_count += count((array) ($attribute['terms'] ?? []));
                continue;
            }
            $custom_count++;
            $option_count += count((array) ($attribute['options'] ?? []));
        }

        if ($product && $product->is_type('variable')) {
            $variation_values = [];
            foreach ((array) ($blueprint['attributes'] ?? []) as $attribute) {
                if (!empty($attribute['is_taxonomy'])) {
                    continue;
                }
                $key = (string) ($attribute['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                foreach ($product->get_children() as $variation_id) {
                    $value = (string) get_post_meta($variation_id, 'attribute_' . $key, true);
                    if ($value !== '') {
                        $variation_values[$key . ':' . $value] = true;
                    }
                }
            }
            $variation_value_count = count($variation_values);
        }

        $todo = [];
        if (!empty($filled_texts)) {
            $todo[] = implode(', ', $filled_texts);
        }
        if ($taxonomy_count > 0) {
            $todo[] = $taxonomy_count . ' глоб. атриб.';
        }
        if ($term_count > 0) {
            $todo[] = $term_count . ' термов';
        }
        if ($option_count > 0) {
            $todo[] = $option_count . ' опций';
        }
        if ($variation_value_count > 0) {
            $todo[] = $variation_value_count . ' знач. вариаций';
        }

        $texts = !empty($filled_texts) ? implode(', ', $filled_texts) : 'нет';
        $attributes = $taxonomy_count . ' глоб. атриб., ' . $custom_count . ' кастом. атриб., ' . $term_count . ' термов, ' . $option_count . ' опций, ' . $variation_value_count . ' знач. вариаций';

        return [
            'check' => 'тексты: ' . $texts . '; ' . $attributes,
            'todo' => !empty($todo) ? implode('; ', $todo) : 'ничего',
            'attributes' => $attributes,
        ];
    }

    private static function build_attributes_blueprint(int $product_id, WC_Product $product): array {
        $attributes = [];
        foreach ($product->get_attributes() as $key => $attribute) {
            $item = [
                'key' => (string) $key,
                'label' => (string) wc_attribute_label($attribute->get_name(), $product),
                'is_taxonomy' => $attribute->is_taxonomy(),
                'taxonomy' => $attribute->is_taxonomy() ? (string) $attribute->get_name() : '',
                'terms' => [],
                'options' => [],
            ];

            if ($attribute->is_taxonomy()) {
                $terms = wc_get_product_terms($product_id, $attribute->get_name(), ['fields' => 'all']);
                foreach ($terms as $term) {
                    $item['terms'][] = [
                        'term_id' => (int) $term->term_id,
                        'name' => (string) $term->name,
                    ];
                }
            } else {
                foreach ($attribute->get_options() as $option) {
                    $item['options'][] = (string) $option;
                }
            }

            if ($item['label'] !== '' || !empty($item['terms']) || !empty($item['options'])) {
                $attributes[] = $item;
            }
        }

        return ['attributes' => $attributes];
    }

    // Attribute writes

    private static function apply_translated_attributes(int $product_id, WC_Product $product, array $blueprint, array $translated_attributes): void {
        global $wpdb;

        $translated_by_key = [];
        foreach ($translated_attributes as $attribute) {
            if (!empty($attribute['key'])) {
                $translated_by_key[(string) $attribute['key']] = $attribute;
            }
        }

        $raw_meta = get_post_meta($product_id, '_product_attributes', true);
        if (!is_array($raw_meta)) {
            $raw_meta = [];
        }

        foreach ($blueprint['attributes'] as $original_attribute) {
            $key = (string) $original_attribute['key'];
            if (!isset($translated_by_key[$key])) {
                continue;
            }

            $translated_attribute = $translated_by_key[$key];

            if (!empty($original_attribute['is_taxonomy']) && !empty($original_attribute['taxonomy'])) {
                self::update_global_taxonomy_label((string) $original_attribute['taxonomy'], (string) ($translated_attribute['label'] ?? ''));

                $translated_terms = [];
                foreach ((array) ($translated_attribute['terms'] ?? []) as $term_item) {
                    if (!isset($term_item['term_id'])) {
                        continue;
                    }
                    $translated_terms[(int) $term_item['term_id']] = (string) ($term_item['name'] ?? '');
                }

                foreach ((array) ($original_attribute['terms'] ?? []) as $term_item) {
                    $term_id = (int) ($term_item['term_id'] ?? 0);
                    $new_name = trim($translated_terms[$term_id] ?? '');
                    if ($term_id > 0 && $new_name !== '') {
                        wp_update_term($term_id, (string) $original_attribute['taxonomy'], ['name' => $new_name]);
                    }
                }
                continue;
            }

            $old_options = array_values(array_map('strval', (array) ($original_attribute['options'] ?? [])));
            $new_options = array_values(array_map('strval', (array) ($translated_attribute['options'] ?? [])));
            if (empty($old_options) || count($old_options) !== count($new_options)) {
                continue;
            }

            if (isset($raw_meta[$key])) {
                $raw_meta[$key]['value'] = implode(' | ', $new_options);
            }

            $map = [];
            foreach ($old_options as $index => $old_value) {
                $map[$old_value] = $new_options[$index];
            }

            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $variation_id) {
                    $meta_key = 'attribute_' . $key;
                    $current = get_post_meta($variation_id, $meta_key, true);
                    if ($current !== '' && isset($map[$current])) {
                        update_post_meta($variation_id, $meta_key, $map[$current]);
                    }
                }
            }
        }

        update_post_meta($product_id, '_product_attributes', $raw_meta);

        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $variation_id) {
                    wc_delete_product_transients($variation_id);
                }
            }
        }

        clean_post_cache($product_id);
        $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_wc_attribute_taxonomies%"');
    }

    private static function update_global_taxonomy_label(string $taxonomy, string $new_label): void {
        global $wpdb;

        $new_label = trim($new_label);
        if ($new_label === '' || strpos($taxonomy, 'pa_') !== 0) {
            return;
        }

        $attribute_name = substr($taxonomy, 3);
        $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $wpdb->update(
            $table,
            ['attribute_label' => $new_label],
            ['attribute_name' => $attribute_name],
            ['%s'],
            ['%s']
        );
        delete_transient('wc_attribute_taxonomies');
    }

    // OpenRouter

    private const CATEGORY_PROMPT = <<<'PROMPT'
Ты — классификатор товаров для каталога оборудования для бань, саун и хаммамов.

Определи одну основную категорию товара. Если текущая категория подходит по назначению товара, верни action "keep". Если не подходит, верни action "move" и выбери путь только из allowed_categories. Никогда не помещай аксессуар, пульт, дымоход, бак или теплообменник в категорию печей или парогенераторов. Бренд не является категорией.

Верни только JSON:
{
  "action": "keep" или "move",
  "path": ["slug", "child-slug"],
  "confidence": 0.0,
  "reason": "краткая причина"
}
Для низкой уверенности ниже 0.75 используй action "keep" и не меняй товар.
PROMPT;

    private static function classify_and_apply_category(WP_Post $post, WC_Product $product, string $api_key, string $model): array|\WP_Error {
        $current = self::product_category_path($product->get_id());
        $text = trim(wp_strip_all_tags((string) $post->post_title . "\n" . (string) $post->post_excerpt . "\n" . (string) $post->post_content));
        $hint = self::category_hint($text);

        if ($hint !== null) {
            $suggestion = ['action' => 'move', 'path' => $hint, 'confidence' => 0.96];
        } else {
            $attributes = [];
            foreach ($product->get_attributes() as $attribute) {
                $values = $attribute->is_taxonomy()
                    ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])
                    : $attribute->get_options();
                $attributes[] = [
                    'name' => wc_attribute_label($attribute->get_name(), $product),
                    'values' => array_values(array_map('strval', (array) $values)),
                ];
            }
            $suggestion = self::call_json_openrouter(self::CATEGORY_PROMPT, [
                'title' => (string) $post->post_title,
                'excerpt' => (string) $post->post_excerpt,
                'content' => mb_substr($text, 0, 5000),
                'brand' => self::product_brand_name($product->get_id()),
                'attributes' => $attributes,
                'current_category' => $current,
                'allowed_categories' => self::category_blueprint(),
            ], $api_key, $model, 1200);
            if (is_wp_error($suggestion)) {
                return $suggestion;
            }
        }

        $path = array_values(array_filter(array_map('sanitize_title', (array) ($suggestion['path'] ?? []))));
        $allowed = array_map(static fn(array $item): string => implode('/', $item['path']), self::category_blueprint());
        if ($path === [] || !in_array(implode('/', $path), $allowed, true)) {
            return ['changed' => false, 'message' => 'оставлено: категория не прошла проверку', 'current' => $current, 'suggested' => $path];
        }

        $confidence = (float) ($suggestion['confidence'] ?? 0);
        $target = implode('/', $path);
        if (($suggestion['action'] ?? '') === 'keep' || $confidence < 0.75 || $target === implode('/', $current)) {
            return ['changed' => false, 'message' => 'категория оставлена: ' . ($current ? implode(' / ', $current) : 'не определена'), 'current' => $current, 'suggested' => $path, 'confidence' => $confidence];
        }

        $term_ids = self::ensure_category_path($path);
        if (is_wp_error($term_ids)) {
            return $term_ids;
        }
        $set = wp_set_object_terms($product->get_id(), $term_ids, 'product_cat', false);
        if (is_wp_error($set)) {
            return $set;
        }

        return ['changed' => true, 'message' => 'перемещён в ' . implode(' / ', $path), 'current' => $current, 'suggested' => $path, 'confidence' => $confidence];
    }

    private static function category_hint(string $text): ?array {
        $text = mb_strtolower($text);
        if (preg_match('/steam outlet|essence holder|aroma|арома|аромат|дозатор аром|вывод пара|подач[аи] пара/u', $text)) {
            return ['accessories', 'aroma-and-steam'];
        }
        if (preg_match('/control unit|control panel|пульт управления|автоматик/u', $text)) {
            return ['control-units'];
        }
        if (preg_match('/chimney|дымоход|flue/u', $text)) {
            return ['chimneys-and-installation', 'chimneys'];
        }
        if (preg_match('/heat exchanger|теплообменник/u', $text)) {
            return ['water-tanks-and-heat-exchangers', 'heat-exchangers'];
        }
        if (preg_match('/water tank|бак для воды/u', $text)) {
            return ['water-tanks-and-heat-exchangers', 'water-tanks'];
        }
        return null;
    }

    private static function product_category_path(int $product_id): array {
        $terms = wp_get_post_terms($product_id, 'product_cat');
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }
        usort($terms, static fn(WP_Term $a, WP_Term $b): int => count(get_ancestors($b->term_id, 'product_cat', 'taxonomy')) <=> count(get_ancestors($a->term_id, 'product_cat', 'taxonomy')));
        $term = $terms[0];
        $ids = array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));
        $ids[] = (int) $term->term_id;
        $path = [];
        foreach ($ids as $id) {
            $ancestor = get_term($id, 'product_cat');
            if ($ancestor && !is_wp_error($ancestor)) {
                $path[] = (string) $ancestor->slug;
            }
        }
        return $path;
    }

    private static function ensure_category_path(array $path): array|\WP_Error {
        $parent = 0;
        $ids = [];
        foreach ($path as $slug) {
            $label = self::category_label($slug);
            $terms = get_terms(['taxonomy' => 'product_cat', 'slug' => $slug, 'parent' => $parent, 'hide_empty' => false, 'number' => 1]);
            if (!is_wp_error($terms) && !empty($terms[0])) {
                $term_id = (int) $terms[0]->term_id;
            } else {
                // WordPress may suffix a slug when an earlier duplicate already exists.
                // Match the approved name and parent before creating another term.
                $siblings = get_terms(['taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC']);
                $term_id = 0;
                if (!is_wp_error($siblings)) {
                    $normalized_label = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($label))));
                    foreach ($siblings as $sibling) {
                        $normalized_name = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($sibling->name))));
                        if ($normalized_name === $normalized_label) {
                            $term_id = (int) $sibling->term_id;
                            break;
                        }
                    }
                }
                if ($term_id > 0) {
                    $ids[] = $term_id;
                    $parent = $term_id;
                    continue;
                }
                $inserted = wp_insert_term($label, 'product_cat', ['slug' => $slug, 'parent' => $parent]);
                if (is_wp_error($inserted)) {
                    return $inserted;
                }
                $term_id = (int) $inserted['term_id'];
            }
            $ids[] = $term_id;
            $parent = $term_id;
        }
        return $ids;
    }

    private static function product_brand_name(int $product_id): string {
        $taxonomy = self::brand_taxonomy();
        if ($taxonomy === '') {
            return '';
        }
        $terms = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'names']);
        return is_wp_error($terms) ? '' : implode(', ', array_map('strval', (array) $terms));
    }

    private static function call_format_openrouter(string $raw_html, string $api_key, string $model): string|\WP_Error {
        $text = wp_strip_all_tags($raw_html);
        $text = preg_replace('/\s{3,}/', "\n\n", trim($text));
        if (mb_strlen($text) < 30) {
            return new \WP_Error('too_short', 'Слишком короткий текст');
        }

        $body = self::call_openrouter([
            'model' => $model,
            'max_tokens' => 2000,
            'messages' => [
                ['role' => 'system', 'content' => self::FORMAT_PROMPT],
                ['role' => 'user', 'content' => $text],
            ],
        ], $api_key);

        if (is_wp_error($body)) {
            return $body;
        }

        $content = self::extract_message_content($body);
        if ($content === '') {
            return new \WP_Error('empty_response', 'Пустой ответ от модели');
        }

        return trim(self::strip_code_fences($content));
    }

    private static function call_translate_product_openrouter(array $payload, string $api_key, string $model): array|\WP_Error {
        $decoded = self::call_json_openrouter(self::TRANSLATE_PRODUCT_PROMPT, $payload, $api_key, $model, 4000);
        if (is_wp_error($decoded)) {
            return $decoded;
        }

        foreach (['title', 'excerpt', 'content'] as $field) {
            if (!array_key_exists($field, $decoded)) {
                return new \WP_Error('missing_field', 'В ответе модели отсутствует поле ' . $field);
            }
        }

        return [
            'title' => (string) $decoded['title'],
            'excerpt' => (string) $decoded['excerpt'],
            'content' => (string) $decoded['content'],
        ];
    }

    private static function call_translate_attributes_openrouter(array $payload, string $api_key, string $model): array|\WP_Error {
        $decoded = self::call_json_openrouter(self::TRANSLATE_ATTRIBUTES_PROMPT, $payload, $api_key, $model, 4000);
        if (is_wp_error($decoded)) {
            return $decoded;
        }
        if (!isset($decoded['attributes']) || !is_array($decoded['attributes'])) {
            return new \WP_Error('missing_attributes', 'В ответе модели нет массива attributes');
        }
        return ['attributes' => $decoded['attributes']];
    }

    private static function call_json_openrouter(string $prompt, array $payload, string $api_key, string $model, int $max_tokens): array|\WP_Error {
        $last_error = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $body = self::call_openrouter([
                'model' => $model,
                'max_tokens' => $max_tokens,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                'response_format' => ['type' => 'json_object'],
            ], $api_key);

            if (is_wp_error($body)) {
                $last_error = $body;
                continue;
            }

            $content = self::extract_message_content($body);
            if ($content === '') {
                $last_error = new \WP_Error('empty_response', 'Пустой ответ от модели');
                continue;
            }

            $decoded = self::decode_json_content($content);
            if (!is_wp_error($decoded)) {
                return $decoded;
            }

            $last_error = $decoded;
        }

        return $last_error instanceof \WP_Error ? $last_error : new \WP_Error('bad_json', 'Модель вернула невалидный JSON');
    }

    private static function call_openrouter(array $payload, string $api_key): array|\WP_Error {
        $last_error = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = wp_remote_post(self::API_URL, [
                'timeout' => 180,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => get_site_url(),
                    'X-Title' => get_bloginfo('name'),
                ],
                'body' => wp_json_encode($payload),
            ]);

            if (is_wp_error($response)) {
                $last_error = $response;
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && is_array($body)) {
                return $body;
            }

            $msg = is_array($body) ? ($body['error']['message'] ?? ('HTTP ' . $code)) : 'Некорректный ответ от API';
            $last_error = new \WP_Error($code === 200 ? 'bad_response' : 'api_error', $msg);
        }

        return $last_error instanceof \WP_Error ? $last_error : new \WP_Error('api_error', 'Ошибка API');
    }

    // UI helpers

    private static function render_language_select(string $id, string $selected): void {
        echo '<select id="' . esc_attr($id) . '">';
        foreach (self::LANGUAGE_OPTIONS as $code => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($code),
                selected($selected, $code, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    private static function render_brand_select(int $selected = 0): void {
        $taxonomy = self::brand_taxonomy();
        echo '<select id="hws-translate-brand">';
        echo '<option value="0">Все бренды</option>';

        if ($taxonomy === '') {
            echo '</select>';
            return;
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                printf(
                    '<option value="%d"%s>%s</option>',
                    (int) $term->term_id,
                    selected($selected, (int) $term->term_id, false),
                    esc_html($term->name)
                );
            }
        }

        echo '</select>';
    }

    private static function render_category_brand_select(): void {
        self::render_taxonomy_select('hws-category-brand', self::brand_taxonomy(), 'Все бренды');
    }

    private static function render_category_parent_select(): void {
        echo '<select id="hws-category-parent">';
        echo '<option value="0">Все категории</option>';
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name', 'order' => 'ASC']);
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                printf('<option value="%d">%s</option>', (int) $term->term_id, esc_html($term->name));
            }
        }
        echo '</select>';
    }

    private static function render_category_child_select(): void {
        echo '<select id="hws-category-child" disabled>';
        echo '<option value="0">Все подкатегории</option>';
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                if ((int) $term->parent <= 0) {
                    continue;
                }
                printf('<option value="%d" data-parent="%d">%s</option>', (int) $term->term_id, (int) $term->parent, esc_html($term->name));
            }
        }
        echo '</select>';
    }

    private static function render_taxonomy_select(string $id, string $taxonomy, string $empty_label): void {
        echo '<select id="' . esc_attr($id) . '">';
        echo '<option value="0">' . esc_html($empty_label) . '</option>';
        if ($taxonomy !== '') {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    printf('<option value="%d">%s</option>', (int) $term->term_id, esc_html($term->name));
                }
            }
        }
        echo '</select>';
    }

    // Data helpers

    private static function category_product_query_args(bool $processed, int $posts_per_page = -1, array $filters = []): array {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => false,
            'meta_query' => [[
                'key' => self::CATEGORY_META_KEY,
                'compare' => $processed ? 'EXISTS' : 'NOT EXISTS',
            ]],
        ];

        $tax_query = [];
        $brand = (int) ($filters['brand'] ?? 0);
        $category = (int) ($filters['category'] ?? 0);
        $subcategory = (int) ($filters['subcategory'] ?? 0);
        $brand_taxonomy = self::brand_taxonomy();
        if ($brand > 0 && $brand_taxonomy !== '') {
            $tax_query[] = ['taxonomy' => $brand_taxonomy, 'field' => 'term_id', 'terms' => [$brand], 'include_children' => false];
        }
        if ($subcategory > 0) {
            $tax_query[] = ['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => [$subcategory], 'include_children' => false];
        } elseif ($category > 0) {
            $tax_query[] = ['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => [$category], 'include_children' => true];
        }
        if ($tax_query) {
            $args['tax_query'] = array_merge(['relation' => 'AND'], $tax_query);
        }
        return $args;
    }

    private static function category_filters_from_request(): array {
        $brand = isset($_POST['brand']) ? absint(wp_unslash($_POST['brand'])) : 0;
        $category = isset($_POST['category']) ? absint(wp_unslash($_POST['category'])) : 0;
        $subcategory = isset($_POST['subcategory']) ? absint(wp_unslash($_POST['subcategory'])) : 0;
        $brand_taxonomy = self::brand_taxonomy();
        if ($brand > 0 && ($brand_taxonomy === '' || !term_exists($brand, $brand_taxonomy))) {
            $brand = 0;
        }
        if ($category > 0) {
            $term = get_term($category, 'product_cat');
            if (!$term || is_wp_error($term) || (int) $term->parent !== 0) {
                $category = 0;
            }
        }
        if ($subcategory > 0) {
            $term = get_term($subcategory, 'product_cat');
            if (!$term || is_wp_error($term) || ($category > 0 && (int) $term->parent !== $category)) {
                $subcategory = 0;
            }
        }
        return ['brand' => $brand, 'category' => $category, 'subcategory' => $subcategory];
    }

    private static function category_blueprint(): array {
        return [
            ['path' => ['russian-bath-stoves'], 'label' => 'Печи для русской бани'],
            ['path' => ['russian-bath-stoves', 'electric'], 'label' => 'Электрические печи'],
            ['path' => ['russian-bath-stoves', 'wood'], 'label' => 'Дровяные печи'],
            ['path' => ['russian-bath-stoves', 'gas'], 'label' => 'Газовые печи'],
            ['path' => ['russian-bath-stoves', 'steam-thermal'], 'label' => 'Паротермальные печи'],
            ['path' => ['sauna-stoves'], 'label' => 'Печи для сауны'],
            ['path' => ['sauna-stoves', 'electric'], 'label' => 'Электрические печи для сауны'],
            ['path' => ['sauna-stoves', 'gas'], 'label' => 'Газовые печи для сауны'],
            ['path' => ['sauna-stoves', 'commercial'], 'label' => 'Коммерческие печи для сауны'],
            ['path' => ['steam-generators-and-hammam'], 'label' => 'Парогенераторы и хаммам'],
            ['path' => ['steam-generators-and-hammam', 'steam-generators'], 'label' => 'Парогенераторы'],
            ['path' => ['steam-generators-and-hammam', 'steam-room-equipment'], 'label' => 'Оборудование для паровых комнат'],
            ['path' => ['steam-generators-and-hammam', 'control-systems'], 'label' => 'Системы управления паром'],
            ['path' => ['commercial'], 'label' => 'Коммерческие решения'],
            ['path' => ['commercial', 'commercial-bath-stoves'], 'label' => 'Коммерческие печи для бани'],
            ['path' => ['commercial', 'commercial-sauna-heaters'], 'label' => 'Коммерческие нагреватели для сауны'],
            ['path' => ['commercial', 'steam-generators'], 'label' => 'Коммерческие парогенераторы'],
            ['path' => ['commercial', 'spa-systems'], 'label' => 'SPA-системы'],
            ['path' => ['control-units'], 'label' => 'Пульты и автоматика'],
            ['path' => ['control-units', 'for-sauna-heaters'], 'label' => 'Пульты для печей сауны'],
            ['path' => ['control-units', 'for-steam-generators'], 'label' => 'Пульты для парогенераторов'],
            ['path' => ['chimneys-and-installation'], 'label' => 'Дымоходы и монтаж'],
            ['path' => ['chimneys-and-installation', 'chimneys'], 'label' => 'Дымоходы'],
            ['path' => ['chimneys-and-installation', 'mounting-elements'], 'label' => 'Монтажные элементы'],
            ['path' => ['chimneys-and-installation', 'gas-burners'], 'label' => 'Газовые горелки'],
            ['path' => ['water-tanks-and-heat-exchangers'], 'label' => 'Баки и теплообменники'],
            ['path' => ['water-tanks-and-heat-exchangers', 'water-tanks'], 'label' => 'Баки для воды'],
            ['path' => ['water-tanks-and-heat-exchangers', 'heat-exchangers'], 'label' => 'Теплообменники'],
            ['path' => ['stones-and-cladding'], 'label' => 'Камни и облицовка'],
            ['path' => ['stones-and-cladding', 'heater-stones'], 'label' => 'Камни для печей'],
            ['path' => ['stones-and-cladding', 'cladding'], 'label' => 'Облицовка'],
            ['path' => ['stones-and-cladding', 'decor'], 'label' => 'Декор'],
            ['path' => ['accessories'], 'label' => 'Аксессуары'],
            ['path' => ['accessories', 'bathing-accessories'], 'label' => 'Банные аксессуары'],
            ['path' => ['accessories', 'pouring-devices'], 'label' => 'Обливные устройства'],
            ['path' => ['accessories', 'aroma-and-steam'], 'label' => 'Арома и пар'],
            ['path' => ['accessories', 'wood-storage'], 'label' => 'Хранение дров'],
        ];
    }

    private static function category_label(string $slug): string {
        foreach (self::category_blueprint() as $item) {
            $path = $item['path'];
            if ((string) end($path) === $slug) {
                return (string) $item['label'];
            }
        }
        return ucwords(str_replace('-', ' ', $slug));
    }

    private static function product_query_args(string $mode, string $meta_key = '', int $brand_id = 0, ?bool $processed = null, int $posts_per_page = -1): array {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => false,
        ];

        if ($meta_key !== '' && $processed !== null) {
            $args['meta_query'] = [[
                'key' => $meta_key,
                'compare' => $processed ? 'EXISTS' : 'NOT EXISTS',
            ]];
        }

        $taxonomy = self::brand_taxonomy();
        if ($mode === 'translate' && $brand_id > 0 && $taxonomy !== '') {
            $args['tax_query'] = [[
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [$brand_id],
            ]];
        }

        return $args;
    }

    private static function reset_meta_marks(string $mode, string $meta_key, int $brand_id): void {
        global $wpdb;

        if ($mode !== 'translate' || $brand_id <= 0 || self::brand_taxonomy() === '') {
            $wpdb->delete($wpdb->postmeta, ['meta_key' => $meta_key]);
            if ($mode === 'translate') {
                $wpdb->delete($wpdb->postmeta, ['meta_key' => self::TRANSLATE_REPORT_META_KEY]);
            }
            return;
        }

        $ids = (new WP_Query(self::product_query_args($mode, '', $brand_id, null)))->posts;
        foreach ($ids as $product_id) {
            delete_post_meta((int) $product_id, $meta_key);
            delete_post_meta((int) $product_id, self::TRANSLATE_REPORT_META_KEY);
        }
    }

    private static function brand_taxonomy(): string {
        foreach (['product_brand', 'pa_brand', 'pwb-brand', 'yith_product_brand', 'brand'] as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                return $taxonomy;
            }
        }

        foreach (get_object_taxonomies('product', 'objects') as $taxonomy => $object) {
            $label = (string) ($object->label ?? '');
            if (stripos($taxonomy, 'brand') !== false || stripos($label, 'brand') !== false || mb_stripos($label, 'бренд') !== false) {
                return (string) $taxonomy;
            }
        }

        return '';
    }

    private static function language_label(string $code): string {
        return self::LANGUAGE_OPTIONS[$code] ?? '';
    }

    private static function sanitize_language_code(mixed $value): string {
        $code = sanitize_text_field(wp_unslash((string) $value));
        return isset(self::LANGUAGE_OPTIONS[$code]) ? $code : 'en';
    }

    private static function payload_has_text(array $payload, array $fields): bool {
        foreach ($fields as $field) {
            if (trim(wp_strip_all_tags((string) ($payload[$field] ?? ''))) !== '') {
                return true;
            }
        }
        return false;
    }

    private static function extract_message_content(array $body): string {
        return (string) ($body['choices'][0]['message']['content'] ?? '');
    }

    private static function strip_code_fences(string $content): string {
        $content = trim($content);
        $content = preg_replace('/^```(?:json|html)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        return trim((string) $content);
    }

    private static function decode_json_content(string $content): array|\WP_Error {
        $content = self::strip_code_fences($content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($content, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return new \WP_Error('bad_json', 'Модель вернула невалидный JSON');
    }

    private static function requested_mode(): string {
        $mode = self::request_string('mode', 'format');
        return in_array($mode, ['format', 'translate', 'clean_excerpt'], true) ? $mode : 'format';
    }

    private static function meta_key_for_mode(string $mode): string {
        if ($mode === 'translate') {
            return self::TRANSLATE_META_KEY;
        }
        if ($mode === 'clean_excerpt') {
            return self::CLEAN_EXCERPT_META_KEY;
        }
        return self::FORMAT_META_KEY;
    }

    private static function request_string(string $key, string $default = ''): string {
        return sanitize_text_field(wp_unslash($_POST[$key] ?? $default));
    }

    private static function request_int(string $key, int $default = 0): int {
        return absint(wp_unslash($_POST[$key] ?? $default));
    }

    private static function append_activity_log(string $mode, string $level, string $message): void {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $mode = in_array($mode, ['format', 'translate', 'clean_excerpt', 'settings', 'system'], true) ? $mode : 'system';
        $level = in_array($level, ['ok', 'err', 'info'], true) ? $level : 'info';
        $logs = self::activity_logs();
        array_unshift($logs, [
            'time' => date_i18n('H:i:s d.m.Y'),
            'mode' => $mode,
            'level' => $level,
            'message' => mb_substr($message, 0, 500),
        ]);
        update_option(self::LOG_OPT_KEY, array_slice($logs, 0, 300), false);
    }

    private static function activity_logs(): array {
        $logs = get_option(self::LOG_OPT_KEY, []);
        return is_array($logs) ? $logs : [];
    }

    private static function assert_permissions(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Нет прав.');
        }
    }

    private static function settings(): array {
        $data = get_option(self::OPT_KEY, []);
        return is_array($data) ? $data : [];
    }
}

HWS_Desc_Formatter::init();
