<?php
/**
 * Bara de anunțuri + antetul lipicios (logo, meniu cu panou de categorii, căutare, cont, coș).
 */

defined( 'ABSPATH' ) || exit;

$has_wc   = class_exists( 'WooCommerce' );
$shop_id  = $has_wc ? (int) wc_get_page_id( 'shop' ) : 0;
$free_min = nt_free_shipping_min();
$tree     = nt_menu_tree( 'primary' );
?>
<div class="nt-topbar">
	<div class="nt-wrap nt-topbar__in">
		<div class="nt-topbar__rotator" data-nt-rotator>
			<?php if ( $free_min ) : ?>
				<p class="is-active"><?php echo nt_icon( 'gift', 16 ); // phpcs:ignore ?><span>Livrare gratuită în Chișinău de la <strong><?php echo esc_html( nt_money( $free_min ) ); ?></strong></span></p>
			<?php endif; ?>
			<p<?php echo $free_min ? '' : ' class="is-active"'; ?>><?php echo nt_icon( 'clock', 16 ); // phpcs:ignore ?><span>Livrăm în <strong>1–48 de ore</strong> de la confirmare</span></p>
			<p><?php echo nt_icon( 'coins', 16 ); // phpcs:ignore ?><span>Plătești <strong>la primire</strong>, fără avans</span></p>
		</div>
		<div class="nt-topbar__links">
			<a href="tel:<?php echo esc_attr( nt_contact( 'phone_raw' ) ); ?>"><?php echo nt_icon( 'phone', 15 ); // phpcs:ignore ?><?php echo esc_html( nt_contact( 'phone' ) ); ?></a>
			<a href="mailto:<?php echo esc_attr( nt_contact( 'email' ) ); ?>"><?php echo nt_icon( 'mail', 15 ); // phpcs:ignore ?><?php echo esc_html( nt_contact( 'email' ) ); ?></a>
		</div>
	</div>
</div>

<header id="masthead" class="nt-header" data-nt-header>
	<div class="nt-wrap nt-header__in">
		<button type="button" class="nt-iconbtn nt-burger" data-nt-open="menu" aria-controls="nt-menu" aria-expanded="false">
			<?php echo nt_icon( 'menu', 22 ); // phpcs:ignore ?><span class="screen-reader-text">Meniu</span>
		</button>

		<a class="nt-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — prima pagină">
			<?php echo nt_logo( 'dark', 150 ); // phpcs:ignore ?>
		</a>

		<nav class="nt-nav" aria-label="Meniu principal">
			<ul class="nt-nav__list">
				<?php
				foreach ( $tree as $node ) :
					$item     = $node['item'];
					$current  = nt_menu_is_current( $item );
					$is_shop  = 'page' === $item->object && (int) $item->object_id === $shop_id;
					$children = $node['children'];
					?>
					<li class="nt-nav__item<?php echo $children ? ' has-sub' : ''; ?><?php echo $is_shop && $children ? ' has-mega' : ''; ?>">
						<a class="nt-nav__link<?php echo $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $item->url ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item->title ); ?></a>
						<?php if ( $children ) : ?>
							<button type="button" class="nt-nav__toggle" aria-expanded="false" aria-label="<?php echo esc_attr( 'Deschide submeniul ' . $item->title ); ?>"><?php echo nt_icon( 'chevron', 16 ); // phpcs:ignore ?></button>
							<?php if ( $is_shop ) : ?>
								<div class="nt-mega">
									<div class="nt-mega__in">
										<div class="nt-mega__main">
											<div class="nt-mega__head">
												<span class="nt-eyebrow">Ce găsești la noi</span>
												<a class="nt-link-arrow" href="<?php echo esc_url( $item->url ); ?>">Toate produsele <?php echo nt_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
											</div>
											<ul class="nt-mega__grid">
												<?php
												foreach ( $children as $child ) :
													$c    = $child['item'];
													$term = 'product_cat' === $c->object ? get_term( (int) $c->object_id, 'product_cat' ) : null;
													?>
													<li>
														<a class="nt-mega__cat" href="<?php echo esc_url( $c->url ); ?>">
															<span class="nt-mega__thumb nt-tint--<?php echo esc_attr( nt_tint( $c->object_id ) ); ?>"><?php echo $term && ! is_wp_error( $term ) ? nt_term_image( $term, 'thumbnail' ) : ''; // phpcs:ignore ?></span>
															<span class="nt-mega__txt">
																<strong><?php echo esc_html( $c->title ); ?></strong>
																<?php if ( $term && ! is_wp_error( $term ) ) : ?>
																	<small><?php echo esc_html( nt_count_label( nt_term_count( $term ) ) ); ?></small>
																<?php endif; ?>
															</span>
														</a>
													</li>
												<?php endforeach; ?>
											</ul>
										</div>
										<aside class="nt-mega__promo">
											<span class="nt-mega__promo-ico"><?php echo nt_icon( 'truck', 28 ); // phpcs:ignore ?></span>
											<?php if ( $free_min ) : ?>
												<p class="nt-mega__promo-title">Livrare gratuită de la <?php echo esc_html( nt_money( $free_min ) ); ?></p>
											<?php else : ?>
												<p class="nt-mega__promo-title">Livrare rapidă în Chișinău</p>
											<?php endif; ?>
											<p>În Chișinău în 1–48 de ore. Restul țării — prin Poșta Moldovei. Plătești la primire.</p>
											<a class="nt-btn nt-btn--leaf nt-btn--sm" href="<?php echo esc_url( get_permalink( get_page_by_path( 'livrare-si-plata' ) ) ); ?>">Cum livrăm <?php echo nt_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
										</aside>
									</div>
								</div>
							<?php else : ?>
								<ul class="nt-sub">
									<?php foreach ( $children as $child ) : ?>
										<li><a href="<?php echo esc_url( $child['item']->url ); ?>"><?php echo esc_html( $child['item']->title ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="nt-actions">
			<a class="nt-searchbtn" href="<?php echo esc_url( home_url( '/?s=&post_type=product' ) ); ?>" data-nt-open="search" aria-controls="nt-search" aria-expanded="false">
				<?php echo nt_icon( 'search', 19 ); // phpcs:ignore ?>
				<span class="nt-searchbtn__label">Caută produse…</span>
				<kbd class="nt-searchbtn__kbd" aria-hidden="true">/</kbd>
			</a>
			<?php if ( $has_wc ) : ?>
				<a class="nt-iconbtn nt-account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare'; ?>" data-tip="<?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare'; ?>">
					<?php echo nt_icon( 'user', 21 ); // phpcs:ignore ?>
					<?php if ( is_user_logged_in() ) : ?><span class="nt-account__dot" aria-hidden="true"></span><?php endif; ?>
				</a>
				<a class="nt-cartbtn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-nt-open="cart" aria-controls="nt-cart" aria-expanded="false">
					<span class="nt-cartbtn__ico"><?php echo nt_icon( 'bag', 21 ); // phpcs:ignore ?><?php echo nt_cart_count_html(); // phpcs:ignore ?></span>
					<?php echo nt_cart_total_html(); // phpcs:ignore ?>
					<span class="screen-reader-text">Coșul de cumpărături</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
