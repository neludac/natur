<?php
/**
 * Plugin Name: Natur.MD – ajustări site
 * Description: Simbol monedă „lei”, redirecționări 301 de pe vechiul site PrestaShop, reguli de livrare și mici ajustări WooCommerce. Aspectul vizual e în tema „natur”.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/* Simbolul monedei magazinului („35,00 lei” în loc de „35,00 MDL”): WooCommerce → Setări → General → „Simbolul afișat”. */
add_filter(
	'woocommerce_currency_symbol',
	static function ( $symbol, $currency ) {
		$custom = trim( (string) get_option( 'natur_currency_symbol', 'lei' ) );
		return $custom && get_option( 'woocommerce_currency' ) === $currency ? $custom : $symbol;
	},
	10,
	2
);
add_filter(
	'woocommerce_general_settings',
	static function ( $settings ) {
		$out = array();
		foreach ( $settings as $field ) {
			$out[] = $field;
			if ( 'woocommerce_currency' === ( $field['id'] ?? '' ) ) {
				$out[] = array(
					'title'    => 'Simbolul afișat',
					'desc'     => 'Cum apare moneda lângă prețuri (ex. „lei”). Gol = simbolul standard WooCommerce.',
					'id'       => 'natur_currency_symbol',
					'default'  => 'lei',
					'type'     => 'text',
					'css'      => 'width: 80px;',
					'desc_tip' => true,
				);
			}
		}
		return $out;
	}
);

/* Când livrarea gratuită e disponibilă, ascunde livrarea contra cost. */
add_filter(
	'woocommerce_package_rates',
	static function ( $rates ) {
		$free = array_filter( $rates, static fn( $r ) => 'free_shipping' === $r->method_id );
		if ( ! $free ) {
			return $rates;
		}
		return array_filter( $rates, static fn( $r ) => 'flat_rate' !== $r->method_id );
	},
	100
);

/* ------------------------------------------------------------------
 * Redirecționări 301 de la URL-urile vechi (PrestaShop) către cele noi.
 * Produsele și categoriile importate păstrează URL-ul vechi în meta `_natur_old_url`.
 * ------------------------------------------------------------------ */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return;
		}
		$path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $path || '/' === $path ) {
			return;
		}
		$path = '/' . ltrim( rawurldecode( $path ), '/' );

		$static = array(
			'/content/1-conditii'          => '/livrare-si-plata/',
			'/content/2-povestea-noastra'  => '/despre-noi/',
			'/content/category/1-home'     => '/',
			'/contact'                     => '/contact/',
			'/quick-order'                 => wc_get_checkout_url(),
			'/order'                       => wc_get_checkout_url(),
			'/cart'                        => wc_get_cart_url(),
			// Magazinul nu are conturi de client (comanda se face fără cont).
			'/my-account'                  => home_url( '/' ),
			'/authentication'              => home_url( '/' ),
			'/login'                       => home_url( '/' ),
			'/contul-meu'                  => home_url( '/' ),
			'/search'                      => home_url( '/?post_type=product&s=' . rawurlencode( sanitize_text_field( wp_unslash( $_GET['search_query'] ?? '' ) ) ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'/new-products'                => wc_get_page_permalink( 'shop' ),
			'/prices-drop'                 => wc_get_page_permalink( 'shop' ),
			'/best-sales'                  => wc_get_page_permalink( 'shop' ),
		);
		$key = untrailingslashit( $path );
		if ( isset( $static[ $key ] ) ) {
			wp_safe_redirect( $static[ $key ], 301 );
			exit;
		}

		global $wpdb;
		// Produs: /categorie/123-nume.html
		if ( preg_match( '~/(\d+)-[^/]*\.html$~', $path, $m ) ) {
			$post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_natur_old_id' AND meta_value = %s LIMIT 1", $m[1] ) );
			if ( $post_id && 'product' === get_post_type( $post_id ) && 'publish' === get_post_status( $post_id ) ) {
				wp_safe_redirect( get_permalink( $post_id ), 301 );
				exit;
			}
			wp_safe_redirect( wc_get_page_permalink( 'shop' ), 302 );
			exit;
		}
		// Categorie: /123-nume
		if ( preg_match( '~^/(\d+)-[^/.]+/?$~', $path, $m ) ) {
			$term_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = '_natur_old_id' AND meta_value = %s LIMIT 1", $m[1] ) );
			$link    = $term_id ? get_term_link( $term_id, 'product_cat' ) : '';
			wp_safe_redirect( $link && ! is_wp_error( $link ) ? $link : wc_get_page_permalink( 'shop' ), $link ? 301 : 302 );
			exit;
		}
	}
);

/* Traduceri lipsă din tema Astra (nu are pachet ro_RO). */
add_filter(
	'gettext_astra',
	static function ( $translation, $text ) {
		static $map = array(
			'Out of stock'             => 'Stoc epuizat',
			'Search'                   => 'Caută',
			'Search...'                => 'Caută...',
			'Search for:'              => 'Caută:',
			'Search &hellip;'          => 'Caută&hellip;',
			'Main Menu'                => 'Meniu principal',
			'Site Navigation'          => 'Navigare site',
			'Close'                    => 'Închide',
			'Shopping Cart'            => 'Coș de cumpărături',
			'Cart'                     => 'Coș',
			'Log in'                   => 'Autentificare',
			'Account'                  => 'Cont',
			'Account icon link'        => 'Contul meu',
			'Scroll to Top'            => 'Mergi sus',
			'Main menu toggle'         => 'Deschide meniul',
			'Skip to content'          => 'Salt la conținut',
			'Sale!'                    => 'Reducere!',
			'Page Not Found'           => 'Pagina nu a fost găsită',
			'This page doesn\'t seem to exist.' => 'Această pagină nu există.',
			'It looks like the link pointing here was faulty. Maybe try searching?' => 'Se pare că linkul este greșit. Încercați o căutare.',
			'Nothing Found'            => 'Nu am găsit nimic',
			'Sorry, but nothing matched your search terms. Please try again with some different keywords.' => 'Nu am găsit rezultate. Încercați alte cuvinte cheie.',
			'Shipping'                 => 'Livrare',
			'Free shipping'            => 'Livrare gratuită',
			'Add %1$s more to get free shipping!' => 'Mai adăugați %1$s pentru livrare gratuită!',
		);
		return $map[ $text ] ?? $translation;
	},
	10,
	2
);

/* Moldova: codul poștal e opțional (mulți clienți nu îl cunosc). */
add_filter(
	'woocommerce_get_country_locale',
	static function ( $locale ) {
		$locale['MD']['postcode']['required'] = false;
		return $locale;
	}
);

add_filter( 'astra_search_field_placeholder', static fn() => 'Caută produse…' );
add_filter(
	'gettext_with_context_astra',
	static function ( $translation, $text ) {
		return 'Search...' === $text ? 'Caută produse…' : $translation;
	},
	10,
	2
);

/* Astra: „%s Products” pe plăcile de categorie. */
add_filter(
	'ngettext_with_context_astra',
	static function ( $translation, $single, $plural, $number ) {
		if ( '%1$s Product' === $single ) {
			return 1 === (int) $number ? '%1$s produs' : '%1$s produse';
		}
		return $translation;
	},
	10,
	4
);

/*
 * Elementor AI (serviciu cloud cu abonament, nefolosit aici): pe „Adaugă produs” apelează
 * ai_get_product_image_unification pentru o ciornă și produce o eroare fatală PHP (500).
 */
add_filter( 'get_user_option_elementor_enable_ai', static fn() => '0' );
