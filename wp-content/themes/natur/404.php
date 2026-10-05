<?php
/**
 * Pagina 404.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="nt-404">
	<div>
		<p class="nt-404__num" aria-hidden="true">4<span class="nt-404__egg"></span>4</p>
		<h1><?php echo nt_txt( 'e404_title' ); // phpcs:ignore ?></h1>
		<?php if ( nt_txt( 'e404_text' ) ) : ?>
			<p><?php echo nt_txt( 'e404_text' ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<form class="nt-msearch" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo nt_icon( 'search', 19 ); // phpcs:ignore ?>
			<label class="screen-reader-text" for="nt-404-search">Caută produse</label>
			<input id="nt-404-search" type="search" name="s" placeholder="<?php echo esc_attr( nt_txt_plain( 'e404_placeholder' ) ); ?>" />
			<input type="hidden" name="post_type" value="product" />
		</form>
		<div class="nt-404__actions">
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="nt-btn nt-btn--dark" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo nt_txt( 'e404_shop' ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
			<?php endif; ?>
			<a class="nt-btn nt-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo nt_txt( 'e404_home' ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
<?php
get_footer();
