<?php
/**
 * Plugin Name: Natur – Video & 360° produs
 * Description: Adaugă pe pagina produsului WooCommerce clipuri video (YouTube, Vimeo, MP4) și prezentare 360° din secvență de cadre. Include shortcode-uri și widget Elementor.
 * Version: 1.0.2
 * Author: Natur.MD
 * Requires Plugins: woocommerce
 * Text Domain: natur-product-media
 */

defined( 'ABSPATH' ) || exit;

final class Natur_Product_Media {

	const VERSION     = '1.0.2';
	const META_VIDEOS = '_natur_videos';
	const META_FRAMES = '_natur_360_frames';
	const NONCE       = 'natur_product_media';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'open_wrap' ), 19 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'close_wrap' ), 21 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'loop_badges' ), 9 );

		add_shortcode( 'natur_360', array( __CLASS__, 'shortcode_360' ) );
		add_shortcode( 'natur_video', array( __CLASS__, 'shortcode_video' ) );

		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_elementor_widget' ) );
	}

	/* ---------------------------------------------------------------- Date */

	public static function get_videos( $product_id ) {
		$videos = get_post_meta( $product_id, self::META_VIDEOS, true );
		return is_array( $videos ) ? array_values( array_filter( $videos ) ) : array();
	}

	public static function get_frames( $product_id ) {
		$frames = get_post_meta( $product_id, self::META_FRAMES, true );
		if ( ! is_array( $frames ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $frames ), 'wp_attachment_is_image' ) );
	}

	/**
	 * Transformă un URL video într-o sursă redabilă.
	 *
	 * @return array{type:string,src:string,thumb:string}|null
	 */
	public static function parse_video( $url ) {
		$url = trim( $url );
		if ( preg_match( '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
			return array(
				'type'  => 'iframe',
				'src'   => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1',
				'thumb' => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg',
			);
		}
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
			return array(
				'type'  => 'iframe',
				'src'   => 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1',
				'thumb' => '',
			);
		}
		if ( preg_match( '~\.(mp4|webm|ogv|mov|m4v)(\?.*)?$~i', $url ) ) {
			$thumb = '';
			$id    = attachment_url_to_postid( $url );
			if ( $id && has_post_thumbnail( $id ) ) {
				$thumb = get_the_post_thumbnail_url( $id, 'woocommerce_single' );
			}
			return array(
				'type'  => 'file',
				'src'   => $url,
				'thumb' => $thumb,
			);
		}
		return null;
	}

	/* --------------------------------------------------------------- Admin */

	public static function add_meta_box() {
		add_meta_box(
			'natur-product-media',
			__( 'Video & prezentare 360°', 'natur-product-media' ),
			array( __CLASS__, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	public static function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		$url = plugin_dir_url( __FILE__ ) . 'assets/';
		wp_enqueue_style( 'natur-pm-admin', $url . 'admin.css', array(), self::VERSION );
		wp_enqueue_script( 'natur-pm-admin', $url . 'admin.js', array( 'jquery', 'jquery-ui-sortable' ), self::VERSION, true );
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		$videos = self::get_videos( $post->ID );
		$frames = self::get_frames( $post->ID );
		?>
		<div class="natur-pm">
			<h4><?php esc_html_e( 'Clipuri video', 'natur-product-media' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Link YouTube / Vimeo sau fișier video (MP4, WebM) din Biblioteca Media. Primul clip este afișat primul.', 'natur-product-media' ); ?></p>
			<div class="natur-pm-videos">
				<?php foreach ( $videos ? $videos : array( '' ) as $video ) : ?>
					<div class="natur-pm-video-row">
						<input type="url" class="widefat" name="natur_videos[]" value="<?php echo esc_attr( $video ); ?>" placeholder="https://www.youtube.com/watch?v=…" />
						<button type="button" class="button natur-pm-pick-video"><?php esc_html_e( 'Din Media', 'natur-product-media' ); ?></button>
						<button type="button" class="button-link-delete natur-pm-remove-video" aria-label="<?php esc_attr_e( 'Șterge', 'natur-product-media' ); ?>">&times;</button>
					</div>
				<?php endforeach; ?>
			</div>
			<p><button type="button" class="button natur-pm-add-video"><?php esc_html_e( '+ Adaugă video', 'natur-product-media' ); ?></button></p>

			<hr />
			<h4><?php esc_html_e( 'Prezentare 360°', 'natur-product-media' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Selectați cadrele fotografiate în jurul produsului (recomandat 24–72 de imagini, aceeași dimensiune). Ordinea se poate schimba prin tragere; „Sortează după nume” le aranjează după numele fișierului.', 'natur-product-media' ); ?></p>
			<input type="hidden" name="natur_360_frames" class="natur-pm-frames-input" value="<?php echo esc_attr( implode( ',', $frames ) ); ?>" />
			<ul class="natur-pm-frames">
				<?php foreach ( $frames as $id ) : ?>
					<li data-id="<?php echo esc_attr( $id ); ?>" data-name="<?php echo esc_attr( basename( get_attached_file( $id ) ) ); ?>"><?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>
				<button type="button" class="button natur-pm-pick-frames"><?php esc_html_e( 'Selectează cadre 360°', 'natur-product-media' ); ?></button>
				<button type="button" class="button natur-pm-sort-frames"><?php esc_html_e( 'Sortează după nume', 'natur-product-media' ); ?></button>
				<button type="button" class="button-link-delete natur-pm-clear-frames"><?php esc_html_e( 'Șterge toate cadrele', 'natur-product-media' ); ?></button>
				<span class="natur-pm-count"><?php echo esc_html( sprintf( _n( '%d cadru', '%d cadre', count( $frames ), 'natur-product-media' ), count( $frames ) ) ); ?></span>
			</p>
		</div>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE . '_nonce' ] ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$videos = isset( $_POST['natur_videos'] ) ? (array) wp_unslash( $_POST['natur_videos'] ) : array();
		$videos = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', $videos ) ) ) );
		self::update_or_delete( $post_id, self::META_VIDEOS, $videos );

		$frames = isset( $_POST['natur_360_frames'] ) ? sanitize_text_field( wp_unslash( $_POST['natur_360_frames'] ) ) : '';
		$frames = array_values( array_filter( array_map( 'absint', explode( ',', $frames ) ) ) );
		self::update_or_delete( $post_id, self::META_FRAMES, $frames );
	}

	private static function update_or_delete( $post_id, $key, $value ) {
		if ( $value ) {
			update_post_meta( $post_id, $key, $value );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	/* ------------------------------------------------------------ Frontend */

	public static function frontend_assets() {
		$url = plugin_dir_url( __FILE__ ) . 'assets/';
		wp_register_style( 'natur-pm', $url . 'frontend.css', array(), self::VERSION );
		wp_register_script( 'natur-pm', $url . 'frontend.js', array(), self::VERSION, true );
		wp_localize_script(
			'natur-pm',
			'naturPM',
			array(
				'loading' => __( 'Se încarcă', 'natur-product-media' ),
				'hint'    => __( 'Trageți pentru a roti', 'natur-product-media' ),
			)
		);
		wp_enqueue_style( 'natur-pm' );
		if ( is_product() ) {
			$id = get_queried_object_id();
			if ( self::get_videos( $id ) || self::get_frames( $id ) ) {
				wp_enqueue_script( 'natur-pm' );
			}
		}
	}

	private static $wrapped = false;

	public static function open_wrap() {
		global $product;
		if ( ! $product || ( ! self::get_videos( $product->get_id() ) && ! self::get_frames( $product->get_id() ) ) ) {
			return;
		}
		self::$wrapped = true;
		echo '<div class="natur-media" data-natur-media>';
	}

	public static function close_wrap() {
		global $product;
		if ( ! self::$wrapped ) {
			return;
		}
		self::$wrapped = false;

		$id     = $product->get_id();
		$videos = self::get_videos( $id );
		$frames = self::get_frames( $id );
		$photos = count( $product->get_gallery_image_ids() ) + ( $product->get_image_id() ? 1 : 0 );

		echo '<div class="natur-media__stage" hidden>';
		if ( $videos ) {
			echo '<div class="natur-media__panel" data-panel="video" hidden>' . self::render_videos( $videos, $product->get_name() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( $frames ) {
			echo '<div class="natur-media__panel" data-panel="spin" hidden>' . self::render_spin( $frames, $product->get_name(), false ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';

		echo '<div class="natur-media__switch" role="tablist" aria-label="' . esc_attr__( 'Tip prezentare', 'natur-product-media' ) . '">';
		printf(
			'<button type="button" role="tab" class="natur-media__btn is-active" aria-selected="true" data-show="photos">%s<span>%s</span></button>',
			self::icon( 'photo' ), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html( sprintf( _n( 'Foto (%d)', 'Foto (%d)', $photos, 'natur-product-media' ), $photos ) )
		);
		if ( $videos ) {
			printf(
				'<button type="button" role="tab" class="natur-media__btn" aria-selected="false" data-show="video">%s<span>%s</span></button>',
				self::icon( 'play' ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( count( $videos ) > 1 ? sprintf( __( 'Video (%d)', 'natur-product-media' ), count( $videos ) ) : __( 'Video', 'natur-product-media' ) )
			);
		}
		if ( $frames ) {
			printf(
				'<button type="button" role="tab" class="natur-media__btn" aria-selected="false" data-show="spin">%s<span>%s</span></button>',
				self::icon( '360' ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html__( '360°', 'natur-product-media' )
			);
		}
		echo '</div></div>';
	}

	public static function render_videos( array $videos, $title ) {
		$items = array_values( array_filter( array_map( array( __CLASS__, 'parse_video' ), $videos ) ) );
		if ( ! $items ) {
			return '';
		}
		$out = '<div class="natur-video">';
		foreach ( $items as $i => $v ) {
			$out .= '<div class="natur-video__item"' . ( $i ? ' hidden' : '' ) . '>';
			if ( 'iframe' === $v['type'] ) {
				$out .= sprintf(
					'<iframe data-src="%s" title="%s" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>',
					esc_url( $v['src'] ),
					esc_attr( $title )
				);
			} else {
				$out .= sprintf(
					'<video controls playsinline preload="none"%s><source src="%s" /></video>',
					$v['thumb'] ? ' poster="' . esc_url( $v['thumb'] ) . '"' : '',
					esc_url( $v['src'] )
				);
			}
			$out .= '</div>';
		}
		if ( count( $items ) > 1 ) {
			$out .= '<div class="natur-video__list">';
			foreach ( $items as $i => $v ) {
				$out .= sprintf(
					'<button type="button" class="natur-video__pick%s" data-index="%d">%s %d</button>',
					$i ? '' : ' is-active',
					$i,
					esc_html__( 'Clip', 'natur-product-media' ),
					$i + 1
				);
			}
			$out .= '</div>';
		}
		return $out . '</div>';
	}

	public static function render_spin( array $frames, $title, $autostart = true ) {
		$urls = array();
		foreach ( $frames as $id ) {
			$urls[] = wp_get_attachment_image_url( $id, 'large' );
		}
		$urls = array_values( array_filter( $urls ) );
		if ( ! $urls ) {
			return '';
		}
		return sprintf(
			'<div class="natur-spin" data-frames="%1$s" data-autostart="%2$s" tabindex="0" role="img" aria-label="%3$s">
				<img class="natur-spin__img" src="%4$s" alt="%3$s" draggable="false" />
				<div class="natur-spin__progress"><span></span></div>
				<div class="natur-spin__hint">%5$s</div>
				<div class="natur-spin__controls">
					<button type="button" data-act="prev" aria-label="%6$s">%9$s</button>
					<button type="button" data-act="play" aria-label="%7$s">%10$s</button>
					<button type="button" data-act="next" aria-label="%8$s">%11$s</button>
					<button type="button" data-act="full" aria-label="%12$s">%13$s</button>
				</div>
			</div>',
			esc_attr( wp_json_encode( $urls ) ),
			$autostart ? '1' : '0',
			esc_attr( sprintf( __( '%s – prezentare 360°', 'natur-product-media' ), $title ) ),
			esc_url( $urls[0] ),
			self::icon( 'drag' ) . '<span>' . esc_html__( 'Trageți pentru a roti', 'natur-product-media' ) . '</span>',
			esc_attr__( 'Rotește la stânga', 'natur-product-media' ),
			esc_attr__( 'Pornește / oprește rotația', 'natur-product-media' ),
			esc_attr__( 'Rotește la dreapta', 'natur-product-media' ),
			self::icon( 'prev' ),
			self::icon( 'pause' ) . self::icon( 'play' ),
			self::icon( 'next' ),
			esc_attr__( 'Ecran complet', 'natur-product-media' ),
			self::icon( 'full' )
		);
	}

	public static function loop_badges() {
		global $product;
		if ( ! $product ) {
			return;
		}
		$badges = '';
		if ( self::get_frames( $product->get_id() ) ) {
			$badges .= '<span class="natur-badge natur-badge--360">360°</span>';
		}
		if ( self::get_videos( $product->get_id() ) ) {
			$badges .= '<span class="natur-badge natur-badge--video">' . self::icon( 'play' ) . esc_html__( 'Video', 'natur-product-media' ) . '</span>';
		}
		if ( $badges ) {
			echo '<span class="natur-badges">' . $badges . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	private static function icon( $name ) {
		$paths = array(
			'photo' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3.2"/><path d="M8 5l1.5-2h5L16 5"/>',
			'play'  => '<path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>',
			'pause' => '<path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor" stroke="none"/>',
			'360'   => '<ellipse cx="12" cy="12" rx="9" ry="4"/><path d="M15 16.5l-2.5-.3 1.6-2"/><path d="M12 3v5"/>',
			'prev'  => '<path d="M15 5l-7 7 7 7"/>',
			'next'  => '<path d="M9 5l7 7-7 7"/>',
			'full'  => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
			'drag'  => '<path d="M7 9l-4 3 4 3M17 9l4 3-4 3M3 12h18"/>',
		);
		return '<svg class="natur-icon natur-icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $name ] . '</svg>';
	}

	/* ------------------------------------------------ Shortcode & Elementor */

	private static function shortcode_product( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts );
		$id   = absint( $atts['id'] );
		if ( ! $id && is_singular( 'product' ) ) {
			$id = get_queried_object_id();
		}
		return $id ? wc_get_product( $id ) : null;
	}

	public static function shortcode_360( $atts ) {
		$product = self::shortcode_product( $atts );
		if ( ! $product || ! self::get_frames( $product->get_id() ) ) {
			return '';
		}
		wp_enqueue_style( 'natur-pm' );
		wp_enqueue_script( 'natur-pm' );
		return '<div class="natur-embed">' . self::render_spin( self::get_frames( $product->get_id() ), $product->get_name() ) . '</div>';
	}

	public static function shortcode_video( $atts ) {
		$product = self::shortcode_product( $atts );
		if ( ! $product || ! self::get_videos( $product->get_id() ) ) {
			return '';
		}
		wp_enqueue_style( 'natur-pm' );
		wp_enqueue_script( 'natur-pm' );
		return '<div class="natur-embed natur-embed--video" data-natur-autoload>' . self::render_videos( self::get_videos( $product->get_id() ), $product->get_name() ) . '</div>';
	}

	public static function register_elementor_widget( $widgets_manager ) {
		require_once __DIR__ . '/includes/class-elementor-widget.php';
		$widgets_manager->register( new Natur_PM_Elementor_Widget() );
	}
}

add_action( 'plugins_loaded', array( 'Natur_Product_Media', 'init' ) );
