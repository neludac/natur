<?php
/**
 * Comanda primită: „Mulțumim, Ana!”, numărul comenzii, telefonul la care sunăm, suma de plată, apoi detaliile comenzii.
 * Textele: Aspect → Personalizare → Natur.MD → Coș și comandă.
 *
 * @package natur
 * @version 8.1.0
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order nt-ty">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
			</p>

		<?php else : ?>
			<?php
			$name  = $order->get_billing_first_name();
			$title = nt_txt_plain( 'ty_title', array( 'nume' => esc_html( $name ) ) );
			if ( '' === $name ) {
				$title = trim( preg_replace( '/[,\s]*\{nume\}/u', '', (string) nt_opt( 'ty_title' ) ) );
			}
			$text = nt_txt(
				'ty_text',
				array(
					'numar'   => esc_html( $order->get_order_number() ),
					'telefon' => esc_html( $order->get_billing_phone() ),
					'total'   => wp_kses_post( nt_trim_zero_decimals( $order->get_formatted_order_total() ) ),
				)
			);
			?>
			<section class="nt-ty__card">
				<span class="nt-ty__check" aria-hidden="true"><?php echo nt_icon( 'check', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2 class="nt-ty__title"><?php echo esc_html( $title ); ?></h2>
				<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<?php if ( nt_txt_plain( 'ty_btn' ) ) : ?>
					<a class="nt-btn nt-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo esc_html( nt_txt_plain( 'ty_btn' ) ); ?> <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</section>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
