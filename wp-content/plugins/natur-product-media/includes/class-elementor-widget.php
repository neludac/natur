<?php
/**
 * Widget Elementor: afișează prezentarea 360° sau clipurile video ale unui produs.
 */

defined( 'ABSPATH' ) || exit;

class Natur_PM_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'natur_product_media';
	}

	public function get_title() {
		return __( 'Produs: Video / 360°', 'natur-product-media' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	public function get_keywords() {
		return array( '360', 'video', 'produs', 'product', 'spin' );
	}

	public function get_style_depends() {
		return array( 'natur-pm' );
	}

	public function get_script_depends() {
		return array( 'natur-pm' );
	}

	private function product_options() {
		$options = array( '' => __( '— Produsul curent —', 'natur-product-media' ) );
		$ids     = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'OR',
					array( 'key' => Natur_Product_Media::META_FRAMES, 'compare' => 'EXISTS' ),
					array( 'key' => Natur_Product_Media::META_VIDEOS, 'compare' => 'EXISTS' ),
				),
			)
		);
		foreach ( $ids as $id ) {
			$options[ $id ] = get_the_title( $id );
		}
		return $options;
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Conținut', 'natur-product-media' ) ) );
		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Produs', 'natur-product-media' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->product_options(),
				'default'     => '',
				'description' => __( 'Sunt listate produsele care au video sau cadre 360°.', 'natur-product-media' ),
			)
		);
		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Afișează', 'natur-product-media' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'360'   => __( 'Prezentare 360°', 'natur-product-media' ),
					'video' => __( 'Video', 'natur-product-media' ),
				),
				'default' => '360',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = absint( $s['product_id'] );
		$sc = '360' === $s['mode'] ? 'natur_360' : 'natur_video';
		$html = do_shortcode( sprintf( '[%s id="%d"]', $sc, $id ) );
		if ( ! $html && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$html = '<p style="padding:1em;border:1px dashed #ccc;text-align:center">' . esc_html__( 'Produsul ales nu are conținut de acest tip.', 'natur-product-media' ) . '</p>';
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
