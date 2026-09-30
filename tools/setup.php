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
$phone    = '060 89 30 00';
$phone_l  = '+37360893000';
$email    = 'contact@natur.md';
$fb       = 'https://www.facebook.com/natur.md';

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

// Logo & iconiță
$logo_id = natur_upload_once( "$data_dir/logo.png", 'Natur.MD logo' );
$icon_id = natur_upload_once( "$data_dir/site-icon.png", 'Natur.MD iconiță' );
set_theme_mod( 'custom_logo', $logo_id );
update_option( 'site_icon', $icon_id );

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
	'woocommerce_enable_checkout_login_reminder' => 'yes',
	'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
	'woocommerce_enable_myaccount_registration' => 'yes',
	'woocommerce_registration_generate_password' => 'yes',
	'woocommerce_checkout_phone_field'    => 'required',
	'woocommerce_checkout_company_field'  => 'hidden',
	'woocommerce_checkout_address_2_field' => 'optional',
	'woocommerce_ship_to_destination'     => 'billing',
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
			'description'        => 'Achitați comanda în numerar la primirea coletului de la curier sau producător.',
			'instructions'       => 'Veți achita comanda la livrare. Vă vom suna pentru confirmarea comenzii.',
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
			'<strong>Gratuit</strong> pentru comenzile de la 500 lei.',
			'<strong>50 lei</strong> pentru comenzile sub 500 lei.',
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
			"La telefon: <a href=\"tel:$phone_l\">$phone</a>.",
			"Pe <a href=\"https://m.me/natur.md\">Facebook Messenger</a>.",
		)
	)
	. $p( "Pentru întrebări ne puteți scrie la <a href=\"mailto:$email\">$email</a> sau suna la <a href=\"tel:$phone_l\">$phone</a>." );

// Formular de contact (Contact Form 7)
$form_id = (int) get_option( 'natur_contact_form_id' );
if ( ! $form_id || ! get_post( $form_id ) ) {
	$form_id = wp_insert_post( array( 'post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'post_title' => 'Formular contact' ) );
	update_option( 'natur_contact_form_id', $form_id );
}
update_post_meta(
	$form_id,
	'_form',
	"<label>Numele dvs. *\n    [text* your-name autocomplete:name]</label>\n\n<label>E-mail *\n    [email* your-email autocomplete:email]</label>\n\n<label>Telefon\n    [tel your-phone autocomplete:tel]</label>\n\n<label>Mesaj *\n    [textarea* your-message]</label>\n\n[acceptance consent] Sunt de acord cu prelucrarea datelor personale conform <a href=\"/politica-de-confidentialitate/\">Politicii de confidențialitate</a>. [/acceptance]\n\n[submit \"Trimite mesajul\"]"
);
update_post_meta(
	$form_id,
	'_mail',
	array(
		'active'             => true,
		'subject'            => '[_site_title] Mesaj nou de la [your-name]',
		'sender'             => '[_site_title] <wordpress@[_site_domain]>',
		'recipient'          => $email,
		'body'               => "De la: [your-name] <[your-email]>\nTelefon: [your-phone]\n\n[your-message]\n\n-- \nTrimis de pe [_site_title] ([_site_url])",
		'additional_headers' => 'Reply-To: [your-email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	)
);
update_post_meta( $form_id, '_locale', 'ro_RO' );
$form_hash = function_exists( 'wpcf7_contact_form' ) && wpcf7_contact_form( $form_id ) ? wpcf7_contact_form( $form_id )->hash() : '';

$contact = "<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"40%\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:40%\">"
	. $h( 'Date de contact', 3 )
	. $p( "<strong>Telefon pentru comenzi:</strong><br><a href=\"tel:$phone_l\">$phone</a>" )
	. $p( "<strong>E-mail:</strong><br><a href=\"mailto:$email\">$email</a>" )
	. $p( "<strong>Facebook:</strong><br><a href=\"$fb\">facebook.com/natur.md</a>" )
	. $p( '<strong>Zona de livrare:</strong><br>mun. Chișinău; restul Moldovei prin Poșta Moldovei (produse neperisabile).' )
	. "</div>\n<!-- /wp:column -->\n\n<!-- wp:column {\"width\":\"60%\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:60%\">"
	. $h( 'Scrieți-ne', 3 )
	. "<!-- wp:shortcode -->\n[contact-form-7 id=\"" . ( $form_hash ? $form_hash : $form_id ) . "\" title=\"Formular contact\"]\n<!-- /wp:shortcode -->\n"
	. "</div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->\n";

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
	. $p( "Pentru orice întrebare: <a href=\"tel:$phone_l\">$phone</a>, <a href=\"mailto:$email\">$email</a>." );

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
	. $p( "Pentru un retur, sunați la <a href=\"tel:$phone_l\">$phone</a> sau scrieți la <a href=\"mailto:$email\">$email</a>." );

$privacy = $p( 'Natur.MD respectă confidențialitatea datelor dumneavoastră și le prelucrează în conformitate cu Legea nr. 133/2011 privind protecția datelor cu caracter personal.' )
	. $h( 'Ce date colectăm' )
	. $ul(
		array(
			'Datele din comandă: nume, telefon, e-mail, adresa de livrare.',
			'Datele contului (dacă vă creați cont): istoricul comenzilor și adresele salvate.',
			'Mesajele trimise prin formularul de contact.',
			'Date tehnice: cookie-uri necesare funcționării coșului și sesiunii.',
		)
	)
	. $h( 'De ce le folosim' )
	. $p( 'Exclusiv pentru procesarea și livrarea comenzilor, comunicarea cu dumneavoastră și îndeplinirea obligațiilor legale. Nu vindem și nu transmitem datele altor persoane, cu excepția curierului sau a Poștei Moldovei, strict pentru livrare.' )
	. $h( 'Cât timp le păstrăm' )
	. $p( 'Datele comenzilor se păstrează pe durata impusă de legislația contabilă; datele contului — până la ștergerea acestuia.' )
	. $h( 'Drepturile dumneavoastră' )
	. $p( "Aveți dreptul de acces, rectificare, ștergere și opoziție. Scrieți-ne la <a href=\"mailto:$email\">$email</a> și vom răspunde în cel mult 15 zile." );

$pages = array();
$pages['home']     = natur_page( 'acasa', 'Acasă', '' );
$pages['about']    = natur_page( 'despre-noi', 'Despre noi', $about );
$pages['delivery'] = natur_page( 'livrare-si-plata', 'Livrare și plată', $delivery );
$pages['contact']  = natur_page( 'contact', 'Contact', $contact );
$pages['terms']    = natur_page( 'termeni-si-conditii', 'Termeni și condiții', $terms );

$refund = get_page_by_path( 'refund_returns' );
$pages['returns'] = natur_page( 'politica-de-retur', 'Politica de retur', $returns, $refund ? array( 'ID' => $refund->ID ) : array() );

$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
$pages['privacy'] = natur_page( 'politica-de-confidentialitate', 'Politica de confidențialitate', $privacy, $privacy_id ? array( 'ID' => $privacy_id ) : array() );
update_option( 'wp_page_for_privacy_policy', $pages['privacy'] );
update_option( 'woocommerce_terms_page_id', $pages['terms'] );
update_option( 'woocommerce_checkout_terms_and_conditions_checkbox_text', 'Am citit și sunt de acord cu [terms]' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pages['home'] );

/* ============================================================ Pagina principală (Elementor) */

$img = static function ( $sku, $size = 'large' ) {
	$pid = wc_get_product_id_by_sku( $sku );
	$id  = $pid ? get_post_thumbnail_id( $pid ) : 0;
	return array( 'id' => $id, 'url' => $id ? wp_get_attachment_image_url( $id, $size ) : '' );
};
$link = static fn( $sku ) => get_permalink( wc_get_product_id_by_sku( $sku ) );
$shop = wc_get_page_permalink( 'shop' );

$hero_img = $img( 'NM-57', 'full' ); // ouă de casă
$hero = el_c(
	array(
		'content_width'               => 'boxed',
		'flex_direction'              => 'column',
		'flex_justify_content'        => 'center',
		'flex_align_items'            => 'flex-start',
		'min_height'                  => array( 'unit' => 'px', 'size' => 560 ),
		'min_height_mobile'           => array( 'unit' => 'px', 'size' => 460 ),
		'flex_gap'                    => array( 'unit' => 'px', 'size' => 20, 'column' => '20', 'row' => '20' ),
		'padding'                     => el_pad( 80, 20, 80, 20 ),
		'background_background'       => 'classic',
		'background_image'            => $hero_img,
		'background_position'         => 'center center',
		'background_size'             => 'cover',
		'background_overlay_background' => 'gradient',
		'background_overlay_color'    => 'rgba(20,28,10,0.88)',
		'background_overlay_color_b'  => 'rgba(20,28,10,0.25)',
		'background_overlay_gradient_angle' => array( 'unit' => 'deg', 'size' => 90 ),
		'background_overlay_opacity'  => array( 'unit' => 'px', 'size' => 1 ),
	),
	array(
		el_w( 'heading', array( 'title' => '100% natural · de la gospodari din Moldova', 'header_size' => 'p', 'align' => 'left', 'title_color' => '#8DC732', 'typography_typography' => 'custom', 'typography_font_weight' => '700', 'typography_text_transform' => 'uppercase', 'typography_letter_spacing' => array( 'unit' => 'px', 'size' => 1.5 ), 'typography_font_size' => array( 'unit' => 'px', 'size' => 14 ) ) ),
		el_heading( 'Mâncare adevărată,<br>direct de la țară', 'h1', 'left', '#FFFFFF', array( 56, 36 ) ),
		el_text( '<p>Carne de pasăre crescută liber, lactate de fermă, ouă de casă, conserve și semințe pentru germinare — fără aditivi, fără E-uri. Livrare în Chișinău în 1–48 de ore, plata la primire.</p>', 'left', 'rgba(255,255,255,0.88)' ),
		el_c(
			array( 'content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => array( 'unit' => 'px', 'size' => 12, 'column' => '12', 'row' => '12' ), 'padding' => el_pad( 8, 0, 0, 0 ) ),
			array( el_button( 'Vezi magazinul', $shop, 'primary', 'left' ), el_button( 'Povestea noastră', get_permalink( $pages['about'] ), 'light', 'left' ) ),
			true
		),
	)
);

$benefit = static function ( $icon, $title, $text ) {
	return el_w(
		'icon-box',
		array(
			'selected_icon'     => array( 'value' => $icon, 'library' => 'fa-solid' ),
			'view'              => 'stacked',
			'shape'             => 'circle',
			'position'          => 'top',
			'title_text'        => $title,
			'description_text'  => $text,
			'title_size'        => 'h3',
			'primary_color'     => '#EEF6E0',
			'secondary_color'   => '#4E7D14',
			'icon_size'         => array( 'unit' => 'px', 'size' => 26 ),
			'icon_padding'      => array( 'unit' => 'px', 'size' => 18 ),
			'title_color'       => '#1F1D1A',
			'title_typography_typography' => 'custom',
			'title_typography_font_size'  => array( 'unit' => 'px', 'size' => 18 ),
			'title_typography_font_weight' => '700',
			'_flex_size'        => 'grow',
			'_element_width'    => 'initial',
			'_element_custom_width' => array( 'unit' => '%', 'size' => 22 ),
			'_element_custom_width_tablet' => array( 'unit' => '%', 'size' => 45 ),
			'_element_custom_width_mobile' => array( 'unit' => '%', 'size' => 100 ),
		)
	);
};
$benefits = el_c(
	array( 'content_width' => 'boxed', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_justify_content' => 'space-between', 'flex_gap' => array( 'unit' => 'px', 'size' => 24, 'column' => '24', 'row' => '24' ), 'padding' => el_pad( 48, 20, 48, 20 ), 'background_background' => 'classic', 'background_color' => '#FFFFFF', 'border_border' => 'solid', 'border_width' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '1', 'left' => '0', 'isLinked' => false ), 'border_color' => '#E5E1D6' ),
	array(
		$benefit( 'fas fa-leaf', '100% natural', 'Fără conservanți, amplificatori de gust sau E-uri.' ),
		$benefit( 'fas fa-truck', 'Livrare 1–48 ore', 'În mun. Chișinău, iar în restul țării prin Poșta Moldovei.' ),
		$benefit( 'fas fa-gift', 'Gratuit de la 500 lei', 'Sub 500 lei, livrarea costă doar 50 lei.' ),
		$benefit( 'fas fa-hand-holding-usd', 'Plata la primire', 'Verificați produsele și achitați la livrare.' ),
	)
);

$sec_cats = el_section(
	array(
		el_heading( 'Categorii', 'h2', 'center', '', array( 36, 28 ) ),
		el_text( '<p>Produse selectate de la gospodării verificate personal de echipa noastră.</p>' ),
		el_w( 'shortcode', array( 'shortcode' => '[product_categories number="8" parent="0" columns="4" orderby="count" order="desc" hide_empty="1"]' ) ),
		el_button( 'Toate categoriile', $shop, 'dark' ),
	),
	'#F5F3EC'
);

$sec_featured = el_section(
	array(
		el_heading( 'Recomandate de noi', 'h2', 'center', '', array( 36, 28 ) ),
		el_text( '<p>Cele mai iubite produse ale clienților Natur.MD.</p>' ),
		el_w( 'shortcode', array( 'shortcode' => '[products visibility="featured" limit="8" columns="4" orderby="rand"]' ) ),
	)
);

$spin = el_c(
	array( 'content_width' => 'boxed', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'flex_gap' => array( 'unit' => 'px', 'size' => 48, 'column' => '48', 'row' => '32' ), 'padding' => el_pad( 72, 20, 72, 20 ), 'padding_mobile' => el_pad( 48, 16, 48, 16 ), 'background_background' => 'classic', 'background_color' => '#1F2A14' ),
	array(
		el_c(
			array( 'content_width' => 'full', 'width' => array( 'unit' => '%', 'size' => 45 ), 'width_mobile' => array( 'unit' => '%', 'size' => 100 ), 'flex_direction' => 'column', 'border_radius' => array( 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ), 'overflow' => 'hidden' ),
			array( el_w( 'natur_product_media', array( 'product_id' => (string) wc_get_product_id_by_sku( 'NM-556' ), 'mode' => '360' ) ) ),
			true
		),
		el_c(
			array( 'content_width' => 'full', 'flex_size' => 'grow', 'width' => array( 'unit' => '%', 'size' => 48 ), 'width_mobile' => array( 'unit' => '%', 'size' => 100 ), 'flex_direction' => 'column', 'flex_gap' => array( 'unit' => 'px', 'size' => 16, 'column' => '16', 'row' => '16' ) ),
			array(
				el_w( 'heading', array( 'title' => 'Nou pe Natur.MD', 'header_size' => 'p', 'align' => 'left', 'title_color' => '#8DC732', 'typography_typography' => 'custom', 'typography_font_weight' => '700', 'typography_text_transform' => 'uppercase', 'typography_letter_spacing' => array( 'unit' => 'px', 'size' => 1.5 ), 'typography_font_size' => array( 'unit' => 'px', 'size' => 14 ) ) ),
				el_heading( 'Priviți produsul din toate unghiurile', 'h2', 'left', '#FFFFFF', array( 36, 28 ) ),
				el_text( '<p>Paginile produselor au acum prezentare 360° și clipuri video. Trageți imaginea cu mouse-ul sau degetul pentru a roti borcanul și a citi eticheta completă — ingredientele, producătorul și termenul de valabilitate.</p>', 'left', 'rgba(255,255,255,0.85)' ),
				el_button( 'Vezi Untul topit GHEE', $link( 'NM-556' ), 'primary', 'left' ),
			),
			true
		),
	)
);

$sec_new = el_section(
	array(
		el_heading( 'Noutăți în magazin', 'h2', 'center', '', array( 36, 28 ) ),
		el_text( '<p>Cele mai noi produse de la producătorii noștri.</p>' ),
		el_w( 'shortcode', array( 'shortcode' => '[products limit="8" columns="4" orderby="id" order="DESC"]' ) ),
		el_button( 'Vezi toate produsele', $shop, 'dark' ),
	),
	'#F5F3EC'
);

// Recenzii reale de pe vechiul site
$reviews = get_comments( array( 'type' => 'review', 'status' => 'approve', 'number' => 200 ) );
usort( $reviews, static fn( $a, $b ) => strlen( $b->comment_content ) <=> strlen( $a->comment_content ) );
$testis = array();
$seen   = array();
foreach ( $reviews as $r ) {
	$len = mb_strlen( $r->comment_content );
	if ( $len > 320 || $len < 60 || isset( $seen[ $r->comment_post_ID ] ) ) {
		continue;
	}
	$seen[ $r->comment_post_ID ] = true;
	$testis[] = el_w(
		'testimonial',
		array(
			'testimonial_content'   => $r->comment_content,
			'testimonial_name'      => $r->comment_author,
			'testimonial_job'       => get_the_title( $r->comment_post_ID ),
			'testimonial_alignment' => 'left',
			'testimonial_image'     => array( 'url' => '', 'id' => '' ),
			'content_typography_typography' => 'custom',
			'content_typography_font_size'  => array( 'unit' => 'px', 'size' => 17 ),
			'content_typography_font_style' => 'italic',
			'content_content_color' => '#4A4540',
			'name_text_color'       => '#1F1D1A',
			'job_text_color'        => '#4E7D14',
			'_background_background' => 'classic',
			'_background_color'     => '#FFFFFF',
			'_padding'              => el_pad( 28, 28, 28, 28 ),
			'_border_border'        => 'solid',
			'_border_width'         => array( 'unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true ),
			'_border_color'         => '#E5E1D6',
			'_border_radius'        => array( 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ),
			'_flex_size'            => 'grow',
			'_element_width'        => 'initial',
			'_element_custom_width' => array( 'unit' => '%', 'size' => 31 ),
			'_element_custom_width_tablet' => array( 'unit' => '%', 'size' => 100 ),
		)
	);
	if ( count( $testis ) === 3 ) {
		break;
	}
}
$sec_reviews = el_section(
	array(
		el_heading( 'Ce spun clienții', 'h2', 'center', '', array( 36, 28 ) ),
		el_c( array( 'content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'stretch', 'flex_gap' => array( 'unit' => 'px', 'size' => 24, 'column' => '24', 'row' => '24' ), 'padding' => el_pad( 16, 0, 0, 0 ) ), $testis, true ),
	)
);

$cta = el_c(
	array( 'content_width' => 'boxed', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_gap' => array( 'unit' => 'px', 'size' => 12, 'column' => '12', 'row' => '12' ), 'padding' => el_pad( 64, 20, 64, 20 ), 'background_background' => 'classic', 'background_color' => '#4E7D14' ),
	array(
		el_heading( 'Preferați să comandați la telefon?', 'h2', 'center', '#FFFFFF', array( 32, 26 ) ),
		el_text( '<p>Sunați-ne și vă ajutăm să alegeți produsele potrivite.</p>', 'center', 'rgba(255,255,255,0.9)' ),
		el_button( '☎ ' . $phone, 'tel:' . $phone_l, 'primary' ),
	)
);

$home_data = array( $hero, $benefits, $sec_cats, $sec_featured, $spin, $sec_new, $sec_reviews, $cta );
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
	$kit['system_colors'] = array(
		array( '_id' => 'primary', 'title' => 'Primar', 'color' => '#4E7D14' ),
		array( '_id' => 'secondary', 'title' => 'Secundar', 'color' => '#8DC732' ),
		array( '_id' => 'text', 'title' => 'Text', 'color' => '#4A4540' ),
		array( '_id' => 'accent', 'title' => 'Accent', 'color' => '#8DC732' ),
	);
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

$footer_menu = $make_menu( 'Meniu subsol' );
foreach ( array( $pages['delivery'], $pages['returns'], $pages['terms'], $pages['privacy'], wc_get_page_id( 'myaccount' ) ) as $pid ) {
	$add_page( $footer_menu, $pid );
}

set_theme_mod( 'nav_menu_locations', array( 'primary' => $primary, 'mobile_menu' => $primary, 'footer_menu' => $footer_menu ) );

/* ============================================================ Astra */

$astra = (array) get_option( 'astra-settings', array() );
$astra = array_merge(
	$astra,
	array(
		'global-color-palette'        => array( 'palette' => array( '#4E7D14', '#3D6410', '#1F1D1A', '#4A4540', '#FFFFFF', '#F5F3EC', '#2B2724', '#E5E1D6', '#111111' ) ),
		'body-font-family'            => "'Nunito Sans', sans-serif",
		'body-font-weight'            => '400',
		'body-font-variant'           => '400',
		'font-size-body'              => array( 'desktop' => 17, 'tablet' => 16, 'mobile' => 16, 'desktop-unit' => 'px', 'tablet-unit' => 'px', 'mobile-unit' => 'px' ),
		'headings-font-family'        => "'Nunito', sans-serif",
		'headings-font-weight'        => '800',
		'headings-font-variant'       => '800',
		'load-google-fonts-locally'   => true,
		'preload-local-fonts'         => true,
		'site-content-width'          => 1240,
		'site-layout'                 => 'ast-full-width-layout',
		'site-content-layout'         => 'normal-width-container',
		'site-sidebar-layout'         => 'no-sidebar',
		'single-page-sidebar-layout'  => 'no-sidebar',
		'archive-product-sidebar-layout' => 'left-sidebar',
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
		'header-html-1'               => "<p>☎ <a href=\"tel:$phone_l\">$phone</a> &nbsp;·&nbsp; ✉ <a href=\"mailto:$email\">$email</a></p>",
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
		'footer-html-2'               => "<p><strong style=\"color:#fff\">Contact</strong></p><p>☎ <a href=\"tel:$phone_l\">$phone</a><br>✉ <a href=\"mailto:$email\">$email</a><br>mun. Chișinău, Republica Moldova</p>",
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
		'shop-grid'                   => array( 'desktop' => 3, 'tablet' => 3, 'mobile' => 2 ),
		'shop-no-of-products'         => 12,
		'shop-product-structure'      => array( 'category', 'title', 'ratings', 'price', 'add_cart' ),
		'shop-add-to-cart-action'     => 'default',
		'single-product-structure'    => array( 'category', 'title', 'ratings', 'price', 'short_desc', 'add_cart', 'meta' ),
		'single-product-sticky-summary' => true,
		'single-product-related-display' => true,
		'single-product-up-sells-display' => true,
		'woo-enable-free-shipping-progress' => true,
		'ast-scroll-to-top'            => true,
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
