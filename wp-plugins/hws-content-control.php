<?php
/**
 * Plugin Name: HWS Content Control
 * Description: Управление текстами и надписями на фронтенде.
 * Version: 1.3.0
 */

defined('ABSPATH') || exit;

final class HWS_Content_Control {

    private const OPTION = 'hws_site_texts';

    private const DEFAULTS = [
        // Карусель проектов на главной
        'home_cases_enabled'       => true,
        'home_cases_title'         => 'Реализованные проекты',
        'home_cases_slides'        => [
            [
                'id' => 1,
                'image' => 'https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__08.webp',
                'title' => 'Частная резиденция',
                'href' => '#',
                'location' => 'Ташкент, Узбекистан',
                'meta' => ['area' => '14 м²', 'type' => 'Русская баня', 'tech' => 'печь · Kastor'],
            ],
            [
                'id' => 2,
                'image' => 'https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__09.webp',
                'title' => 'SPA комплекс отеля',
                'href' => '#',
                'location' => 'Баку, Азербайджан',
                'meta' => ['area' => '120 м²', 'type' => 'SPA-зона', 'tech' => 'пар · EOS'],
            ],
            [
                'id' => 3,
                'image' => 'https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__14-1024x648.webp',
                'title' => 'Загородный дом',
                'href' => '#',
                'location' => 'Ташкентская область',
                'meta' => ['area' => '9 м²', 'type' => 'Финская сауна', 'tech' => 'печь · Harvia'],
            ],
            [
                'id' => 4,
                'image' => '/assets/herobg.png',
                'title' => 'Частный SPA',
                'href' => '#',
                'location' => 'Баку, Азербайджан',
                'meta' => ['area' => '26 м²', 'type' => 'Хаммам', 'tech' => 'пар · EasySteam'],
            ],
            [
                'id' => 5,
                'image' => 'https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__14.webp',
                'title' => 'Вилла у бассейна',
                'href' => '#',
                'location' => 'Самарканд, Узбекистан',
                'meta' => ['area' => '18 м²', 'type' => 'Сауна', 'tech' => 'печь · HUUM'],
            ],
        ],
        // Главная
        'home_categories_title'     => 'Решения для любых задач',
        'home_products_title'       => 'Подобранная коллекция',
        'home_blog_title'           => 'База знаний',
        'home_how_title'            => 'Как мы работаем',
        'home_how_1_number'         => 'Шаг 1.',
        'home_how_1_title'          => 'Консультация.',
        'home_how_1_description'    => 'Обсуждаем задачи и пожелания.',
        'home_how_2_number'         => 'Шаг 2.',
        'home_how_2_title'          => 'Расчёт.',
        'home_how_2_description'    => 'Подбираем оптимальное решение.',
        'home_how_3_number'         => 'Шаг 3.',
        'home_how_3_title'          => 'Подбор.',
        'home_how_3_description'    => 'Формируем спецификацию оборудования.',
        'home_how_4_number'         => 'Шаг 4.',
        'home_how_4_title'          => 'Поставка.',
        'home_how_4_description'    => 'Доставляем оборудование на объект.',
        'home_how_5_number'         => 'Шаг 5.',
        'home_how_5_title'          => 'Монтаж.',
        'home_how_5_description'    => 'Профессиональная установка.',
        'home_how_6_number'         => 'Шаг 6.',
        'home_how_6_title'          => 'Запуск.',
        'home_how_6_description'    => 'Проверка и запуск системы.',
        'home_how_7_number'         => 'Шаг 7.',
        'home_how_7_title'          => 'Обслуживание.',
        'home_how_7_description'    => 'Постгарантийное сопровождение: плановое ТО, замена расходников, консультации по эксплуатации.',
        // Каталог
        'catalog_overview_title'    => 'Каталог HWS',
        'catalog_overview_lead'     => 'Каталог организован по реальным сценариям выбора: сначала тип решения, затем подкатегория, и только после этого фильтры по мощности, объёму, серии и бренду.',
        'catalog_collections_title' => 'Популярные подборки по ключевым разделам',
        // Страница бренда
        'brand_categories_title'    => 'Ключевые разделы бренда',
        // База знаний
        'knowledge_page_title'      => 'База знаний',
        // Страница товара
        'product_description_title' => 'Описание товара',
    ];

    private const LABELS = [
        'home_categories_title'     => 'Заголовок раздела «Решения для задач»',
        'home_products_title'       => 'Заголовок раздела «Подобранная коллекция»',
        'home_blog_title'           => 'Заголовок раздела «База знаний»',
        'home_how_title'            => 'Заголовок блока «Как мы работаем»',
        'catalog_overview_title'    => 'H1 страницы каталога',
        'catalog_overview_lead'     => 'Подзаголовок страницы каталога',
        'catalog_collections_title' => 'Заголовок блока «Популярные подборки»',
        'brand_categories_title'    => 'Заголовок раздела категорий на странице бренда',
        'knowledge_page_title'      => 'Заголовок страницы базы знаний',
        'product_description_title' => 'Заголовок блока описания товара',
    ];

    private const SECTIONS = [
        'Главная страница'  => ['home_categories_title', 'home_products_title', 'home_blog_title', 'home_how_title'],
        'Как мы работаем'   => [
            'home_how_1_number', 'home_how_1_title', 'home_how_1_description',
            'home_how_2_number', 'home_how_2_title', 'home_how_2_description',
            'home_how_3_number', 'home_how_3_title', 'home_how_3_description',
            'home_how_4_number', 'home_how_4_title', 'home_how_4_description',
            'home_how_5_number', 'home_how_5_title', 'home_how_5_description',
            'home_how_6_number', 'home_how_6_title', 'home_how_6_description',
            'home_how_7_number', 'home_how_7_title', 'home_how_7_description',
        ],
        'Каталог'           => ['catalog_overview_title', 'catalog_overview_lead', 'catalog_collections_title'],
        'Страница бренда'   => ['brand_categories_title'],
        'База знаний'       => ['knowledge_page_title'],
        'Страница товара'   => ['product_description_title'],
    ];

    public static function init(): void {
        add_action('admin_menu',             [__CLASS__, 'admin_menu']);
        add_action('admin_post_hws_cc_save', [__CLASS__, 'handle_save']);
        add_action('graphql_register_types', [__CLASS__, 'register_graphql']);
    }

    public static function admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Тексты сайта',
            'Тексты сайта',
            'manage_woocommerce',
            'hws-content-control',
            [__CLASS__, 'render_page']
        );
    }

    public static function handle_save(): void {
        check_admin_referer('hws_cc_save');
        if (!current_user_can('manage_woocommerce')) wp_die('Forbidden', 403);

        $texts = get_option(self::OPTION, []);
        if (!is_array($texts)) $texts = [];

        foreach (array_keys(self::DEFAULTS) as $key) {
            if ($key === 'home_cases_enabled' || $key === 'home_cases_slides') continue;
            $val = sanitize_textarea_field(wp_unslash($_POST[$key] ?? ''));
            if ($val !== '') {
                $texts[$key] = $val;
            } else {
                unset($texts[$key]); // empty = revert to default
            }
        }

        $texts['home_cases_enabled'] = !empty($_POST['home_cases_enabled']);
        $cases_title = sanitize_text_field(wp_unslash($_POST['home_cases_title'] ?? ''));
        $texts['home_cases_title'] = $cases_title !== '' ? $cases_title : self::DEFAULTS['home_cases_title'];
        $texts['home_cases_slides'] = self::sanitize_slides($_POST['home_cases_slides'] ?? []);

        update_option(self::OPTION, $texts, false);
        if (function_exists('hws_revalidate')) hws_revalidate();

        wp_redirect(add_query_arg(['page' => 'hws-content-control', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public static function render_page(): void {
        $texts = get_option(self::OPTION, []);
        if (!is_array($texts)) $texts = [];

        $saved = !empty($_GET['saved']);
        ?>
        <div class="wrap" style="max-width:800px">
            <h1>Тексты сайта</h1>
            <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible"><p>✓ Сохранено и кэш обновлён</p></div>
            <?php endif; ?>
            <p style="color:#666;margin-bottom:0">Измените текст и нажмите «Сохранить». Очистите поле чтобы вернуть текст по умолчанию.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hws_cc_save'); ?>
                <input type="hidden" name="action" value="hws_cc_save">

                <?php foreach (self::SECTIONS as $section_title => $keys): ?>
                <h2 style="margin-top:28px;border-bottom:1px solid #ddd;padding-bottom:6px"><?php echo esc_html($section_title); ?></h2>
                <table class="form-table" style="margin-top:8px">
                    <?php foreach ($keys as $key):
                        $default = self::DEFAULTS[$key];
                        $current = $texts[$key] ?? $default;
                        $label   = self::LABELS[$key] ?? $key;
                        if (preg_match('/^home_how_(\d+)_(number|title|description)$/', $key, $matches)) {
                            $step_labels = [
                                'number' => 'Номер шага',
                                'title' => 'Название шага',
                                'description' => 'Описание шага',
                            ];
                            $label = 'Шаг ' . $matches[1] . ' — ' . $step_labels[$matches[2]];
                        }
                        $is_long = str_contains($key, 'lead') || str_contains($key, 'description');
                    ?>
                    <tr>
                        <th style="width:30%;vertical-align:top;padding-top:14px">
                            <label for="<?php echo esc_attr($key); ?>">
                                <strong><?php echo esc_html($label); ?></strong>
                            </label>
                        </th>
                        <td>
                            <?php if ($is_long): ?>
                            <textarea
                                id="<?php echo esc_attr($key); ?>"
                                name="<?php echo esc_attr($key); ?>"
                                rows="4"
                                class="large-text"
                            ><?php echo esc_textarea($current); ?></textarea>
                            <?php else: ?>
                            <input
                                type="text"
                                id="<?php echo esc_attr($key); ?>"
                                name="<?php echo esc_attr($key); ?>"
                                class="large-text"
                                value="<?php echo esc_attr($current); ?>"
                            >
                            <?php endif; ?>
                            <?php if (isset($texts[$key]) && $texts[$key] !== $default): ?>
                            <p class="description" style="margin-top:4px;color:#999">
                                По умолчанию: «<?php echo esc_html($default); ?>»
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endforeach; ?>

                <?php
                $cases_enabled = array_key_exists('home_cases_enabled', $texts)
                    ? (bool) $texts['home_cases_enabled']
                    : self::DEFAULTS['home_cases_enabled'];
                $cases_title = $texts['home_cases_title'] ?? self::DEFAULTS['home_cases_title'];
                $cases_slides = self::get_cases_slides($texts);
                ?>
                <h2 style="margin-top:28px;border-bottom:1px solid #ddd;padding-bottom:6px">Карусель проектов на главной</h2>
                <table class="form-table" style="margin-top:8px">
                    <tr>
                        <th style="width:30%;vertical-align:top;padding-top:14px"><label for="home_cases_title"><strong>Заголовок блока</strong></label></th>
                        <td><input type="text" id="home_cases_title" name="home_cases_title" class="large-text" value="<?php echo esc_attr($cases_title); ?>"></td>
                    </tr>
                    <tr>
                        <th style="vertical-align:top;padding-top:14px"><strong>Видимость блока</strong></th>
                        <td><label><input type="checkbox" name="home_cases_enabled" value="1" <?php checked($cases_enabled); ?>> Показывать карусель на главной</label></td>
                    </tr>
                </table>
                <div id="hws-cases-slides">
                    <?php foreach ($cases_slides as $index => $slide): ?>
                    <?php self::render_slide($index, $slide); ?>
                    <?php endforeach; ?>
                </div>
                <p><button type="button" class="button" id="hws-add-case-slide">Добавить слайд</button></p>

                <div style="margin-top:24px"><?php submit_button('Сохранить', 'primary', 'submit', false); ?></div>
            </form>
        </div>
        <script>
        (() => {
            const list = document.getElementById('hws-cases-slides');
            const add = document.getElementById('hws-add-case-slide');
            if (!list || !add) return;
            let index = list.querySelectorAll('.hws-case-slide').length;
            add.addEventListener('click', () => {
                const wrapper = document.createElement('div');
                wrapper.className = 'hws-case-slide';
                wrapper.style = 'border:1px solid #ccd0d4;padding:16px;margin:16px 0;background:#fff;max-width:800px';
                const template = <?php echo wp_json_encode(self::slide_template()); ?>;
                wrapper.innerHTML = template.replaceAll('__INDEX__', index++);
                list.appendChild(wrapper);
            });
            list.addEventListener('click', (event) => {
                if (event.target instanceof HTMLElement && event.target.matches('.hws-remove-case-slide')) {
                    event.target.closest('.hws-case-slide')?.remove();
                }
            });
        })();
        </script>
        <?php
    }

    private static function render_slide(int $index, array $slide): void {
        $slide = self::normalize_slide($slide, $index + 1);
        echo '<div class="hws-case-slide" style="border:1px solid #ccd0d4;padding:16px;margin:16px 0;background:#fff;max-width:800px">';
        echo '<h3 style="margin-top:0">Слайд ' . esc_html($index + 1) . '</h3>';
        echo self::slide_template($index, $slide);
        echo '</div>';
    }

    private static function slide_template(?int $index = null, array $slide = []): string {
        $index_value = $index === null ? '__INDEX__' : (string) $index;
        $slide = self::normalize_slide($slide, 0);
        $fields = [
            'image' => 'Изображение (URL)',
            'title' => 'Заголовок',
            'href' => 'Ссылка',
            'location' => 'Локация',
            'area' => 'Площадь',
            'type' => 'Тип',
            'tech' => 'Технология',
        ];
        $html = '<div class="hws-case-fields">';
        foreach ($fields as $field => $label) {
            $value = $slide[$field] ?? ($slide['meta'][$field] ?? '');
            $name = 'home_cases_slides[' . $index_value . '][' . $field . ']';
            $html .= '<p><label><strong>' . esc_html($label) . '</strong><br><input type="text" class="large-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></label></p>';
        }
        $html .= '<button type="button" class="button hws-remove-case-slide">Удалить слайд</button></div>';
        return $html;
    }

    private static function normalize_slide(array $slide, int $fallback_id): array {
        return [
            'id' => (int) ($slide['id'] ?? $fallback_id),
            'image' => (string) ($slide['image'] ?? ''),
            'title' => (string) ($slide['title'] ?? ''),
            'href' => (string) ($slide['href'] ?? '#'),
            'location' => (string) ($slide['location'] ?? ''),
            'meta' => [
                'area' => (string) ($slide['meta']['area'] ?? $slide['area'] ?? ''),
                'type' => (string) ($slide['meta']['type'] ?? $slide['type'] ?? ''),
                'tech' => (string) ($slide['meta']['tech'] ?? $slide['tech'] ?? ''),
            ],
        ];
    }

    private static function sanitize_slides($slides): array {
        if (!is_array($slides)) return [];
        $result = [];
        foreach (array_values($slides) as $index => $slide) {
            if (!is_array($slide)) continue;
            $normalized = self::normalize_slide($slide, $index + 1);
            $result[] = [
                'id' => $normalized['id'] > 0 ? $normalized['id'] : $index + 1,
                'image' => esc_url_raw($normalized['image']),
                'title' => sanitize_text_field($normalized['title']),
                'href' => esc_url_raw($normalized['href']) ?: '#',
                'location' => sanitize_text_field($normalized['location']),
                'meta' => [
                    'area' => sanitize_text_field($normalized['meta']['area']),
                    'type' => sanitize_text_field($normalized['meta']['type']),
                    'tech' => sanitize_text_field($normalized['meta']['tech']),
                ],
            ];
        }
        return $result;
    }

    private static function get_cases_slides(array $texts): array {
        $slides = $texts['home_cases_slides'] ?? self::DEFAULTS['home_cases_slides'];
        return is_array($slides) ? array_map(static fn ($slide, $index) => self::normalize_slide((array) $slide, $index + 1), $slides, array_keys($slides)) : [];
    }

    public static function get_texts(): array {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) $saved = [];
        return array_merge(self::DEFAULTS, $saved);
    }

    public static function register_graphql(): void {
        register_graphql_object_type('HwsHowStep', [
            'description' => 'Шаг блока «Как мы работаем»',
            'fields' => [
                'number' => ['type' => 'String'],
                'title' => ['type' => 'String'],
                'description' => ['type' => 'String'],
            ],
        ]);

        register_graphql_object_type('HwsCaseMeta', [
            'fields' => [
                'area' => ['type' => 'String'],
                'type' => ['type' => 'String'],
                'tech' => ['type' => 'String'],
            ],
        ]);
        register_graphql_object_type('HwsCaseSlide', [
            'fields' => [
                'id' => ['type' => 'Int'],
                'image' => ['type' => 'String'],
                'title' => ['type' => 'String'],
                'href' => ['type' => 'String'],
                'location' => ['type' => 'String'],
                'meta' => ['type' => 'HwsCaseMeta'],
            ],
        ]);

        register_graphql_object_type('HwsSiteTexts', [
            'description' => 'Редактируемые тексты фронтенда',
            'fields'      => [
                'catalogCollectionsTitle' => ['type' => 'String'],
                'catalogOverviewTitle'    => ['type' => 'String'],
                'catalogOverviewLead'     => ['type' => 'String'],
                'homeCategoriesTitle'     => ['type' => 'String'],
                'homeProductsTitle'       => ['type' => 'String'],
                'homeBlogTitle'           => ['type' => 'String'],
                'homeHowTitle'            => ['type' => 'String'],
                'homeHowSteps'            => ['type' => ['list_of' => 'HwsHowStep']],
                'brandCategoriesTitle'    => ['type' => 'String'],
                'knowledgePageTitle'      => ['type' => 'String'],
                'productDescriptionTitle' => ['type' => 'String'],
                'homeCasesEnabled'       => ['type' => 'Boolean'],
                'homeCasesTitle'         => ['type' => 'String'],
                'homeCasesSlides'        => ['type' => ['list_of' => 'HwsCaseSlide']],
            ],
        ]);

        register_graphql_field('RootQuery', 'hwsSiteTexts', [
            'type'        => 'HwsSiteTexts',
            'description' => 'Редактируемые тексты фронтенда (управляются из WP Admin → Тексты сайта)',
            'resolve'     => function () {
                $t = self::get_texts();
                return [
                    'catalogCollectionsTitle' => $t['catalog_collections_title'],
                    'catalogOverviewTitle'    => $t['catalog_overview_title'],
                    'catalogOverviewLead'     => $t['catalog_overview_lead'],
                    'homeCategoriesTitle'     => $t['home_categories_title'],
                    'homeProductsTitle'       => $t['home_products_title'],
                    'homeBlogTitle'           => $t['home_blog_title'],
                    'homeHowTitle'            => $t['home_how_title'],
                    'homeHowSteps'            => array_map(static function (int $i) use ($t): array {
                        return [
                            'number' => $t['home_how_' . $i . '_number'],
                            'title' => $t['home_how_' . $i . '_title'],
                            'description' => $t['home_how_' . $i . '_description'],
                        ];
                    }, range(1, 7)),
                    'brandCategoriesTitle'    => $t['brand_categories_title'],
                    'knowledgePageTitle'      => $t['knowledge_page_title'],
                    'productDescriptionTitle' => $t['product_description_title'],
                    'homeCasesEnabled' => (bool) ($t['home_cases_enabled'] ?? true),
                    'homeCasesTitle' => $t['home_cases_title'] ?? self::DEFAULTS['home_cases_title'],
                    'homeCasesSlides' => self::get_cases_slides($t),
                ];
            },
        ]);
    }
}

HWS_Content_Control::init();
