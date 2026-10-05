<?php
/**
 * Banda „Te ajutăm la telefon” + subsolul. Textele: Aspect → Personalizare → Natur.MD → Subsol.
 */

defined( 'ABSPATH' ) || exit;

$has_wc  = class_exists( 'WooCommerce' );
$minimal = $has_wc && ( is_checkout() || is_cart() );
// Banda nu apare pe pagina care are deja cardurile de contact (telefon, Messenger).
$help = ! $minimal && nt_opt( 'help_show' ) && nt_contact( 'phone' ) && ! nt_page_has_section( 'contact' );
$word = nt_plain( nt_opt( 'footer_word' ) );
$dot  = strrpos( $word, '.' );
// Politica de confidențialitate (Setări → Confidențialitate) și cea de cookies apar pe toate paginile, și în coș/la comandă.
$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
$legal   = array_filter( array_unique( array( $privacy, (int) nt_opt( 'cookie_page' ) ) ), static fn( $id ) => $id && 'publish' === get_post_status( $id ) );
?>
<footer class="nt-footer<?php echo $minimal ? ' is-minimal' : ( $help ? '' : ' no-help' ); ?>" id="colophon">
	<?php if ( $help ) : ?>
		<section class="nt-wrap nt-help" aria-labelledby="nt-help-title" data-reveal>
			<div class="nt-help__card">
				<div class="nt-help__art" aria-hidden="true">
					<?php if ( nt_txt( 'help_bubble_1' ) ) : ?>
						<span class="nt-help__bubble nt-help__bubble--1"><?php echo nt_txt( 'help_bubble_1' ); // phpcs:ignore ?></span>
					<?php endif; ?>
					<?php if ( nt_txt( 'help_bubble_2' ) ) : ?>
						<span class="nt-help__bubble nt-help__bubble--2"><?php echo nt_txt( 'help_bubble_2' ); // phpcs:ignore ?></span>
					<?php endif; ?>
					<span class="nt-help__phone"><?php echo nt_icon( 'phone', 34 ); // phpcs:ignore ?></span>
				</div>
				<div class="nt-help__copy">
					<?php if ( nt_txt( 'help_eyebrow' ) ) : ?>
						<p class="nt-eyebrow"><?php echo nt_txt( 'help_eyebrow' ); // phpcs:ignore ?></p>
					<?php endif; ?>
					<h2 id="nt-help-title" class="nt-help__title"><?php echo nt_txt( 'help_title' ); // phpcs:ignore ?></h2>
					<?php if ( nt_txt( 'help_text' ) ) : ?>
						<p><?php echo nt_txt( 'help_text' ); // phpcs:ignore ?></p>
					<?php endif; ?>
					<div class="nt-help__actions">
						<a class="nt-btn nt-btn--dark nt-btn--lg" href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo nt_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
						<?php if ( nt_contact( 'messenger' ) && nt_txt( 'help_chat' ) ) : ?>
							<a class="nt-btn nt-btn--light nt-btn--lg" href="<?php echo esc_url( nt_contact( 'messenger' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_icon( 'chat', 18 ); // phpcs:ignore ?> <?php echo nt_txt( 'help_chat' ); // phpcs:ignore ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="nt-footer__body">
		<?php if ( ! $minimal ) : ?>
			<div class="nt-wrap nt-footer__grid">
				<div class="nt-footer__brand">
					<a class="nt-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><?php echo nt_logo( 'light', 150 ); // phpcs:ignore ?></a>
					<?php if ( nt_txt( 'footer_about' ) ) : ?>
						<p><?php echo nt_txt( 'footer_about' ); // phpcs:ignore ?></p>
					<?php endif; ?>
					<div class="nt-social">
						<?php
						$social = array(
							'facebook'  => array( 'facebook', 'Facebook' ),
							'instagram' => array( 'instagram', 'Instagram' ),
							'messenger' => array( 'chat', 'Messenger' ),
						);
						foreach ( $social as $key => [ $icon, $label ] ) :
							if ( nt_contact( $key ) ) :
								?>
								<a href="<?php echo esc_url( nt_contact( $key ) ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $label ); ?>"><?php echo nt_icon( $icon, 20 ); // phpcs:ignore ?></a>
								<?php
							endif;
						endforeach;
						?>
						<?php if ( nt_contact( 'email' ) ) : ?>
							<a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>" aria-label="E-mail"><?php echo nt_icon( 'mail', 20 ); // phpcs:ignore ?></a>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $has_wc ) : ?>
					<nav class="nt-footer__col" aria-label="<?php echo esc_attr( nt_txt_plain( 'footer_shop' ) ); ?>">
						<h2 class="nt-footer__title"><?php echo nt_txt( 'footer_shop' ); // phpcs:ignore ?></h2>
						<ul>
							<?php
							$shop_menu = nt_menu_tree( 'nt_footer_shop' );
							if ( $shop_menu ) :
								foreach ( $shop_menu as $node ) :
									?>
									<li><a href="<?php echo esc_url( $node['item']->url ); ?>"><?php echo esc_html( $node['item']->title ); ?></a></li>
									<?php
								endforeach;
							else :
								foreach ( nt_top_categories( (int) nt_opt( 'footer_shop_num' ) ) as $t ) :
									?>
									<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a></li>
									<?php
								endforeach;
							endif;
							?>
							<?php if ( nt_txt( 'footer_shop_all' ) ) : ?>
								<li><a class="nt-footer__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo nt_txt( 'footer_shop_all' ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 14 ); // phpcs:ignore ?></a></li>
							<?php endif; ?>
						</ul>
					</nav>
				<?php endif; ?>

				<nav class="nt-footer__col" aria-label="<?php echo esc_attr( nt_txt_plain( 'footer_info' ) ); ?>">
					<h2 class="nt-footer__title"><?php echo nt_txt( 'footer_info' ); // phpcs:ignore ?></h2>
					<ul>
						<?php foreach ( nt_menu_tree( 'footer_menu' ) as $node ) : ?>
							<li><a href="<?php echo esc_url( $node['item']->url ); ?>"><?php echo esc_html( $node['item']->title ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<div class="nt-footer__col nt-footer__contact">
					<h2 class="nt-footer__title"><?php echo nt_txt( 'footer_contact' ); // phpcs:ignore ?></h2>
					<?php if ( nt_contact( 'phone' ) ) : ?>
						<a class="nt-footer__phone" href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
					<?php endif; ?>
					<ul>
						<?php if ( nt_contact( 'email' ) ) : ?>
							<li><?php echo nt_icon( 'mail', 16 ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>"><?php echo esc_html( nt_contact( 'email' ) ); ?></a></li>
						<?php endif; ?>
						<?php if ( nt_contact( 'area' ) ) : ?>
							<li><?php echo nt_icon( 'pin', 16 ); // phpcs:ignore ?><span><?php echo esc_html( nt_contact( 'area' ) ); ?></span></li>
						<?php endif; ?>
						<?php if ( nt_txt( 'footer_pay' ) ) : ?>
							<li><?php echo nt_icon( 'coins', 16 ); // phpcs:ignore ?><span><?php echo nt_txt( 'footer_pay' ); // phpcs:ignore ?></span></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>

			<?php if ( '' !== $word ) : ?>
				<div class="nt-footer__word" aria-hidden="true"><?php echo false === $dot || 0 === $dot ? esc_html( $word ) : esc_html( substr( $word, 0, $dot ) ) . '<span>' . esc_html( substr( $word, $dot ) ) . '</span>'; ?></div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="nt-footer__bottom">
			<div class="nt-wrap nt-footer__bottom-in">
				<div class="nt-footer__meta">
					<p><?php echo nt_txt( 'footer_copy' ); // phpcs:ignore ?></p>
					<?php if ( $legal ) : ?>
						<nav class="nt-footer__legal" aria-label="Informații legale">
							<ul>
								<?php foreach ( $legal as $id ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"<?php echo $privacy === $id ? ' rel="privacy-policy"' : ''; ?>><?php echo esc_html( get_the_title( $id ) ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</nav>
					<?php endif; ?>
				</div>
				<?php if ( nt_txt( 'footer_love' ) ) : ?>
					<p class="nt-footer__love"><?php echo nt_txt( 'footer_love', array( 'inima' => nt_icon( 'heart', 14 ) ) ); // phpcs:ignore ?></p>
				<?php endif; ?>
				<button type="button" class="nt-totop" data-nt-totop aria-label="Mergi sus"><?php echo nt_icon( 'arrow-up', 18 ); // phpcs:ignore ?></button>
			</div>
		</div>
	</div>
</footer>
