<?php
/**
 * Comanda într-un singur pas: datele de livrare, produsele (editabile) și butonul de trimitere.
 * Câmpurile și textele: inc/checkout.php și Aspect → Personalizare → Natur.MD → Coș și comandă.
 *
 * @package natur
 * @version 9.4.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// Dacă magazinul cere cont pentru comandă și nu permite crearea lui aici.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
	return;
}

$main  = array();
$more  = array();
foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) {
	if ( empty( $field['nt_more'] ) ) {
		$main[ $key ] = $field;
	} else {
		$more[ $key ] = $field;
	}
}
// Câmpurile opționale stau deschise dacă vizitatorul le-a completat deja (clientul autentificat are e-mailul din cont).
$more_open = false;
foreach ( $more as $key => $field ) {
	$more_open = $more_open || ( ! is_user_logged_in() && '' !== (string) $checkout->get_value( $key ) );
}
$order_fields = apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ? $checkout->get_checkout_fields( 'order' ) : array(); // phpcs:ignore WooCommerce.Commenting.CommentHooks
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout nt-ck" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<div class="nt-ck__main">

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<section class="nt-ck__card nt-ck__fields woocommerce-billing-fields" id="customer_details" aria-labelledby="nt-ck-fields-title">
			<?php if ( nt_txt( 'ck_fields_title' ) ) : ?>
				<h2 class="nt-ck__title" id="nt-ck-fields-title"><?php echo nt_txt( 'ck_fields_title' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
			<?php endif; ?>

			<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

			<div class="woocommerce-billing-fields__field-wrapper">
				<?php
				foreach ( $main as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php if ( $more || $order_fields ) : ?>
				<details class="nt-ck__more"<?php echo $more_open ? ' open' : ''; ?>>
					<summary><?php echo nt_icon( 'plus', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( nt_txt_plain( 'ck_more' ) ); ?></summary>
					<div class="nt-ck__more-in woocommerce-additional-fields">
						<?php
						foreach ( $more as $key => $field ) {
							woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
						}
						do_action( 'woocommerce_before_order_notes', $checkout );
						foreach ( $order_fields as $key => $field ) {
							woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
						}
						do_action( 'woocommerce_after_order_notes', $checkout );
						?>
					</div>
				</details>
			<?php endif; ?>

			<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
		</section>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<div class="nt-ck__pay">
			<?php woocommerce_checkout_payment(); ?>
		</div>

	</div>

	<aside class="nt-ck__card nt-ck__sum" aria-labelledby="order_review_heading">
		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
		<h2 class="nt-ck__title" id="order_review_heading"><?php echo esc_html( nt_txt_plain( 'ck_sum_title' ) ); ?></h2>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action( 'woocommerce_checkout_order_review' ); // Plata și butonul sunt în .nt-ck__pay, sub date (inc/checkout.php). ?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</aside>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
