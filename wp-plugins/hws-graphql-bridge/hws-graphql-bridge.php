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
					$product_id = null;

					if ( isset( $source->wc_data ) && is_object( $source->wc_data ) && method_exists( $source->wc_data, 'get_id' ) ) {
						$product_id = $source->wc_data->get_id();
					} elseif ( isset( $source->ID ) ) {
						$product_id = $source->ID;
					}

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
