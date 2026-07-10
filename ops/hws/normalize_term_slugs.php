<?php
/**
 * Normalize percent-encoded Cyrillic term slugs to transliterated latin slugs.
 *
 * Targets WooCommerce attribute taxonomies (pa_*) and product_brand.
 * For each term:
 *   - recompute slug from term name via transliteration
 *   - if unchanged -> skip
 *   - if new slug free -> rename term slug
 *   - if new slug already used by another term -> merge (reassign objects, delete source)
 *
 * Env:
 *   HWS_NORMALIZE_MODE = dry-run (default) | run
 *   HWS_NORMALIZE_TAX  = comma list override (default: all pa_* + product_brand)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function hwsn_log( string $m ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $m );
		return;
	}
	echo $m . PHP_EOL;
}

function hws_translit_cyr( string $value ): string {
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

function hws_slugify_meta( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = str_replace( [ '³', '²', '¹', '№' ], [ '3', '2', '1', 'n' ], $value );
	$value = hws_translit_cyr( $value );
	return sanitize_title( $value );
}

$mode    = getenv( 'HWS_NORMALIZE_MODE' ) ?: 'dry-run';
$dry_run = 'run' !== $mode;

$tax_override = trim( (string) ( getenv( 'HWS_NORMALIZE_TAX' ) ?: '' ) );
if ( '' !== $tax_override ) {
	$taxonomies = array_values( array_filter( array_map( 'trim', explode( ',', $tax_override ) ) ) );
} else {
	$taxonomies = get_taxonomies( [], 'names' );
	$taxonomies = array_values( array_filter( $taxonomies, static function ( string $t ): bool {
		return 0 === strpos( $t, 'pa_' ) || 'product_brand' === $t;
	} ) );
}

hwsn_log( 'Mode: ' . ( $dry_run ? 'dry-run' : 'RUN' ) );
hwsn_log( 'Taxonomies: ' . implode( ', ', $taxonomies ) );

$renamed = 0;
$merged  = 0;
$skipped = 0;

foreach ( $taxonomies as $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		hwsn_log( 'SKIP missing taxonomy: ' . $taxonomy );
		continue;
	}

	$terms = get_terms( [
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	] );
	if ( is_wp_error( $terms ) ) {
		hwsn_log( 'ERR get_terms ' . $taxonomy . ': ' . $terms->get_error_message() );
		continue;
	}

	foreach ( $terms as $term ) {
		// Only repair broken percent-encoded slugs. Leave intentional clean
		// (English semantic) slugs untouched even if they differ from the name.
		if ( false === strpos( $term->slug, '%' ) ) {
			$skipped++;
			continue;
		}
		$new_slug = hws_slugify_meta( $term->name );
		if ( '' === $new_slug || $new_slug === $term->slug ) {
			$skipped++;
			continue;
		}

		$conflict = get_term_by( 'slug', $new_slug, $taxonomy );

		if ( $conflict && (int) $conflict->term_id !== (int) $term->term_id ) {
			// Merge source term ($term) into $conflict.
			hwsn_log( sprintf(
				'[MERGE] %s: "%s" (%s #%d, %d obj) -> "%s" (#%d)',
				$taxonomy,
				$term->name,
				$term->slug,
				$term->term_id,
				$term->count,
				$conflict->slug,
				$conflict->term_id
			) );
			if ( ! $dry_run ) {
				$object_ids = get_objects_in_term( $term->term_id, $taxonomy );
				if ( ! is_wp_error( $object_ids ) ) {
					foreach ( $object_ids as $object_id ) {
						wp_set_object_terms( (int) $object_id, (int) $conflict->term_id, $taxonomy, true );
					}
				}
				wp_delete_term( $term->term_id, $taxonomy );
			}
			$merged++;
			continue;
		}

		hwsn_log( sprintf(
			'[RENAME] %s: "%s" %s -> %s',
			$taxonomy,
			$term->name,
			$term->slug,
			$new_slug
		) );
		if ( ! $dry_run ) {
			$res = wp_update_term( $term->term_id, $taxonomy, [ 'slug' => $new_slug ] );
			if ( is_wp_error( $res ) ) {
				hwsn_log( 'ERR rename ' . $term->slug . ': ' . $res->get_error_message() );
				continue;
			}
		}
		$renamed++;
	}
}

hwsn_log( sprintf( 'Done. renamed=%d merged=%d skipped=%d dry_run=%s', $renamed, $merged, $skipped, $dry_run ? 'yes' : 'no' ) );
