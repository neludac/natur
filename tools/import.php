<?php
/**
 * Importă conținutul extras de pe natur.md în WooCommerce.
 * Rulare: ddev wp eval-file tools/import.php
 * Idempotent: categoriile și produsele existente (după ID-ul vechi) nu se recreează, ci se completează
 * (descrierea lungă, toate categoriile, produsele recomandate). O descriere editată între timp
 * în admin nu se suprascrie.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir  = __DIR__ . '/data';
$data = json_decode( file_get_contents( $dir . '/natur.json' ), true );

// Catalogul = lista de prețuri (data/catalog.json): doar produsele de acolo, cu titlul de acolo.
// Categoriile fără produse din listă nu se mai creează (vezi „Păstrăm doar categoriile care au produse”).
$catalog_title    = array_column( json_decode( file_get_contents( $dir . '/catalog.json' ), true )['products'], 'title', 'old_id' );
$data['products'] = array_values( array_filter( $data['products'], static fn( $p ) => isset( $catalog_title[ $p['id'] ] ) ) );
foreach ( $data['products'] as &$p ) {
	$p['name'] = $catalog_title[ $p['id'] ];
}
unset( $p );

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

/** Textul vizibil al unui fragment HTML (pentru comparații). */
function natur_plain( $html ) {
	$text = html_entity_decode( wp_strip_all_tags( preg_replace( '/<[^>]+>/', ' ', (string) $html ) ), ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/', ' ', str_replace( "\xC2\xA0", ' ', $text ) ) );
}

function natur_post_by_old_id( $old_id ) {
	$found = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'meta_key' => '_natur_old_id', 'meta_value' => $old_id, 'fields' => 'ids', 'numberposts' => 1 ) );
	return $found ? (int) $found[0] : 0;
}

/**
 * Categoria importată după ID-ul vechi. SQL direct: WooCommerce ordonează product_cat după meta „order”,
 * iar get_terms( meta_key ) întoarce atunci alte categorii.
 */
function natur_term_by_old_id( $old_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT tm.term_id FROM {$wpdb->termmeta} tm JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id AND tt.taxonomy = 'product_cat' WHERE tm.meta_key = '_natur_old_id' AND tm.meta_value = %s ORDER BY tm.term_id LIMIT 1", $old_id ) );
}

/** Ambalarea: „pentru 500 gr” → „500 gr”. */
function natur_unit( $p ) {
	return trim( preg_replace( '/^pentru\s+/iu', '', $p['unit'] ) );
}

/** Rândurile adăugate sub fiecare descriere: ambalarea și prezentarea magazinului. */
function natur_desc_footer( $p ) {
	$unit = natur_unit( $p );
	$out  = $unit ? "\n<p><strong>Ambalare:</strong> " . esc_html( $unit ) . '</p>' : '';
	return $out . "\n<p>Produs natural selectat de echipa Natur.MD de la gospodari și mici producători din Moldova. Livrare în Chișinău în 1–48 de ore; plata la primirea comenzii.</p>";
}

/** Linkurile spre natur.md duc la paginile corespunzătoare de pe site-ul nou. */
function natur_new_link( $url ) {
	if ( ! preg_match( '~^(?:https?:)?//(?:www\.)?natur\.md(/[^#]*)?~i', $url, $m ) ) {
		return $url;
	}
	$path = wp_parse_url( $m[1] ?? '/', PHP_URL_PATH ) ?: '/';
	parse_str( (string) wp_parse_url( $m[1] ?? '', PHP_URL_QUERY ), $query );
	if ( preg_match( '~/(\d+)-[^/]*\.html$~', $path, $mm ) && natur_post_by_old_id( $mm[1] ) ) {
		return get_permalink( natur_post_by_old_id( $mm[1] ) );
	}
	if ( '/search' === $path && ! empty( $query['search_query'] ) ) {
		return add_query_arg( array( 's' => rawurlencode( $query['search_query'] ), 'post_type' => 'product' ), home_url( '/' ) );
	}
	if ( preg_match( '~^/(\d+)-[^/.]+/?$~', $path, $mm ) && natur_term_by_old_id( $mm[1] ) ) {
		return get_term_link( natur_term_by_old_id( $mm[1] ), 'product_cat' );
	}
	return home_url( $path ); // restul îl preiau redirecționările vechi din natur-site.php
}

/** O imagine din descriere: urcată o singură dată în Media (meta `_natur_src`), apoi refolosită. */
function natur_desc_img( $tag, $p, $post_id, $dir ) {
	static $cache = array();
	if ( ! preg_match( '~\bsrc="([^"]+)"~i', $tag, $m ) ) {
		return '';
	}
	$src  = html_entity_decode( $m[1] );
	$src  = str_starts_with( $src, '/' ) ? 'http://natur.md' . $src : $src;
	$file = $p['desc_images'][ $src ] ?? '';
	if ( ! $file ) {
		return ''; // imagine care nu mai există pe site-ul vechi
	}
	if ( ! isset( $cache[ $file ] ) ) {
		$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_natur_src', 'meta_value' => $file, 'fields' => 'ids', 'numberposts' => 1 ) );
		$id    = $found ? (int) $found[0] : natur_attach( "$dir/opt/$file", $post_id, $p['name'] );
		if ( $id && ! $found ) {
			update_post_meta( $id, '_natur_src', $file );
		}
		$cache[ $file ] = $id;
	}
	$img = $cache[ $file ] ? wp_get_attachment_image_src( $cache[ $file ], 'large' ) : null;
	if ( ! $img ) {
		return '';
	}
	$alt = preg_match( '~\balt="([^"]+)"~i', $tag, $a ) ? html_entity_decode( $a[1] ) : $p['name'];
	return sprintf( '<img class="wp-image-%d size-large" src="%s" alt="%s" width="%d" height="%d" />', $cache[ $file ], esc_url( $img[0] ), esc_attr( $alt ), $img[1], $img[2] );
}

/**
 * Descrierea lungă (tabul „Detalii” de pe natur.md), cu imaginile și formatarea ei;
 * h1 devine h3, fiindcă pagina produsului are deja titlul h1.
 */
function natur_description( $p, $post_id, $dir ) {
	$html = $p['description'];
	if ( '' === natur_plain( $html ) && false === stripos( $html, '<img' ) ) {
		$html = $p['short'];
	}
	$html = preg_replace( '~<(/?)h1\b~i', '<$1h3', $html );
	$html = preg_replace( '~<a\s+name="[^"]*"\s*>(.*?)</a>~is', '$1', $html );
	$html = wp_kses_post( $html );
	$html = preg_replace_callback( '~<img\b[^>]*>~i', static fn( $m ) => natur_desc_img( $m[0], $p, $post_id, $dir ), $html );
	$html = preg_replace_callback( '~\bhref="([^"]*)"~i', static fn( $m ) => 'href="' . esc_url( natur_new_link( html_entity_decode( $m[1] ) ) ) . '"', $html );
	$html = preg_replace( '~<p[^>]*>(?:\s|&nbsp;|\xC2\xA0)*</p>~u', '', $html );
	return trim( $html ) . natur_desc_footer( $p );
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

$term_of   = array();
$new_terms = array();
$order     = 0;
foreach ( $data['categories'] as $c ) {
	$order++;
	if ( empty( $has[ $c['id'] ] ) ) {
		continue;
	}
	$existing = natur_term_by_old_id( $c['id'] );
	if ( $existing ) {
		$term_of[ $c['id'] ] = $existing;
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
	$new_terms[]         = $res['term_id'];
	update_term_meta( $res['term_id'], '_natur_old_id', $c['id'] );
	update_term_meta( $res['term_id'], '_natur_old_url', wp_parse_url( $c['url'], PHP_URL_PATH ) );
	update_term_meta( $res['term_id'], 'order', $order );
	WP_CLI::log( "Categorie: $name" . ( empty( $c['hidden'] ) ? '' : ' (ascunsă în meniul vechi)' ) );
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

$post_of = array(); // ID vechi => ID produs
$is_new  = array();
$n       = 0;
foreach ( $data['products'] as $p ) {
	$found = natur_post_by_old_id( $p['id'] );
	if ( $found ) {
		$post_of[ $p['id'] ] = $found;
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

	$unit = natur_unit( $p );
	$product->set_short_description( $p['short'] );
	$product->set_description( $p['short'] . natur_desc_footer( $p ) ); // descrierea lungă vine mai jos

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
	$post_of[ $p['id'] ] = $pid;
	$is_new[ $p['id'] ]  = true;

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

/* ------------------------------------- Completare: descriere, categorii, recomandate */

// După crearea tuturor produselor, ca linkurile dintre descrieri să găsească pagina nouă.
$completed = 0;
foreach ( $data['products'] as $p ) {
	$pid = $post_of[ $p['id'] ] ?? 0;
	if ( ! $pid ) {
		continue;
	}
	$product = wc_get_product( $pid );
	$changed = array();

	// Descrierea e „a importului” dacă e cea generată inițial (scurtă + ambalare) sau cea scrisă ultima dată aici.
	$current = (string) get_post_field( 'post_content', $pid, 'raw' );
	$ours    = ! empty( $is_new[ $p['id'] ] )
		|| natur_plain( $current ) === natur_plain( $p['short'] . natur_desc_footer( $p ) )
		|| md5( $current ) === get_post_meta( $pid, '_natur_desc_md5', true );
	if ( $ours ) {
		$desc = natur_description( $p, $pid, $dir );
		if ( $desc !== $current ) {
			$product->set_description( $desc );
			$changed[] = 'descriere';
		}
	} else {
		WP_CLI::warning( "#{$p['id']} {$p['name']}: descrierea a fost editată în admin – nu o suprascriu." );
	}

	$want = array();
	foreach ( $p['categories'] as $cid ) {
		if ( isset( $term_of[ $cid ] ) ) {
			$want[] = $term_of[ $cid ];
		}
	}
	// Categoria implicită („Diverse”) pusă automat de WooCommerce dispare când produsul primește una reală.
	$cats = array_values( array_unique( array_merge( $product->get_category_ids(), $want ) ) );
	if ( $want ) {
		$cats = array_values( array_diff( $cats, array( (int) get_option( 'default_product_cat' ) ) ) );
	}
	if ( array_diff( $want, $product->get_category_ids() ) || count( $cats ) !== count( $product->get_category_ids() ) ) {
		$product->set_category_ids( $cats );
		$changed[] = 'categorii';
	}

	$acc = array();
	foreach ( $p['accessories'] ?? array() as $a ) {
		if ( isset( $post_of[ $a ] ) ) {
			$acc[] = $post_of[ $a ];
		}
	}
	if ( array_diff( $acc, $product->get_upsell_ids() ) ) {
		$product->set_upsell_ids( array_values( array_unique( array_merge( $product->get_upsell_ids(), $acc ) ) ) );
		$changed[] = 'produse recomandate';
	}

	if ( $changed ) {
		$product->save();
		$completed++;
		if ( empty( $is_new[ $p['id'] ] ) ) {
			WP_CLI::log( "Completat #{$p['id']} {$p['name']}: " . implode( ', ', $changed ) );
		}
	}
	if ( $ours ) {
		update_post_meta( $pid, '_natur_desc_md5', md5( (string) get_post_field( 'post_content', $pid, 'raw' ) ) );
	}
}

// Imagini pentru categoriile noi: fotografia unui produs din categorie.
foreach ( $new_terms as $tid ) {
	$ids = get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => $tid, 'include_children' => true ) ), 'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ) ) );
	if ( $ids ) {
		update_term_meta( $tid, 'thumbnail_id', get_post_thumbnail_id( $ids[0] ) );
	}
}

wp_defer_term_counting( false );
wp_defer_comment_counting( false );
wc_recount_all_terms();
wc_delete_product_transients();
WP_CLI::success( "Import finalizat: $n produse noi, $completed produse completate." );
