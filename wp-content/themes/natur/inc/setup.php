<?php
/**
 * Fonturi, stiluri, scripturi și ajustări generale ale temei.
 */

defined( 'ABSPATH' ) || exit;

/** Versiunea unui fișier din temă (data modificării) — reîmprospătează cache-ul browserului la fiecare schimbare. */
function nt_ver( $rel ) {
	$path = NT_DIR . '/' . $rel;
	return file_exists( $path ) ? (string) filemtime( $path ) : '1';
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'nt-fonts', NT_URI . '/assets/css/fonts.css', array(), nt_ver( 'assets/css/fonts.css' ) );
		wp_enqueue_style( 'nt', NT_URI . '/assets/css/natur.css', array( 'astra-theme-css', 'nt-fonts' ), nt_ver( 'assets/css/natur.css' ) );

		$deps = array( 'jquery' );
		if ( class_exists( 'WooCommerce' ) ) {
			// Actualizează coșul lateral și contorul după adăugarea în coș (WooCommerce nu îl mai încarcă implicit).
			wp_enqueue_script( 'wc-cart-fragments' );
			$deps[] = 'wc-cart-fragments';
		}
		wp_enqueue_script( 'nt', NT_URI . '/assets/js/natur.js', $deps, nt_ver( 'assets/js/natur.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script(
			'nt',
			'window.NT = ' . wp_json_encode(
				array(
					'storeApi' => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
					'home'     => home_url( '/' ),
					'cartUrl'  => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
					'freeMin'  => nt_free_shipping_min(),
					// Textele afișate de script (Aspect → Personalizare → Natur.MD).
					't'        => array(
						'searchNone'  => nt_txt_plain( 'search_none' ),
						'searchEmpty' => nt_txt_plain( 'search_empty' ),
						'found1'      => nt_txt_plain( 'search_found_1' ),
						'foundN'      => nt_txt_plain( 'search_found_n' ),
						'searchAll'   => nt_txt_plain( 'search_all' ),
						'toast'       => nt_txt_plain( 'toast_title' ),
						'toastBtn'    => nt_txt_plain( 'toast_btn' ),
						'add'         => nt_txt_plain( 'card_btn' ),
						'added'       => nt_txt_plain( 'card_btn_added' ),
					),
				)
			) . ';',
			'before'
		);
	},
	20
);

/*
 * Fonturile Astra (Google) nu mai sunt folosite: tema își încarcă fonturile local.
 * Coșul lateral Astra pentru mobil caută antetul Astra (înlocuit de temă) și ar da erori la tasta Esc.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_dequeue_style( 'astra-google-fonts' );
		wp_dequeue_script( 'astra-mobile-cart' );
	},
	100
);

add_action(
	'wp_head',
	static function () {
		// Clasa „js” înainte de randare: animațiile de apariție nu ascund conținutul fără JavaScript.
		echo "<script>document.documentElement.classList.add('js')</script>\n";
		foreach ( array( 'fraunces-latin-normal', 'manrope-latin-normal' ) as $font ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( NT_URI . "/assets/fonts/$font.woff2" ) );
		}
		echo '<meta name="theme-color" content="' . esc_attr( nt_bg_color() ) . '">' . "\n";
	},
	1
);

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'nt';
		return $classes;
	}
);

/* Locație de meniu proprie: coloana „Magazin” din subsol (fără meniu: categoriile cu cele mai multe produse). */
add_action( 'after_setup_theme', static fn() => register_nav_menus( array( 'nt_footer_shop' => 'Subsol — coloana Magazin' ) ), 20 );

/* Astra: fără butonul propriu „Mergi sus” (tema are unul în subsol). */
add_filter( 'astra_get_option_scroll-to-top-enable', '__return_false' );

/* Magazin, categorii, căutare: fără bară laterală (navigarea se face prin categoriile din antet și „chips”). */
add_filter(
	'astra_page_layout',
	static function ( $layout ) {
		return is_shop() || is_product_taxonomy() || is_search() ? 'no-sidebar' : $layout;
	}
);

/* Paginile pot avea un rezumat, afișat ca subtitlu sub titlu. */
add_action( 'init', static fn() => add_post_type_support( 'page', 'excerpt' ) );
add_action(
	'nt_page_header_bottom',
	static function () {
		if ( is_page() && ! is_front_page() && has_excerpt() ) {
			echo '<p class="nt-page-sub">' . esc_html( get_the_excerpt() ) . '</p>';
		}
	}
);

/* Avatare: inițiale colorate în locul siluetei gri implicite (recenzii, comentarii). */
add_filter(
	'pre_get_avatar',
	static function ( $avatar, $id_or_email, $args ) {
		if ( ! $id_or_email instanceof WP_Comment ) {
			return $avatar;
		}
		$name = $id_or_email->comment_author;
		return sprintf(
			'<span class="nt-avatar nt-tint--%s" style="width:%2$dpx;height:%2$dpx" aria-hidden="true">%3$s</span>',
			esc_attr( nt_tint( $name ) ),
			(int) $args['size'],
			esc_html( nt_initials( $name ) )
		);
	},
	10,
	3
);
