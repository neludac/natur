<?php
/**
 * Coșul lateral: produse cu cantitate − / +, subtotal, butonul spre comandă; stare goală ilustrată.
 *
 * @package natur
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' );

if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>
	<ul class="woocommerce-mini-cart cart_list product_list_widget nt-mc">
		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			$visible    = apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}
			$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
			$thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'thumbnail' ), $cart_item, $cart_item_key );
			$permalink    = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			$unit         = (float) wc_get_price_to_display( $_product );
			$pack         = nt_pack_label( $_product );
			$max          = $_product->get_max_purchase_quantity();
			?>
			<li class="woocommerce-mini-cart-item mini_cart_item nt-mc__item" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
				<a class="nt-mc__img" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<div class="nt-mc__info">
					<?php if ( $permalink ) : ?>
						<a class="nt-mc__name" href="<?php echo esc_url( $permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
					<?php else : ?>
						<span class="nt-mc__name"><?php echo wp_kses_post( $product_name ); ?></span>
					<?php endif; ?>
					<span class="nt-mc__meta"><?php echo esc_html( nt_money( $unit ) . ( $pack ? ( 'per kg' === $pack ? ' / kg' : ' · ' . $pack ) : '' ) ); ?></span>
					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="nt-mc__row">
						<div class="nt-qty nt-qty--sm" data-nt-mc-qty>
							<button type="button" data-step="-1" aria-label="Scade cantitatea"<?php disabled( $cart_item['quantity'] <= 1 ); ?>><?php echo nt_icon( 'minus', 16 ); // phpcs:ignore ?></button>
							<span class="nt-qty__val" aria-label="Cantitate"><?php echo (int) $cart_item['quantity']; ?></span>
							<button type="button" data-step="1" aria-label="Crește cantitatea"<?php disabled( $max > 0 && $cart_item['quantity'] >= $max ); ?>><?php echo nt_icon( 'plus', 16 ); // phpcs:ignore ?></button>
						</div>
						<span class="nt-mc__line"><?php echo esc_html( nt_money( $unit * $cart_item['quantity'] ) ); ?></span>
					</div>
				</div>
				<?php
				echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput
					'woocommerce_cart_item_remove_link',
					sprintf(
						'<a role="button" href="%s" class="remove remove_from_cart_button nt-mc__remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s" data-success_message="%s">%s</a>',
						esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
						esc_attr( 'Scoate din coș: ' . wp_strip_all_tags( $product_name ) ),
						esc_attr( $product_id ),
						esc_attr( $cart_item_key ),
						esc_attr( $_product->get_sku() ),
						esc_attr( '„' . wp_strip_all_tags( $product_name ) . '” a fost scos din coș' ),
						nt_icon( 'trash', 18 )
					),
					$cart_item_key
				);
				?>
			</li>
			<?php
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>
	</ul>

	<div class="nt-mc__foot">
		<div class="nt-mc__sum">
			<span>Subtotal</span>
			<strong><?php echo esc_html( nt_money( WC()->cart->get_displayed_subtotal() ) ); ?></strong>
		</div>
		<?php if ( nt_txt( 'mc_note' ) ) : ?>
			<p class="nt-mc__note"><?php echo nt_txt( 'mc_note' ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<a class="nt-btn nt-btn--dark nt-btn--lg nt-btn--block checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php echo nt_txt( 'mc_checkout' ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
	</div>

<?php else : ?>

	<div class="nt-mc__empty">
		<svg class="nt-mc__basket" viewBox="0 0 160 120" aria-hidden="true">
			<ellipse cx="80" cy="108" rx="58" ry="7" fill="#EADFC9"/>
			<path d="M52 46c0-16 12-28 28-28s28 12 28 28" fill="none" stroke="#C9A877" stroke-width="6" stroke-linecap="round"/>
			<path d="M26 48h108l-11 52a8 8 0 0 1-8 6H45a8 8 0 0 1-8-6z" fill="#E7C898"/>
			<path d="M26 48h108l-2 10H28z" fill="#D7B07A"/>
			<path d="M50 62v34M66 62v34M80 62v34M94 62v34M110 62v34" stroke="#D7B07A" stroke-width="4" stroke-linecap="round"/>
			<g class="nt-mc__leaf"><path d="M96 40c10-18 26-22 36-20-2 12-14 26-36 20z" fill="#8DC63F"/><path d="M98 39c9-7 18-12 28-15" stroke="#5E9128" stroke-width="2" fill="none" stroke-linecap="round"/></g>
			<circle cx="70" cy="40" r="11" fill="#FFF6E6" stroke="#EADFC9" stroke-width="2"/>
		</svg>
		<p class="nt-mc__empty-title"><?php echo nt_txt( 'mc_empty_title' ); // phpcs:ignore ?></p>
		<?php if ( nt_txt( 'mc_empty_text' ) ) : ?>
			<p><?php echo nt_txt( 'mc_empty_text' ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<a class="nt-btn nt-btn--dark" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo nt_txt( 'mc_empty_btn' ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
	</div>

<?php endif; ?>

<?php
do_action( 'woocommerce_after_mini_cart' );
