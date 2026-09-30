<?php
/**
 * Plugin Name: Natur.MD – stoc „În curând” și reduceri
 * Description: Stare de stoc „În curând” (produs anunțat, vizibil, dar care nu poate fi comandat, cu dată estimată) și insigna de reducere cu procent („-25%”).
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

const NATUR_SOON      = 'comingsoon';
const NATUR_SOON_DATE = '_natur_available_from';

function natur_is_soon( $product ) {
	return $product instanceof WC_Product && NATUR_SOON === $product->get_stock_status( 'edit' );
}

/** „În curând · disponibil din 15 octombrie 2026” (data e opțională). */
function natur_soon_text( $product ) {
	$date = $product->get_meta( NATUR_SOON_DATE );
	$text = 'În curând';
	if ( $date && strtotime( $date ) ) {
		$text .= ' · disponibil din ' . date_i18n( 'j F Y', strtotime( $date ) );
	}
	return $text;
}

/* ------------------------------------------------------------------
 * Stare de stoc „În curând”: apare în editorul produsului, editare rapidă/în masă și filtrul listei de produse.
 * ------------------------------------------------------------------ */
add_filter( 'woocommerce_product_stock_status_options', static fn( $o ) => $o + array( NATUR_SOON => 'În curând' ) );

/* Nu se poate adăuga în coș (nici prin ?add-to-cart=ID, nici prin Store API). */
add_filter( 'woocommerce_product_is_in_stock', static fn( $in, $p ) => natur_is_soon( $p ) ? false : $in, 10, 2 );
add_filter( 'woocommerce_is_purchasable', static fn( $can, $p ) => natur_is_soon( $p ) ? false : $can, 10, 2 );

add_filter( 'woocommerce_get_availability_text', static fn( $t, $p ) => natur_is_soon( $p ) ? natur_soon_text( $p ) : $t, 10, 2 );
add_filter( 'woocommerce_get_availability_class', static fn( $c, $p ) => natur_is_soon( $p ) ? 'coming-soon' : $c, 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', static fn( $t, $p ) => natur_is_soon( $p ) ? 'Vezi detalii' : $t, 10, 2 );

/* Insigna pe cardul produsului (același loc și stil ca „Stoc epuizat” din Astra). */
add_action(
	'woocommerce_shop_loop_item_title',
	static function () {
		global $product;
		if ( natur_is_soon( $product ) ) {
			echo '<span class="ast-shop-product-out-of-stock natur-soon-badge">În curând</span>';
		}
	},
	8
);

/* Pagina produsului: WooCommerce nu afișează disponibilitatea pentru produsele necumpărabile. */
add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		if ( natur_is_soon( $product ) ) {
			echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	},
	30
);

/* Date structurate: PreOrder în loc de OutOfStock. */
add_filter(
	'woocommerce_structured_data_product',
	static function ( $markup, $product ) {
		if ( natur_is_soon( $product ) && ! empty( $markup['offers'] ) ) {
			foreach ( $markup['offers'] as &$offer ) {
				$offer['availability'] = 'https://schema.org/PreOrder';
			}
		}
		return $markup;
	},
	10,
	2
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_add_inline_style(
			'astra-theme-css',
			'.natur-soon-badge { background: #F2A900 !important; color: #fff !important; }
.single-product .stock.coming-soon { display: inline-block; padding: .35em .8em; border-radius: 4px; background: #FFF4D6; color: #8A5A00; font-weight: 700; }'
		);
	},
	20
);

/* Admin: data estimată, afișată doar când starea este „În curând”. */
add_action(
	'woocommerce_product_options_stock_status',
	static function () {
		global $product_object;
		woocommerce_wp_text_input(
			array(
				'id'            => NATUR_SOON_DATE,
				'label'         => 'Disponibil din',
				'type'          => 'date',
				'value'         => $product_object ? $product_object->get_meta( NATUR_SOON_DATE ) : '',
				'desc_tip'      => true,
				'description'   => 'Opțional: data estimată de la care produsul „În curând” poate fi comandat. Schimbați apoi starea în „În stoc”.',
				'wrapper_class' => 'natur-soon-date',
			)
		);
		?>
		<script>
		jQuery( function ( $ ) {
			var toggle = function () { $( '.natur-soon-date' ).toggle( $( 'input[name="_stock_status"]:checked, select#_stock_status' ).val() === '<?php echo esc_js( NATUR_SOON ); ?>' ); };
			$( document.body ).on( 'change', 'input[name="_stock_status"], select#_stock_status', toggle );
			toggle();
		} );
		</script>
		<?php
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	static function ( $product ) {
		$date = sanitize_text_field( wp_unslash( $_POST[ NATUR_SOON_DATE ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$product->update_meta_data( NATUR_SOON_DATE, $date );
		} else {
			$product->delete_meta_data( NATUR_SOON_DATE );
		}
	}
);

/* Lista de produse: coloana „Stoc” (WooCommerce o ascunde când gestiunea cantităților e dezactivată global). */
add_filter(
	'manage_edit-product_columns',
	static function ( $columns ) {
		if ( isset( $columns['is_in_stock'] ) ) {
			return $columns;
		}
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'price' === $key ) {
				$out['is_in_stock'] = __( 'Stock', 'woocommerce' );
			}
		}
		return $out;
	},
	20
);

add_filter(
	'woocommerce_admin_stock_html',
	static fn( $html, $product ) => natur_is_soon( $product ) ? '<mark class="onbackorder">' . esc_html( natur_soon_text( $product ) ) . '</mark>' : $html,
	10,
	2
);

/* ------------------------------------------------------------------
 * Insigna de reducere cu procent: „-25%” (la variabile: cea mai mare reducere).
 * Rulează după filtrul Astra și păstrează markup-ul ei.
 * ------------------------------------------------------------------ */
function natur_sale_percent( $product ) {
	$pairs = array();
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices();
		foreach ( $prices['regular_price'] as $id => $regular ) {
			$pairs[] = array( (float) $regular, (float) $prices['sale_price'][ $id ] );
		}
	} else {
		$pairs[] = array( (float) $product->get_regular_price(), (float) $product->get_sale_price() );
	}
	$max = 0;
	foreach ( $pairs as [ $regular, $sale ] ) {
		if ( $regular > 0 && $sale < $regular ) {
			$max = max( $max, (int) round( ( $regular - $sale ) / $regular * 100 ) );
		}
	}
	return $max;
}

add_filter(
	'woocommerce_sale_flash',
	static function ( $markup, $post, $product ) {
		$pct = $markup && $product ? natur_sale_percent( $product ) : 0;
		return $pct ? preg_replace( '~>[^<>]*</span>\s*$~', '>-' . $pct . '%</span>', $markup ) : $markup;
	},
	20,
	3
);

/* Cardurile din magazin (stilul „modern” Astra) nu trec prin woocommerce_sale_flash. */
add_filter(
	'astra_addon_shop_cards_buttons_html',
	static function ( $html, $product ) {
		$pct = $product && $product->is_on_sale() ? natur_sale_percent( $product ) : 0;
		return $pct ? preg_replace( '~(<span[^>]*ast-onsale-card[^>]*>)[^<]*(</span>)~', '${1}-' . $pct . '%${2}', $html, 1 ) : $html;
	},
	10,
	2
);
