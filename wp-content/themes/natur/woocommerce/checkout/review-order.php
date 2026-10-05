<?php
/**
 * Comanda ta: produsele cu cantitate − / + și ștergere (natur.js), apoi livrarea și totalul.
 * Se reîncarcă prin AJAX la fiecare schimbare (fragmentul .woocommerce-checkout-review-order-table).
 * Sumele fără „,00” („560 lei”), ca în magazin.
 *
 * @package natur
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-checkout-review-order-table nt-ck-sum">
	<ul class="nt-ck-sum__items">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
			$visible  = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}
			$name  = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
			$unit  = (float) wc_get_price_to_display( $_product );
			$pack  = nt_pack_label( $_product );
			$max   = $_product->get_max_purchase_quantity();
			$qty   = (int) $cart_item['quantity'];
			$fixed = $_product->is_sold_individually();
			?>
			<li class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item nt-ck-item', $cart_item, $cart_item_key ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks ?>" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
				<span class="nt-ck-item__img" aria-hidden="true"><?php echo $_product->get_image( 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div class="nt-ck-item__info">
					<span class="nt-ck-item__name product-name"><?php echo wp_kses_post( $name ); ?></span>
					<span class="nt-ck-item__meta">
						<?php if ( $_product->is_on_sale() ) : ?>
							<del><?php echo esc_html( nt_money( wc_get_price_to_display( $_product, array( 'price' => $_product->get_regular_price() ) ) ) ); ?></del>
						<?php endif; ?>
						<?php echo esc_html( nt_money( $unit ) . ( $pack ? ( 'per kg' === $pack ? ' / kg' : ' · ' . $pack ) : '' ) ); ?>
					</span>
					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="nt-ck-item__row">
						<?php if ( $fixed ) : ?>
							<span class="nt-ck-item__qty product-quantity">× <?php echo (int) $qty; ?></span>
						<?php else : ?>
							<div class="nt-qty nt-qty--sm" data-nt-ck-qty>
								<button type="button" data-step="-1" aria-label="Scade cantitatea"<?php disabled( $qty <= 1 ); ?>><?php echo nt_icon( 'minus', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
								<span class="nt-qty__val product-quantity" aria-label="Cantitate"><?php echo (int) $qty; ?></span>
								<button type="button" data-step="1" aria-label="Crește cantitatea"<?php disabled( $max > 0 && $qty >= $max ); ?>><?php echo nt_icon( 'plus', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
							</div>
						<?php endif; ?>
						<span class="nt-ck-item__sum product-total"><?php echo wp_kses_post( nt_trim_zero_decimals( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $qty ), $cart_item, $cart_item_key ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks ?></span>
					</div>
				</div>
				<a class="nt-ck-item__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="<?php echo esc_attr( 'Scoate din comandă: ' . wp_strip_all_tags( $name ) ); ?>"><?php echo nt_icon( 'trash', 17 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</li>
			<?php
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</ul>

	<dl class="nt-ck-sum__totals">
		<div class="cart-subtotal">
			<dt><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></dt>
			<dd><?php echo nt_ck_html( 'wc_cart_totals_subtotal_html' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<dt><?php wc_cart_totals_coupon_label( $coupon ); ?></dt>
				<dd><?php echo nt_ck_html( 'wc_cart_totals_coupon_html', $coupon ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<?php
			foreach ( WC()->shipping()->get_packages() as $i => $package ) :
				$rates  = $package['rates'];
				$chosen = wc_get_chosen_shipping_method_for_package( $i, $package );
				$rate   = $rates[ $chosen ] ?? reset( $rates );
				?>
				<div class="woocommerce-shipping-totals shipping nt-ck-ship">
					<?php if ( ! $rates ) : ?>
						<dt><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></dt>
						<dd class="nt-ck-ship__none"><?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks ?></dd>
					<?php elseif ( 1 === count( $rates ) ) : ?>
						<dt><?php esc_html_e( 'Shipping', 'woocommerce' ); ?> <small><?php echo esc_html( $rate->get_label() ); ?></small></dt>
						<dd>
							<?php echo esc_html( (float) $rate->get_cost() > 0 ? nt_money( (float) $rate->get_cost() + array_sum( $rate->get_taxes() ) ) : nt_txt_plain( 'ck_ship_free' ) ); ?>
							<input type="hidden" name="shipping_method[<?php echo esc_attr( $i ); ?>]" data-index="<?php echo esc_attr( $i ); ?>" id="shipping_method_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( $rate->get_id() ); ?>" class="shipping_method">
						</dd>
					<?php else : ?>
						<dt><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></dt>
						<dd>
							<ul id="shipping_method" class="woocommerce-shipping-methods nt-ck-ship__list">
								<?php foreach ( $rates as $r ) : ?>
									<li>
										<label>
											<input type="radio" name="shipping_method[<?php echo esc_attr( $i ); ?>]" data-index="<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( $r->get_id() ); ?>" class="shipping_method"<?php checked( $r->get_id(), $rate->get_id() ); ?>>
											<?php echo esc_html( $r->get_label() ); ?> — <?php echo esc_html( (float) $r->get_cost() > 0 ? nt_money( (float) $r->get_cost() + array_sum( $r->get_taxes() ) ) : nt_txt_plain( 'ck_ship_free' ) ); ?>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</dd>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="fee">
				<dt><?php echo esc_html( $fee->name ); ?></dt>
				<dd><?php echo nt_ck_html( 'wc_cart_totals_fee_html', $fee ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
			</div>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<div class="tax-total">
				<dt><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></dt>
				<dd><?php echo nt_ck_html( 'wc_cart_totals_taxes_total_html' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<div class="order-total nt-ck-sum__total">
			<dt><?php esc_html_e( 'Total', 'woocommerce' ); ?></dt>
			<dd><?php echo nt_ck_html( 'wc_cart_totals_order_total_html' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
		</div>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</dl>
</div>
