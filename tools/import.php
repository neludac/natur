<?php
/**
 * Importă conținutul extras de pe natur.md în WooCommerce.
 * Rulare: ddev wp eval-file tools/import.php
 * Idempotent: produsele/categoriile existente (după ID-ul vechi) sunt sărite.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir  = __DIR__ . '/data';
$data = json_decode( file_get_contents( $dir . '/natur.json' ), true );

wp_defer_term_counting( true );
wp_defer_comment_counting( true );

/** Numele categoriei fără traducerea rusă din paranteze. */
function natur_clean_cat_name( $name ) {
	$name = trim( preg_replace( '/\s*\([^)]*\p{Cyrillic}[^)]*\)\s*/u', ' ', $name ) );
	$map  = array(
		'GLUTEN FREE'              => 'Fără gluten',
		'Seminte pentru germinare' => 'Semințe pentru germinare',
		'Adaos biologic activ'     => 'Adaosuri biologic active',
		'Paste fainoase'           => 'Paste făinoase',
		'Hrana animale de campanie' => 'Hrană pentru animale de companie',
	);
	return $map[ $name ] ?? $name;
}

function natur_attach( $file, $parent, $title ) {
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $file . ': ' . $id->get_error_message() );
		@unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return $id;
}

/* ------------------------------------------------------------ Categorii */

// Păstrăm doar categoriile care au produse (direct sau în subcategorii).
$by_id   = array_column( $data['categories'], null, 'id' );
$has     = array();
foreach ( $data['products'] as $p ) {
	foreach ( $p['categories'] as $cid ) {
		for ( $c = $cid; $c; $c = $by_id[ $c ]['parent'] ) {
			$has[ $c ] = true;
		}
	}
}

$term_of = array();
$order   = 0;
foreach ( $data['categories'] as $c ) {
	$order++;
	if ( empty( $has[ $c['id'] ] ) ) {
		continue;
	}
	$existing = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'meta_key' => '_natur_old_id', 'meta_value' => $c['id'], 'fields' => 'ids' ) );
	if ( $existing ) {
		$term_of[ $c['id'] ] = $existing[0];
		continue;
	}
	$name   = natur_clean_cat_name( $c['name'] );
	$parent = $c['parent'] ? ( $term_of[ $c['parent'] ] ?? 0 ) : 0;
	$slug   = sanitize_title( remove_accents( $name ) );
	if ( term_exists( $slug, 'product_cat' ) ) {
		$slug = sanitize_title( remove_accents( natur_clean_cat_name( $by_id[ $c['parent'] ]['name'] ?? '' ) . ' ' . $name ) );
	}
	$res = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent, 'slug' => $slug, 'description' => $c['description'] ?? '' ) );
	if ( is_wp_error( $res ) ) {
		WP_CLI::warning( $name . ': ' . $res->get_error_message() );
		continue;
	}
	$term_of[ $c['id'] ] = $res['term_id'];
	update_term_meta( $res['term_id'], '_natur_old_id', $c['id'] );
	update_term_meta( $res['term_id'], '_natur_old_url', wp_parse_url( $c['url'], PHP_URL_PATH ) );
	update_term_meta( $res['term_id'], 'order', $order );
	WP_CLI::log( "Categorie: $name" );
}

/* ------------------------------------------------------------- Atribut */

$attr_id = wc_attribute_taxonomy_id_by_name( 'ambalare' );
if ( ! $attr_id ) {
	$attr_id = wc_create_attribute( array( 'name' => 'Ambalare', 'slug' => 'ambalare', 'type' => 'select', 'has_archives' => false ) );
	register_taxonomy( 'pa_ambalare', array( 'product' ) );
}

/* ------------------------------------------------------------- Produse */

$media_360   = array( 556, 91, 896 );
$media_video = array( 556, 98, 23, 58, 601, 170 );
$featured    = array( 556, 91, 896, 58, 23, 98, 601, 170, 25, 57, 721, 454 );

$n = 0;
foreach ( $data['products'] as $p ) {
	$found = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'meta_key' => '_natur_old_id', 'meta_value' => $p['id'], 'fields' => 'ids' ) );
	if ( $found ) {
		continue;
	}
	$n++;
	$product = new WC_Product_Simple();
	$product->set_name( $p['name'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );

	$regular = $p['regular_price'] > $p['price'] ? $p['regular_price'] : $p['price'];
	$product->set_regular_price( wc_format_decimal( $regular ) );
	if ( $p['regular_price'] > $p['price'] ) {
		$product->set_sale_price( wc_format_decimal( $p['price'] ) );
	}
	$product->set_stock_status( $p['in_stock'] ? 'instock' : 'outofstock' );
	$product->set_sku( 'NM-' . $p['id'] );
	$product->set_featured( in_array( $p['id'], $featured, true ) );

	$unit = trim( preg_replace( '/^pentru\s+/iu', '', $p['unit'] ) );
	$short = $p['short'];
	$desc  = $p['short'];
	if ( $unit ) {
		$desc .= "\n<p><strong>Ambalare:</strong> " . esc_html( $unit ) . '</p>';
	}
	$desc .= "\n<p>Produs natural selectat de echipa Natur.MD de la gospodari și mici producători din Moldova. Livrare în Chișinău în 1–48 de ore; plata la primirea comenzii.</p>";
	$product->set_short_description( $short );
	$product->set_description( $desc );

	$cats = array();
	foreach ( $p['categories'] as $cid ) {
		if ( isset( $term_of[ $cid ] ) ) {
			$cats[] = $term_of[ $cid ];
		}
	}
	$product->set_category_ids( $cats );
	$product->set_reviews_allowed( true );

	if ( $unit ) {
		$term = term_exists( $unit, 'pa_ambalare' );
		if ( ! $term ) {
			$term = wp_insert_term( $unit, 'pa_ambalare' );
		}
		$attr = new WC_Product_Attribute();
		$attr->set_id( $attr_id );
		$attr->set_name( 'pa_ambalare' );
		$attr->set_options( array( (int) $term['term_id'] ) );
		$attr->set_visible( true );
		$product->set_attributes( array( $attr ) );
	}

	$pid = $product->save();
	update_post_meta( $pid, '_natur_old_id', $p['id'] );
	update_post_meta( $pid, '_natur_old_url', wp_parse_url( $p['url'], PHP_URL_PATH ) );

	// Imagini
	$ids = array();
	foreach ( $p['local_images'] as $i => $fn ) {
		$id = natur_attach( "$dir/opt/$fn", $pid, $p['name'] . ( $i ? ' – ' . ( $i + 1 ) : '' ) );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	if ( $ids ) {
		$product->set_image_id( array_shift( $ids ) );
		$product->set_gallery_image_ids( $ids );
		$product->save();
	}

	// 360°
	if ( in_array( $p['id'], $media_360, true ) ) {
		$frames = array();
		$files  = glob( "$dir/360/{$p['id']}/*.jpg" );
		sort( $files, SORT_NATURAL );
		foreach ( $files as $f ) {
			$frames[] = natur_attach( $f, $pid, $p['name'] . ' – 360°' );
		}
		update_post_meta( $pid, Natur_Product_Media::META_FRAMES, array_filter( $frames ) );
	}

	// Video
	if ( in_array( $p['id'], $media_video, true ) && file_exists( "$dir/video/{$p['id']}.mp4" ) ) {
		$vid = natur_attach( "$dir/video/{$p['id']}.mp4", $pid, $p['name'] . ' – video' );
		if ( $vid ) {
			set_post_thumbnail( $vid, $product->get_image_id() );
			update_post_meta( $pid, Natur_Product_Media::META_VIDEOS, array( wp_get_attachment_url( $vid ) ) );
		}
	}

	// Recenzii
	foreach ( $p['reviews'] as $r ) {
		$date = $r['date'] ? $r['date'] . ' 12:00:00' : current_time( 'mysql' );
		$cid  = wp_insert_comment(
			array(
				'comment_post_ID'      => $pid,
				'comment_author'       => $r['author'],
				'comment_author_email' => '',
				'comment_content'      => $r['text'],
				'comment_type'         => 'review',
				'comment_approved'     => 1,
				'comment_date'         => $date,
				'comment_date_gmt'     => get_gmt_from_date( $date ),
			)
		);
		// Vechiul site ascundea stelele (nota implicită 3), deci importăm doar textul recenziei.
		update_comment_meta( $cid, 'verified', 1 );
	}
	if ( $p['reviews'] ) {
		WC_Comments::clear_transients( $pid );
	}

	WP_CLI::log( sprintf( '[%d] %s – %d imagini', $n, $p['name'], count( $p['local_images'] ) ) );
}

wp_defer_term_counting( false );
wp_defer_comment_counting( false );
wc_delete_product_transients();
WP_CLI::success( "Import finalizat: $n produse noi." );
