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
	<p class="nt-empty__title"><?php echo nt_txt( 'noprod_title' ); // phpcs:ignore ?></p>
	<?php if ( nt_txt( 'noprod_text' ) ) : ?>
		<p><?php echo nt_txt( 'noprod_text' ); // phpcs:ignore ?></p>
	<?php endif; ?>
	<a class="nt-btn nt-btn--dark" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo nt_txt( 'noprod_btn' ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
</div>
