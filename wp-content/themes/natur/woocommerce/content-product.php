<?php
/**
 * Cardul unui produs în liste (magazin, categorii, prima pagină, produse similare).
 *
 * Întregul card e clicabil (linkul din titlu acoperă cardul); butonul „+” și categoria rămân deasupra.
 *
 * @package natur
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$cat  = nt_product_cat( $product );
$pack = nt_pack_label( $product );
?>
<li <?php wc_product_class( 'nt-card', $product ); ?>>
	<div class="nt-card__media">
		<?php echo nt_card_images( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo nt_card_badges( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo nt_card_button( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="nt-card__body">
		<?php if ( $cat ) : ?>
			<a class="nt-card__cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endif; ?>
		<h3 class="woocommerce-loop-product__title nt-card__title">
			<a class="nt-card__link" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>
		<div class="nt-card__foot">
			<span class="price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php if ( $pack ) : ?>
				<span class="nt-card__pack"><?php echo esc_html( $pack ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</li>
