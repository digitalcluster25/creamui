<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// One-off: fix attribute term names that were created with English slug as name.
//
// Runs as: wp --allow-root eval-file fix_attribute_term_names.php --path=/var/www/html
// Set HWS_FIX_MODE=run to apply; defaults to dry-run.

$dry_run = 'run' !== ( getenv( 'HWS_FIX_MODE' ) ?: 'dry-run' );

function fix_log( string $msg ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $msg );
	} else {
		echo $msg . PHP_EOL;
	}
}

// -----------------------------------------------------------------------
// 1. Rename terms whose name === slug (English) to proper Russian names.
// -----------------------------------------------------------------------

$rename_map = [
	'pa_equipment-type' => [
		'steam-thermal-stove' => 'Паротермальная печь',
		'steam-generator'     => 'Парогенератор',
		'control-unit'        => 'Пульт управления',
	],
];

$renamed = 0;

foreach ( $rename_map as $taxonomy => $slugs ) {
	foreach ( $slugs as $slug => $new_name ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			fix_log( "SKIP rename: {$taxonomy}:{$slug} — term not found" );
			continue;
		}
		if ( $term->name === $new_name ) {
			fix_log( "SKIP rename: {$taxonomy}:{$slug} — already '{$new_name}'" );
			continue;
		}
		fix_log( ( $dry_run ? '[DRY] ' : '' ) . "RENAME {$taxonomy}:{$slug} '{$term->name}' → '{$new_name}'" );
		if ( ! $dry_run ) {
			$result = wp_update_term( $term->term_id, $taxonomy, [ 'name' => $new_name ] );
			if ( is_wp_error( $result ) ) {
				fix_log( "  ERROR: " . $result->get_error_message() );
			} else {
				$renamed++;
			}
		} else {
			$renamed++;
		}
	}
}

// -----------------------------------------------------------------------
// 2. Merge duplicate pa_usage-class terms created by VVD importer.
//
// VVD payload used 'private' and 'commercial' as value strings, which were
// slugified to 'private'/'commercial' and inserted as new terms. Pre-existing
// EasySteam terms 'chastnoe-ispolzovanie' and 'kommercheskoe-ispolzovanie'
// already carry the correct Russian names and products. Merge by:
//   a) reassigning products from old term to canonical term
//   b) deleting old duplicate term
// -----------------------------------------------------------------------

$merge_map = [
	'pa_usage-class' => [
		// old_slug => canonical_slug
		'private'    => 'chastnoe-ispolzovanie',
		'commercial' => 'kommercheskoe-ispolzovanie',
	],
];

$merged = 0;
$moved  = 0;

foreach ( $merge_map as $taxonomy => $pairs ) {
	foreach ( $pairs as $old_slug => $canonical_slug ) {
		$old_term = get_term_by( 'slug', $old_slug, $taxonomy );
		$new_term = get_term_by( 'slug', $canonical_slug, $taxonomy );

		if ( ! $old_term || is_wp_error( $old_term ) ) {
			fix_log( "SKIP merge: {$taxonomy}:{$old_slug} — source term not found" );
			continue;
		}
		if ( ! $new_term || is_wp_error( $new_term ) ) {
			fix_log( "SKIP merge: {$taxonomy}:{$canonical_slug} — target term not found" );
			continue;
		}

		$old_id = (int) $old_term->term_id;
		$new_id = (int) $new_term->term_id;
		$objects = get_objects_in_term( $old_id, $taxonomy );
		$count   = is_array( $objects ) ? count( $objects ) : 0;

		fix_log( ( $dry_run ? '[DRY] ' : '' ) . "MERGE {$taxonomy}:{$old_slug} (id={$old_id}, {$count} products) → {$canonical_slug} (id={$new_id})" );

		if ( ! $dry_run ) {
			if ( is_array( $objects ) ) {
				foreach ( $objects as $object_id ) {
					wp_remove_object_terms( (int) $object_id, $old_id, $taxonomy );
					wp_set_object_terms( (int) $object_id, [ $new_id ], $taxonomy, true );
					$moved++;
				}
			}
			$del = wp_delete_term( $old_id, $taxonomy );
			if ( is_wp_error( $del ) ) {
				fix_log( "  ERROR deleting {$old_slug}: " . $del->get_error_message() );
			} else {
				fix_log( "  DELETED term {$old_slug} (id={$old_id})" );
				$merged++;
			}
		} else {
			$moved  += $count;
			$merged++;
		}
	}
}

fix_log( sprintf(
	'Done. renamed=%d merged=%d products_moved=%d dry_run=%s',
	$renamed,
	$merged,
	$moved,
	$dry_run ? 'yes' : 'no'
) );
