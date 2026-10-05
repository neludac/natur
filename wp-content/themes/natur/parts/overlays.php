<?php
/**
 * Panouri: căutare, meniu (mobil), coș lateral; notificări; bara de navigare pentru mobil.
 */

defined( 'ABSPATH' ) || exit;

$has_wc  = class_exists( 'WooCommerce' );
$shop_id = $has_wc ? (int) wc_get_page_id( 'shop' ) : 0;
$mtree   = nt_menu_tree( 'mobile_menu' );
$mtree   = $mtree ? $mtree : nt_menu_tree( 'primary' );
$popular = nt_opt_lines( 'search_popular' );
?>
<div class="nt-search" id="nt-search" role="dialog" aria-modal="true" aria-label="Căutare produse" hidden>
	<div class="nt-scrim" data-nt-close></div>
	<div class="nt-search__panel">
		<div class="nt-wrap">
			<form class="nt-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo nt_icon( 'search', 24 ); // phpcs:ignore ?>
				<label class="screen-reader-text" for="nt-search-input">Caută produse</label>
				<input id="nt-search-input" class="nt-search__input" type="search" name="s" placeholder="<?php echo esc_attr( nt_txt_plain( 'search_placeholder' ) ); ?>" autocomplete="off" spellcheck="false" aria-controls="nt-search-results" aria-describedby="nt-search-hint" />
				<input type="hidden" name="post_type" value="product" />
				<button type="button" class="nt-iconbtn nt-search__close" data-nt-close aria-label="Închide căutarea"><?php echo nt_icon( 'close', 22 ); // phpcs:ignore ?></button>
			</form>
			<div class="nt-search__body">
				<div class="nt-search__popular" data-nt-popular>
					<?php if ( $popular ) : ?>
						<p id="nt-search-hint" class="nt-search__label"><?php echo nt_txt( 'search_popular_lbl' ); // phpcs:ignore ?></p>
						<div class="nt-chips">
							<?php foreach ( $popular as $q ) : ?>
								<a class="nt-chip" href="<?php echo esc_url( add_query_arg( array( 's' => $q, 'post_type' => 'product' ), home_url( '/' ) ) ); ?>" data-nt-q="<?php echo esc_attr( $q ); ?>"><?php echo esc_html( $q ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="nt-search__results" id="nt-search-results" aria-live="polite"></div>
			</div>
		</div>
	</div>
</div>

<div class="nt-drawer nt-drawer--left" id="nt-menu" role="dialog" aria-modal="true" aria-label="Meniu" hidden>
	<div class="nt-scrim" data-nt-close></div>
	<div class="nt-drawer__panel">
		<div class="nt-drawer__head">
			<a class="nt-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo nt_logo( 'dark', 120 ); // phpcs:ignore ?></a>
			<button type="button" class="nt-iconbtn" data-nt-close aria-label="Închide meniul"><?php echo nt_icon( 'close', 22 ); // phpcs:ignore ?></button>
		</div>
		<div class="nt-drawer__body">
			<form class="nt-msearch" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo nt_icon( 'search', 19 ); // phpcs:ignore ?>
				<label class="screen-reader-text" for="nt-msearch-input">Caută produse</label>
				<input id="nt-msearch-input" type="search" name="s" placeholder="<?php echo esc_attr( nt_txt_plain( 'search_label' ) ); ?>" />
				<input type="hidden" name="post_type" value="product" />
			</form>
			<nav class="nt-mnav" aria-label="Meniu mobil">
				<ul>
					<?php
					foreach ( $mtree as $node ) :
						$item    = $node['item'];
						$current = nt_menu_is_current( $item );
						if ( $node['children'] ) :
							?>
							<li class="nt-mnav__group">
								<details<?php echo 'page' === $item->object && (int) $item->object_id === $shop_id ? ' open' : ''; ?>>
									<summary class="<?php echo $current ? 'is-current' : ''; ?>"><?php echo esc_html( $item->title ); ?><?php echo nt_icon( 'chevron', 18 ); // phpcs:ignore ?></summary>
									<ul class="nt-mnav__sub">
										<li><a class="nt-mnav__all" href="<?php echo esc_url( $item->url ); ?>"><span class="nt-mnav__thumb nt-tint--sage"><?php echo nt_icon( 'grid', 18 ); // phpcs:ignore ?></span><?php echo nt_txt( 'mega_all' ); // phpcs:ignore ?></a></li>
										<?php
										foreach ( $node['children'] as $child ) :
											$c    = $child['item'];
											$term = 'product_cat' === $c->object ? get_term( (int) $c->object_id, 'product_cat' ) : null;
											?>
											<li><a href="<?php echo esc_url( $c->url ); ?>"><span class="nt-mnav__thumb nt-tint--<?php echo esc_attr( nt_tint( $c->object_id ) ); ?>"><?php echo $term && ! is_wp_error( $term ) ? nt_term_image( $term, 'thumbnail' ) : ''; // phpcs:ignore ?></span><?php echo esc_html( $c->title ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</details>
							</li>
						<?php else : ?>
							<li><a class="nt-mnav__link<?php echo $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( $has_wc ) : ?>
						<li><a class="nt-mnav__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare / cont nou'; ?></a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</div>
		<div class="nt-drawer__foot">
			<?php if ( nt_contact( 'phone' ) ) : ?>
				<a class="nt-btn nt-btn--dark nt-btn--block" href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo nt_icon( 'phone', 18 ); // phpcs:ignore ?> Sună: <?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
			<?php endif; ?>
			<div class="nt-drawer__links">
				<?php if ( nt_contact( 'email' ) ) : ?>
					<a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>"><?php echo nt_icon( 'mail', 16 ); // phpcs:ignore ?><?php echo esc_html( nt_contact( 'email' ) ); ?></a>
				<?php endif; ?>
				<?php if ( nt_contact( 'facebook' ) ) : ?>
					<a href="<?php echo esc_url( nt_contact( 'facebook' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_icon( 'facebook', 16 ); // phpcs:ignore ?>Facebook</a>
				<?php endif; ?>
				<?php if ( nt_contact( 'instagram' ) ) : ?>
					<a href="<?php echo esc_url( nt_contact( 'instagram' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_icon( 'instagram', 16 ); // phpcs:ignore ?>Instagram</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php if ( $has_wc ) : ?>
	<div class="nt-drawer nt-drawer--right" id="nt-cart" role="dialog" aria-modal="true" aria-labelledby="nt-cart-title" hidden>
		<div class="nt-scrim" data-nt-close></div>
		<div class="nt-drawer__panel">
			<div class="nt-drawer__head">
				<h2 id="nt-cart-title" class="nt-drawer__title">Coșul tău <?php echo nt_cart_count_html( true ); // phpcs:ignore ?></h2>
				<button type="button" class="nt-iconbtn" data-nt-close aria-label="Închide coșul"><?php echo nt_icon( 'close', 22 ); // phpcs:ignore ?></button>
			</div>
			<?php echo nt_freeship_html(); // phpcs:ignore ?>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</div>
	</div>

	<?php if ( ! is_cart() && ! is_checkout() && ! is_product() ) : ?>
		<nav class="nt-tabbar" aria-label="Navigare rapidă">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><?php echo nt_icon( 'home', 22 ); // phpcs:ignore ?><span>Acasă</span></a>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" data-nt-open="menu" aria-controls="nt-menu"<?php echo is_shop() || is_product_taxonomy() ? ' aria-current="page"' : ''; ?>><?php echo nt_icon( 'grid', 22 ); // phpcs:ignore ?><span>Categorii</span></a>
			<a href="<?php echo esc_url( home_url( '/?s=&post_type=product' ) ); ?>" data-nt-open="search" aria-controls="nt-search"><?php echo nt_icon( 'search', 22 ); // phpcs:ignore ?><span>Caută</span></a>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-nt-open="cart" aria-controls="nt-cart"><span class="nt-tabbar__ico"><?php echo nt_icon( 'bag', 22 ); // phpcs:ignore ?><?php echo nt_cart_count_html(); // phpcs:ignore ?></span><span>Coș</span></a>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"<?php echo is_account_page() ? ' aria-current="page"' : ''; ?>><?php echo nt_icon( 'user', 22 ); // phpcs:ignore ?><span>Cont</span></a>
		</nav>
	<?php endif; ?>

	<?php nt_sticky_add_to_cart(); ?>
<?php endif; ?>

<div class="nt-toasts" aria-live="polite" aria-atomic="false"></div>
