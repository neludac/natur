<?php
/**
 * Comanda într-un singur pas: o pagină cu Telefon, „Unde livrăm?”, Nume și Adresă.
 * Doar telefonul e obligatoriu; numele și adresa se pot lămuri la telefon.
 *
 * - Pagina „Coș” duce direct la comandă (cantitățile se schimbă pe aceeași pagină);
 * - „Comandă acum” de pe pagina produsului adaugă produsul și deschide comanda;
 * - fără e-mail obligatoriu, cod poștal, cont sau bifă de termeni; plata e la livrare.
 *
 * Șabloane: woocommerce/checkout/form-checkout.php, review-order.php, thankyou.php.
 * Textele: Aspect → Personalizare → Natur.MD → Coș și comandă.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Codul regiunii magazinului (WooCommerce → Setări → Adresa magazinului), ex. „C” = Chișinău. */
function nt_ck_city_state() {
	return (string) WC()->countries->get_base_state();
}

/** Numele regiunii magazinului („Chișinău”). */
function nt_ck_city_name() {
	$states = WC()->countries->get_states( WC()->countries->get_base_country() );
	return (string) ( $states[ nt_ck_city_state() ] ?? '' );
}

/** Clientul are aleasă livrarea în orașul magazinului (implicit, pentru clienții noi). */
function nt_ck_is_city() {
	return WC()->customer && WC()->customer->get_billing_state() === nt_ck_city_state();
}

/** Prefixul țării afișat în fața câmpului „Telefon” („+373”); gol = fără prefix. */
function nt_ck_phone_prefix() {
	return nt_txt_plain( 'ck_phone_prefix' );
}

/** Numărul fără prefixul țării și fără 0-ul din față: „+373 69 123 456” / „069 123 456” → „69 123 456”. */
function nt_ck_phone_local( $phone ) {
	$phone  = trim( (string) $phone );
	$digits = preg_replace( '/\D+/', '', nt_ck_phone_prefix() );
	if ( '' === $digits ) {
		return $phone;
	}
	$phone = preg_replace( '/^(\+|00)\s*' . $digits . '\s*/', '', $phone );
	return ltrim( preg_replace( '/^0+/', '', $phone ) );
}

/** Ieșirea unei funcții WooCommerce care afișează o sumă, fără zecimale nule: „560 lei”. */
function nt_ck_html( callable $fn, ...$args ) {
	ob_start();
	$fn( ...$args );
	return nt_trim_zero_decimals( (string) ob_get_clean() );
}

/* ================================================================== Câmpurile */

add_filter(
	'woocommerce_checkout_fields',
	static function ( $fields ) {
		$city = nt_ck_city_state();

		$fields['billing'] = array(
			// Numele complet într-un singur câmp; la trimitere se împarte în prenume + nume (vezi mai jos).
			'billing_first_name' => array(
				'label'        => nt_txt_plain( 'ck_name' ),
				'placeholder'  => nt_txt_plain( 'ck_name_ph' ),
				'required'     => false,
				'autocomplete' => 'name',
				'class'        => array( 'form-row-wide' ),
				'priority'     => 45, // Imediat deasupra adresei.
			),
			'billing_phone'      => array(
				'type'         => 'tel',
				'label'        => nt_txt_plain( 'ck_phone' ),
				'placeholder'  => nt_txt_plain( 'ck_phone_ph' ),
				'required'     => true,
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
				'class'        => array( 'form-row-wide' ),
				'priority'     => 20,
			),
			// Țara e mereu cea a magazinului; câmp ascuns, ca scriptul WooCommerce să nu-l transforme în listă.
			'billing_country'    => array(
				'type'     => 'hidden',
				'default'  => WC()->countries->get_base_country(),
				'class'    => array( 'nt-ck__hidden' ),
				'priority' => 30,
			),
			'billing_address_1'  => array(
				'label'             => nt_txt_plain( 'ck_address' ),
				'placeholder'       => nt_txt_plain( ! $city || nt_ck_is_city() ? 'ck_address_ph' : 'ck_address_ph_other' ),
				'required'          => false,
				'autocomplete'      => 'street-address',
				'class'             => array( 'form-row-wide' ),
				'custom_attributes' => array(
					'data-ph-city'  => nt_txt_plain( 'ck_address_ph' ),
					'data-ph-other' => nt_txt_plain( 'ck_address_ph_other' ),
				),
				'priority'          => 50,
			),
			// Opțional, sub „Adaugă un comentariu sau e-mail”.
			'billing_email'      => array(
				'type'         => 'email',
				'label'        => nt_txt_plain( 'ck_email' ),
				'placeholder'  => nt_txt_plain( 'ck_email_ph' ),
				'required'     => false,
				'validate'     => array( 'email' ),
				'autocomplete' => 'email',
				'class'        => array( 'form-row-wide' ),
				'priority'     => 60,
				'nt_more'      => true,
			),
		);

		// „Unde livrăm?”: orașul magazinului (curier) sau altă localitate (Poșta Moldovei) — alege zona de livrare.
		if ( $city ) {
			$fields['billing']['billing_state'] = array(
				'type'     => 'nt_zone',
				'label'    => nt_txt_plain( 'ck_zone' ),
				'validate' => array( 'state' ),
				'class'    => array( 'form-row-wide', 'update_totals_on_change' ),
				'priority' => 40,
			);
			uasort( $fields['billing'], static fn( $a, $b ) => $a['priority'] <=> $b['priority'] );
		}

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = nt_txt_plain( 'ck_note' );
			$fields['order']['order_comments']['placeholder'] = nt_txt_plain( 'ck_note_ph' );
		}
		return $fields;
	},
	20
);

/* Câmpul „Telefon”: prefixul țării („+373”) în fața numărului. */
add_filter(
	'woocommerce_form_field_tel',
	static function ( $field, $key ) {
		$prefix = nt_ck_phone_prefix();
		if ( 'billing_phone' !== $key || '' === $prefix ) {
			return $field;
		}
		return str_replace(
			'<span class="woocommerce-input-wrapper">',
			sprintf(
				'<span class="woocommerce-input-wrapper nt-ck-phone" style="--nt-pfx:%d"><span class="nt-ck-phone__prefix" id="billing_phone_prefix">%s</span>',
				mb_strlen( $prefix ),
				esc_html( $prefix )
			),
			str_replace( '<input type="tel"', '<input type="tel" aria-describedby="billing_phone_prefix"', $field )
		);
	},
	10,
	2
);

/**
 * Câmpul „Unde livrăm?”: două butoane radio (merg și fără JavaScript) + un câmp ascuns #billing_state,
 * citit de WooCommerce când recalculează livrarea (natur.js îl sincronizează cu alegerea).
 */
add_filter(
	'woocommerce_form_field_nt_zone',
	static function ( $field, $key, $args, $value ) {
		$city  = nt_ck_city_state();
		$value = $city === (string) $value ? $city : '';
		$opts  = array(
			$city => array( 'city', nt_ck_city_name(), nt_txt( 'ck_zone_city_note' ) ),
			''    => array( 'other', nt_txt_plain( 'ck_zone_other' ), nt_txt( 'ck_zone_other_note' ) ),
		);
		$html  = '';
		foreach ( $opts as $val => [ $slug, $title, $note ] ) {
			$html .= sprintf(
				'<label class="nt-zone__opt" for="%1$s"><input type="radio" class="input-radio" name="%2$s" id="%1$s" value="%3$s"%4$s><span class="nt-zone__txt"><strong>%5$s</strong>%6$s</span></label>',
				esc_attr( $args['id'] . '_' . $slug ),
				esc_attr( $key ),
				esc_attr( $val ),
				checked( $value, $val, false ),
				esc_html( $title ),
				$note ? '<small>' . $note . '</small>' : ''
			);
		}
		return sprintf(
			'<div class="form-row nt-zone %1$s" id="%2$s_field"><span class="nt-zone__label" id="%2$s_label">%3$s</span><div class="nt-zone__opts" role="radiogroup" aria-labelledby="%2$s_label">%4$s</div><input type="hidden" id="%2$s" value="%5$s"></div>',
			esc_attr( implode( ' ', (array) $args['class'] ) ),
			esc_attr( $args['id'] ),
			esc_html( $args['label'] ),
			$html,
			esc_attr( $value )
		);
	},
	10,
	4
);

/* Clientul care revine: numele complet în câmpul „Nume”, telefonul fără prefixul țării. */
add_filter(
	'woocommerce_checkout_get_value',
	static function ( $value, $input ) {
		if ( null !== $value || ! WC()->customer ) {
			return $value;
		}
		if ( 'billing_phone' === $input ) {
			$phone = nt_ck_phone_local( WC()->customer->get_billing_phone() );
			return '' !== $phone ? $phone : null;
		}
		if ( 'billing_first_name' !== $input ) {
			return $value;
		}
		$name = trim( WC()->customer->get_billing_first_name() . ' ' . WC()->customer->get_billing_last_name() );
		return '' !== $name ? $name : null;
	},
	10,
	2
);

/* La trimitere: „Ana Popescu” → prenume „Ana” + nume „Popescu”; telefonul cu prefixul țării; țara magazinului; livrarea la aceeași adresă. */
add_filter(
	'woocommerce_checkout_posted_data',
	static function ( $data ) {
		$allowed = array_keys( WC()->countries->get_allowed_countries() );
		if ( empty( $data['billing_country'] ) || ! in_array( $data['billing_country'], $allowed, true ) ) {
			$data['billing_country'] = WC()->countries->get_base_country();
		}
		// „69 123 456” → „+373 69 123 456” (prefixul e afișat în fața câmpului).
		$local = nt_ck_phone_local( $data['billing_phone'] ?? '' );
		if ( '' !== $local && '' !== nt_ck_phone_prefix() ) {
			$data['billing_phone'] = nt_ck_phone_prefix() . ' ' . $local;
		}
		if ( isset( $data['billing_first_name'] ) && ! isset( $data['billing_last_name'] ) ) {
			$parts                      = preg_split( '/\s+/u', trim( $data['billing_first_name'] ), 2 );
			$data['billing_first_name'] = $parts[0];
			$data['billing_last_name']  = $parts[1] ?? '';
		}
		// Clientul autentificat care n-a scris un e-mail primește confirmarea pe e-mailul contului.
		if ( empty( $data['billing_email'] ) && is_user_logged_in() ) {
			$data['billing_email'] = wp_get_current_user()->user_email;
		}
		if ( empty( $data['ship_to_different_address'] ) && WC()->cart && WC()->cart->needs_shipping() ) {
			foreach ( array( 'first_name', 'last_name', 'country', 'state', 'address_1', 'phone' ) as $f ) {
				$data[ "shipping_$f" ] = $data[ "billing_$f" ] ?? '';
			}
			foreach ( array( 'company', 'address_2', 'city', 'postcode' ) as $f ) {
				$data[ "shipping_$f" ] = '';
			}
		}
		return $data;
	}
);

/* Mesajele de eroare fără prefixul „Facturare” (ex. „Nume este un câmp obligatoriu.”). */
add_filter(
	'gettext_with_context_woocommerce',
	static fn( $translation, $text, $context ) => 'Billing %s' === $text && 'checkout-validation' === $context ? '%s' : $translation,
	10,
	3
);

/* Telefonul trebuie să aibă cel puțin 8 cifre fără prefixul țării (WooCommerce verifică doar caracterele). */
add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		$phone = (string) ( $data['billing_phone'] ?? '' );
		if ( '' === $phone || $errors->get_error_message( 'billing_phone_required' ) ) {
			return;
		}
		if ( $errors->get_error_message( 'billing_phone_validation' ) || strlen( preg_replace( '/\D+/', '', nt_ck_phone_local( $phone ) ) ) < 8 ) {
			$errors->remove( 'billing_phone_validation' );
			$errors->add( 'billing_phone_validation', nt_txt( 'ck_phone_bad' ), array( 'id' => 'billing_phone' ) );
		}
	},
	10,
	2
);

/* Butonul de trimitere arată totalul: „Trimite comanda · 560 lei” (data-value: textul pus la loc de scriptul WooCommerce). */
add_filter(
	'woocommerce_order_button_html',
	static function ( $html ) {
		$label = nt_txt_plain( 'ck_btn' );
		if ( '' === $label ) {
			return $html;
		}
		if ( WC()->cart && WC()->cart->needs_payment() ) {
			$label .= ' · ' . nt_money( WC()->cart->get_total( 'edit' ) );
		}
		return sprintf(
			'<button type="submit" class="button alt nt-btn nt-btn--dark nt-btn--lg nt-btn--block nt-ck__submit" name="woocommerce_checkout_place_order" id="place_order" value="%1$s" data-value="%1$s">%1$s</button>',
			esc_attr( $label )
		);
	}
);

add_action(
	'init',
	static function () {
		// Plata și butonul de trimitere au locul lor în șablon, sub date (nu în lista produselor).
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		// „Trimițând comanda, ești de acord cu …” sub buton, nu deasupra lui.
		if ( remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 ) ) {
			add_action( 'woocommerce_review_order_after_submit', 'wc_checkout_privacy_policy_text' );
		}
	}
);

add_action(
	'wp',
	static function () {
		if ( ! is_checkout() ) {
			return;
		}
		// „Ai un cupon?” apare doar dacă magazinul are cupoane publicate.
		$has = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( ! $has ) {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}
	}
);

/* ================================================================== Drumul spre comandă */

/* Pagina „Coș” cu produse duce direct la comandă; coșul gol rămâne pe pagina „Coș”. */
add_action(
	'template_redirect',
	static function () {
		if ( is_cart() && WC()->cart && ! WC()->cart->is_empty() && ! is_customize_preview() ) {
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	},
	5
);

/* „Comandă acum” lângă „Adaugă în coș”: adaugă produsul (cu cantitatea aleasă) și deschide comanda. */
add_action(
	'woocommerce_after_add_to_cart_button',
	static function () {
		global $product;
		$label = nt_txt_plain( 'prod_buy_now' );
		if ( '' === $label || ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() || $product->is_type( 'external' ) ) {
			return;
		}
		printf(
			'<button type="submit" class="nt-btn nt-btn--leaf nt-buynow" formaction="%s">%s%s</button>',
			esc_url( add_query_arg( array( 'add-to-cart' => $product->get_id(), 'nt-buy-now' => 1 ), $product->get_permalink() ) ),
			esc_html( $label ),
			nt_icon( 'arrow-right', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
);

add_filter(
	'woocommerce_add_to_cart_redirect',
	static function ( $url ) {
		if ( empty( $_REQUEST['nt-buy-now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $url;
		}
		wc_clear_notices(); // fără „… a fost adăugat în coș” deasupra formularului
		return wc_get_checkout_url();
	},
	20
);

/* Pagina de confirmare are titlul „Comandă primită” (al WooCommerce), nu „Finalizare comandă”. */
add_filter(
	'the_title',
	static function ( $title, $id = 0 ) {
		if ( ! is_admin() && (int) $id === wc_get_page_id( 'checkout' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return WC()->query->get_endpoint_title( 'order-received' ) ?: $title;
		}
		return $title;
	},
	20,
	2
);

/* Sub titlul paginii de comandă: o frază despre cât de simplu e. */
add_action(
	'nt_page_header_bottom',
	static function () {
		if ( is_checkout() && ! is_wc_endpoint_url() && nt_txt( 'ck_intro' ) ) {
			echo '<p class="nt-ck-intro">' . nt_txt( 'ck_intro' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
);
