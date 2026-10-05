<?php
/**
 * WooCommerce: card de produs, magazin/categorii, pagina produsului, coș lateral.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	function nt_sticky_add_to_cart() {}
	return;
}

/* ================================================================== Coș: contor, total, livrare gratuită */

function nt_cart_count() {
	return WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

function nt_cart_count_html() {
	$n = nt_cart_count();
	return sprintf( '<span class="nt-cart-count" data-count="%1$d" aria-label="%2$s">%1$d</span>', $n, esc_attr( nt_count_label( $n ) . ' în coș' ) );
}

function nt_cart_total_html() {
	$total = WC()->cart ? WC()->cart->get_displayed_subtotal() : 0;
	return '<span class="nt-cart-total">' . esc_html( nt_cart_count() ? nt_money( $total ) : 'Coș' ) . '</span>';
}

/** Cât mai lipsește până la livrarea gratuită (null dacă nu există prag). */
function nt_freeship_left() {
	$min = nt_free_shipping_min();
	if ( ! $min || ! WC()->cart ) {
		return null;
	}
	return max( 0, $min - (float) WC()->cart->get_displayed_subtotal() );
}

/** Bara de progres „Mai adaugă X lei pentru livrare gratuită”. */
function nt_freeship_html() {
	$min  = nt_free_shipping_min();
	$left = nt_freeship_left();
	if ( null === $left ) {
		return '<div class="nt-freeship" hidden></div>';
	}
	$empty = 0 === nt_cart_count();
	$p     = $empty ? 0 : min( 1, 1 - $left / $min );
	if ( $empty ) {
		$msg = nt_txt( 'fs_empty' );
	} elseif ( $left > 0 ) {
		$msg = nt_txt( 'fs_left', array( 'suma' => esc_html( nt_money( $left ) ) ) );
	} else {
		$msg = nt_txt( 'fs_done' );
	}
	return sprintf(
		'<div class="nt-freeship%1$s" style="--p:%2$s"><p>%3$s<span>%4$s</span></p><div class="nt-freeship__bar" aria-hidden="true"><span></span></div></div>',
		$left <= 0 && ! $empty ? ' is-done' : '',
		esc_attr( round( $p, 3 ) ),
		$left <= 0 && ! $empty ? nt_icon( 'gift', 18 ) : nt_icon( 'truck', 18 ),
		$msg
	);
}

/** Varianta scurtă, pentru pagina produsului. */
function nt_freeship_note() {
	$min  = nt_free_shipping_min();
	$left = nt_freeship_left();
	if ( null === $left ) {
		return '<span class="nt-freeship-note"></span>';
	}
	if ( 0 === nt_cart_count() ) {
		$txt = nt_txt( 'note_empty' );
	} elseif ( $left > 0 ) {
		$txt = nt_txt( 'note_left', array( 'suma' => esc_html( nt_money( $left ) ) ) );
	} else {
		$txt = nt_txt( 'note_done' );
	}
	return '<span class="nt-freeship-note">' . $txt . '</span>';
}

/** Coș gol, pe pagina produsului: cât lipsește până la livrarea gratuită cu acest produs. */
function nt_freeship_product_note() {
	global $product;
	$min   = nt_free_shipping_min();
	$price = $product ? (float) wc_get_price_to_display( $product ) : 0;
	if ( ! $price || ! $min ) {
		return '';
	}
	$txt = $price >= $min ? nt_txt( 'note_prod_done' ) : nt_txt( 'note_prod_left', array( 'suma' => esc_html( nt_money( $min - $price ) ) ) );
	return '<span class="nt-freeship-note--product">' . $txt . '</span>';
}

add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		$fragments['.nt-cart-count']     = nt_cart_count_html();
		$fragments['.nt-cart-total']     = nt_cart_total_html();
		$fragments['.nt-freeship']       = nt_freeship_html();
		$fragments['.nt-freeship-note']  = nt_freeship_note();
		return $fragments;
	}
);

/* ================================================================== Prețuri: „460 lei” în loc de „460,00 lei” în vitrină */

add_filter(
	'woocommerce_get_price_html',
	static function ( $html ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $html;
		}
		return nt_trim_zero_decimals( $html );
	},
	20
);

/** „460,00 lei” → „460 lei” (separatorul zecimal și numărul de zecimale din WooCommerce → Setări → General). */
function nt_trim_zero_decimals( $html ) {
	$dec = wc_get_price_decimals();
	if ( $dec < 1 ) {
		return $html;
	}
	$sep = preg_quote( wc_get_price_decimal_separator(), '/' );
	return preg_replace( '/(\d)' . $sep . str_repeat( '0', $dec ) . '(?=&nbsp;|\x{00A0}|\s*<|\s|$)/u', '$1', $html );
}

/**
 * Ambalajul produsului, afișat pe card („720 ml”, „per kg”): atributul ales în
 * Personalizare → Natur.MD → Magazin și categorii.
 */
function nt_pack_label( WC_Product $product ) {
	$attr = (string) nt_opt( 'card_pack_attr' );
	$raw  = $attr ? trim( (string) $product->get_attribute( $attr ) ) : '';
	if ( '' === $raw ) {
		return '';
	}
	if ( preg_match( '/^kg$/i', $raw ) ) {
		return 'per kg';
	}
	$raw = preg_replace( '/(\d)\s*(gr?|ml|l|kg|caps|buc)\b/iu', '$1 $2', $raw );
	$raw = preg_replace( '/\b(\d+) gr\b/u', '$1 g', $raw );
	return mb_strlen( $raw ) > 18 ? '' : $raw;
}

/** Categoria cea mai specifică a produsului (pentru eticheta de pe card). */
function nt_product_cat( WC_Product $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	usort( $terms, static fn( $a, $b ) => ( $b->parent > 0 ) <=> ( $a->parent > 0 ) );
	$skip = (int) nt_opt( 'card_cat_skip' );
	foreach ( $terms as $t ) {
		if ( $t->term_id !== $skip && $t->parent !== $skip ) {
			return $t;
		}
	}
	return $terms[0];
}

/* ================================================================== Card de produs (woocommerce/content-product.php) */

function nt_card_badges( WC_Product $product ) {
	$out = '';
	if ( $product->is_on_sale() ) {
		$pct  = function_exists( 'natur_sale_percent' ) ? natur_sale_percent( $product ) : 0;
		$out .= '<span class="onsale nt-badge nt-badge--sale">' . esc_html( $pct ? "-$pct%" : 'Reducere' ) . '</span>';
	}
	if ( function_exists( 'natur_is_soon' ) && natur_is_soon( $product ) ) {
		$out .= '<span class="natur-soon-badge nt-badge nt-badge--soon">În curând</span>';
	} elseif ( ! $product->is_in_stock() ) {
		$out .= '<span class="nt-badge nt-badge--oos">Stoc epuizat</span>';
	}
	if ( class_exists( 'Natur_Product_Media' ) ) {
		if ( Natur_Product_Media::get_frames( $product->get_id() ) ) {
			$out .= '<span class="nt-badge nt-badge--media">' . nt_icon( 'rotate', 13 ) . '360°</span>';
		}
		if ( Natur_Product_Media::get_videos( $product->get_id() ) ) {
			$out .= '<span class="nt-badge nt-badge--media">' . nt_icon( 'play', 12 ) . 'Video</span>';
		}
	}
	return $out ? '<div class="nt-card__badges">' . $out . '</div>' : '';
}

function nt_card_images( WC_Product $product ) {
	$size  = 'woocommerce_thumbnail';
	$main  = $product->get_image( $size, array( 'class' => 'nt-card__img', 'loading' => 'lazy' ) );
	$extra = '';
	$gal   = $product->get_gallery_image_ids();
	if ( $gal ) {
		$extra = wp_get_attachment_image( $gal[0], $size, false, array( 'class' => 'nt-card__img nt-card__img--alt', 'loading' => 'lazy', 'alt' => '' ) );
	}
	return '<div class="nt-card__imgs' . ( $extra ? ' has-alt' : '' ) . '">' . $main . $extra . '</div>';
}

function nt_card_button( WC_Product $product ) {
	$name = $product->get_name();
	if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) {
		return sprintf(
			'<a href="%1$s" data-quantity="1" data-product_id="%2$d" data-product_sku="%3$s" class="button add_to_cart_button ajax_add_to_cart nt-add" aria-label="%4$s" rel="nofollow" data-nt-name="%5$s" data-nt-img="%6$s">%7$s%8$s<span class="nt-add__label">%9$s</span></a>',
			esc_url( $product->add_to_cart_url() ),
			$product->get_id(),
			esc_attr( $product->get_sku() ),
			esc_attr( 'Adaugă în coș: ' . $name ),
			esc_attr( $name ),
			esc_url( (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ),
			nt_icon( 'plus', 20, 'nt-add__plus' ),
			nt_icon( 'check', 20, 'nt-add__check' ),
			esc_html( nt_txt_plain( 'card_btn' ) )
		);
	}
	$label = $product->is_type( 'variable' ) && $product->is_in_stock() ? 'Alege' : 'Detalii';
	return sprintf(
		'<a href="%1$s" class="nt-add nt-add--more" aria-label="%2$s">%3$s<span class="nt-add__label">%4$s</span></a>',
		esc_url( $product->get_permalink() ),
		esc_attr( $label . ': ' . $name ),
		nt_icon( 'arrow-right', 20, 'nt-add__plus' ),
		esc_html( $label )
	);
}

/* ================================================================== Magazin / categorii / căutare */

function nt_is_catalog() {
	return is_shop() || is_product_taxonomy();
}

add_action(
	'wp',
	static function () {
		if ( ! nt_is_catalog() ) {
			return;
		}
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		add_filter( 'woocommerce_show_page_title', '__return_false' );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		add_action( 'woocommerce_before_main_content', 'nt_archive_hero', 25 );
		add_action( 'woocommerce_before_shop_loop', static fn() => print( '<div class="nt-toolbar">' ), 15 );
		add_action( 'woocommerce_before_shop_loop', static fn() => print( '</div>' ), 35 );
	}
);

/** Antetul paginilor de magazin: titlu, descriere, imagine, categorii. */
function nt_archive_hero() {
	$term   = is_product_taxonomy() ? get_queried_object() : null;
	$search = is_search() ? get_search_query() : '';
	$found  = (int) $GLOBALS['wp_query']->found_posts;

	$para = static fn( $html ) => '' !== $html ? '<p>' . $html . '</p>' : '';
	if ( $search ) {
		$title = nt_txt_plain( 'search_title', array( 'cautare' => esc_html( $search ) ) );
		$desc  = $found ? $para( nt_txt( 'search_desc', array( 'numar' => esc_html( nt_count_label( $found ) ) ) ) ) : '';
	} elseif ( $term ) {
		$title = $term->name;
		$desc  = $term->description ? wc_format_content( do_shortcode( wp_kses_post( $term->description ) ) ) : $para(
			nt_txt(
				'cat_desc',
				array(
					'numar'     => esc_html( nt_count_label( nt_term_count( $term ) ) ),
					'categorie' => esc_html( $term->name ),
				)
			)
		);
	} else {
		$title = woocommerce_page_title( false );
		$desc  = $para( nt_txt( 'shop_desc', array( 'numar' => esc_html( nt_count_label( (int) wp_count_posts( 'product' )->publish ) ) ) ) );
	}
	$tint = nt_tint( $term ? $term->term_id : 'shop' );
	?>
	<section class="nt-ahero nt-tint--<?php echo esc_attr( $tint ); ?>">
		<div class="nt-ahero__copy">
			<?php
			woocommerce_breadcrumb(
				array(
					'delimiter'   => '<span class="nt-crumbs__sep" aria-hidden="true">/</span>',
					'wrap_before' => '<nav class="woocommerce-breadcrumb nt-crumbs" aria-label="Breadcrumb">',
					'wrap_after'  => '</nav>',
				)
			);
			?>
			<h1 class="page-title nt-ahero__title"><?php echo esc_html( $title ); ?></h1>
			<div class="nt-ahero__desc term-description"><?php echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<ul class="nt-ahero__facts">
				<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
					<?php if ( nt_txt( "shop_fact_$i" ) ) : ?>
						<li><?php echo nt_icon( nt_opt( "shop_fact_{$i}_icon" ), 16 ); // phpcs:ignore ?><?php echo nt_txt( "shop_fact_$i" ); // phpcs:ignore ?></li>
					<?php endif; ?>
				<?php endfor; ?>
			</ul>
		</div>
		<div class="nt-ahero__art" aria-hidden="true">
			<?php
			if ( $term && get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
				echo '<span class="nt-ahero__photo">' . nt_term_image( $term, 'woocommerce_single', array( 'loading' => 'eager' ) ) . '</span>'; // phpcs:ignore
			} else {
				$i = 0;
				foreach ( nt_top_categories( 3 ) as $t ) {
					echo '<span class="nt-ahero__photo nt-ahero__photo--' . ( ++$i ) . '">' . nt_term_image( $t, 'woocommerce_thumbnail', array( 'loading' => 'eager' ) ) . '</span>'; // phpcs:ignore
				}
			}
			if ( nt_txt_plain( 'shop_sticker' ) ) {
				echo nt_sticker( nt_txt_plain( 'shop_sticker' ) ); // phpcs:ignore
			}
			?>
		</div>
	</section>
	<?php
	nt_category_chips( $term );
}

/** Rând de categorii (derulabil pe orizontală). */
function nt_category_chips( $term ) {
	$items  = array();
	$active = $term ? $term->term_id : 0;
	if ( $term ) {
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true ) );
		if ( $children && ! is_wp_error( $children ) ) {
			$items[] = array( 'Toate', get_term_link( $term ), true, null );
			foreach ( $children as $c ) {
				$items[] = array( $c->name, get_term_link( $c ), false, $c );
			}
		} elseif ( $term->parent ) {
			$parent  = get_term( $term->parent, 'product_cat' );
			$items[] = array( 'Toate din ' . $parent->name, get_term_link( $parent ), false, null );
			foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->parent, 'hide_empty' => true ) ) as $c ) {
				$items[] = array( $c->name, get_term_link( $c ), $c->term_id === $active, $c );
			}
		}
	}
	if ( ! $items ) {
		$items[] = array( 'Toate', wc_get_page_permalink( 'shop' ), ! $term, null );
		foreach ( nt_top_categories() as $c ) {
			$items[] = array( $c->name, get_term_link( $c ), $c->term_id === $active, $c );
		}
	}
	echo '<nav class="nt-catchips" aria-label="Categorii"><ul>';
	foreach ( $items as [ $name, $url, $is_active, $t ] ) {
		printf(
			'<li><a class="nt-catchip%s" href="%s"%s><span class="nt-catchip__img nt-tint--%s">%s</span>%s</a></li>',
			$is_active ? ' is-active' : '',
			esc_url( $url ),
			$is_active ? ' aria-current="page"' : '',
			esc_attr( nt_tint( $t ? $t->term_id : 'all' ) ),
			$t ? nt_term_image( $t, 'thumbnail' ) : nt_icon( 'grid', 16 ), // phpcs:ignore
			esc_html( $name )
		);
	}
	echo '</ul></nav>';
}

/** Insignă rotundă cu text pe cerc („100% natural · fără E-uri …”). */
function nt_sticker( $text, $icon = 'leaf' ) {
	static $n = 0;
	$id = 'nt-sticker-path-' . ( ++$n );
	return sprintf(
		'<span class="nt-sticker"><svg viewBox="0 0 120 120" aria-hidden="true"><defs><path id="%1$s" d="M60,60 m-44,0 a44,44 0 1,1 88,0 a44,44 0 1,1 -88,0"/></defs><text><textPath href="#%1$s" textLength="270">%2$s</textPath></text></svg><span class="nt-sticker__ico">%3$s</span></span>',
		esc_attr( $id ),
		esc_html( mb_strtoupper( $text ) ),
		nt_icon( $icon, 26 )
	);
}

/* ================================================================== Pagina produsului */

/* Insigna „-25%” lângă preț (nu peste galerie, unde sunt butoanele Foto / Video / 360°). */
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
add_filter(
	'woocommerce_get_price_html',
	static function ( $html, $product ) {
		if ( ! is_product() || ! $product->is_on_sale() || get_queried_object_id() !== $product->get_id() ) {
			return $html;
		}
		if ( ! did_action( 'woocommerce_before_single_product_summary' ) || did_action( 'woocommerce_after_single_product_summary' ) ) {
			return $html;
		}
		$pct = function_exists( 'natur_sale_percent' ) ? natur_sale_percent( $product ) : 0;
		return $pct ? $html . ' <span class="onsale nt-badge nt-badge--sale">-' . $pct . '%</span>' : $html;
	},
	30,
	2
);

/* Disponibilitate + avantaje (livrare, plată) lângă butonul „Adaugă în coș”. */
add_action(
	'woocommerce_before_add_to_cart_form',
	static function () {
		global $product;
		if ( $product && $product->is_in_stock() && $product->is_purchasable() ) {
			if ( nt_txt( 'prod_avail' ) ) {
				echo '<p class="nt-avail"><span class="nt-avail__dot" aria-hidden="true"></span>' . nt_txt( 'prod_avail' ) . '</p>'; // phpcs:ignore
			}
		}
	}
);

add_action(
	'woocommerce_after_add_to_cart_form',
	static function () {
		?>
		<ul class="nt-perks">
			<?php
			$tints = array( 1 => 'mint', 2 => 'butter', 3 => 'peach' );
			foreach ( $tints as $i => $tint ) :
				$title = nt_txt( "perk_{$i}_title" );
				if ( '' === $title ) {
					continue;
				}
				// Avantajul 2 (livrarea gratuită) e urmat de cât mai lipsește până la prag.
				$text = 2 === $i ? ( nt_cart_count() ? nt_freeship_note() : nt_freeship_product_note() ) : nt_txt( "perk_{$i}_text" );
				?>
				<li><span class="nt-perks__ico nt-tint--<?php echo esc_attr( $tint ); ?>"><?php echo nt_icon( nt_opt( "perk_{$i}_icon" ), 20 ); // phpcs:ignore ?></span><span><strong><?php echo $title; // phpcs:ignore ?></strong> <?php echo $text; // phpcs:ignore ?></span></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( nt_contact( 'phone' ) && nt_txt( 'prod_ask' ) ) : ?>
			<p class="nt-askus"><?php echo nt_icon( 'phone', 16 ); // phpcs:ignore ?> <?php echo nt_txt( 'prod_ask' ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<?php
	}
);

add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		return array_merge( $args, array( 'posts_per_page' => 4, 'columns' => 4 ) );
	},
	20
);
add_filter( 'woocommerce_product_related_products_heading', static fn() => nt_txt_plain( 'related_title' ) );
add_filter( 'woocommerce_product_upsells_products_heading', static fn() => nt_txt_plain( 'upsells_title' ) );

/* Câmpul de cantitate: butoane − / + (adăugate de natur.js; aici doar clasa pentru stil). */
add_filter( 'woocommerce_quantity_input_classes', static fn( $c ) => array_merge( $c, array( 'nt-qty__input' ) ) );

/** Bara „Adaugă în coș” lipită jos pe mobil, când butonul principal nu se mai vede. */
function nt_sticky_add_to_cart() {
	if ( ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	?>
	<div class="nt-satc" data-nt-satc aria-hidden="true">
		<?php echo wp_get_attachment_image( $product->get_image_id(), 'thumbnail', false, array( 'class' => 'nt-satc__img', 'alt' => '' ) ); ?>
		<div class="nt-satc__txt">
			<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			<span class="nt-satc__price"><?php echo wp_kses_post( nt_trim_zero_decimals( wc_price( wc_get_price_to_display( $product ) ) ) ); ?></span>
		</div>
		<button type="button" class="nt-btn nt-btn--dark" data-nt-satc-btn tabindex="-1"><?php echo nt_icon( 'bag', 18 ); // phpcs:ignore ?> <?php echo esc_html( nt_txt_plain( 'card_btn' ) ); ?></button>
	</div>
	<?php
}

/* Coșul și pagina de comandă (într-un singur pas): inc/checkout.php. */

/* ================================================================== Diverse */

/* Mesajul „X a fost adăugat în coș” de pe pagina produsului: buton spre coș, nu link text. */
add_filter(
	'wc_add_to_cart_message_html',
	static function ( $message ) {
		return str_replace( 'class="button wc-forward', 'class="button wc-forward nt-btn nt-btn--sm nt-btn--dark', $message );
	}
);
