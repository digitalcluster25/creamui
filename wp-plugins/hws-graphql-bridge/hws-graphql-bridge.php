<?php
/**
 * Plugin Name: HWS GraphQL Bridge
 * Description: Делает данные товаров HWS доступными во WPGraphQL: бренд (product_brand) и
 *               структурированные характеристики (_hws_specs_html -> hwsSpecs). Не трогает
 *               woographql/WooCommerce core — только filter/register хуки.
 * Version: 0.1.0
 * Author: HWS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/frontend-revalidate.php';

/**
 * 1) Включаем таксономию product_brand в GraphQL-схему.
 *    WooGraphQL делает это для product_cat/product_tag/атрибутов, но не для product_brand
 *    (см. woographql/includes/class-core-schema-filters.php::register_taxonomy_args) —
 *    добавляем по тому же паттерну, без правки чужого кода.
 */
add_filter(
	'register_taxonomy_args',
	function ( $args, $taxonomy ) {
		if ( 'product_brand' === $taxonomy ) {
			$args['show_in_graphql']     = true;
			$args['graphql_single_name'] = 'productBrand';
			$args['graphql_plural_name'] = 'productBrands';
		}
		return $args;
	},
	10,
	2
);

/**
 * Достаёт ID товара из модели WPGraphQL (Model\Product), общая логика для всех резолверов ниже.
 *
 * @param mixed $source
 * @return int|null
 */
function hws_graphql_bridge_get_product_id( $source ): ?int {
	if ( isset( $source->wc_data ) && is_object( $source->wc_data ) && method_exists( $source->wc_data, 'get_id' ) ) {
		return (int) $source->wc_data->get_id();
	}
	if ( isset( $source->ID ) ) {
		return (int) $source->ID;
	}
	return null;
}

function hws_graphql_bridge_translit_cyr( string $value ): string {
	$map = [
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
		'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	];
	$value = mb_strtolower( $value, 'UTF-8' );
	return strtr( $value, $map );
}

function hws_graphql_bridge_slugify( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = hws_graphql_bridge_translit_cyr( $value );
	return sanitize_title( $value );
}

function hws_graphql_bridge_get_attachment_best_image_url( int $attachment_id, array $sizes ): ?string {
	$base_url = wp_get_attachment_url( $attachment_id );
	$meta     = wp_get_attachment_metadata( $attachment_id );

	if ( empty( $meta['file'] ) || empty( $base_url ) ) {
		return $base_url ?: null;
	}

	$uploads = wp_get_upload_dir();
	$basedir = trailingslashit( dirname( $meta['file'] ) );

	foreach ( $sizes as $size ) {
		$file = $meta['sizes'][ $size ]['file'] ?? null;
		if ( $file ) {
			return trailingslashit( $uploads['baseurl'] ) . $basedir . $file;
		}
	}

	return $base_url ?: null;
}

/**
 * 2) Поле hwsSpecs на интерфейсе Product — разбирает _hws_specs_html
 *    в массив {label, value}. Подтверждено на выборке товаров всех 3 брендов
 *    (ВВД/EasySteam/Sangens), что meta-ключ присутствует универсально.
 */
add_action(
	'graphql_register_types',
	function () {

		register_graphql_object_type(
			'HwsSpecRow',
			[
				'description' => __( 'Строка характеристики товара (название/значение), разобранная из _hws_specs_html', 'hws-graphql-bridge' ),
				'fields'      => [
					'label' => [ 'type' => 'String' ],
					'value' => [ 'type' => 'String' ],
				],
			]
		);

		register_graphql_field(
			'Product',
			'hwsSpecs',
			[
				'type'        => [ 'list_of' => 'HwsSpecRow' ],
				'description' => __( 'Характеристики товара (распарсены из _hws_specs_html)', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );

					if ( empty( $product_id ) ) {
						return [];
					}

					$html = get_post_meta( $product_id, '_hws_specs_html', true );
					if ( empty( $html ) ) {
						return [];
					}

					return hws_graphql_bridge_parse_specs_html( $html );
				},
			]
		);

		register_graphql_field(
			'Product',
			'hwsPriceOnRequest',
			[
				'type'        => 'Boolean',
				'description' => __( 'Товар публикуется без публичной цены и должен показываться как "Цена по запросу"', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return false;
					}

					return 'yes' === get_post_meta( $product_id, '_hws_price_on_request', true );
				},
			]
		);

		register_graphql_field(
			'Product',
			'hwsPriceCurrency',
			[
				'type'        => 'String',
				'description' => __( 'Код базовой валюты хранения цены товара', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return null;
					}

					$currency = get_post_meta( $product_id, '_hws_price_currency', true );
					if ( ! $currency ) {
						$currency = get_woocommerce_currency();
					}

					return $currency ?: null;
				},
			]
		);

		register_graphql_field(
			'Product',
			'hwsSourceBrand',
			[
				'type'        => 'String',
				'description' => __( 'Резервное название бренда из source meta, если taxonomy product_brand ещё не привязана', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return null;
					}

					$value = get_post_meta( $product_id, '_hws_source_brand', true );
					return $value ?: null;
				},
			]
		);

		register_graphql_field(
			'Product',
			'hwsSourceBaseArticle',
			[
				'type'        => 'String',
				'description' => __( 'Резервный базовый артикул товара из source meta, если SKU у parent-поста не заполнен', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return null;
					}

					$value = get_post_meta( $product_id, '_hws_source_base_article', true );
					return $value ?: null;
				},
			]
		);

		register_graphql_field(
			'Product',
			'hwsSourceImageUrl',
			[
				'type'        => 'String',
				'description' => __( 'Резервный URL исходного изображения товара, если attachment ещё не привязан', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return null;
					}

					$url = get_post_meta( $product_id, '_hws_source_base_image', true );
					return $url ?: null;
				},
			]
		);

		register_graphql_field(
			'ProductVariation',
			'hwsSourceImageUrl',
			[
				'type'        => 'String',
				'description' => __( 'Резервный URL исходного изображения вариации, если attachment ещё не привязан', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$variation_id = $source->databaseId ?? $source->ID ?? null;
					if ( empty( $variation_id ) ) {
						return null;
					}

					$url = get_post_meta( (int) $variation_id, '_hws_source_image', true );
					return $url ?: null;
				},
			]
		);

		register_graphql_field(
			'ProductVariation',
			'hwsSourceOptionsJson',
			[
				'type'        => 'String',
				'description' => __( 'JSON исходных опций производителя для вариации', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$variation_id = $source->databaseId ?? $source->ID ?? null;
					if ( empty( $variation_id ) ) {
						return null;
					}

					$value = get_post_meta( (int) $variation_id, '_hws_source_options', true );
					return $value ?: null;
				},
			]
		);

		/**
		 * 3) Поле hwsCommerceInfo на интерфейсе Product — условия доставки/оплаты/гарантии
		 *    по бренду товара. Источник данных — плагин hws-commerce-info (его публичный
		 *    геттер get_settings_for_brand), сам он раньше рендерился только в PHP-шаблон
		 *    WooCommerce и был недостижим для headless-фронта.
		 */
		register_graphql_object_type(
			'HwsCommerceInfo',
			[
				'description' => __( 'Условия доставки/оплаты/гарантии для бренда товара (заполняются в WooCommerce → Оплата и доставка)', 'hws-graphql-bridge' ),
				'fields'      => [
					'deliveryTitle' => [ 'type' => 'String' ],
					'deliveryText'  => [ 'type' => 'String' ],
					'paymentTitle'  => [ 'type' => 'String' ],
					'paymentText'   => [ 'type' => 'String' ],
					'warrantyTitle' => [ 'type' => 'String' ],
					'warrantyText'  => [ 'type' => 'String' ],
					'note'          => [ 'type' => 'String' ],
				],
			]
		);

		register_graphql_field(
			'Product',
			'hwsCommerceInfo',
			[
				'type'        => 'HwsCommerceInfo',
				'description' => __( 'Условия доставки/оплаты/гарантии для бренда товара', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					if ( ! class_exists( 'HWS_Commerce_Info' ) ) {
						return null;
					}

					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) || ! taxonomy_exists( 'product_brand' ) ) {
						return null;
					}

					$brands = wp_get_post_terms( $product_id, 'product_brand' );
					$brand  = ( is_array( $brands ) && ! is_wp_error( $brands ) && ! empty( $brands[0] ) ) ? $brands[0] : null;
					if ( ! $brand ) {
						$source_brand = trim( (string) get_post_meta( $product_id, '_hws_source_brand', true ) );
						if ( '' !== $source_brand ) {
							$brand = get_term_by( 'slug', sanitize_title( $source_brand ), 'product_brand' );
							if ( ! $brand || is_wp_error( $brand ) ) {
								$brand = get_term_by( 'name', $source_brand, 'product_brand' );
							}
						}
					}
					if ( ! $brand || is_wp_error( $brand ) ) {
						return null;
					}

					$row = HWS_Commerce_Info::get_settings_for_brand( $brand->term_id );
					if ( empty( $row['enabled'] ) ) {
						return null;
					}

					return [
						'deliveryTitle' => $row['delivery_title'],
						'deliveryText'  => $row['delivery_text'],
						'paymentTitle'  => $row['payment_title'],
						'paymentText'   => $row['payment_text'],
						'warrantyTitle' => $row['warranty_title'],
						'warrantyText'  => $row['warranty_text'],
						'note'          => $row['note'],
					];
				},
			]
		);

		/**
		 * 4) Поле logoUrl на типе productBrand — читает встроенную миниатюру термина
		 *    (WooCommerce core уже даёт UI для неё на edit-tags.php?taxonomy=product_brand,
		 *    тот же механизм и term meta key "thumbnail_id", что и у категорий товаров —
		 *    см. WC_Admin_Brands::edit_thumbnail_field в woocommerce/includes/admin/class-wc-admin-brands.php).
		 *    Ничего нового в админке не добавляли — просто прокидываем уже существующее поле в GraphQL.
		 */
		register_graphql_field(
			'productBrand',
			'logoUrl',
			[
				'type'        => 'String',
				'description' => __( 'URL логотипа бренда (миниатюра термина product_brand)', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$term_id = $source->term_id ?? null;
					if ( empty( $term_id ) ) {
						return null;
					}

					$thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );
					if ( empty( $thumbnail_id ) ) {
						return null;
					}

					$url = wp_get_attachment_url( $thumbnail_id );
					return $url ?: null;
				},
			]
		);

		register_graphql_field(
			'MediaItem',
			'hwsOptimizedUrl',
			[
				'type'        => 'String',
				'description' => __( 'Оптимизированный storefront URL изображения без original full-size файла', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$attachment_id = $source->databaseId ?? $source->ID ?? null;
					if ( empty( $attachment_id ) ) {
						return null;
					}

					return hws_graphql_bridge_get_attachment_best_image_url(
						(int) $attachment_id,
						[ 'medium_large', 'woocommerce_single', 'large', 'medium', 'thumbnail' ]
					);
				},
			]
		);
		/**
		 * 4.1) Поле hwsSubtitle на типе productCategory — отдаёт описание
		 *      категории товаров из стандартного поля Description в админке.
		 *      WooGraphQL в текущей конфигурации description для productCategories
		 *      не прокидывает, поэтому headless-фронт читает его через явное поле.
		 */
		register_graphql_field(
			'productCategory',
			'hwsSubtitle',
			[
				'type'        => 'String',
				'description' => __( 'Подзаголовок категории для карточек и баннеров (берётся из стандартного Description в админке WooCommerce)', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$term_id = $source->term_id ?? null;
					if ( empty( $term_id ) ) {
						return null;
					}

					$description = term_description( (int) $term_id, 'product_cat' );
					if ( ! is_string( $description ) ) {
						return null;
					}

					$description = trim( wp_strip_all_tags( $description ) );
					return '' !== $description ? $description : null;
				},
			]
		);
		register_graphql_field(
			'productCategory',
			'hwsImageUrl',
			[
				'type'        => 'String',
				'description' => __( 'URL оптимизированной обложки категории товаров из стандартного WooCommerce term thumbnail', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$term_id = $source->term_id ?? null;
					if ( empty( $term_id ) ) {
						return null;
					}

					$thumbnail_id = get_term_meta( (int) $term_id, 'thumbnail_id', true );
					if ( empty( $thumbnail_id ) ) {
						return null;
					}

					$thumbnail_id = (int) $thumbnail_id;
					$meta         = wp_get_attachment_metadata( $thumbnail_id );
					$base_url     = wp_get_attachment_url( $thumbnail_id );

					if ( empty( $meta['file'] ) || empty( $base_url ) ) {
						return $base_url ?: null;
					}

					$uploads = wp_get_upload_dir();
					$basedir = trailingslashit( dirname( $meta['file'] ) );
					$sizes   = [ 'medium_large', 'woocommerce_single', 'large', 'medium', 'thumbnail' ];

					foreach ( $sizes as $size ) {
						$file = $meta['sizes'][ $size ]['file'] ?? null;
						if ( $file ) {
							return trailingslashit( $uploads['baseurl'] ) . $basedir . $file;
						}
					}

					return $base_url ?: null;
				},
			]
		);
		/**
		 * 5) Поле hwsVariantGroups на интерфейсе Product — разбирает кастомный
		 *    JSON _hws_source_payload.option_groups у variable-товаров
		 *    в аддитивную модель {key, label, options:[{value, slug, priceModifier}]}.
		 *    delta_price в исходных данных — в рублях, конвертируем в USD через
		 *    _hws_usd_rub_rate (формула подтверждена эмпирически: 165000/71.209≈2317≈
		 *    реальная минимальная цена товара 2320$ в WooCommerce).
		 *    Дефолтная опция (is_default) ставится первой в массиве — компонент
		 *    ProductPage.tsx жёстко берёт options[0] как baseline, не смотрит на флаг.
		 */
		register_graphql_object_type(
			'HwsVariantOption',
			[
				'description' => __( 'Опция группы вариаций с надбавкой к цене в валюте каталога', 'hws-graphql-bridge' ),
				'fields'      => [
					'value'         => [ 'type' => 'String' ],
					'slug'          => [ 'type' => 'String' ],
					'priceModifier' => [ 'type' => 'Float' ],
				],
			]
		);

		register_graphql_object_type(
			'HwsVariantGroup',
			[
				'description' => __( 'Группа вариаций товара (например "Варианты кожуха")', 'hws-graphql-bridge' ),
				'fields'      => [
					'key'     => [ 'type' => 'String' ],
					'label'   => [ 'type' => 'String' ],
					'options' => [ 'type' => [ 'list_of' => 'HwsVariantOption' ] ],
				],
			]
		);

		register_graphql_field(
			'Product',
			'hwsVariantGroups',
			[
				'type'        => [ 'list_of' => 'HwsVariantGroup' ],
				'description' => __( 'Группы вариаций с аддитивными надбавками к цене (распарсены из _hws_source_payload.option_groups)', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return [];
					}

					$raw = get_post_meta( $product_id, '_hws_source_payload', true );
					$payload = json_decode( $raw, true );
					if ( is_array( $payload ) && ! empty( $payload['option_groups'] ) && is_array( $payload['option_groups'] ) ) {
						return hws_graphql_bridge_map_variant_groups( $payload['option_groups'], 1.0 );
					}

					return hws_graphql_bridge_get_wc_variant_groups( $product_id );
				},
			]
		);
		/**
		 * 6) hwsContactChannels — глобальное (не по бренду) GraphQL-поле с публичными
		 *    контактами для кнопок мессенджеров. Источник — плагин hws-contact-channels.
		 *    Зарегистрировано как RootQuery field, не на Product — это не привязано к товару.
		 */
		register_graphql_object_type(
			'HwsContactChannels',
			[
				'description' => __( 'Публичные контакты для кнопок мессенджеров (WhatsApp/Telegram)', 'hws-graphql-bridge' ),
				'fields'      => [
					'whatsappNumber'   => [ 'type' => 'String' ],
					'telegramUsername' => [ 'type' => 'String' ],
				],
			]
		);

		register_graphql_field(
			'RootQuery',
			'hwsContactChannels',
			[
				'type'        => 'HwsContactChannels',
				'description' => __( 'Публичные контакты для кнопок мессенджеров', 'hws-graphql-bridge' ),
				'resolve'     => function () {
					if ( ! class_exists( 'HWS_Contact_Channels' ) ) {
						return null;
					}
					$row = HWS_Contact_Channels::get_settings();
					return [
						'whatsappNumber'   => $row['whatsapp_number'],
						'telegramUsername' => $row['telegram_username'],
					];
				},
			]
		);

		register_graphql_field(
			'RootQuery',
			'hwsProductRedirect',
			[
				'type'        => 'String',
				'description' => __( 'Целевой slug для 301 со старой карточки товара', 'hws-graphql-bridge' ),
				'args'        => [
					'slug' => [ 'type' => 'String' ],
				],
				'resolve'     => function ( $root, $args ) {
					$slug      = sanitize_title( (string) ( $args['slug'] ?? '' ) );
					$redirects = get_option( 'hws_product_redirects', [] );
					$target    = is_array( $redirects ) ? ( $redirects[ $slug ] ?? null ) : null;

					return is_string( $target ) && '' !== sanitize_title( $target ) ? sanitize_title( $target ) : null;
				},
			]
		);
		/**
		 * 7) hwsFacingOptions — свотчи облицовки/материала. На оригинале (sangens.com)
		 *    это НЕ JS-вариации внутри одной карточки, а навигация между РАЗНЫМИ
		 *    товарами одной модели в разной облицовке (Glass Black/White, Brick, Stone —
		 *    каждый отдельный product post). Воспроизводим тот же паттерн: WooCommerce
		 *    cross-sell (_crosssell_ids, родное поле, не наша придумка) связывает товары,
		 *    plus meta _hws_facing_icon_id/_hws_facing_label на каждом для иконки и подписи.
		 */
		register_graphql_object_type(
			'HwsFacingOption',
			[
				'description' => __( 'Вариант облицовки/материала — ссылка на соответствующий товар той же модели', 'hws-graphql-bridge' ),
				'fields'      => [
					'label'    => [ 'type' => 'String' ],
					'iconUrl'  => [ 'type' => 'String' ],
					'slug'     => [ 'type' => 'String' ],
					'isActive' => [ 'type' => 'Boolean' ],
				],
			]
		);

		register_graphql_field(
			'Product',
			'hwsFacingOptions',
			[
				'type'        => [ 'list_of' => 'HwsFacingOption' ],
				'description' => __( 'Варианты облицовки/материала (свотчи), включая сам текущий товар', 'hws-graphql-bridge' ),
				'resolve'     => function ( $source ) {
					$product_id = hws_graphql_bridge_get_product_id( $source );
					if ( empty( $product_id ) ) {
						return [];
					}

					$cross_sell_ids = get_post_meta( $product_id, '_crosssell_ids', true );
					if ( ! is_array( $cross_sell_ids ) ) {
						$cross_sell_ids = [];
					}

					$all_ids = array_unique( array_merge( [ $product_id ], $cross_sell_ids ) );
					$options = [];

					foreach ( $all_ids as $id ) {
						$icon_id = get_post_meta( $id, '_hws_facing_icon_id', true );
						$label   = get_post_meta( $id, '_hws_facing_label', true );

						if ( empty( $icon_id ) || empty( $label ) ) {
							continue; // товар без своей иконки/лейбла — не настоящий вариант облицовки.
						}

						$post = get_post( $id );
						if ( ! $post ) {
							continue;
						}

						$options[] = [
							'label'    => $label,
							'iconUrl'  => wp_get_attachment_url( $icon_id ) ?: null,
							'slug'     => $post->post_name,
							'isActive' => (int) $id === (int) $product_id,
						];
					}

					return $options;
				},
			]
		);
	}
);

/**
 * @param array<int, array{id?: mixed, name?: string, values?: array<int, array{name?: string, delta_price?: float, is_default?: bool, sort_order?: int}>}> $groups
 * @param float $rate unused legacy argument
 * @return array<int, array{key: string, label: string, options: array<int, array{value: string, slug: string, priceModifier: float}>}>
 */
function hws_graphql_bridge_map_variant_groups( array $groups, float $rate ): array {
	unset( $rate );
	$result = [];

	foreach ( $groups as $group ) {
		if ( empty( $group['name'] ) || empty( $group['values'] ) || ! is_array( $group['values'] ) ) {
			continue;
		}

		$values = $group['values'];

		// is_default первой, затем по sort_order — компонент берёт options[0] как baseline.
		usort(
			$values,
			function ( $a, $b ) {
				$a_default = ! empty( $a['is_default'] );
				$b_default = ! empty( $b['is_default'] );
				if ( $a_default !== $b_default ) {
					return $a_default ? -1 : 1;
				}
				return ( $a['sort_order'] ?? 0 ) <=> ( $b['sort_order'] ?? 0 );
			}
		);

		$options = [];
		foreach ( $values as $value ) {
			if ( empty( $value['name'] ) ) {
				continue;
			}
			$delta_rub      = (float) ( $value['delta_price'] ?? 0 );
			$options[]      = [
				'value'         => $value['name'],
				'slug'          => hws_graphql_bridge_slugify( (string) $value['name'] ),
				'priceModifier' => $delta_rub,
			];
		}

		if ( empty( $options ) ) {
			continue;
		}

		$result[] = [
			'key'     => ! empty( $group['id'] ) ? (string) $group['id'] : hws_graphql_bridge_slugify( (string) $group['name'] ),
			'label'   => $group['name'],
			'options' => $options,
		];
	}

	return $result;
}

/**
 * Возвращает группы из нативных атрибутов WooCommerce variable product.
 * Это fallback для товаров, созданных в WooCommerce, а не импортированных
 * из _hws_source_payload.
 *
 * @return array<int, array{key: string, label: string, options: array<int, array{value: string, slug: string, priceModifier: float}>}>
 */
function hws_graphql_bridge_get_wc_variant_groups( int $product_id ): array {
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return [];
	}

	$result = [];
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_variation() ) {
			continue;
		}

		$name   = (string) $attribute->get_name();
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product_id, $name, [ 'fields' => 'names' ] )
			: $attribute->get_options();
		$values = array_values( array_filter( array_map( 'strval', $values ) ) );
		if ( empty( $values ) ) {
			continue;
		}

		$options = [];
		foreach ( $values as $value ) {
			$options[] = [
				'value'         => $value,
				'slug'          => hws_graphql_bridge_slugify( $value ),
				'priceModifier' => 0,
			];
		}

		$result[] = [
			'key'     => preg_replace( '/^pa_/', '', $name ),
			'label'   => wc_attribute_label( $name, $product ),
			'options' => $options,
		];
	}

	return $result;
}

/**
 * Разбирает HTML-таблицу вида
 * <table><tbody><tr><th>Label</th><td>Value</td></tr>...</tbody></table>
 * в массив ['label' => ..., 'value' => ...].
 *
 * @param string $html
 * @return array<int, array{label: string, value: string}>
 */
function hws_graphql_bridge_parse_specs_html( string $html ): array {
	$rows = [];

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();

	foreach ( $dom->getElementsByTagName( 'tr' ) as $tr ) {
		$th = $tr->getElementsByTagName( 'th' );
		$td = $tr->getElementsByTagName( 'td' );

		if ( $th->length > 0 && $td->length > 0 ) {
			$label = trim( $th->item( 0 )->textContent );
			$value = trim( $td->item( 0 )->textContent );

			if ( '' !== $label && '' !== $value ) {
				$rows[] = [
					'label' => $label,
					'value' => $value,
				];
			}
		}
	}

	return $rows;
}
