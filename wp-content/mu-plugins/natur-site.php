<?php
/**
 * Plugin Name: Natur.MD – ajustări site
 * Description: Simbol monedă „lei”, redirecționări 301 de pe vechiul site PrestaShop, reguli de livrare și mici ajustări WooCommerce.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/* Moneda: „35,00 lei” în loc de „35,00 MDL”. */
add_filter(
	'woocommerce_currency_symbol',
	static function ( $symbol, $currency ) {
		return 'MDL' === $currency ? 'lei' : $symbol;
	},
	10,
	2
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
			'/my-account'                  => wc_get_page_permalink( 'myaccount' ),
			'/authentication'              => wc_get_page_permalink( 'myaccount' ),
			'/login'                       => wc_get_page_permalink( 'myaccount' ),
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

/* Ajustări vizuale peste tema Astra. */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$css = '
.site-footer .site-primary-footer-wrap, .site-footer .site-primary-footer-wrap p, .site-footer .ast-builder-html-element { color: #C9D3BC; }
.site-footer a, .site-footer .ast-builder-html-element a { color: #fff; }
.site-footer a:hover { color: #8DC732; }
.site-footer .widget-title, .site-footer h4 { color: #fff; font-size: 16px; margin-bottom: .8em; }
.site-footer .wp-block-list { list-style: none; margin: 0; padding: 0; }
.site-footer .wp-block-list li { margin: 0 0 .45em; }
.site-footer .footer-widget-area .wp-block-list a, .site-footer .menu-link { color: #C9D3BC; }
.site-footer .footer-widget-area .wp-block-list a:hover, .site-footer .menu-link:hover { color: #fff; }
.site-below-footer-wrap .ast-footer-copyright { color: #9AA88A; }
.ast-above-header-bar .ast-builder-html-element p { margin: 0; }
.ast-mobile-header-content .ast-header-html-1 .ast-builder-html-element, .ast-mobile-header-content .ast-header-html-1 a { color: var(--ast-global-color-3) !important; }
.ast-mobile-header-content .ast-header-html-1 .ast-builder-html-element { padding: 12px 20px 20px; font-size: 15px; }
.single-product div.product .product_title { font-size: clamp(26px, 3vw, 36px); line-height: 1.2; margin-bottom: .3em; }
.single-product div.product p.price { font-size: 26px; font-weight: 800; color: var(--ast-global-color-0); }
.woocommerce ul.products li.product .woocommerce-loop-product__title { font-size: 16px; }
.site-primary-footer-wrap .ast-builder-html-element, .site-primary-footer-wrap .ast-builder-html-element p, .site-primary-footer-wrap .footer-widget-area { text-align: left !important; }
.site-primary-footer-wrap .ast-builder-grid-row > div { justify-content: flex-start !important; align-items: flex-start !important; }
.site-primary-footer-wrap .footer-nav-wrap .ast-nav-menu { align-items: flex-start !important; }
.site-primary-footer-wrap .footer-nav-wrap .menu-link { padding: 0 0 .45em !important; }
.site-primary-footer-wrap .footer-nav-wrap::before { content: "Informații"; display: block; color: #fff; font-weight: 800; font-size: 16px; margin-bottom: .8em; }
';
		wp_add_inline_style( 'astra-theme-css', $css );
	},
	20
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
