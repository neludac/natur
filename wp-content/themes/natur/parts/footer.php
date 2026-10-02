<?php
/**
 * Banda „Te ajutăm la telefon” + subsolul.
 */

defined( 'ABSPATH' ) || exit;

$has_wc = class_exists( 'WooCommerce' );
$minimal = $has_wc && ( is_checkout() || is_cart() );
$help    = ! $minimal && ! is_page( 'contact' ); // pagina Contact are deja telefonul și Messenger
?>
<footer class="nt-footer<?php echo $minimal ? ' is-minimal' : ( $help ? '' : ' no-help' ); ?>" id="colophon">
	<?php if ( $help ) : ?>
		<section class="nt-wrap nt-help" aria-labelledby="nt-help-title" data-reveal>
			<div class="nt-help__card">
				<div class="nt-help__art" aria-hidden="true">
					<span class="nt-help__bubble nt-help__bubble--1">Bună! Ce-mi recomandați pentru micul dejun?</span>
					<span class="nt-help__bubble nt-help__bubble--2">Ouă de casă și brânză proaspătă!</span>
					<span class="nt-help__phone"><?php echo nt_icon( 'phone', 34 ); // phpcs:ignore ?></span>
				</div>
				<div class="nt-help__copy">
					<p class="nt-eyebrow">Suntem aici pentru tine</p>
					<h2 id="nt-help-title" class="nt-help__title">Preferi să comanzi <em>la telefon?</em></h2>
					<p>Te ajutăm să alegi produsele potrivite și confirmăm comanda în câteva minute.</p>
					<div class="nt-help__actions">
						<a class="nt-btn nt-btn--dark nt-btn--lg" href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo nt_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
						<a class="nt-btn nt-btn--light nt-btn--lg" href="<?php echo esc_url( nt_contact( 'messenger' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_icon( 'chat', 18 ); // phpcs:ignore ?> Scrie-ne pe Messenger</a>
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
					<p>Produse naturale de la gospodari și mici producători din Moldova. Fără aditivi, fără E-uri — mâncare adevărată, ca pentru propria familie.</p>
					<div class="nt-social">
						<a href="<?php echo esc_url( nt_contact( 'facebook' ) ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo nt_icon( 'facebook', 20 ); // phpcs:ignore ?></a>
						<a href="<?php echo esc_url( nt_contact( 'messenger' ) ); ?>" target="_blank" rel="noopener" aria-label="Messenger"><?php echo nt_icon( 'chat', 20 ); // phpcs:ignore ?></a>
						<a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>" aria-label="E-mail"><?php echo nt_icon( 'mail', 20 ); // phpcs:ignore ?></a>
					</div>
				</div>

				<?php if ( $has_wc ) : ?>
					<nav class="nt-footer__col" aria-label="Categorii">
						<h2 class="nt-footer__title">Magazin</h2>
						<ul>
							<?php foreach ( nt_top_categories( 7 ) as $t ) : ?>
								<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a></li>
							<?php endforeach; ?>
							<li><a class="nt-footer__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Toate produsele <?php echo nt_icon( 'arrow-right', 14 ); // phpcs:ignore ?></a></li>
						</ul>
					</nav>
				<?php endif; ?>

				<nav class="nt-footer__col" aria-label="Informații">
					<h2 class="nt-footer__title">Informații</h2>
					<ul>
						<?php foreach ( nt_menu_tree( 'footer_menu' ) as $node ) : ?>
							<li><a href="<?php echo esc_url( $node['item']->url ); ?>"><?php echo esc_html( $node['item']->title ); ?></a></li>
						<?php endforeach; ?>
						<?php
						$about = get_page_by_path( 'despre-noi' );
						if ( $about ) :
							?>
							<li><a href="<?php echo esc_url( get_permalink( $about ) ); ?>">Despre noi</a></li>
						<?php endif; ?>
					</ul>
				</nav>

				<div class="nt-footer__col nt-footer__contact">
					<h2 class="nt-footer__title">Contact</h2>
					<a class="nt-footer__phone" href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
					<ul>
						<li><?php echo nt_icon( 'mail', 16 ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>"><?php echo esc_html( nt_contact( 'email' ) ); ?></a></li>
						<li><?php echo nt_icon( 'pin', 16 ); // phpcs:ignore ?><span><?php echo esc_html( nt_contact( 'area' ) ); ?></span></li>
						<li><?php echo nt_icon( 'coins', 16 ); // phpcs:ignore ?><span>Plata la livrare, în numerar</span></li>
					</ul>
				</div>
			</div>

			<div class="nt-footer__word" aria-hidden="true">natur<span>.md</span></div>
		<?php endif; ?>

		<div class="nt-footer__bottom">
			<div class="nt-wrap nt-footer__bottom-in">
				<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Natur.MD — produse naturale din Moldova.</p>
				<p class="nt-footer__love">Făcut cu <?php echo nt_icon( 'heart', 14 ); // phpcs:ignore ?> în Moldova</p>
				<button type="button" class="nt-totop" data-nt-totop aria-label="Mergi sus"><?php echo nt_icon( 'arrow-up', 18 ); // phpcs:ignore ?></button>
			</div>
		</div>
	</div>
</footer>
