<?php
/**
 * Plugin Name: HWS Image Background Remover
 * Description: Локальная пакетная обработка изображений товаров с удалением фона.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

final class HWS_Image_Background_Remover {

	private const OPTION = 'hws_ibr_job';
	private const BACKUP_META = '_hws_ibr_original_backup';
	private const PROCESSED_META = '_hws_ibr_processed_sha256';

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
		add_action( 'wp_ajax_hws_ibr_status', [ __CLASS__, 'ajax_status' ] );
		add_action( 'wp_ajax_hws_ibr_start', [ __CLASS__, 'ajax_start' ] );
		add_action( 'wp_ajax_hws_ibr_next', [ __CLASS__, 'ajax_next' ] );
		add_action( 'wp_ajax_hws_ibr_reset', [ __CLASS__, 'ajax_reset' ] );
	}

	public static function admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			'Удаление фона изображений',
			'Удаление фона изображений',
			'manage_woocommerce',
			'hws-image-background-remover',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function admin_assets( string $hook ): void {
		if ( 'woocommerce_page_hws-image-background-remover' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'hws-image-background-remover' ), 403 );
		}

		$brands = taxonomy_exists( 'product_brand' )
			? get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false ] )
			: [];
		$job    = get_option( self::OPTION, [] );
		$binary = self::rembg_binary();
		?>
		<div class="wrap" style="max-width:900px">
			<h1>Удаление фона изображений</h1>
			<p>Обработка выполняется локально на сервере через rembg. GPT, OpenAI и внешние API не используются.</p>
			<?php if ( ! $binary ) : ?>
				<div class="notice notice-error"><p>Локальная библиотека rembg не найдена. Установите её на сервере перед запуском.</p></div>
			<?php endif; ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hws-ibr-brand">Бренд</label></th>
					<td>
						<select id="hws-ibr-brand">
							<option value="">Выберите бренд</option>
							<?php foreach ( $brands as $brand ) : ?>
								<option value="<?php echo esc_attr( $brand->term_id ); ?>"><?php echo esc_html( $brand->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Будут обработаны изображения товаров, связанных с выбранным брендом.</p>
					</td>
				</tr>
			</table>
			<div class="card" style="max-width:760px;padding:16px">
				<p><strong>Всего:</strong> <span id="hws-ibr-total">0</span> &nbsp; <strong>Готово:</strong> <span id="hws-ibr-processed">0</span> &nbsp; <strong>Ошибки:</strong> <span id="hws-ibr-failed">0</span></p>
				<div style="height:12px;background:#dcdcde;border-radius:6px;overflow:hidden"><div id="hws-ibr-bar" style="height:100%;width:0;background:#2271b1;transition:width .2s"></div></div>
				<p id="hws-ibr-message" aria-live="polite">Выберите бренд.</p>
				<p>
					<button type="button" class="button button-primary" id="hws-ibr-start" <?php disabled( ! $binary ); ?>>Запустить</button>
					<button type="button" class="button" id="hws-ibr-stop" style="display:none">Остановить</button>
					<button type="button" class="button" id="hws-ibr-reset">Сбросить прогресс</button>
				</p>
				<pre id="hws-ibr-log" style="max-height:260px;overflow:auto;background:#1d2327;color:#fff;padding:12px"></pre>
			</div>
		</div>
		<script>
		(function($){
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'hws_ibr' ) ); ?>;
			var running = false;
			var selectedBrand = '';
			function request(action, extra) {
				return $.post(ajaxurl, $.extend({action: action, brand: selectedBrand, _ajax_nonce: nonce}, extra || {}));
			}
			function log(message) {
				$('#hws-ibr-log').append(document.createTextNode('[' + new Date().toLocaleTimeString() + '] ' + message + '\n'));
				var logEl = document.getElementById('hws-ibr-log');
				logEl.scrollTop = logEl.scrollHeight;
			}
			function render(data) {
				var total = parseInt(data.total, 10) || 0;
				var processed = parseInt(data.processed, 10) || 0;
				var failed = parseInt(data.failed, 10) || 0;
				$('#hws-ibr-total').text(total);
				$('#hws-ibr-processed').text(processed);
				$('#hws-ibr-failed').text(failed);
				$('#hws-ibr-bar').css('width', (total ? Math.round((processed + failed) / total * 100) : 0) + '%');
			}
			function status() {
				if (!selectedBrand) { render({}); return; }
				request('hws_ibr_status').done(function(response){
					if (response.success) { render(response.data); }
				});
			}
			function finish() {
				running = false;
				$('#hws-ibr-start').show();
				$('#hws-ibr-stop').hide();
				$('#hws-ibr-brand').prop('disabled', false);
			}
			function next() {
				if (!running) return;
				request('hws_ibr_next').done(function(response){
					if (!response.success) {
						log('Ошибка: ' + (response.data || 'неизвестная ошибка'));
						finish();
						return;
					}
					render(response.data);
					if (response.data.done) {
						$('#hws-ibr-message').text('Обработка завершена.');
						log('Обработка завершена.');
						finish();
						return;
					}
					log(response.data.message);
					setTimeout(next, 150);
				}).fail(function(){
					log('Соединение прервано, повтор через 3 секунды.');
					setTimeout(next, 3000);
				});
			}
			$('#hws-ibr-brand').on('change', function(){
				selectedBrand = $(this).val();
				$('#hws-ibr-log').empty();
				$('#hws-ibr-message').text(selectedBrand ? 'Проверяем изображения…' : 'Выберите бренд.');
				status();
			});
			$('#hws-ibr-start').on('click', function(){
				if (!selectedBrand) return;
				request('hws_ibr_start').done(function(response){
					if (!response.success) { log('Ошибка: ' + response.data); return; }
					render(response.data);
					running = true;
					$('#hws-ibr-start').hide();
					$('#hws-ibr-stop').show();
					$('#hws-ibr-brand').prop('disabled', true);
					$('#hws-ibr-message').text('Обработка выполняется…');
					next();
				});
			});
			$('#hws-ibr-stop').on('click', finish);
			$('#hws-ibr-reset').on('click', function(){
				if (!selectedBrand || !window.confirm('Сбросить прогресс выбранного бренда?')) return;
				request('hws_ibr_reset').done(function(response){
					if (response.success) { log('Прогресс сброшен.'); render({}); status(); }
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	private static function check_request(): int {
		check_ajax_referer( 'hws_ibr' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Недостаточно прав.', 403 );
		}
		$brand_id = absint( $_POST['brand'] ?? 0 );
		if ( ! $brand_id || ! taxonomy_exists( 'product_brand' ) || ! term_exists( $brand_id, 'product_brand' ) ) {
			wp_send_json_error( 'Выберите существующий бренд.', 400 );
		}
		return $brand_id;
	}

	public static function ajax_status(): void {
		$brand_id = self::check_request();
		$job      = self::get_job( $brand_id );
		if ( ! $job ) {
			$ids = self::brand_attachment_ids( $brand_id );
			wp_send_json_success( [ 'total' => count( $ids ), 'processed' => 0, 'failed' => 0, 'done' => 0 ] );
		}
		wp_send_json_success( self::job_status( $job ) );
	}

	public static function ajax_start(): void {
		$brand_id = self::check_request();
		if ( ! self::rembg_binary() ) {
			wp_send_json_error( 'Локальная библиотека rembg не установлена.', 500 );
		}
		$ids = self::brand_attachment_ids( $brand_id );
		$job = [
			'brand'     => $brand_id,
			'queue'     => $ids,
			'processed' => [],
			'failed'    => [],
			'created'   => time(),
		];
		update_option( self::OPTION, $job, false );
		wp_send_json_success( self::job_status( $job ) );
	}

	public static function ajax_next(): void {
		$brand_id = self::check_request();
		$job      = self::get_job( $brand_id );
		if ( ! $job ) {
			wp_send_json_error( 'Сначала запустите обработку.', 400 );
		}
		if ( empty( $job['queue'] ) ) {
			wp_send_json_success( array_merge( self::job_status( $job ), [ 'done' => true, 'message' => 'Готово.' ] ) );
		}

		$attachment_id = absint( array_shift( $job['queue'] ) );
		$result        = self::process_attachment( $attachment_id );
		if ( $result['ok'] ) {
			$job['processed'][] = $attachment_id;
		} else {
			$job['failed'][ $attachment_id ] = $result['message'];
		}
		update_option( self::OPTION, $job, false );
		$status = self::job_status( $job );
		$status['done']    = empty( $job['queue'] );
		$status['message'] = $result['message'];
		wp_send_json_success( $status );
	}

	public static function ajax_reset(): void {
		$brand_id = self::check_request();
		$job      = self::get_job( $brand_id );
		if ( $job ) {
			delete_option( self::OPTION );
		}
		wp_send_json_success( [ 'total' => 0, 'processed' => 0, 'failed' => 0 ] );
	}

	private static function get_job( int $brand_id ): ?array {
		$job = get_option( self::OPTION, [] );
		return is_array( $job ) && (int) ( $job['brand'] ?? 0 ) === $brand_id ? $job : null;
	}

	private static function job_status( array $job ): array {
		return [
			'total'     => count( $job['queue'] ) + count( $job['processed'] ) + count( $job['failed'] ),
			'processed' => count( $job['processed'] ),
			'failed'    => count( $job['failed'] ),
			'pending'   => count( $job['queue'] ),
		];
	}

	private static function brand_attachment_ids( int $brand_id ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}
		$product_ids = wc_get_products( [
			'limit'    => -1,
			'return'   => 'ids',
			'status'   => [ 'publish', 'draft', 'pending', 'private' ],
			'tax_query' => [ [
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => [ $brand_id ],
			] ],
		] );
		$attachment_ids = [];
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$attachment_ids[] = $product->get_image_id();
			$attachment_ids   = array_merge( $attachment_ids, $product->get_gallery_image_ids() );
		}
		return array_values( array_filter( array_unique( array_map( 'absint', $attachment_ids ) ) ) );
	}

	private static function rembg_binary(): ?string {
		$candidates = [
			defined( 'HWS_IBR_REMBG_BIN' ) ? HWS_IBR_REMBG_BIN : '',
			'/var/www/html/wp-content/hws-rembg/bin/rembg',
			'/opt/hws-rembg/bin/rembg',
			'/usr/local/bin/rembg',
			'/usr/bin/rembg',
		];
		foreach ( $candidates as $candidate ) {
			if ( $candidate && is_executable( $candidate ) ) {
				return $candidate;
			}
		}
		return null;
	}

	private static function process_attachment( int $attachment_id ): array {
		$binary = self::rembg_binary();
		if ( ! $binary ) {
			return [ 'ok' => false, 'message' => 'Локальная библиотека rembg недоступна.' ];
		}
		$source = get_attached_file( $attachment_id );
		if ( ! $source || ! is_readable( $source ) ) {
			return [ 'ok' => false, 'message' => "#{$attachment_id}: исходный файл не найден." ];
		}
		if ( get_post_meta( $attachment_id, self::PROCESSED_META, true ) ) {
			return [ 'ok' => true, 'message' => "#{$attachment_id}: уже обработано, пропуск." ];
		}

		$uploads = wp_upload_dir();
		$backup  = trailingslashit( $uploads['basedir'] ) . 'hws-image-background-remover-backups/' . gmdate( 'Y-m-d' ) . '/' . $attachment_id . '-' . wp_basename( $source );
		if ( ! get_post_meta( $attachment_id, self::BACKUP_META, true ) ) {
			wp_mkdir_p( dirname( $backup ) );
			if ( ! copy( $source, $backup ) ) {
				return [ 'ok' => false, 'message' => "#{$attachment_id}: не удалось сохранить backup." ];
			}
			update_post_meta( $attachment_id, self::BACKUP_META, str_replace( trailingslashit( $uploads['basedir'] ), '', $backup ) );
		}

		$temp = trailingslashit( dirname( $source ) ) . '.hws-ibr-' . $attachment_id . '-' . wp_generate_password( 10, false, false ) . '.png';
		$command = 'U2NET_HOME=/opt/hws-rembg/models timeout 240s ' . escapeshellarg( $binary ) . ' i ' . escapeshellarg( $source ) . ' ' . escapeshellarg( $temp );
		$output  = [];
		$code    = 0;
		exec( $command . ' 2>&1', $output, $code );
		if ( 0 !== $code || ! is_readable( $temp ) || filesize( $temp ) < 100 ) {
			@unlink( $temp );
			return [ 'ok' => false, 'message' => "#{$attachment_id}: rembg завершился с ошибкой." ];
		}

		$relative_dir = ltrim( str_replace( trailingslashit( $uploads['basedir'] ), '', dirname( $source ) ), '/' );
		$base         = pathinfo( wp_basename( $source ), PATHINFO_FILENAME );
		$target       = trailingslashit( dirname( $source ) ) . $base . '-transparent.png';
		if ( ! rename( $temp, $target ) ) {
			@unlink( $temp );
			return [ 'ok' => false, 'message' => "#{$attachment_id}: не удалось сохранить PNG." ];
		}

		$relative_file = ( $relative_dir ? trailingslashit( $relative_dir ) : '' ) . wp_basename( $target );
		update_attached_file( $attachment_id, $relative_file );
		wp_update_post( [ 'ID' => $attachment_id, 'post_mime_type' => 'image/png' ] );
		$metadata = wp_generate_attachment_metadata( $attachment_id, $target );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
		update_post_meta( $attachment_id, self::PROCESSED_META, hash_file( 'sha256', $target ) );
		return [ 'ok' => true, 'message' => "#{$attachment_id}: фон удалён." ];
	}
}

HWS_Image_Background_Remover::init();
