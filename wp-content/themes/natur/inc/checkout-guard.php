<?php
/**
 * Protecție ascunsă anti-bot pentru comanda într-un singur pas (fără CAPTCHA, fără servicii externe, fără cookie-uri noi).
 *
 * Cumpărătorul nu vede nimic. La trimitere, serverul verifică:
 *
 * Blocate (mesajul „ck_guard_error”, cu telefonul magazinului pentru cine chiar e om):
 * - câmpul-capcană (ascuns în afara ecranului) e completat — roboții care completează orice câmp;
 * - lipsește dovada trimisă de natur.js sau semnătura ei nu se potrivește — cereri trimise direct, fără browser;
 * - formularul a fost trimis la mai puțin de NT_GUARD_MIN_SECONDS după deschiderea paginii, ori cu un jeton mai vechi de 2 zile;
 * - prea multe comenzi de la aceeași adresă IP într-o oră (filtrul `nt_ck_guard_rate`);
 * - comenzile prin Store API (/wc/store/checkout), cât timp pagina de comandă folosește formularul clasic.
 *
 * Marcate „În așteptare” (comanda se creează, dar magazinul o verifică la telefon înainte s-o trimită):
 * - niciun eveniment real de tastatură, mouse sau atingere (formular completat și trimis de un script);
 * - browser automatizat (navigator.webdriver) + completare în mai puțin de 1,5 s.
 * Comanda marcată primește o notă privată cu motivele, iar clientul nu primește e-mailul „În așteptare”.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

const NT_GUARD_FIELD       = 'nt_hc';      // Dovada scrisă de natur.js.
const NT_GUARD_TRAP        = 'nt_website'; // Câmpul-capcană.
const NT_GUARD_MIN_SECONDS = 3;
const NT_GUARD_MAX_AGE     = 2 * DAY_IN_SECONDS; // Cât trăiește sesiunea WooCommerce.

/** Identificatorul sesiunii WooCommerce (vizitator sau client autentificat) — jetonul nu poate fi folosit în altă sesiune. */
function nt_guard_sid() {
	return WC()->session ? (string) WC()->session->get_customer_unique_id() : '';
}

function nt_guard_sign( $ts ) {
	return substr( wp_hash( 'nt-ck|' . $ts . '|' . nt_guard_sid(), 'nonce' ), 0, 20 );
}

/** IP-ul clientului, ca hash (nu se păstrează adresa în clar). */
function nt_guard_ip_key() {
	return 'nt_ck_rate_' . substr( wp_hash( WC_Geolocation::get_ip_address(), 'nonce' ), 0, 16 );
}

/** [număr maxim de comenzi, interval în secunde] de la același IP. */
function nt_guard_rate() {
	return (array) apply_filters( 'nt_ck_guard_rate', array( 10, HOUR_IN_SECONDS ) );
}

/** Comenzile recente de la IP-ul curent (marcajele de timp din fereastra filtrului). */
function nt_guard_recent() {
	[ , $window ] = nt_guard_rate();
	$now          = time();
	return array_values( array_filter( (array) get_transient( nt_guard_ip_key() ), static fn( $t ) => $now - (int) $t < $window ) );
}

/* Câmpurile ascunse, după datele clientului (zona care nu se reîncarcă la recalcularea totalului). */
add_action(
	'woocommerce_after_checkout_billing_form',
	static function () {
		$ts = time();
		printf(
			'<div class="nt-hp" aria-hidden="true"><label for="%1$s">Website</label><input type="text" name="%1$s" id="%1$s" value="" tabindex="-1" autocomplete="off"></div><input type="hidden" name="%2$s" value="" data-nt-hc="%3$s">',
			esc_attr( NT_GUARD_TRAP ),
			esc_attr( NT_GUARD_FIELD ),
			esc_attr( $ts . '.' . nt_guard_sign( $ts ) )
		);
	}
);

/**
 * Citește dovada „v1.<ts>.<sig>.<taste>.<atingeri>.<mișcări>.<ecran tactil>.<webdriver>.<ms pe pagină>.<ms de la prima interacțiune>”.
 *
 * @return array|null null = lipsește sau e falsificată.
 */
function nt_guard_proof( $raw ) {
	$p = explode( '.', (string) $raw );
	if ( 10 !== count( $p ) || 'v1' !== $p[0] || ! ctype_digit( $p[1] ) || ! hash_equals( nt_guard_sign( $p[1] ), $p[2] ) ) {
		return null;
	}
	foreach ( array_slice( $p, 3 ) as $n ) {
		if ( ! ctype_digit( $n ) ) {
			return null;
		}
	}
	return array(
		'ts'    => (int) $p[1],
		'keys'  => (int) $p[3],
		'taps'  => (int) $p[4],
		'moves' => (int) $p[5],
		'touch' => '1' === $p[6],
		'wd'    => '1' === $p[7],
		'page'  => (int) $p[8],
		'fill'  => (int) $p[9],
	);
}

/** Motivele pentru care comanda trimisă pare automată (verificate la validare, salvate pe comandă). */
$GLOBALS['nt_guard_flags'] = array();

add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		$GLOBALS['nt_guard_flags'] = array();
		// Întâi erorile obișnuite (ex. telefon incomplet): omul le corectează, iar comanda nu se creează oricum.
		if ( $errors->has_errors() ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce a verificat deja nonce-ul formularului.
		$trap  = isset( $_POST[ NT_GUARD_TRAP ] ) ? trim( (string) wp_unslash( $_POST[ NT_GUARD_TRAP ] ) ) : '';
		$proof = nt_guard_proof( isset( $_POST[ NT_GUARD_FIELD ] ) ? wp_unslash( $_POST[ NT_GUARD_FIELD ] ) : '' );
		// phpcs:enable

		$age  = $proof ? time() - $proof['ts'] : 0;
		$rate = nt_guard_rate();
		$why  = '';
		if ( '' !== $trap ) {
			$why = 'capcană completată';
		} elseif ( ! $proof ) {
			$why = 'fără dovada din browser';
		} elseif ( $age < NT_GUARD_MIN_SECONDS || $age > NT_GUARD_MAX_AGE ) {
			$why = "trimis la {$age} s după deschiderea paginii";
		} elseif ( count( nt_guard_recent() ) >= (int) $rate[0] ) {
			$why = 'prea multe comenzi de la același IP';
		}
		if ( $why ) {
			wc_get_logger()->notice( "Comandă blocată: $why.", array( 'source' => 'natur-anti-bot' ) );
			$errors->add( 'nt_guard', nt_txt( 'ck_guard_error' ) ?: __( 'Error processing checkout. Please try again.', 'woocommerce' ) );
			return;
		}

		$score = 0;
		$flags = array();
		if ( ! $proof['keys'] && ! $proof['taps'] ) {
			$score  += 2;
			$flags[] = 'nicio apăsare de tastă, click sau atingere reală';
		}
		if ( $proof['wd'] ) {
			$score  += 1;
			$flags[] = 'browser automatizat (webdriver)';
		}
		if ( $proof['fill'] < 1500 ) {
			$score  += 1;
			$flags[] = sprintf( 'completat în %.1f s', $proof['fill'] / 1000 );
		}
		if ( $score >= 2 ) {
			$GLOBALS['nt_guard_flags'] = $flags;
		}
	},
	999,
	2
);

/* Comanda suspectă: motivele pe comandă (vizibile doar în administrare). */
add_action(
	'woocommerce_checkout_create_order',
	static function ( $order ) {
		if ( $GLOBALS['nt_guard_flags'] ) {
			$order->update_meta_data( '_nt_guard', implode( '; ', $GLOBALS['nt_guard_flags'] ) );
		}
	}
);

add_action(
	'woocommerce_checkout_order_processed',
	static function ( $order_id, $posted, $order ) {
		$times   = nt_guard_recent();
		$times[] = time();
		set_transient( nt_guard_ip_key(), $times, nt_guard_rate()[1] );

		$why = $order->get_meta( '_nt_guard' );
		if ( $why ) {
			$order->add_order_note( "Verificare anti-bot: comanda pare trimisă automat ($why). Confirmă la telefon înainte de expediere." );
		}
	},
	10,
	3
);

/* Plata la livrare: comanda suspectă rămâne „În așteptare” în loc de „În procesare”. */
add_filter(
	'woocommerce_cod_process_payment_order_status',
	static fn( $status, $order = null ) => $order instanceof WC_Order && $order->get_meta( '_nt_guard' ) ? 'on-hold' : $status,
	20,
	2
);

/* Fără e-mail „În așteptare” către adresa scrisă de un posibil robot (nu folosim magazinul pentru spam). */
add_filter(
	'woocommerce_email_enabled_customer_on_hold_order',
	static fn( $enabled, $order = null ) => $order instanceof WC_Order && $order->get_meta( '_nt_guard' ) ? false : $enabled,
	20,
	2
);

/* Store API: comanda prin /wc/store/checkout ocolește formularul; o închidem cât timp pagina de comandă e cea clasică. */
add_filter(
	'rest_pre_dispatch',
	static function ( $result, $server, $request ) {
		if ( null !== $result || 'POST' !== $request->get_method() || ! preg_match( '#^/wc/store(/v\d+)?/checkout#', $request->get_route() ) ) {
			return $result;
		}
		$page = wc_get_page_id( 'checkout' );
		if ( $page > 0 && has_block( 'woocommerce/checkout', $page ) ) {
			return $result;
		}
		return new WP_Error( 'nt_guard', wp_strip_all_tags( nt_txt( 'ck_guard_error' ) ), array( 'status' => 403 ) );
	},
	10,
	3
);
