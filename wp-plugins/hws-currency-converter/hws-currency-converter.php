<?php
/**
 * Plugin Name: HWS Currency Converter
 * Description: Хранит базовую валюту магазина, источник курсов и отдаёт курсы через REST для headless storefront.
 * Version: 0.1.0
 * Author: HWS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HWS_Currency_Converter {
	private const OPTION = 'hws_currency_converter_settings';
	private const TRANSIENT = 'hws_currency_converter_rates_v1';
	private const MENU_SLUG = 'hws-currency-converter';
	private const REST_NAMESPACE = 'hws-currency/v1';

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_save_settings' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_filter( 'woocommerce_currency', [ __CLASS__, 'filter_woocommerce_currency' ] );
	}

	public static function activate(): void {
		if ( ! get_option( self::OPTION ) ) {
			update_option( self::OPTION, self::default_settings(), false );
		}
	}

	public static function default_settings(): array {
		return [
			'provider_label'   => 'ЦБ РФ',
			'provider_url'     => 'https://www.cbr-xml-daily.ru/daily_json.js',
			'base_currency'    => 'RUB',
			'display_currency' => 'USD',
			'enabled'          => [ 'USD', 'AZN', 'UZS', 'RUB' ],
		];
	}

	public static function settings(): array {
		$settings = get_option( self::OPTION, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}
		return array_merge( self::default_settings(), $settings );
	}

	public static function admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			'Конвертер валют',
			'Конвертер валют',
			'manage_woocommerce',
			self::MENU_SLUG,
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function maybe_save_settings(): void {
		if ( ! is_admin() || ! isset( $_POST['hws_currency_converter_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Недостаточно прав.' );
		}

		check_admin_referer( 'hws_currency_converter_save' );

		$incoming = isset( $_POST['hws_currency_converter'] ) && is_array( $_POST['hws_currency_converter'] )
			? wp_unslash( $_POST['hws_currency_converter'] )
			: [];

		$enabled = array_values(
			array_intersect(
				[ 'USD', 'AZN', 'UZS', 'RUB' ],
				array_map( 'sanitize_text_field', (array) ( $incoming['enabled'] ?? [] ) )
			)
		);

		if ( empty( $enabled ) ) {
			$enabled = [ 'USD', 'RUB' ];
		}

		$settings = [
			'provider_label'   => sanitize_text_field( $incoming['provider_label'] ?? 'ЦБ РФ' ),
			'provider_url'     => esc_url_raw( $incoming['provider_url'] ?? '' ),
			'base_currency'    => self::sanitize_currency( $incoming['base_currency'] ?? 'RUB' ),
			'display_currency' => self::sanitize_currency( $incoming['display_currency'] ?? 'USD' ),
			'enabled'          => $enabled,
		];

		update_option( self::OPTION, $settings, false );
		delete_transient( self::TRANSIENT );

		if ( ! empty( $_POST['hws_currency_converter_refresh'] ) ) {
			self::get_rates( true );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::MENU_SLUG,
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function sanitize_currency( $value ): string {
		$value = strtoupper( sanitize_text_field( (string) $value ) );
		return in_array( $value, [ 'USD', 'AZN', 'UZS', 'RUB' ], true ) ? $value : 'RUB';
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/rates',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_rates' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public static function rest_rates(): WP_REST_Response {
		$settings = self::settings();
		$rates    = self::get_rates();

		return new WP_REST_Response(
			[
				'providerLabel'   => $settings['provider_label'],
				'providerUrl'     => $settings['provider_url'],
				'baseCurrency'    => $settings['base_currency'],
				'displayCurrency' => $settings['display_currency'],
				'enabled'         => $settings['enabled'],
				'USD'             => $rates['USD'],
				'AZN'             => $rates['AZN'],
				'UZS'             => $rates['UZS'],
				'RUB'             => $rates['RUB'],
				'updatedAt'       => $rates['updatedAt'],
			]
		);
	}

	public static function filter_woocommerce_currency( string $currency ): string {
		$settings = self::settings();
		return $settings['base_currency'] ?: $currency;
	}

	public static function get_rates( bool $force = false ): array {
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$settings = self::settings();
		$url      = $settings['provider_url'] ?: self::default_settings()['provider_url'];
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return self::fallback_rates();
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		$rates   = self::parse_rates_payload( is_array( $payload ) ? $payload : [] );

		if ( ! $rates ) {
			return self::fallback_rates();
		}

		set_transient( self::TRANSIENT, $rates, HOUR_IN_SECONDS );
		return $rates;
	}

	private static function fallback_rates(): array {
		$fallback = [
			'USD'       => 1,
			'AZN'       => 1.7,
			'UZS'       => 12600,
			'RUB'       => 80,
			'updatedAt' => gmdate( DATE_ATOM ),
		];
		return $fallback;
	}

	private static function parse_rates_payload( array $payload ): ?array {
		if ( isset( $payload['Valute'] ) && is_array( $payload['Valute'] ) ) {
			$usd = self::rub_per_unit( $payload['Valute']['USD'] ?? null );
			$azn = self::rub_per_unit( $payload['Valute']['AZN'] ?? null );
			$uzs = self::rub_per_unit( $payload['Valute']['UZS'] ?? null );
			if ( ! $usd || ! $azn || ! $uzs ) {
				return null;
			}
			return [
				'USD'       => 1,
				'AZN'       => $usd / $azn,
				'UZS'       => $usd / $uzs,
				'RUB'       => $usd,
				'updatedAt' => $payload['Date'] ?? gmdate( DATE_ATOM ),
			];
		}

		if ( isset( $payload['rates'] ) && is_array( $payload['rates'] ) ) {
			$base = strtoupper( (string) ( $payload['base'] ?? 'USD' ) );
			$rates = $payload['rates'];
			if ( ! isset( $rates['USD'], $rates['AZN'], $rates['UZS'], $rates['RUB'] ) ) {
				return null;
			}
			if ( 'USD' !== $base ) {
				return null;
			}
			return [
				'USD'       => 1,
				'AZN'       => (float) $rates['AZN'],
				'UZS'       => (float) $rates['UZS'],
				'RUB'       => (float) $rates['RUB'],
				'updatedAt' => $payload['updatedAt'] ?? gmdate( DATE_ATOM ),
			];
		}

		return null;
	}

	private static function rub_per_unit( $value ): ?float {
		if ( ! is_array( $value ) ) {
			return null;
		}
		$nominal = isset( $value['Nominal'] ) ? (float) $value['Nominal'] : 0.0;
		$price   = isset( $value['Value'] ) ? (float) $value['Value'] : 0.0;
		if ( $nominal <= 0 || $price <= 0 ) {
			return null;
		}
		return $price / $nominal;
	}

	public static function render_admin_page(): void {
		$settings = self::settings();
		$rates    = self::get_rates();
		?>
		<div class="wrap">
			<h1>Конвертер валют HWS</h1>
			<p>Базовая валюта хранения цен в WooCommerce. Фронт конвертирует её в нужную валюту по выбранному источнику курса.</p>
			<?php if ( ! empty( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Настройки конвертера сохранены.</p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'hws_currency_converter_save' ); ?>
				<input type="hidden" name="hws_currency_converter_action" value="save">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hws-provider-label">Название источника</label></th>
						<td><input id="hws-provider-label" class="regular-text" name="hws_currency_converter[provider_label]" value="<?php echo esc_attr( $settings['provider_label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="hws-provider-url">URL источника курса</label></th>
						<td><input id="hws-provider-url" class="large-text" name="hws_currency_converter[provider_url]" value="<?php echo esc_attr( $settings['provider_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="hws-base-currency">Хранить цены в</label></th>
						<td><?php self::render_currency_select( 'hws_currency_converter[base_currency]', (string) $settings['base_currency'], 'hws-base-currency' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="hws-display-currency">Основная валюта витрины</label></th>
						<td><?php self::render_currency_select( 'hws_currency_converter[display_currency]', (string) $settings['display_currency'], 'hws-display-currency' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Разрешённые валюты витрины</th>
						<td>
							<?php foreach ( [ 'USD', 'AZN', 'UZS', 'RUB' ] as $currency ) : ?>
								<label style="display:inline-block; margin-right:16px;">
									<input type="checkbox" name="hws_currency_converter[enabled][]" value="<?php echo esc_attr( $currency ); ?>" <?php checked( in_array( $currency, (array) $settings['enabled'], true ) ); ?>>
									<?php echo esc_html( $currency ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Сохранить настройки' ); ?>
				<?php submit_button( 'Сохранить и обновить курс', 'secondary', 'hws_currency_converter_refresh', false ); ?>
			</form>

			<hr>
			<h2>Текущие курсы</h2>
			<table class="widefat striped" style="max-width:720px">
				<thead><tr><th>Валюта</th><th>Значение</th></tr></thead>
				<tbody>
					<tr><td>USD</td><td><?php echo esc_html( (string) $rates['USD'] ); ?></td></tr>
					<tr><td>AZN</td><td><?php echo esc_html( (string) $rates['AZN'] ); ?></td></tr>
					<tr><td>UZS</td><td><?php echo esc_html( (string) $rates['UZS'] ); ?></td></tr>
					<tr><td>RUB за 1 USD</td><td><?php echo esc_html( (string) $rates['RUB'] ); ?></td></tr>
					<tr><td>Обновлено</td><td><?php echo esc_html( (string) $rates['updatedAt'] ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function render_currency_select( string $name, string $selected, string $id ): void {
		?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<?php foreach ( [ 'RUB', 'USD', 'AZN', 'UZS' ] as $currency ) : ?>
				<option value="<?php echo esc_attr( $currency ); ?>" <?php selected( $selected, $currency ); ?>><?php echo esc_html( $currency ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}

HWS_Currency_Converter::init();
register_activation_hook( __FILE__, [ 'HWS_Currency_Converter', 'activate' ] );
