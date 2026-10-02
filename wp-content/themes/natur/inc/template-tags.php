<?php
/**
 * Funcții ajutătoare folosite de șabloane.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date de contact (Aspect → Personalizare → Natur.MD → Date de contact; opțiunea `natur_contact`).
 * `phone_raw` (pentru linkurile tel:) se formează din telefonul afișat și prefixul țării magazinului.
 */
function nt_contact( $key ) {
	$c = (array) get_option( 'natur_contact', array() );
	if ( 'phone_raw' === $key ) {
		return nt_phone_raw( $c['phone'] ?? '' );
	}
	return isset( $c[ $key ] ) ? trim( (string) $c[ $key ] ) : '';
}

/** „060 89 30 00” → „+37360893000” (prefixul țării din WooCommerce → Setări → Adresa magazinului). */
function nt_phone_raw( $phone ) {
	$phone  = trim( (string) $phone );
	$digits = preg_replace( '/\D+/', '', $phone );
	if ( '' === $digits ) {
		return '';
	}
	if ( str_starts_with( $phone, '+' ) ) {
		return '+' . $digits;
	}
	if ( str_starts_with( $digits, '00' ) ) {
		return '+' . substr( $digits, 2 );
	}
	if ( str_starts_with( $digits, '0' ) && function_exists( 'WC' ) && WC()->countries ) {
		$code = WC()->countries->get_country_calling_code( WC()->countries->get_base_country() );
		if ( $code ) {
			return $code . substr( $digits, 1 );
		}
	}
	return $digits;
}

/**
 * Logo-ul site-ului: cel din Aspect → Personalizare → Identitatea site-ului;
 * varianta „light” (fundal închis) folosește câmpul „Logo pentru fundal închis”, apoi logo-ul principal.
 */
function nt_logo( $variant = 'dark', $width = 150 ) {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( 'light' === $variant && get_theme_mod( 'nt_logo_light' ) ) {
		$id = (int) get_theme_mod( 'nt_logo_light' );
	}
	$meta = $id ? wp_get_attachment_metadata( $id ) : null;
	if ( ! $id || empty( $meta['width'] ) ) {
		return '<span class="nt-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}
	return wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			'class'   => 'nt-logo__img',
			'width'   => (int) $width,
			'height'  => (int) round( $width * $meta['height'] / $meta['width'] ),
			'alt'     => get_bloginfo( 'name' ),
			'loading' => 'eager',
			'sizes'   => (int) $width . 'px',
		)
	);
}

/**
 * Pragul livrării gratuite (lei), citit din metodele „Livrare gratuită” ale zonelor de livrare.
 */
function nt_free_shipping_min() {
	static $min = null;
	if ( null !== $min ) {
		return $min;
	}
	$min = 0.0;
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'free_shipping' === $method->id && 'yes' === $method->enabled && in_array( $method->requires, array( 'min_amount', 'either' ), true ) ) {
					$min = (float) $method->min_amount;
					break 2;
				}
			}
		}
	}
	return $min;
}

/** Costul livrării contra cost (prima metodă „Tarif fix” activă din zonele de livrare), ex. „50 lei”. */
function nt_shipping_cost() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return '';
	}
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'flat_rate' === $method->id && 'yes' === $method->enabled && is_numeric( $method->cost ) ) {
				return nt_money( $method->cost );
			}
		}
	}
	return '';
}

/** Suma fără zecimale inutile, în formatul monedei din WooCommerce: „500 lei”, „47,50 lei”. */
function nt_money( $amount ) {
	$amount = (float) $amount;
	if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
		return number_format( $amount, floor( $amount ) == $amount ? 0 : 2, ',', ' ' ); // phpcs:ignore Universal.Operators.StrictComparisons
	}
	$dec    = floor( $amount ) == $amount ? 0 : wc_get_price_decimals(); // phpcs:ignore Universal.Operators.StrictComparisons
	$num    = number_format( $amount, $dec, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
	$symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	switch ( get_option( 'woocommerce_currency_pos' ) ) {
		case 'left':
			return $symbol . $num;
		case 'left_space':
			return $symbol . ' ' . $num;
		case 'right':
			return $num . $symbol;
		default:
			return $num . ' ' . $symbol;
	}
}

/** Nuanțe pastelate pentru plăci (categorii, avatare). */
function nt_tint( $seed ) {
	$tints = array( 'mint', 'peach', 'butter', 'sky', 'lilac', 'rose', 'sage' );
	return $tints[ absint( crc32( (string) $seed ) ) % count( $tints ) ];
}

/** Inițialele unui nume („Maria Testescu” → „MT”). */
function nt_initials( $name ) {
	$parts = preg_split( '/[^\p{L}]+/u', trim( (string) $name ), -1, PREG_SPLIT_NO_EMPTY );
	$out   = '';
	foreach ( array_slice( $parts, 0, 2 ) as $p ) {
		$out .= mb_strtoupper( mb_substr( $p, 0, 1 ) );
	}
	return $out ? $out : '•';
}

/**
 * Elementele meniului dintr-o locație, organizate pe niveluri.
 *
 * @return array<int, array{item: WP_Post, children: array}>
 */
function nt_menu_tree( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return array();
	}
	_wp_menu_item_classes_by_context( $items );
	$by_parent = array();
	foreach ( $items as $item ) {
		$by_parent[ (int) $item->menu_item_parent ][] = $item;
	}
	$build = static function ( $parent ) use ( &$build, $by_parent ) {
		$out = array();
		foreach ( $by_parent[ $parent ] ?? array() as $item ) {
			$out[] = array( 'item' => $item, 'children' => $build( (int) $item->ID ) );
		}
		return $out;
	};
	return $build( 0 );
}

/** Elementul de meniu e pagina curentă (sau un strămoș al ei)? */
function nt_menu_is_current( WP_Post $item ) {
	$cls = (array) $item->classes;
	return (bool) array_intersect( $cls, array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent', 'current_page_parent', 'current-page-ancestor' ) );
}

/** Imaginea unei categorii de produse (sau a primului produs din ea). */
function nt_term_image( $term, $size = 'woocommerce_thumbnail', $attr = array() ) {
	$id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( ! $id ) {
		return '';
	}
	return wp_get_attachment_image( $id, $size, false, array_merge( array( 'loading' => 'lazy', 'alt' => '' ), $attr ) );
}

/** Numărul de produse vizibile dintr-o categorie, inclusiv subcategoriile (contorul calculat de WooCommerce). */
function nt_term_count( $term ) {
	$n = get_term_meta( $term->term_id, 'product_count_product_cat', true );
	return '' === $n ? (int) $term->count : (int) $n;
}

/** Categoriile principale cu produse (fără categoria implicită), după numărul de produse. */
function nt_top_categories( $limit = 0 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	// WooCommerce înlocuiește `count` cu numărul de produse vizibile (inclusiv din subcategorii); sortăm după el.
	usort( $terms, static fn( $a, $b ) => $b->count <=> $a->count ?: strcmp( $a->name, $b->name ) );
	return $limit ? array_slice( $terms, 0, $limit ) : $terms;
}

/** „1 produs”, „6 produse”, „24 de produse” (regula românească pentru „de”). */
function nt_count_label( $n, $one = 'produs', $many = 'produse' ) {
	$n = (int) $n;
	if ( 1 === $n ) {
		return "1 $one";
	}
	$de = ( $n >= 20 && ( 0 === $n % 100 || $n % 100 >= 20 ) ) ? ' de' : '';
	return $n . $de . ' ' . $many;
}
