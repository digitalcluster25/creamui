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
					if ( ! is_array( $brands ) || is_wp_error( $brands ) || empty( $brands[0] ) ) {
						return null;
					}

					$row = HWS_Commerce_Info::get_settings_for_brand( $brands[0]->term_id );
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
	}
);

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
