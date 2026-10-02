<?php
/**
 * Rețete video (prima pagină): cartonașe cu copertă, titlu, clip și produsul folosit în rețetă.
 *
 * Se editează în Elementor, cu widgetul „Natur: Rețete video”. Clipul se deschide pe site, într-o fereastră:
 * fișier MP4 din Media, reel / postare de Instagram sau clip YouTube. Dacă linkul nu e un clip (de ex. profilul
 * de Instagram), cartonașul îl deschide într-o filă nouă.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'elementor/widgets/register',
	static function ( $widgets ) {
		require_once NT_DIR . '/inc/class-nt-recipes-widget.php';
		$widgets->register( new NT_Recipes_Widget() );
	}
);

/** Adresa de încorporare a unui reel / postări de Instagram sau a unui clip YouTube; '' pentru alte linkuri. */
function nt_embed_url( $url ) {
	if ( preg_match( '~instagram\.com/(?:[\w.]+/)?(p|tv|reels?)/([\w-]+)~i', (string) $url, $m ) ) {
		return sprintf( 'https://www.instagram.com/%s/%s/embed/', in_array( $m[1], array( 'p', 'tv' ), true ) ? $m[1] : 'reel', $m[2] );
	}
	if ( preg_match( '~(?:youtube\.com/(?:shorts/|embed/|watch\?(?:.*&)?v=)|youtu\.be/)([\w-]{11})~i', (string) $url, $m ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&playsinline=1';
	}
	return '';
}

/**
 * Cartonașele de rețete și fereastra în care rulează clipul.
 *
 * @param array $items Elementele widgetului: image{id,url}, title, link{url}, video{url}, product (ID).
 * @param array $text  Etichetele widgetului: prod_label, reel_label, more_label („Deschide pe %s”).
 */
function nt_recipes_html( array $items, array $text = array() ) {
	$text  = wp_parse_args( array_filter( $text ), array( 'prod_label' => 'Gătește cu', 'reel_label' => 'Rețetă video', 'more_label' => 'Deschide pe %s' ) );
	$items = array_values( array_filter( $items, static fn( $it ) => ! empty( $it['image']['url'] ) || ! empty( $it['title'] ) ) );
	if ( ! $items ) {
		return '';
	}
	ob_start();
	?>
	<div class="nt-recipes" data-nt-recipes>
		<ul class="nt-recipes__track" aria-label="Rețete video">
			<?php
			foreach ( $items as $i => $it ) :
				$title   = trim( (string) ( $it['title'] ?? '' ) );
				$url     = (string) ( $it['link']['url'] ?? '' );
				$video   = (string) ( $it['video']['url'] ?? '' );
				$embed   = $video ? '' : nt_embed_url( $url );
				$is_ig   = false !== stripos( $url, 'instagram.com' );
				$site    = $is_ig ? 'Instagram' : ( preg_match( '~youtu\.?be~i', $url ) ? 'YouTube' : '' );
				$product = ! empty( $it['product'] ) && function_exists( 'wc_get_product' ) ? wc_get_product( (int) $it['product'] ) : null;
				$product = $product && 'publish' === $product->get_status() ? $product : null;
				$img_id  = (int) ( $it['image']['id'] ?? 0 );
				$img     = $img_id
					? wp_get_attachment_image( $img_id, 'woocommerce_single', false, array( 'class' => 'nt-recipe__img', 'alt' => '', 'sizes' => '(max-width: 767px) 76vw, (max-width: 1024px) 42vw, 300px' ) )
					: sprintf( '<img class="nt-recipe__img" src="%s" alt="" loading="lazy">', esc_url( $it['image']['url'] ?? '' ) );
				$href    = $video ? $video : $url;
				$tag     = $href ? 'a' : 'div';
				$attrs   = $href ? sprintf(
					' href="%1$s" target="_blank" rel="noopener"%2$s%3$s%4$s',
					esc_url( $href ),
					$video ? ' data-nt-video="' . esc_url( $video ) . '"' : '',
					$embed ? ' data-nt-embed="' . esc_url( $embed ) . '"' : '',
					$site ? sprintf( ' data-nt-link="%s" data-nt-more="%s"', esc_url( $url ), esc_attr( str_replace( '%s', $site, $text['more_label'] ) ) ) : ''
				) : '';
				?>
				<li class="nt-recipe<?php echo $product ? ' has-prod' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
					<div class="nt-recipe__media">
						<?php echo $img; // phpcs:ignore ?>
						<<?php echo $tag; // phpcs:ignore ?> class="nt-recipe__link"<?php echo $attrs; // phpcs:ignore ?>>
							<span class="nt-recipe__play" aria-hidden="true"><?php echo nt_icon( 'play', 26 ); // phpcs:ignore ?></span>
							<span class="nt-recipe__title"><?php echo nl2br( esc_html( $title ) ); // phpcs:ignore ?></span>
							<?php if ( $href ) : ?>
								<span class="screen-reader-text"><?php echo $video || $embed ? '— privește rețeta video' : ( $is_ig ? '— rețeta pe Instagram (se deschide într-o filă nouă)' : '— se deschide într-o filă nouă' ); ?></span>
							<?php endif; ?>
							<?php if ( $is_ig ) : ?>
								<span class="nt-recipe__src" aria-hidden="true"><?php echo nt_icon( 'instagram', 20 ); // phpcs:ignore ?></span>
							<?php endif; ?>
						</<?php echo $tag; // phpcs:ignore ?>>
					</div>
					<?php if ( $product ) : ?>
						<div class="nt-recipe__prod">
							<?php echo wp_get_attachment_image( $product->get_image_id(), 'thumbnail', false, array( 'class' => 'nt-recipe__pimg', 'alt' => '' ) ); ?>
							<span class="nt-recipe__ptxt">
								<span class="nt-recipe__ptag"><?php echo esc_html( $text['prod_label'] ); ?></span>
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
								<span class="price"><?php echo $product->get_price_html(); // phpcs:ignore ?></span>
							</span>
							<?php echo nt_card_button( $product ); // phpcs:ignore ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<dialog class="nt-reel" aria-labelledby="nt-reel-title" data-nt-reel>
			<div class="nt-reel__box">
				<button type="button" class="nt-iconbtn nt-reel__close" data-nt-reel-close aria-label="Închide clipul"><?php echo nt_icon( 'close', 22 ); // phpcs:ignore ?></button>
				<div class="nt-reel__player" data-nt-reel-player></div>
				<div class="nt-reel__info">
					<p class="nt-eyebrow"><?php echo esc_html( $text['reel_label'] ); ?></p>
					<h3 class="nt-reel__title" id="nt-reel-title" data-nt-reel-title></h3>
					<div class="nt-reel__prod" data-nt-reel-prod></div>
					<a class="nt-link-arrow nt-reel__more" href="#" target="_blank" rel="noopener" data-nt-reel-more hidden><?php echo esc_html( str_replace( '%s', 'Instagram', $text['more_label'] ) ); ?> <?php echo nt_icon( 'arrow-out', 16 ); // phpcs:ignore ?></a>
				</div>
			</div>
		</dialog>
	</div>
	<?php
	return ob_get_clean();
}
