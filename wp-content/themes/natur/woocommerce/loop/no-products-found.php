<?php
/**
 * Nu s-au găsit produse (căutare / categorie goală).
 *
 * @package natur
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-no-products-found nt-empty">
	<span class="nt-empty__ico"><?php echo nt_icon( 'search', 32 ); // phpcs:ignore ?></span>
	<p class="nt-empty__title">N-am găsit nimic aici… deocamdată</p>
	<p>Încearcă un cuvânt mai scurt sau alege o categorie. Sau sună-ne la <a href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo esc_html( nt_contact( 'phone' ) ); ?></a> — te ajutăm să găsești ce-ți trebuie.</p>
	<a class="nt-btn nt-btn--dark" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Vezi toate produsele <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
</div>
