<?php
/**
 * Configurează site-ul Natur.MD: WooCommerce, livrare, plată, pagini, meniuri, temă și pagina principală.
 * Rulare: ddev wp eval-file tools/setup.php   (poate fi rulat de mai multe ori)
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$data_dir = __DIR__ . '/data';
// Datele de contact de pornire; după instalare se schimbă în Aspect → Personalizare → Natur.MD → Date de contact.
$phone     = '060 89 30 00';
$email     = 'contact@natur.md';
$fb        = 'https://www.facebook.com/natur.md';
$ig        = 'https://www.instagram.com/natur.md/';
$messenger = 'https://m.me/natur.md';

/* ================================================================ Helpers */

function natur_upload_once( $file, $title ) {
	$found = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_natur_asset', 'meta_value' => basename( $file ), 'fields' => 'ids' ) );
	if ( $found ) {
		return $found[0];
	}
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	update_post_meta( $id, '_natur_asset', basename( $file ) );
	return $id;
}

/** Creează sau actualizează o pagină după slug. */
function natur_page( $slug, $title, $content = '', $extra = array() ) {
	$page = get_page_by_path( $slug );
	$args = array_merge(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		),
		$extra
	);
	if ( $page ) {
		$args['ID'] = $page->ID;
		return wp_update_post( wp_slash( $args ) );
	}
	return wp_insert_post( wp_slash( $args ) );
}

function natur_eid() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

function el_c( array $settings, array $elements = array(), $inner = false ) {
	return array( 'id' => natur_eid(), 'elType' => 'container', 'isInner' => $inner, 'settings' => $settings, 'elements' => $elements );
}

function el_w( $type, array $settings ) {
	return array( 'id' => natur_eid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}

function el_pad( $t, $r, $b, $l ) {
	return array( 'unit' => 'px', 'top' => (string) $t, 'right' => (string) $r, 'bottom' => (string) $b, 'left' => (string) $l, 'isLinked' => false );
}

function el_heading( $text, $tag = 'h2', $align = 'center', $color = '', $size = null ) {
	$s = array( 'title' => $text, 'header_size' => $tag, 'align' => $align );
	if ( $color ) {
		$s['title_color'] = $color;
	}
	if ( $size ) {
		$s['typography_typography'] = 'custom';
		$s['typography_font_size']  = array( 'unit' => 'px', 'size' => $size[0] );
		$s['typography_font_size_mobile'] = array( 'unit' => 'px', 'size' => $size[1] );
		$s['typography_font_weight'] = '800';
		$s['typography_line_height'] = array( 'unit' => 'em', 'size' => 1.15 );
	}
	return el_w( 'heading', $s );
}

function el_text( $html, $align = 'center', $color = '' ) {
	$s = array( 'editor' => $html, 'align' => $align );
	if ( $color ) {
		$s['text_color'] = $color;
	}
	return el_w( 'text-editor', $s );
}

function el_button( $text, $url, $style = 'primary', $align = 'center' ) {
	$s = array(
		'text'                  => $text,
		'link'                  => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
		'align'                 => $align,
		'size'                  => 'md',
		'border_radius'         => array( 'unit' => 'px', 'top' => '999', 'right' => '999', 'bottom' => '999', 'left' => '999', 'isLinked' => true ),
		'text_padding'          => el_pad( 14, 28, 14, 28 ),
		'typography_typography' => 'custom',
		'typography_font_weight' => '700',
	);
	if ( 'primary' === $style ) {
		$s['background_color']        = '#8DC732';
		$s['button_text_color']       = '#1F1D1A';
		$s['button_background_hover_color'] = '#FFFFFF';
		$s['hover_color']             = '#1F1D1A';
	} elseif ( 'light' === $style ) {
		$s['background_color']        = 'rgba(255,255,255,0)';
		$s['button_text_color']       = '#FFFFFF';
		$s['border_border']           = 'solid';
		$s['border_width']            = array( 'unit' => 'px', 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'isLinked' => true );
		$s['border_color']            = '#FFFFFF';
		$s['button_background_hover_color'] = '#FFFFFF';
		$s['hover_color']             = '#1F1D1A';
	} else {
		$s['background_color']        = '#4E7D14';
		$s['button_text_color']       = '#FFFFFF';
		$s['button_background_hover_color'] = '#3D6410';
		$s['hover_color']             = '#FFFFFF';
	}
	return el_w( 'button', $s );
}

function el_section( array $elements, $bg = '', $pad = array( 72, 20, 72, 20 ) ) {
	$s = array(
		'content_width'  => 'boxed',
		'flex_direction' => 'column',
		'flex_gap'       => array( 'unit' => 'px', 'size' => 16, 'column' => '16', 'row' => '16' ),
		'padding'        => el_pad( ...$pad ),
		'padding_mobile' => el_pad( 48, 16, 48, 16 ),
	);
	if ( $bg ) {
		$s['background_background'] = 'classic';
		$s['background_color']      = $bg;
	}
	return el_c( $s, $elements );
}

/* ============================================================ Setări generale */

update_option( 'blogname', 'Natur.MD' );
update_option( 'blogdescription', 'Produse naturale de la gospodari și mici producători din Moldova' );
update_option( 'admin_email', 'nelu.dac@gmail.com' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );
update_option( 'uploads_use_yearmonth_folders', 1 );
update_option( 'thumbnail_size_w', 300 );
update_option( 'thumbnail_size_h', 300 );

wp_delete_post( 1, true ); // „Salut, lume!”
foreach ( get_posts( array( 'post_type' => 'page', 'title' => 'Pagină exemplu', 'post_status' => 'any', 'fields' => 'ids' ) ) as $sample_id ) {
	wp_delete_post( $sample_id, true );
}

// Datele de contact folosite de temă (antet, subsol, pagina produsului, Contact, shortcode-urile [natur_telefon] / [natur_email]).
// Se păstrează ce s-a schimbat deja din Personalizare; se completează doar câmpurile lipsă.
update_option(
	'natur_contact',
	array_merge(
		array(
			'phone'     => $phone,
			'email'     => $email,
			'facebook'  => $fb,
			'messenger' => $messenger,
			'instagram' => $ig,
			'area'      => 'mun. Chișinău, Republica Moldova',
		),
		array_filter( array_diff_key( (array) get_option( 'natur_contact', array() ), array( 'phone_raw' => 1 ) ) )
	)
);

// Tema „natur” (copil Astra): antet, subsol, carduri și pagini proprii. theme_mod-urile de mai jos se salvează pentru ea.
if ( wp_get_theme( 'natur' )->exists() && 'natur' !== get_stylesheet() ) {
	switch_theme( 'natur' );
}

// Logo & iconiță
$logo_id = natur_upload_once( "$data_dir/logo.png", 'Natur.MD logo' );
$icon_id = natur_upload_once( "$data_dir/site-icon.png", 'Natur.MD iconiță' );
set_theme_mod( 'custom_logo', $logo_id );
update_option( 'site_icon', $icon_id );
// Logo pentru fundal închis (subsol): Personalizare → Identitatea site-ului → „Logo pentru fundal închis”.
if ( ! get_theme_mod( 'nt_logo_light' ) ) {
	set_theme_mod( 'nt_logo_light', natur_upload_once( WP_CONTENT_DIR . '/themes/natur/assets/img/logo-light.png', 'Natur.MD logo (fundal închis)' ) );
}

/* ============================================================ WooCommerce */

$wc = array(
	'woocommerce_store_address'           => 'mun. Chișinău',
	'woocommerce_store_address_2'         => '',
	'woocommerce_store_city'              => 'Chișinău',
	'woocommerce_store_postcode'          => 'MD-2001',
	'woocommerce_default_country'         => 'MD:C',
	'woocommerce_allowed_countries'       => 'specific',
	'woocommerce_specific_allowed_countries' => array( 'MD' ),
	'woocommerce_ship_to_countries'       => '',
	'woocommerce_default_customer_address' => 'base',
	'woocommerce_currency'                => 'MDL',
	'woocommerce_currency_pos'            => 'right_space',
	'woocommerce_price_thousand_sep'      => ' ',
	'woocommerce_price_decimal_sep'       => ',',
	'woocommerce_price_num_decimals'      => 2,
	'woocommerce_calc_taxes'              => 'no',
	'woocommerce_weight_unit'             => 'kg',
	'woocommerce_dimension_unit'          => 'cm',
	'woocommerce_enable_reviews'          => 'yes',
	'woocommerce_review_rating_verification_label' => 'yes',
	'woocommerce_review_rating_verification_required' => 'no',
	'woocommerce_enable_review_rating'    => 'yes',
	'woocommerce_review_rating_required'  => 'yes',
	'woocommerce_manage_stock'            => 'no',
	'woocommerce_hide_out_of_stock_items' => 'no',
	'woocommerce_enable_guest_checkout'   => 'yes',
	// Comanda într-un singur pas (tema: inc/checkout.php): fără autentificare sau cont la comandă.
	'woocommerce_enable_checkout_login_reminder' => 'no',
	'woocommerce_enable_signup_and_login_from_checkout' => 'no',
	'woocommerce_enable_myaccount_registration' => 'yes',
	'woocommerce_registration_generate_password' => 'yes',
	'woocommerce_checkout_phone_field'    => 'required',
	'woocommerce_checkout_company_field'  => 'hidden',
	'woocommerce_checkout_address_2_field' => 'optional',
	'woocommerce_ship_to_destination'     => 'billing_only', // livrare la adresa din formular, fără „altă adresă de livrare”
	'woocommerce_cart_redirect_after_add' => 'no',
	'woocommerce_enable_ajax_add_to_cart' => 'yes',
	'woocommerce_email_from_name'         => 'Natur.MD',
	'woocommerce_email_from_address'      => $email,
	'woocommerce_email_footer_text'       => 'Natur.MD — produse naturale de la gospodari din Moldova · ' . $phone,
	'woocommerce_email_base_color'        => '#4E7D14',
	'woocommerce_coming_soon'             => 'no',
	'woocommerce_store_pages_only'        => 'no',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_allow_tracking'          => 'no',
	'woocommerce_task_list_hidden'        => 'yes',
	'woocommerce_task_list_complete'      => 'yes',
	'woocommerce_onboarding_profile'      => array( 'skipped' => true ),
	'woocommerce_default_catalog_orderby' => 'popularity',
	'woocommerce_catalog_columns'         => 4,
	'woocommerce_catalog_rows'            => 4,
	'woocommerce_shop_page_display'       => '',
	'woocommerce_category_archive_display' => '',
	'woocommerce_thumbnail_cropping'      => '1:1',
	'woocommerce_single_image_width'      => 800,
	'woocommerce_thumbnail_image_width'   => 400,
	'woocommerce_product_type'            => 'physical',
	'woocommerce_sell_in_person'          => 'no',
	// Statistici (Analytics) actualizate imediat la fiecare comandă, nu o dată la 12 ore.
	'woocommerce_analytics_scheduled_import' => 'no',
);
foreach ( $wc as $k => $v ) {
	update_option( $k, $v );
}

// Pagini WooCommerce în română
$wc_pages = array(
	'shop'      => array( 'magazin', 'Magazin' ),
	'cart'      => array( 'cos', 'Coș' ),
	'checkout'  => array( 'finalizare-comanda', 'Finalizare comandă' ),
	'myaccount' => array( 'contul-meu', 'Contul meu' ),
);
foreach ( $wc_pages as $key => [ $slug, $title ] ) {
	wp_update_post( array( 'ID' => wc_get_page_id( $key ), 'post_name' => $slug, 'post_title' => $title ) );
}

// Textele statice din blocul Coș (pagina a fost creată înainte de activarea limbii române)
$cart_page = get_post( wc_get_page_id( 'cart' ) );
wp_update_post(
	array(
		'ID'           => $cart_page->ID,
		'post_content' => wp_slash( str_replace( array( '>New in store<', '>You may be interested in&hellip;<', '>Your cart is currently empty!<' ), array( '>Noutăți în magazin<', '>Poate vă interesează&hellip;<', '>Coșul dvs. este gol momentan!<' ), $cart_page->post_content ) ),
	)
);

// Pagina de comandă: formularul simplu al temei (șablonul clasic WooCommerce, nu blocul „Finalizare comandă”).
wp_update_post(
	array(
		'ID'           => wc_get_page_id( 'checkout' ),
		'post_content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
	)
);

// URL-uri în română: /produs/…, /categorie/…
update_option( 'woocommerce_permalinks', array( 'product_base' => '/produs', 'category_base' => 'categorie', 'tag_base' => 'eticheta', 'attribute_base' => '', 'use_verbose_page_rules' => false ) );

// Plăți: plata la livrare
$cod = get_option( 'woocommerce_cod_settings', array() );
update_option(
	'woocommerce_cod_settings',
	array_merge(
		(array) $cod,
		array(
			'enabled'            => 'yes',
			'title'              => 'Plata la livrare',
			'description'        => 'Plătești în numerar, când primești comanda de la curier sau producător.',
			'instructions'       => '', // pagina „Comandă primită” spune deja suma și că plata e la primire
			'enable_for_methods' => array(),
			'enable_for_virtual' => 'yes',
		)
	)
);
foreach ( array( 'bacs', 'cheque', 'paypal' ) as $gw ) {
	$s = get_option( "woocommerce_{$gw}_settings", array() );
	if ( is_array( $s ) ) {
		$s['enabled'] = 'no';
		update_option( "woocommerce_{$gw}_settings", $s );
	}
}

// Livrare
global $wpdb;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	( new WC_Shipping_Zone( $z['id'] ) )->delete();
}
$add_method = static function ( WC_Shipping_Zone $zone, $type, array $settings ) {
	$instance = $zone->add_shipping_method( $type );
	$method   = WC_Shipping_Zones::get_shipping_method( $instance );
	update_option( $method->get_instance_option_key(), array_merge( $method->instance_settings, $settings ) );
};

$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'mun. Chișinău' );
$zone->set_zone_order( 1 );
$zone->add_location( 'MD:C', 'state' );
$zone->save();
$add_method( $zone, 'free_shipping', array( 'title' => 'Livrare gratuită (comenzi de la 500 lei)', 'requires' => 'min_amount', 'min_amount' => '500', 'ignore_discounts' => 'no' ) );
$add_method( $zone, 'flat_rate', array( 'title' => 'Livrare prin curier (1–48 ore)', 'tax_status' => 'none', 'cost' => '50' ) );

$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'Moldova – Poșta Moldovei' );
$zone->set_zone_order( 2 );
$zone->add_location( 'MD', 'country' );
$zone->save();
$add_method( $zone, 'free_shipping', array( 'title' => 'Poșta Moldovei – gratuit (de la 500 lei, doar produse neperisabile)', 'requires' => 'min_amount', 'min_amount' => '500', 'ignore_discounts' => 'no' ) );
$add_method( $zone, 'flat_rate', array( 'title' => 'Poșta Moldovei (doar produse neperisabile)', 'tax_status' => 'none', 'cost' => '50' ) );

WC_Cache_Helper::get_transient_version( 'shipping', true );

// Imagini pentru categorii: prima imagine a unui produs din categorie
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	if ( get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
		continue;
	}
	$ids = get_posts( array( 'post_type' => 'product', 'posts_per_page' => 1, 'fields' => 'ids', 'orderby' => 'rand', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => $term->term_id, 'include_children' => true ) ), 'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ) ) );
	if ( $ids ) {
		update_term_meta( $term->term_id, 'thumbnail_id', get_post_thumbnail_id( $ids[0] ) );
	}
}
update_option( 'default_product_cat', get_term_by( 'slug', 'diverse', 'product_cat' )->term_id ?? get_option( 'default_product_cat' ) );

/* ============================================================ Pagini */

$p = static fn( $t ) => "<!-- wp:paragraph -->\n<p>$t</p>\n<!-- /wp:paragraph -->\n\n";
$h = static fn( $t, $l = 2 ) => "<!-- wp:heading" . ( 2 === $l ? '' : " {\"level\":$l}" ) . " -->\n<h$l class=\"wp-block-heading\">$t</h$l>\n<!-- /wp:heading -->\n\n";
$ul = static function ( array $items ) {
	$li = '';
	foreach ( $items as $i ) {
		$li .= "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->\n";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">$li</ul>\n<!-- /wp:list -->\n\n";
};

$about = $p( 'Știți din ce se produc lactatele ieftine și carnea „frumoasă”, dar plină de antibiotice, steroizi și hormoni? Noi ne-am informat — și ne-am șocat. După ce am aflat mai multe despre culisele producției industriale de mâncare, am hotărât: gata, consumăm doar mâncare naturală!' )
	. $p( 'Așa s-a născut <strong>Natur.MD</strong> — din dorința de a avea un loc unde putem găsi produse veritabile, fără amplificatori de gust, fără adaosuri chimice, fără E-uri.' )
	. $h( 'Ce ne dorim' )
	. $p( 'Dorim să mâncăm o bucățică de carne adevărată și să bem un pahar de lapte adevărat. Vrem unt, nu margarină! Știm că și dumneavoastră sunteți ca noi — iar dacă încă nu, veți fi cu siguranță.' )
	. $h( 'Cum lucrăm' )
	. $ul(
		array(
			'Selectăm produse de la gospodari și mici producători din Moldova.',
			'Vizităm fiecare gospodărie ca să vedem cu ochii noștri condițiile în care sunt crescute animalele și păsările.',
			'Refuzăm produsele obținute prin cruzime față de animale sau prin exploatarea oamenilor.',
			'Livrăm în Chișinău în 1–48 de ore, iar plata se face la primirea comenzii.',
		)
	)
	. $p( 'Noi spunem <strong>NU</strong> alimentelor toxice și <strong>DA</strong> produselor naturale, crescute cu grijă și făcute cu drag, ca pentru propria familie.' )
	. "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>„Ai grijă de corpul tău, pentru că este singurul loc în care va trebui să trăiești.”</p>\n<!-- /wp:paragraph --><cite>Jim Rohn</cite></blockquote>\n<!-- /wp:quote -->\n\n"
	. $p( '<strong>Bine ați venit în familia Natur.MD!</strong>' );

$delivery = $p( 'Natur.MD este o prezentare de produse NATURALE, selectate de la cei mai buni gospodari de la țară și de la mici producători.' )
	. $h( 'Costul livrării' )
	. $ul(
		array(
			'<strong>Gratuit</strong> pentru comenzile de la [natur_livrare_gratuita].',
			'<strong>[natur_cost_livrare]</strong> pentru comenzile sub [natur_livrare_gratuita].',
		)
	)
	. $h( 'Plata' )
	. $p( 'Achitarea comenzii se face în momentul primirii coletului de la agentul de livrare sau de la producător.' )
	. $h( 'Termenul de livrare' )
	. $p( 'Livrăm în 1–48 de ore, în funcție de produs, din momentul confirmării telefonice a comenzii. În prezent livrăm în raza mun. Chișinău.' )
	. $h( 'Comenzi din afara mun. Chișinău' )
	. $p( 'Comenzile din afara Chișinăului se expediază prin Poșta Moldovei, cu condiția ca produsele comandate să nu fie perisabile.' )
	. $h( 'Recepția produselor' )
	. $p( 'Dacă la recepție observați că produsele nu corespund comenzii, le puteți refuza, iar noi vom lua măsurile care se impun. Achitarea comenzii către agentul de livrare reprezintă acceptul că produsele au ajuns în stare bună și corespund cantitativ și calitativ comenzii.' )
	. $h( 'Cum comand?' )
	. $ul(
		array(
			'Online, pe site — adăugați produsele în coș și finalizați comanda (nu este obligatoriu să vă creați cont).',
			"La telefon: [natur_telefon].",
			"Pe <a href=\"$messenger\">Facebook Messenger</a>.",
		)
	)
	. $p( "Pentru întrebări ne puteți scrie la [natur_email] sau suna la [natur_telefon]." );

// Pagina Contact fără formular: formularul Contact Form 7 creat anterior se șterge, iar modulul se dezactivează.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$old_form = (int) get_option( 'natur_contact_form_id' );
if ( $old_form && 'wpcf7_contact_form' === get_post_type( $old_form ) ) {
	wp_delete_post( $old_form, true );
}
delete_option( 'natur_contact_form_id' );
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	deactivate_plugins( 'contact-form-7/wp-contact-form-7.php' );
}

// Contact: widgeturile Elementor „Natur: Carduri de contact” și „Natur: Întrebări frecvente” (textele se editează în Elementor).
$faq_items = array();
foreach (
	array(
		array( 'Cât costă livrarea?', '<strong>Gratuit</strong> pentru comenzile de la [natur_livrare_gratuita] și <strong>[natur_cost_livrare]</strong> pentru cele mai mici — atât în Chișinău, cât și prin Poșta Moldovei.' ),
		array( 'În cât timp ajunge comanda?', 'În 1–48 de ore, în funcție de produs, din momentul în care confirmăm comanda la telefon.' ),
		array( 'Livrați și în afara Chișinăului?', 'Da, prin Poșta Moldovei, pentru produsele neperisabile. Produsele perisabile — carne, lactate, ouă — le livrăm doar în Chișinău.' ),
		array( 'Cum plătesc?', 'La primirea coletului, în numerar. Nu plătești nimic în avans.' ),
		array( 'Trebuie să-mi fac cont ca să comand?', 'Nu. Adaugi produsele în coș și finalizezi comanda. Contul e opțional — îți păstrează istoricul comenzilor și adresele.' ),
		array( 'Pot comanda la telefon sau pe Messenger?', "Da. Sună la [natur_telefon] sau scrie-ne pe <a href=\"$messenger\">Messenger</a> — notăm comanda și stabilim împreună ora livrării." ),
		array( 'Ce fac dacă un produs nu e în regulă?', 'Verifică coletul în prezența curierului: dacă produsele nu corespund comenzii, le poți refuza pe loc, fără costuri. O problemă de calitate descoperită mai târziu ne-o semnalezi în cel mult 24 de ore, cu o fotografie. Detalii în <a href="/politica-de-retur/">Politica de retur</a>.' ),
	) as [ $q, $a ]
) {
	$faq_items[] = array( '_id' => natur_eid(), 'question' => $q, 'answer' => "<p>$a</p>" );
}
$contact = '';

$terms = $p( 'Prezentele condiții reglementează utilizarea site-ului natur.md și plasarea comenzilor prin intermediul acestuia. Prin plasarea unei comenzi confirmați că ați citit și acceptați aceste condiții.' )
	. $h( '1. Comenzi' )
	. $p( 'Comanda devine fermă după confirmarea telefonică de către echipa Natur.MD. Ne rezervăm dreptul de a refuza sau anula comenzi în cazul în care produsele nu mai sunt disponibile; în acest caz veți fi anunțat(ă) imediat.' )
	. $h( '2. Prețuri' )
	. $p( 'Prețurile sunt afișate în lei moldovenești (MDL) și includ toate taxele aplicabile. Costul livrării este afișat separat în coș și la finalizarea comenzii.' )
	. $h( '3. Livrare și plată' )
	. $p( 'Condițiile de livrare și plată sunt descrise pe pagina <a href="/livrare-si-plata/">Livrare și plată</a>. Plata se face la primirea comenzii.' )
	. $h( '4. Recepție și retur' )
	. $p( 'Verificați produsele la recepție. Produsele alimentare perisabile nu pot fi returnate după recepție, cu excepția cazurilor în care nu corespund comenzii sau calității declarate. Detalii în <a href="/politica-de-retur/">Politica de retur</a>.' )
	. $h( '5. Date personale' )
	. $p( 'Datele personale sunt prelucrate conform <a href="/politica-de-confidentialitate/">Politicii de confidențialitate</a>.' )
	. $h( '6. Contact' )
	. $p( "Pentru orice întrebare: [natur_telefon], [natur_email]." );

$returns = $p( 'Ne dorim ca fiecare comandă să vă aducă bucurie. Dacă ceva nu este în regulă, suntem aici să rezolvăm.' )
	. $h( 'La recepție' )
	. $p( 'Verificați coletul în prezența curierului. Dacă produsele nu corespund comenzii (cantitate, tip, termen de valabilitate, aspect), le puteți refuza pe loc, fără costuri.' )
	. $h( 'După recepție' )
	. $ul(
		array(
			'<strong>Produse alimentare perisabile</strong> (carne, lactate, ouă etc.): nu pot fi returnate după recepție, cu excepția problemelor de calitate semnalate în cel mult 24 de ore, cu fotografie.',
			'<strong>Produse neperisabile, nedesfăcute</strong> (cosmetice, suplimente, semințe, ustensile): pot fi returnate în termen de 14 zile de la primire, în ambalajul original.',
		)
	)
	. $h( 'Rambursarea' )
	. $p( 'Rambursăm contravaloarea produselor returnate în cel mult 14 zile de la acceptarea returului, în numerar sau prin transfer bancar.' )
	. $p( "Pentru un retur, sunați la [natur_telefon] sau scrieți la [natur_email]." );

$privacy = $p( 'Natur.MD respectă confidențialitatea datelor dumneavoastră și le prelucrează în conformitate cu Legea nr. 133/2011 privind protecția datelor cu caracter personal.' )
	. $h( 'Ce date colectăm' )
	. $ul(
		array(
			'Datele din comandă: nume, telefon, e-mail, adresa de livrare.',
			'Datele contului (dacă vă creați cont): istoricul comenzilor și adresele salvate.',
			'Mesajele pe care ni le trimiteți prin e-mail sau Messenger.',
			'Date tehnice: cookie-uri necesare funcționării coșului și a contului, precum și sursa vizitei (de exemplu, o căutare Google sau Facebook), salvată împreună cu comanda. Detalii în <a href="/politica-de-cookies/">Politica de cookies</a>.',
		)
	)
	. $h( 'De ce le folosim' )
	. $p( 'Exclusiv pentru procesarea și livrarea comenzilor, comunicarea cu dumneavoastră și îndeplinirea obligațiilor legale. Nu vindem și nu transmitem datele altor persoane, cu excepția curierului sau a Poștei Moldovei, strict pentru livrare.' )
	. $h( 'Cât timp le păstrăm' )
	. $p( 'Datele comenzilor se păstrează pe durata impusă de legislația contabilă; datele contului — până la ștergerea acestuia.' )
	. $h( 'Drepturile dumneavoastră' )
	. $p( "Aveți dreptul de acces, rectificare, ștergere și opoziție. Scrieți-ne la [natur_email] și vom răspunde în cel mult 15 zile." );

// Cookie-urile listate sunt cele setate efectiv de site (WordPress, WooCommerce cu „Order attribution”); actualizați lista la orice serviciu nou.
$cookies = $p( 'Cookie-urile sunt fișiere mici pe care site-ul le salvează în browserul dumneavoastră, ca să țină minte, de exemplu, ce ați pus în coș. Natur.MD folosește doar cookie-uri proprii, strict pentru funcționarea magazinului.' )
	. $h( 'Cookie-uri necesare' )
	. $p( 'Fără ele, coșul și contul nu pot funcționa.' )
	. $ul(
		array(
			'<strong>wp_woocommerce_session_…</strong> — păstrează coșul de cumpărături de la o pagină la alta. Durată: 2 zile.',
			'<strong>woocommerce_cart_hash</strong>, <strong>woocommerce_items_in_cart</strong> — arată site-ului când s-a schimbat conținutul coșului. Durată: până la închiderea browserului.',
			'<strong>wordpress_logged_in_…</strong>, <strong>wordpress_sec_…</strong> — vă țin autentificat în „Contul meu”. Durată: până la închiderea browserului sau 14 zile, dacă bifați „Ține-mă minte”.',
			'<strong>wordpress_test_cookie</strong> — verifică, la autentificare, dacă browserul acceptă cookie-uri. Durată: până la închiderea browserului.',
		)
	)
	. $h( 'Sursa comenzilor' )
	. $ul(
		array(
			'<strong>sbjs_…</strong> (sbjs_first, sbjs_current, sbjs_session și altele) — notează de unde ați ajuns pe site (o căutare Google, Facebook, un link direct) și tipul dispozitivului, ca să știm ce canal ne-a adus o comandă. Informația se salvează doar împreună cu comanda, pe site-ul nostru, și nu este transmisă altor companii. Durată: sbjs_session — 30 de minute, celelalte — până la închiderea browserului.',
		)
	)
	. $h( 'Recenzii' )
	. $ul(
		array(
			'<strong>comment_author_…</strong>, <strong>comment_author_email_…</strong> — doar dacă lăsați o recenzie și alegeți să vă salvăm numele și e-mailul pentru data viitoare. Durată: aproximativ 1 an.',
		)
	)
	. $h( 'Memoria browserului' )
	. $p( 'Pe lângă cookie-uri, site-ul păstrează în memoria browserului (localStorage și sessionStorage) o copie a coșului mic din antet (<strong>wc_cart_hash_…</strong>, <strong>wc_fragments_…</strong>), ca să se afișeze rapid pe fiecare pagină.' )
	. $h( 'Ce nu folosim' )
	. $p( 'Nu folosim cookie-uri de publicitate și nici instrumente de analiză ale altor companii (de exemplu, Google Analytics sau Facebook Pixel).' )
	. $h( 'Conținut de pe alte site-uri' )
	. $p( 'Videoclipurile cu rețete se încarcă de pe Instagram sau YouTube doar după ce apăsați pe ele. Din acel moment, platforma respectivă poate folosi propriile cookie-uri, conform politicii ei; YouTube se încarcă în modul cu confidențialitate sporită (youtube-nocookie.com). Butoanele Facebook, Instagram și Messenger sunt simple linkuri și nu încarcă nimic până nu le accesați.' )
	. $h( 'Cum controlați cookie-urile' )
	. $p( 'Puteți vedea, bloca sau șterge cookie-urile din setările browserului, de regulă în secțiunea „Confidențialitate” sau „Securitate”. Dacă blocați cookie-urile necesare, coșul și autentificarea în cont nu vor funcționa.' )
	. $h( 'Mai multe informații' )
	. $p( 'Cum prelucrăm datele personale citiți în <a href="/politica-de-confidentialitate/">Politica de confidențialitate</a>. Pentru întrebări: [natur_telefon], [natur_email].' );

$pages = array();
$pages['home']     = natur_page( 'acasa', 'Acasă', '' );
$pages['about']    = natur_page( 'despre-noi', 'Despre noi', $about, array( 'post_excerpt' => 'Cum a început Natur.MD și de ce alegem doar mâncare adevărată' ) );
$pages['delivery'] = natur_page( 'livrare-si-plata', 'Livrare și plată', $delivery, array( 'post_excerpt' => 'Livrare în 1–48 de ore, gratuită de la [natur_livrare_gratuita]. Plătești la primire.' ) );
$pages['contact']  = natur_page( 'contact', 'Contact', $contact, array( 'post_excerpt' => 'Sună-ne sau scrie-ne — îți răspundem cu drag, ca unui prieten' ) );
$pages['terms']    = natur_page( 'termeni-si-conditii', 'Termeni și condiții', $terms );

$refund = get_page_by_path( 'refund_returns' );
$pages['returns'] = natur_page( 'politica-de-retur', 'Politica de retur', $returns, $refund ? array( 'ID' => $refund->ID ) : array() );

$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
$pages['privacy'] = natur_page( 'politica-de-confidentialitate', 'Politica de confidențialitate', $privacy, $privacy_id ? array( 'ID' => $privacy_id ) : array() );
update_option( 'wp_page_for_privacy_policy', $pages['privacy'] );
$pages['cookies'] = natur_page( 'politica-de-cookies', 'Politica de cookies', $cookies );
update_option( 'woocommerce_terms_page_id', $pages['terms'] );
// Fără bifă de termeni la comandă: acordul e în textul de sub buton (Personalizare → WooCommerce → Finalizare comandă).
update_option( 'woocommerce_checkout_terms_and_conditions_checkbox_text', '' );
update_option( 'woocommerce_registration_privacy_policy_text', 'Datele tale personale sunt folosite pentru a-ți gestiona contul și comenzile, conform [privacy_policy].' );
update_option( 'woocommerce_checkout_privacy_policy_text', 'Trimițând comanda, ești de acord cu [terms] și cu [privacy_policy].' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pages['home'] );

/* ============================================================ Pagina principală (Elementor) */

$link = static fn( $sku ) => get_permalink( wc_get_product_id_by_sku( $sku ) );
$shop = wc_get_page_permalink( 'shop' );

/*
 * Secțiunile au clase CSS (nt-*) stilizate de tema „natur”. Totul se editează în Elementor: titlurile, textele și butoanele
 * în widgeturile standard, iar colajele, categoriile, pașii, produsele, cifrele și recenziile în widgeturile temei
 * (categoria „Natur.MD”, inc/elementor-widgets.php). Mai jos se dau doar valorile care diferă de cele implicite ale widgetului.
 */
$media = static function ( $sku ) {
	$id = (int) get_post_thumbnail_id( wc_get_product_id_by_sku( $sku ) );
	return array( 'id' => $id, 'url' => (string) wp_get_attachment_url( $id ) );
};
$cat_ids = static fn( array $slugs ) => array_values( array_filter( array_map( static fn( $slug ) => (string) ( get_term_by( 'slug', $slug, 'product_cat' )->term_id ?? '' ), $slugs ) ) );
$rows    = static fn( array $items ) => array_map( static fn( $item ) => array( '_id' => natur_eid() ) + $item, $items );
$el_x = static function ( $classes, array $elements, $inner = true, array $extra = array() ) {
	return el_c( array_merge( array( 'content_width' => 'full', 'css_classes' => trim( 'nt-x ' . $classes ) ), $extra ), $elements, $inner );
};
$el_h = static fn( $text, $tag, $class ) => el_w( 'heading', array( 'title' => $text, 'header_size' => $tag, '_css_classes' => $class ) );
$el_t = static fn( $html, $class = 'nt-lead' ) => el_w( 'text-editor', array( 'editor' => $html, '_css_classes' => $class ) );
$el_btn = static fn( $text, $url, $style ) => el_w( 'button', array( 'text' => $text, 'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ), '_css_classes' => 'nt-ebtn ' . $style ) );
$el_head = static function ( $eyebrow, $title, $link = null, $center = false ) use ( $el_x, $el_h, $el_btn ) {
	$els = array( $el_x( 'nt-sec__titles', array( $el_h( $eyebrow, 'p', 'nt-eyebrow' ), $el_h( $title, 'h2', 'nt-h2' ) ) ) );
	if ( $link ) {
		$els[] = $el_btn( $link[0], $link[1], 'nt-ebtn--ghost nt-ebtn--arrow' );
	}
	return $el_x( 'nt-sec__head' . ( $center ? ' nt-sec__head--center' : '' ), $els );
};

$hero = $el_x(
	'nt-hero',
	array(
		$el_x(
			'nt-hero__in',
			array(
				$el_x(
					'nt-hero__copy',
					array(
						$el_h( '100% natural · de la gospodari din Moldova', 'p', 'nt-hero__badge' ),
						$el_h( 'Mâncare adevărată, <em>direct de la țară</em>', 'h1', 'nt-hero__title' ),
						$el_t( '<p>Ouă de casă, lactate de fermă, carne de pasăre crescută liber și conserve ca la bunica — fără aditivi, fără E-uri. Livrăm în Chișinău în 1–48 de ore, iar plata o faci la primire.</p>', 'nt-hero__lead' ),
						$el_x( 'nt-hero__btns', array( $el_btn( 'Alege produsele', $shop, 'nt-ebtn--dark nt-ebtn--arrow' ), $el_btn( 'Cum funcționează', '#cum-functioneaza', 'nt-ebtn--ghost' ) ) ),
						el_w( 'natur_hero_proof', array() ),
					)
				),
				el_w(
					'natur_hero_art',
					array(
						'main'         => $media( 'NM-57' ),
						'second'       => $media( 'NM-998' ),
						'third'        => $media( 'NM-58' ),
						'pick'         => (string) wc_get_product_id_by_sku( 'NM-58' ),
						'_css_classes' => 'nt-hero__art',
					)
				),
			)
		),
	),
	false
);

$sec_cats = $el_x(
	'nt-sec',
	array(
		$el_head( 'Ce găsești la noi', 'Bunătăți din Moldova, <em>pe categorii</em>', array( 'Toate produsele', $shop ) ),
		el_w( 'natur_categories', array( 'number' => 8 ) ),
	),
	false
);

$sec_how = $el_x(
	'nt-sec',
	array(
		$el_x(
			'nt-how',
			array(
				$el_head( 'Simplu, ca la piață', 'Cum comanzi <em>de la noi</em>', null, true ),
				el_w( 'natur_steps', array() ),
			)
		),
	),
	false,
	array( '_element_id' => 'cum-functioneaza' )
);

$sec_products = $el_x(
	'nt-sec',
	array(
		$el_head( 'Alese pentru tine', 'Proaspete <em>pe rafturile noastre</em>', array( 'Tot magazinul', $shop ) ),
		el_w(
			'natur_product_tabs',
			array(
				'limit' => 8,
				'tabs'  => $rows(
					array(
						array( 'label' => 'Preferatele clienților', 'source' => 'featured', 'orderby' => 'rand' ),
						array( 'label' => 'Proaspăt adăugate', 'source' => 'new', 'orderby' => 'date' ),
						array( 'label' => 'Conserve și paste', 'source' => 'cats', 'cats' => $cat_ids( array( 'conserve', 'paste-fainoase' ) ), 'orderby' => 'rand' ),
						array( 'label' => 'Pentru sănătate', 'source' => 'cats', 'cats' => $cat_ids( array( 'ayurvedice', 'suplimente-alimentare', 'adaosuri-biologic-active' ) ), 'orderby' => 'rand' ),
					)
				),
			)
		),
	),
	false
);

$spot = $el_x(
	'nt-spot',
	array(
		$el_x( 'nt-spot__media', array( el_w( 'natur_product_media', array( 'product_id' => (string) wc_get_product_id_by_sku( 'NM-556' ), 'mode' => '360' ) ) ) ),
		$el_x(
			'nt-spot__copy',
			array(
				$el_h( 'Nou pe Natur.MD', 'p', 'nt-eyebrow' ),
				$el_h( 'Privește produsul <em>din toate unghiurile</em>', 'h2', 'nt-h2' ),
				$el_t( '<p>Paginile produselor au acum prezentare 360° și clipuri video. Rotește borcanul cu mouse-ul sau cu degetul și citește eticheta completă — ingredientele, producătorul și termenul de valabilitate.</p>' ),
				$el_t( '<ul class="nt-checks"><li>Rotire 360° cu degetul sau cu mouse-ul</li><li>Clipuri video cu produsul</li><li>Ecran complet, ca să citești eticheta</li></ul>', 'nt-spot__list' ),
				$el_btn( 'Vezi Untul topit GHEE', $link( 'NM-556' ), 'nt-ebtn--leaf nt-ebtn--arrow' ),
			)
		),
	),
	false
);

/*
 * Rețete video: cartonașele vin din widgetul temei „Natur: Rețete video” (copertă, titlu, link spre reel, produs).
 * Coperțile din tools/data/recipes sunt demonstrative; linkurile duc deocamdată la profilul de Instagram.
 */
$recipes = array();
foreach (
	array(
		array( 'paste-cu-carne-de-prepelita', "Paste\ncu carne de prepeliță", 'NM-20' ),
		array( 'supa-din-prepelita', "Supă din prepeliță\npentru zile răcoroase", 'NM-587' ),
		array( 'prepelita-la-cuptor-cu-legume', "Prepeliță la cuptor\ncu legume", 'NM-772' ),
		array( 'pate-din-carne-de-prepelita', "Pate din carne de prepeliță\n— ideal la micul dejun", 'NM-91' ),
	) as [ $slug, $title, $sku ]
) {
	$img_id    = natur_upload_once( "$data_dir/recipes/reteta-$slug.jpg", 'Rețetă: ' . str_replace( "\n", ' ', $title ) );
	$recipes[] = array(
		'_id'     => natur_eid(),
		'image'   => array( 'id' => $img_id, 'url' => wp_get_attachment_url( $img_id ) ),
		'title'   => $title,
		'link'    => array( 'url' => $ig, 'is_external' => 'on', 'nofollow' => '' ),
		'product' => (string) wc_get_product_id_by_sku( $sku ),
	);
}
$sec_recipes = $el_x(
	'nt-sec nt-recipes-sec',
	array(
		$el_x(
			'nt-sec__head',
			array(
				$el_x(
					'nt-sec__titles',
					array(
						$el_h( 'Din bucătăria noastră', 'p', 'nt-eyebrow' ),
						$el_h( 'Rețete și idei <em>delicioase</em>', 'h2', 'nt-h2' ),
						$el_t( '<p>Descoperă cum poți găti rapid și gustos cu produsele noastre.</p>' ),
					)
				),
				el_w( 'button', array( 'text' => 'Urmărește-ne pe Instagram', 'link' => array( 'url' => $ig, 'is_external' => 'on', 'nofollow' => '' ), '_css_classes' => 'nt-ebtn nt-ebtn--ghost nt-ebtn--ig' ) ),
			)
		),
		el_w( 'natur_recipes', array( 'items' => $recipes ) ),
	),
	false
);

$sec_story = $el_x(
	'nt-sec nt-story',
	array(
		el_w(
			'natur_story_art',
			array(
				'main'   => $media( 'NM-170' ),
				'second' => $media( 'NM-98' ),
				'third'  => $media( 'NM-1004' ),
			)
		),
		$el_x(
			'nt-story__copy',
			array(
				$el_h( 'Povestea noastră', 'p', 'nt-eyebrow' ),
				$el_h( 'Am început cu grija <em>pentru propria familie</em>', 'h2', 'nt-h2' ),
				$el_t( '<p>Când am aflat din ce se produc lactatele ieftine și carnea „frumoasă”, am hotărât: gata, doar mâncare adevărată. Așa s-a născut Natur.MD — produse de la gospodari și mici producători, pe care îi vizităm personal ca să vedem cu ochii noștri cum sunt crescute animalele și păsările.</p>' ),
				el_w( 'natur_stats', array() ),
				$el_btn( 'Citește povestea', get_permalink( $pages['about'] ), 'nt-ebtn--dark nt-ebtn--arrow' ),
			)
		),
	),
	false
);

$sec_reviews = $el_x(
	'nt-sec',
	array(
		$el_head( 'Vorbesc clienții', 'Ce spun cei care <em>au gustat</em>' ),
		el_w( 'natur_reviews', array( 'number' => 10 ) ),
	),
	false
);

$home_data = array( $hero, $sec_cats, $sec_how, $sec_products, $spot, $sec_recipes, $sec_story, $sec_reviews );
update_post_meta( $pages['home'], '_elementor_edit_mode', 'builder' );
update_post_meta( $pages['home'], '_elementor_template_type', 'wp-page' );
update_post_meta( $pages['home'], '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $pages['home'], '_wp_page_template', 'elementor_header_footer' );
update_post_meta( $pages['home'], '_elementor_data', wp_slash( wp_json_encode( $home_data ) ) );
update_post_meta( $pages['home'], '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
// Astra: fără titlu și fără container pe pagina principală
update_post_meta( $pages['home'], 'site-post-title', 'disabled' );
update_post_meta( $pages['home'], 'ast-site-content-layout', 'full-width-container' );
update_post_meta( $pages['home'], 'site-content-style', 'unboxed' );
update_post_meta( $pages['home'], 'site-sidebar-layout', 'no-sidebar' );

// Pagina Contact (Elementor; titlul și subtitlul paginii rămân ale temei)
$contact_data = array(
	$el_x(
		'nt-contact-page',
		array(
			el_w( 'natur_contact', array( 'zone_link' => array( 'url' => get_permalink( $pages['delivery'] ), 'is_external' => '', 'nofollow' => '' ) ) ),
			el_w( 'natur_faq', array( 'items' => $faq_items ) ),
		),
		false,
		array( 'flex_direction' => 'column' )
	),
);
update_post_meta( $pages['contact'], '_elementor_edit_mode', 'builder' );
update_post_meta( $pages['contact'], '_elementor_template_type', 'wp-page' );
update_post_meta( $pages['contact'], '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $pages['contact'], '_wp_page_template', 'default' );
update_post_meta( $pages['contact'], '_elementor_data', wp_slash( wp_json_encode( $contact_data ) ) );

// Setările temei legate de datele acestui magazin (Aspect → Personalizare → Natur.MD)
set_theme_mod( 'mega_promo_page', $pages['delivery'] );
set_theme_mod( 'cookie_page', $pages['cookies'] );
set_theme_mod( 'card_pack_attr', 'pa_ambalare' );
set_theme_mod( 'card_cat_skip', (int) ( get_term_by( 'slug', 'horeca', 'product_cat' )->term_id ?? 0 ) );

// Elementor: culorile și fonturile vin din temă; setări globale ale kitului
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );
update_option( 'elementor_cpt_support', array( 'page', 'post' ) );
update_option( 'elementor_onboarded', true );
update_option( 'elementor_tracker_notice', '1' );
update_option( 'elementor_allow_tracking', 'no' );
$kit_id = (int) get_option( 'elementor_active_kit' );
if ( $kit_id ) {
	$kit = (array) get_post_meta( $kit_id, '_elementor_page_settings', true );
	// Culorile globale Elementor colorează și tema (inc/options.php → nt_palette_map()); se schimbă în Elementor → Setări site.
	// Se scriu o singură dată, ca să nu se piardă culorile alese ulterior în Elementor.
	if ( ! get_option( 'natur_kit_colors' ) ) {
		update_option( 'natur_kit_colors', 1 );
		$kit['system_colors'] = array(
			array( '_id' => 'primary', 'title' => 'Pădure', 'color' => '#1F3D2B' ),
			array( '_id' => 'secondary', 'title' => 'Frunză', 'color' => '#8DC63F' ),
			array( '_id' => 'text', 'title' => 'Text', 'color' => '#4E5C52' ),
			array( '_id' => 'accent', 'title' => 'Gălbenuș', 'color' => '#FFC94A' ),
		);
		$custom               = array_filter( (array) ( $kit['custom_colors'] ?? array() ), static fn( $c ) => ! in_array( $c['_id'] ?? '', array( 'ntbg', 'ntink', 'ntmoss' ), true ) );
		$kit['custom_colors'] = array_merge(
			array(
				array( '_id' => 'ntbg', 'title' => 'Fundal', 'color' => '#FBF7EF' ),
				array( '_id' => 'ntink', 'title' => 'Titluri', 'color' => '#1E2B22' ),
				array( '_id' => 'ntmoss', 'title' => 'Linkuri', 'color' => '#3C6B27' ),
			),
			array_values( $custom )
		);
	}
	$kit['container_width'] = array( 'unit' => 'px', 'size' => 1240 );
	$kit['viewport_md']     = 768;
	$kit['viewport_lg']     = 1025;
	update_post_meta( $kit_id, '_elementor_page_settings', $kit );
}

/* ============================================================ Meniuri */

$make_menu = static function ( $name ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		return $menu->term_id;
	}
	return wp_create_nav_menu( $name );
};
$add_page = static fn( $menu, $page_id, $parent = 0, $title = '' ) => wp_update_nav_menu_item( $menu, 0, array( 'menu-item-object-id' => $page_id, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent, 'menu-item-title' => $title ) );
$add_cat  = static fn( $menu, $term_id, $parent = 0 ) => wp_update_nav_menu_item( $menu, 0, array( 'menu-item-object-id' => $term_id, 'menu-item-object' => 'product_cat', 'menu-item-type' => 'taxonomy', 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ) );

$primary = $make_menu( 'Meniu principal' );
$add_page( $primary, $pages['home'] );
$shop_item = $add_page( $primary, wc_get_page_id( 'shop' ) );
$top_cats  = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_product_cat' ) ), 'orderby' => 'meta_value_num', 'meta_key' => 'order' ) );
foreach ( $top_cats as $t ) {
	$item = $add_cat( $primary, $t->term_id, $shop_item );
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $t->term_id, 'hide_empty' => false ) ) as $child ) {
		if ( $child->count || get_term_children( $child->term_id, 'product_cat' ) ) {
			$add_cat( $primary, $child->term_id, $item );
		}
	}
}
$add_page( $primary, $pages['about'] );
$add_page( $primary, $pages['delivery'] );
$add_page( $primary, $pages['contact'] );

// Confidențialitatea și cookie-urile au rândul lor în bara de jos a subsolului, pe toate paginile.
$footer_menu = $make_menu( 'Meniu subsol' );
foreach ( array( $pages['about'], $pages['delivery'], $pages['returns'], $pages['terms'], $pages['contact'], wc_get_page_id( 'myaccount' ) ) as $pid ) {
	$add_page( $footer_menu, $pid );
}

set_theme_mod( 'nav_menu_locations', array( 'primary' => $primary, 'mobile_menu' => $primary, 'footer_menu' => $footer_menu ) );

/* ============================================================ Astra */

$astra = (array) get_option( 'astra-settings', array() );
$astra = array_merge(
	$astra,
	array(
		// Paleta temei „natur” (fonturile Fraunces + Manrope sunt încărcate local de temă).
		'global-color-palette'        => array( 'palette' => array( '#3C6B27', '#1F3D2B', '#1E2B22', '#4E5C52', '#FBF7EF', '#FFFFFF', '#F3ECDD', '#EAE2D2', '#1E2B22' ) ),
		'body-font-family'            => 'inherit',
		'body-font-weight'            => 'inherit',
		'body-font-variant'           => '',
		'font-size-body'              => array( 'desktop' => 16.5, 'tablet' => 16, 'mobile' => 16, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
		'headings-font-family'        => 'inherit',
		'headings-font-weight'        => 'inherit',
		'headings-font-variant'       => '',
		'load-google-fonts-locally'   => false,
		'preload-local-fonts'         => false,
		'site-content-width'          => 1240,
		'site-layout'                 => 'ast-full-width-layout',
		'site-content-layout'         => 'normal-width-container',
		'site-sidebar-layout'         => 'no-sidebar',
		'single-page-sidebar-layout'  => 'no-sidebar',
		'archive-product-sidebar-layout' => 'no-sidebar',
		'single-product-sidebar-layout' => 'no-sidebar',
		'archive-product-ast-content-layout' => 'normal-width-container',
		'single-product-ast-content-layout' => 'normal-width-container',
		'ast-header-responsive-logo-width' => array( 'desktop' => 170, 'tablet' => 150, 'mobile' => 130 ),
		'display-site-title-responsive'   => array( 'desktop' => 0, 'tablet' => 0, 'mobile' => 0 ),
		'display-site-tagline-responsive' => array( 'desktop' => 0, 'tablet' => 0, 'mobile' => 0 ),
		'header-desktop-items'        => array(
			'popup'   => array( 'popup_content' => array( 'mobile-menu' ) ),
			'above'   => array( 'above_left' => array( 'html-1' ), 'above_left_center' => array(), 'above_center' => array(), 'above_right_center' => array(), 'above_right' => array( 'html-2' ) ),
			'primary' => array( 'primary_left' => array( 'logo' ), 'primary_left_center' => array(), 'primary_center' => array( 'menu-1' ), 'primary_right_center' => array(), 'primary_right' => array( 'search', 'account', 'woo-cart' ) ),
			'below'   => array( 'below_left' => array(), 'below_left_center' => array(), 'below_center' => array(), 'below_right_center' => array(), 'below_right' => array() ),
		),
		'header-mobile-items'         => array(
			'popup'   => array( 'popup_content' => array( 'search', 'mobile-menu', 'html-1' ) ),
			'above'   => array( 'above_left' => array(), 'above_center' => array( 'html-2' ), 'above_right' => array() ),
			'primary' => array( 'primary_left' => array( 'logo' ), 'primary_center' => array(), 'primary_right' => array( 'woo-cart', 'mobile-trigger' ) ),
			'below'   => array( 'below_left' => array(), 'below_center' => array(), 'below_right' => array() ),
		),
		'header-html-1'               => "<p>☎ [natur_telefon] &nbsp;·&nbsp; ✉ [natur_email]</p>",
		'header-html-2'               => '<p>🚚 Livrare gratuită în Chișinău pentru comenzi de la 500 lei</p>',
		'hba-header-height'           => array( 'desktop' => 38, 'tablet' => 34, 'mobile' => 34 ),
		'hba-header-bg-obj-responsive' => array(
			'desktop' => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
			'tablet'  => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
			'mobile'  => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
		),
		'hba-header-separator'        => 0,
		'header-html-1color'         => array( 'desktop' => '#DCE8C8', 'tablet' => '#DCE8C8', 'mobile' => '#DCE8C8' ),
		'header-html-1link-color'    => array( 'desktop' => '#FFFFFF', 'tablet' => '#FFFFFF', 'mobile' => '#FFFFFF' ),
		'header-html-2color'         => array( 'desktop' => '#DCE8C8', 'tablet' => '#DCE8C8', 'mobile' => '#DCE8C8' ),
		'font-size-section-hb-html-1' => array( 'desktop' => 14, 'tablet' => 13, 'mobile' => 13, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
		'font-size-section-hb-html-2' => array( 'desktop' => 14, 'tablet' => 12, 'mobile' => 12, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
		'hb-header-height'            => array( 'desktop' => 84, 'tablet' => 72, 'mobile' => 64 ),
		'header-main-menu-label'      => '',
		'woo-header-cart-click-action' => 'flyout',
		'woo-header-cart-total-display' => true,
		'woo-header-cart-title-display' => false,
		'woo-header-cart-icon'        => 'bag',
		'woo-header-cart-badge-display' => true,
		'header-account-type'         => 'woocommerce',
		'header-account-login-style'  => 'icon',
		'header-account-action-type'  => 'link',
		'header-account-link-type'    => 'default',
		'header-search-box-type'      => 'slide-search',
		'footer-desktop-items'        => array(
			'above'   => array( 'above_1' => array(), 'above_2' => array(), 'above_3' => array(), 'above_4' => array(), 'above_5' => array() ),
			'primary' => array( 'primary_1' => array( 'html-1' ), 'primary_2' => array( 'widget-1' ), 'primary_3' => array( 'menu' ), 'primary_4' => array( 'html-2' ), 'primary_5' => array() ),
			'below'   => array( 'below_1' => array( 'copyright' ), 'below_2' => array( 'social-icons-1' ), 'below_3' => array(), 'below_4' => array(), 'below_5' => array() ),
		),
		'hb-footer-column'            => '4',
		'hb-footer-layout'            => array( 'desktop' => '4-equal', 'tablet' => '2-equal', 'mobile' => 'full' ),
		'hbb-footer-column'           => '2',
		'hbb-footer-layout'           => array( 'desktop' => '2-equal', 'tablet' => '2-equal', 'mobile' => 'full' ),
		'hb-footer-bg-obj-responsive' => array(
			'desktop' => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
			'tablet'  => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
			'mobile'  => array( 'background-color' => '#1F2A14', 'background-type' => 'color' ),
		),
		'hbb-footer-bg-obj-responsive' => array(
			'desktop' => array( 'background-color' => '#161E0E', 'background-type' => 'color' ),
			'tablet'  => array( 'background-color' => '#161E0E', 'background-type' => 'color' ),
			'mobile'  => array( 'background-color' => '#161E0E', 'background-type' => 'color' ),
		),
		'hb-footer-main-sep'          => 0,
		'hbb-footer-separator'        => 0,
		'hbb-footer-top-border-color' => '#161E0E',
		'hb-footer-vertical-alignment' => 'flex-start',
		'footer-html-1'               => '<p><strong style="color:#fff;font-size:20px">natur<span style="color:#8DC732">.md</span></strong></p><p>Produse naturale selectate de la gospodari și mici producători din Moldova. Fără aditivi, fără E-uri — mâncare adevărată, ca pentru propria familie.</p>',
		'footer-html-2'               => "<p><strong style=\"color:#fff\">Contact</strong></p><p>☎ [natur_telefon]<br>✉ [natur_email]<br>mun. Chișinău, Republica Moldova</p>",
		'footer-html-1color'         => array( 'desktop' => '#C9D3BC', 'tablet' => '#C9D3BC', 'mobile' => '#C9D3BC' ),
		'footer-html-2color'         => array( 'desktop' => '#C9D3BC', 'tablet' => '#C9D3BC', 'mobile' => '#C9D3BC' ),
		'footer-html-1link-color'    => array( 'desktop' => '#FFFFFF', 'tablet' => '#FFFFFF', 'mobile' => '#FFFFFF' ),
		'footer-html-2link-color'    => array( 'desktop' => '#FFFFFF', 'tablet' => '#FFFFFF', 'mobile' => '#FFFFFF' ),
		'footer-menu-alignment'       => array( 'desktop' => 'flex-start', 'tablet' => 'flex-start', 'mobile' => 'flex-start' ),
		'footer-menu-layout'          => array( 'desktop' => 'vertical', 'tablet' => 'vertical', 'mobile' => 'vertical' ),
		'footer-menu-color-responsive' => array( 'desktop' => '#C9D3BC', 'tablet' => '#C9D3BC', 'mobile' => '#C9D3BC' ),
		'footer-menu-h-color-responsive' => array( 'desktop' => '#FFFFFF', 'tablet' => '#FFFFFF', 'mobile' => '#FFFFFF' ),
		'footer-copyright-editor'     => '© [current_year] Natur.MD — produse naturale din Moldova. Toate drepturile rezervate.',
		'footer-copyright-color'      => '#9AA88A',
		'footer-social-icons-1' => array(
			'items' => array(
				array( 'id' => 'facebook', 'enabled' => true, 'source' => 'icon', 'url' => $fb, 'color' => '#C9D3BC', 'background' => '', 'icon' => 'facebook', 'label' => 'Facebook' ),
			),
		),
		'footer-social-1-color'       => array( 'desktop' => '#C9D3BC', 'tablet' => '#C9D3BC', 'mobile' => '#C9D3BC' ),
		'footer-social-1-h-color'     => array( 'desktop' => '#FFFFFF', 'tablet' => '#FFFFFF', 'mobile' => '#FFFFFF' ),
		'footer-social-1-alignment'   => array( 'desktop' => 'right', 'tablet' => 'right', 'mobile' => 'center' ),
		'footer-widget-alignment-1'   => array( 'desktop' => 'left', 'tablet' => 'left', 'mobile' => 'left' ),
		'footer-widget-1-title-color' => '#FFFFFF',
		'footer-widget-1-color'       => '#C9D3BC',
		'footer-widget-1-link-color'  => '#C9D3BC',
		'footer-widget-1-link-h-color' => '#FFFFFF',
		'shop-grid'                   => array( 'desktop' => 4, 'tablet' => 3, 'mobile' => 2 ),
		'shop-no-of-products'         => 12,
		'shop-product-structure'      => array( 'category', 'title', 'ratings', 'price', 'add_cart' ),
		'shop-add-to-cart-action'     => 'default',
		'single-product-structure'    => array( 'category', 'title', 'ratings', 'price', 'short_desc', 'add_cart', 'meta' ),
		'single-product-sticky-summary' => true,
		'single-product-related-display' => true,
		'single-product-up-sells-display' => true,
		'woo-enable-free-shipping-progress' => true,
		'scroll-to-top-enable'         => false,
		'button-radius-fields'        => array( 'desktop' => array( 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999 ), 'tablet' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ), 'mobile' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ), 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
		'theme-button-padding'        => array( 'desktop' => array( 'top' => 12, 'right' => 24, 'bottom' => 12, 'left' => 24 ), 'tablet' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ), 'mobile' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ), 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
	)
);
update_option( 'astra-settings', $astra );

/* ============================================================ Widgeturi (subsol + sidebar magazin) */

$cat_links = '';
foreach ( array_slice( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ), 0, 8 ) as $t ) {
	$cat_links .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
}
$blocks = array(
	1 => '<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Categorii</h4><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list">' . $cat_links . '</ul><!-- /wp:list -->',
	2 => '<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Categorii</h4><!-- /wp:heading --><!-- wp:woocommerce/product-categories {"hasCount":true} /-->',
);
update_option( 'widget_block', array( 1 => array( 'content' => $blocks[1] ), 2 => array( 'content' => $blocks[2] ), '_multiwidget' => 1 ) );
$sidebars                       = (array) get_option( 'sidebars_widgets', array() );
$sidebars['footer-widget-1']    = array( 'block-1' );
$sidebars['astra-woo-shop-sidebar'] = array( 'block-2' );
$sidebars['sidebar-1']          = array();
foreach ( $sidebars as $k => $v ) {
	if ( is_array( $v ) && 'wp_inactive_widgets' !== $k && ! in_array( $k, array( 'footer-widget-1', 'astra-woo-shop-sidebar' ), true ) ) {
		$sidebars[ $k ] = array();
	}
}
update_option( 'sidebars_widgets', $sidebars );

/* ============================================================ Finalizare */

flush_rewrite_rules( false );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
wc_delete_product_transients();
delete_transient( 'wc_term_counts' );
WP_CLI::success( 'Configurare finalizată. Pagini: ' . wp_json_encode( $pages ) );
