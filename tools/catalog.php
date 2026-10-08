<?php
/**
 * Aliniază catalogul la lista de prețuri (tools/data/catalog.json):
 *  - produsele importate de pe natur.md care nu sunt în listă se șterg definitiv;
 *  - produsele din listă primesc titlul din listă;
 *  - categoriile rămase fără produse (nici în subcategorii) se șterg (meniul își scoate singur legăturile).
 * Produsele adăugate în admin (fără meta _natur_old_id) nu se ating.
 * Rulare: ddev wp eval-file tools/catalog.php [simulare]   (idempotent; „simulare” doar afișează ce s-ar schimba)
 */

defined( 'ABSPATH' ) || exit;

$dry     = in_array( 'simulare', $args ?? array(), true );
$catalog = json_decode( file_get_contents( __DIR__ . '/data/catalog.json' ), true );
$title   = array_column( $catalog['products'], 'title', 'old_id' );

/* ------------------------------------------------------------- Produse */

$kept    = 0;
$renamed = 0;
$deleted = 0;
$found   = array();
$removed = array(); // la „simulare” produsele rămân în baza de date, dar categoriile se socotesc fără ele
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $pid ) {
	$old     = (int) get_post_meta( $pid, '_natur_old_id', true );
	$product = wc_get_product( $pid );
	if ( ! $old || ! $product ) {
		WP_CLI::log( "Păstrat (adăugat în admin): #$pid " . get_the_title( $pid ) );
		continue;
	}
	if ( ! isset( $title[ $old ] ) ) {
		WP_CLI::log( "Șters: #$pid " . $product->get_name() );
		if ( ! $dry ) {
			$product->delete( true );
		}
		$removed[] = $pid;
		$deleted++;
		continue;
	}
	$kept++;
	$found[ $old ] = true;
	if ( $product->get_name() !== $title[ $old ] ) {
		WP_CLI::log( "Redenumit: #$pid „" . $product->get_name() . "” → „{$title[ $old ]}”" );
		if ( ! $dry ) {
			$product->set_name( $title[ $old ] );
			$product->save();
		}
		$renamed++;
	}
}
// Produsele recomandate („Îți recomandăm și”, cross-sell) care trimit la produse șterse.
foreach ( wc_get_products( array( 'limit' => -1, 'status' => array_keys( get_post_statuses() ), 'exclude' => $removed ) ) as $product ) {
	$up    = array_values( array_diff( $product->get_upsell_ids(), $removed, array_filter( $product->get_upsell_ids(), static fn( $id ) => ! wc_get_product( $id ) ) ) );
	$cross = array_values( array_diff( $product->get_cross_sell_ids(), $removed, array_filter( $product->get_cross_sell_ids(), static fn( $id ) => ! wc_get_product( $id ) ) ) );
	if ( $up !== $product->get_upsell_ids() || $cross !== $product->get_cross_sell_ids() ) {
		WP_CLI::log( "Recomandări curățate: #{$product->get_id()} {$product->get_name()}" );
		if ( ! $dry ) {
			$product->set_upsell_ids( $up );
			$product->set_cross_sell_ids( $cross );
			$product->save();
		}
	}
}
foreach ( array_diff_key( $title, $found ) as $old => $name ) {
	WP_CLI::warning( "Din listă, dar nu există pe site: #$old $name" );
}

/* ----------------------------------------------------------- Categorii */

// Frunzele întâi: o categorie-părinte se golește abia după ce îi dispar subcategoriile.
$default = (int) get_option( 'default_product_cat' );
$gone    = array();
do {
	$again = false;
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'exclude' => array_merge( array( $default ), $gone ) ) ) as $term ) {
		$children = array_diff( get_term_children( $term->term_id, 'product_cat' ), $gone );
		$has      = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'post__not_in' => $removed, 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => $term->term_id, 'include_children' => true ) ) ) );
		if ( $children || $has ) {
			continue;
		}
		WP_CLI::log( "Categorie ștearsă: {$term->name}" );
		if ( ! $dry ) {
			wp_delete_term( $term->term_id, 'product_cat' );
		}
		$gone[] = $term->term_id;
		$again  = true;
	}
} while ( $again );

if ( ! $dry ) {
	wc_recount_all_terms();
	wc_delete_product_transients();
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}
WP_CLI::success( sprintf( '%s%d produse păstrate (%d redenumite), %d șterse, %d categorii șterse.', $dry ? '[simulare] ' : '', $kept, $renamed, $deleted, count( $gone ) ) );
