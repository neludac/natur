<?php
/**
 * Pune pe produse fotografiile și filmările reale din tools/data/media (pregătite cu product_media.py):
 *  - fotografia devine imaginea principală, iar cea veche trece prima în galerie;
 *  - clipul trece primul în caseta „Video & prezentare 360°”, în locul clipului demonstrativ, cu fotografia ca poster;
 *  - produsele din lista de prețuri care lipseau de pe site se creează (titlu, preț, ambalare și termen din listă,
 *    descrierea scurtă după etichetă).
 * Rulare: ddev wp eval-file tools/product_media.php   (idempotent: fișierele deja urcate se refolosesc, meta `_natur_src`)
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__ . '/data/media';

$sterile = ' Produs sterilizat.';
$items   = array(
	array( 'sku' => 'NM-997', 'photo' => 'Boloneze', 'video' => 'Boloneze_video' ),
	array( 'sku' => 'NM-572', 'photo' => 'Bulion_mare', 'video' => 'Bulion_mare_video' ),
	array( 'sku' => 'NM-587', 'photo' => 'Bulion_mic', 'video' => 'Bulion_mic_video' ),
	array( 'sku' => 'NM-1007', 'photo' => 'Chiftelute_sos_rosu', 'video' => 'Chiftelute_sos_rosu_video' ),
	array( 'sku' => 'NM-556', 'photo' => 'Ghee_mare', 'video' => 'Ghee_mare_video' ),
	array( 'sku' => 'NM-310', 'photo' => 'Ghee_mic', 'video' => 'Ghee_mic_video' ),
	array( 'sku' => 'NM-1006', 'photo' => 'Grisells' ),
	array( 'sku' => 'NM-836', 'photo' => 'Hrana_animale' ),
	array( 'sku' => 'NM-90', 'photo' => 'Inabusita', 'video' => 'Inabusita_video' ),
	array( 'sku' => 'NM-595', 'photo' => 'Maioneza' ),
	array( 'sku' => 'NM-967', 'photo' => 'Mustar', 'video' => 'Mustar_video' ),
	array( 'sku' => 'NM-72', 'photo' => 'Otet', 'video' => 'Otet_video' ),
	array( 'sku' => 'NM-91', 'photo' => 'Pate', 'video' => 'Pate_video' ),
	array( 'sku' => 'NM-976', 'photo' => 'Pipote', 'video' => 'Pipote_inbusite_video' ),
	array( 'sku' => 'NM-104', 'photo' => 'Taietei_1kg' ),
	array( 'sku' => 'NM-1008', 'photo' => 'Tartinabila', 'video' => 'Tartinabila_video' ),
	array( 'sku' => 'NM-947', 'photo' => 'Zeama', 'video' => 'Zeama_video' ),

	// Din lista de prețuri DP_Poarta_15.06.2026.xlsx (row = rândul din listă), nu existau pe natur.md.
	array(
		'photo' => 'Bulion_cu_perisoare',
		'video' => 'Perisoare_video',
		'new'   => array( 'row' => 33, 'name' => 'Bulion cu perișoare de prepeliță, 430g', 'price' => 70, 'unit' => 'borcan, 500 ml', 'term' => '2 ani la t° +2+20 °C', 'cats' => array( 'Conserve', 'Din carne' ), 'short' => 'Bulion cu perișoare din prepeliță, 430 g.' . $sterile ),
	),
	array(
		'photo' => 'Bulion_piept',
		'video' => 'Bulion_piept_video',
		'new'   => array( 'row' => 36, 'name' => 'Bulion cu piept de prepeliță, 430g', 'price' => 70, 'unit' => 'borcan, 500 ml', 'term' => '2 ani la t° +2+20 °C', 'cats' => array( 'Conserve', 'Din carne' ), 'short' => 'Bulion cu piept de prepeliță, 430 g.' . $sterile ),
	),
	array(
		'photo' => 'Confit',
		'video' => 'Confit_video',
		'new'   => array( 'row' => 44, 'name' => 'CONFIT de prepeliță în unt GHEE, 200g', 'price' => 200, 'unit' => 'borcan, 250 ml', 'term' => '2 ani la t° +2+20 °C', 'cats' => array( 'Conserve', 'Din carne' ), 'short' => 'Confit din piept de prepeliță în unt GHEE, 200 g.' . $sterile ),
	),
	array(
		'photo' => 'Piept_asezonat',
		'new'   => array( 'row' => 30, 'name' => 'Piept asezonat de prepeliță', 'price' => 160, 'unit' => 'borcan, 230 g', 'term' => '2 ani la t° +2+20 °C', 'cats' => array( 'Conserve', 'Din carne' ), 'short' => 'Piept de prepeliță asezonat, înăbușit în suc propriu, 200 g.' . $sterile ),
	),
	array(
		'photo' => 'Velute',
		'video' => 'Chiftelute_sos_velute_video',
		'new'   => array( 'row' => 32, 'name' => 'Chifteluțe din piept de prepeliță în sos Velouté', 'price' => 155, 'unit' => 'borcan, 500 ml', 'term' => '2 ani la t° +2+20 °C', 'cats' => array( 'Conserve', 'Din carne' ), 'short' => 'Chifteluțe din piept de prepeliță în sos velouté, 430 g.' . $sterile ),
	),
	array(
		'photo' => 'Romanita',
		'video' => 'Romanita_video',
		'new'   => array( 'row' => 23, 'name' => 'Paste Romanița, 250g', 'price' => 55, 'unit' => 'borcan, 580 ml', 'term' => '12 luni la t° max. 30 °C', 'cats' => array( 'Paste făinoase' ), 'short' => 'Paste „Romanița” din griș și ouă de prepeliță, 250 g, în borcan de sticlă.' ),
	),
	array(
		'photo' => 'Taglietelli_1kg',
		'new'   => array( 'row' => 22, 'name' => 'Tăiței Tagliatelle cu ou de prepeliță', 'price' => 170, 'unit' => '1000 g', 'term' => '5 luni la t° max. 30 °C', 'cats' => array( 'Paste făinoase', 'HoReCa' ), 'short' => 'Tăiței Tagliatelle cu ouă de prepeliță, ambalați la 1 kg.' ),
	),
);

/** Un fișier din data/media, urcat o singură dată în Media (meta `_natur_src`), cu numele produsului. */
function natur_media_attach( $file, $name, $parent, $title ) {
	$src   = 'media/' . basename( $file );
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_natur_src', 'meta_value' => $src, 'fields' => 'ids', 'numberposts' => 1 ) );
	if ( $found ) {
		return (int) $found[0];
	}
	$tmp = wp_tempnam( $name );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $file . ': ' . $id->get_error_message() );
		@unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_natur_src', $src );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return $id;
}

function natur_media_term( $taxonomy, $name ) {
	$term = term_exists( $name, $taxonomy );
	if ( ! $term ) {
		$term = wp_insert_term( $name, $taxonomy );
	}
	return (int) $term['term_id'];
}

/** Produsul din lista de prețuri, creat la prima rulare (meta `_natur_list_row`). */
function natur_media_new_product( $spec ) {
	$found = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'meta_key' => '_natur_list_row', 'meta_value' => $spec['row'], 'fields' => 'ids', 'numberposts' => 1 ) );
	if ( $found ) {
		return wc_get_product( $found[0] );
	}
	$product = new WC_Product_Simple();
	$product->set_name( $spec['name'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( wc_format_decimal( $spec['price'] ) );
	$product->set_stock_status( 'instock' );
	$product->set_reviews_allowed( true );
	$product->set_short_description( $spec['short'] );
	$product->set_description(
		'<p>' . esc_html( $spec['short'] ) . "</p>\n"
		. '<p><strong>Ambalare:</strong> ' . esc_html( $spec['unit'] ) . "</p>\n"
		. '<p><strong>Termen de valabilitate:</strong> ' . esc_html( $spec['term'] ) . "</p>\n"
		. '<p>Produs natural selectat de echipa Natur.MD de la gospodari și mici producători din Moldova. Livrare în Chișinău în 1–48 de ore; plata la primirea comenzii.</p>'
	);
	$product->set_category_ids( array_map( static fn( $c ) => (int) get_term_by( 'name', $c, 'product_cat' )->term_id, $spec['cats'] ) );

	$attrs = array();
	foreach ( array( 'pa_ambalare' => $spec['unit'], 'pa_termen-valabilitate' => $spec['term'] ) as $taxonomy => $value ) {
		$attr = new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attr->set_name( $taxonomy );
		$attr->set_options( array( natur_media_term( $taxonomy, $value ) ) );
		$attr->set_position( count( $attrs ) );
		$attr->set_visible( true );
		$attrs[] = $attr;
	}
	$product->set_attributes( $attrs );
	$product->update_meta_data( '_natur_list_row', $spec['row'] ); // salvat odată cu produsul: o rulare întreruptă nu-l dublează
	$product->save();
	WP_CLI::log( "Produs nou: #{$product->get_id()} {$spec['name']} ({$spec['unit']}, {$spec['price']} lei)" );
	return wc_get_product( $product->get_id() ); // recitit: obiectul salvat nu are încă slug-ul generat
}

$photos = 0;
$videos = 0;
foreach ( $items as $item ) {
	$product = isset( $item['new'] ) ? natur_media_new_product( $item['new'] ) : wc_get_product( wc_get_product_id_by_sku( $item['sku'] ) );
	if ( ! $product ) {
		WP_CLI::warning( "Nu există produsul {$item['sku']} ({$item['photo']})" );
		continue;
	}
	$pid  = $product->get_id();
	$slug = $product->get_slug();

	$photo = natur_media_attach( "$dir/{$item['photo']}.jpg", "$slug.jpg", $pid, $product->get_name() );
	$old   = (int) $product->get_image_id();
	if ( $photo && $old !== $photo ) {
		$gallery = array_merge( $old ? array( $old ) : array(), $product->get_gallery_image_ids() );
		$product->set_image_id( $photo );
		$product->set_gallery_image_ids( array_values( array_diff( $gallery, array( $photo ) ) ) );
		$product->save();
		WP_CLI::log( "Foto: #$pid {$product->get_name()} ← {$item['photo']}.jpg" );
		$photos++;
	}

	if ( empty( $item['video'] ) ) {
		continue;
	}
	$vid = natur_media_attach( "$dir/{$item['video']}.mp4", "$slug-video.mp4", $pid, $product->get_name() . ' – video' );
	if ( ! $vid ) {
		continue;
	}
	set_post_thumbnail( $vid, $product->get_image_id() );
	$url    = wp_get_attachment_url( $vid );
	$demo   = (int) get_post_meta( $pid, '_natur_old_id', true ) . '.mp4'; // clipul demonstrativ din make_media.py
	$keep   = array_filter( Natur_Product_Media::get_videos( $pid ), static fn( $v ) => $v !== $url && basename( $v ) !== $demo );
	$list   = array_merge( array( $url ), array_values( $keep ) );
	if ( Natur_Product_Media::get_videos( $pid ) !== $list ) {
		update_post_meta( $pid, Natur_Product_Media::META_VIDEOS, $list );
		WP_CLI::log( "Video: #$pid {$product->get_name()} ← {$item['video']}.mp4" );
		$videos++;
	}
}

wc_recount_all_terms();
wc_delete_product_transients();
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
WP_CLI::success( "$photos fotografii principale și $videos clipuri puse pe produse." );
