<?php
/**
 * Widget Elementor „Natur: Rețete video” — cartonașele din secțiunea „Rețete și idei delicioase”.
 * Afișarea: nt_recipes_html() din inc/recipes.php.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class NT_Recipes_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'natur_recipes';
	}

	public function get_title() {
		return 'Natur: Rețete video';
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'rețete', 'retete', 'video', 'instagram', 'reel', 'natur' );
	}

	/** Produsele publicate, pentru lista „Produsul din rețetă”. */
	private function product_options() {
		$options = array( '' => '— fără produs —' );
		$ids     = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $ids as $id ) {
			$options[ $id ] = get_the_title( $id );
		}
		return $options;
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Rețete' ) );

		$item = new Repeater();
		$item->add_control(
			'image',
			array(
				'label'       => 'Copertă',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
				'description' => 'Fotografie verticală (3:4), de cel puțin 600 px lățime.',
			)
		);
		$item->add_control(
			'title',
			array(
				'label'       => 'Titlu',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'Rețetă nouă',
				'description' => 'Enter = rând nou pe cartonaș.',
			)
		);
		$item->add_control(
			'link',
			array(
				'label'       => 'Link spre clip',
				'type'        => Controls_Manager::URL,
				'options'     => false,
				'placeholder' => 'https://www.instagram.com/reel/…',
				'description' => 'Reel sau postare de Instagram ori clip YouTube — rulează pe site, într-o fereastră. Alt link (de ex. profilul) se deschide într-o filă nouă.',
			)
		);
		$item->add_control(
			'video',
			array(
				'label'       => 'Sau un clip MP4 din Media',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'description' => 'Opțional. Are prioritate față de link.',
			)
		);
		$item->add_control(
			'product',
			array(
				'label'       => 'Produsul din rețetă',
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->product_options(),
				'default'     => '',
				'description' => 'Apare sub cartonaș, cu butonul „Adaugă în coș”.',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'Cartonașe',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $item->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array( array( 'title' => 'Rețetă nouă' ) ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'texts', array( 'label' => 'Texte' ) );
		$this->add_control(
			'prod_label',
			array(
				'label'   => 'Deasupra produsului',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Gătește cu',
			)
		);
		$this->add_control(
			'reel_label',
			array(
				'label'   => 'Deasupra titlului, în fereastra clipului',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Rețetă video',
			)
		);
		$this->add_control(
			'more_label',
			array(
				'label'       => 'Linkul spre clipul original',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Deschide pe %s',
				'description' => '%s devine „Instagram” sau „YouTube”.',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo nt_recipes_html( (array) $s['items'], array_intersect_key( $s, array_flip( array( 'prod_label', 'reel_label', 'more_label' ) ) ) ); // phpcs:ignore
	}
}
