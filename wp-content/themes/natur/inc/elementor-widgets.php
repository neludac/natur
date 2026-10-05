<?php
/**
 * Clasele widgeturilor Elementor (încărcate doar când Elementor le înregistrează).
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager as C;

/** Baza comună: numele, categoria, controalele cu valorile implicite din nt_section_defaults(), afișarea. */
abstract class NT_Elementor_Widget extends \Elementor\Widget_Base {

	const SECTION = '';
	const TITLE   = '';
	const ICON    = 'eicon-leaf';

	public function get_name() {
		return 'natur_' . static::SECTION;
	}

	public function get_title() {
		return static::TITLE;
	}

	public function get_icon() {
		return static::ICON;
	}

	public function get_categories() {
		return array( 'natur' );
	}

	public function get_keywords() {
		return array( 'natur', static::SECTION );
	}

	/* Conținutul vine din magazin (produse, recenzii, contact): nu se păstrează în memoria cache a Elementor. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function render() {
		echo nt_section( static::SECTION, $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Adaugă un control pe widget sau pe un repeater.
	 *
	 * @param object $target $this sau un \Elementor\Repeater.
	 * @param string $type   text, textarea, wysiwyg, number, switch, media, url, icon, tint, select, select2.
	 * @param array  $extra  Opțiuni Elementor suplimentare (default, options, condition, description…).
	 */
	protected function ctl( $target, $key, $label, $type = 'text', array $extra = array() ) {
		$types = array(
			'text'     => C::TEXT,
			'textarea' => C::TEXTAREA,
			'wysiwyg'  => C::WYSIWYG,
			'number'   => C::NUMBER,
			'switch'   => C::SWITCHER,
			'media'    => C::MEDIA,
			'url'      => C::URL,
			'icon'     => C::SELECT,
			'tint'     => C::SELECT,
			'select'   => C::SELECT,
			'select2'  => C::SELECT2,
		);
		$args = array(
			'label' => $label,
			'type'  => $types[ $type ],
		);
		if ( $target === $this && ! array_key_exists( 'default', $extra ) ) {
			$args['default'] = nt_section_defaults( static::SECTION )[ $key ] ?? '';
		}
		if ( in_array( $type, array( 'text', 'textarea', 'wysiwyg', 'select2', 'url' ), true ) ) {
			$args['label_block'] = true;
		}
		if ( 'icon' === $type ) {
			$args['options'] = nt_icon_choices();
		} elseif ( 'tint' === $type ) {
			$args['options'] = nt_tint_choices();
		} elseif ( 'switch' === $type ) {
			$args += array( 'label_on' => 'Da', 'label_off' => 'Nu', 'return_value' => 'yes' );
		} elseif ( 'media' === $type ) {
			$args['default'] = array( 'url' => '', 'id' => '' );
		}
		$target->add_control( $key, array_merge( $args, $extra ) );
	}

	/** Listă de elemente (repeater); $fields( $repeater ) adaugă câmpurile unui element. */
	protected function items( $key, $label, callable $fields, $title_field ) {
		$r = new \Elementor\Repeater();
		$fields( $r );
		$this->add_control(
			$key,
			array(
				'label'       => $label,
				'type'        => C::REPEATER,
				'fields'      => $r->get_controls(),
				'default'     => nt_section_defaults( static::SECTION )[ $key ],
				'title_field' => $title_field,
			)
		);
	}

	protected function section( $id, $label ) {
		$this->start_controls_section( $id, array( 'label' => $label ) );
	}

	/** Notă informativă în panoul widgetului. */
	protected function note( $id, $html ) {
		$this->add_control(
			$id,
			array(
				'type'            => C::RAW_HTML,
				'raw'             => $html,
				'content_classes' => 'elementor-descriptor',
			)
		);
	}

	/** Textul de ajutor despre shortcode-urile disponibile. */
	protected function shortcodes_note( $id ) {
		$this->note( $id, 'În texte puteți folosi <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code> și shortcode-urile [natur_telefon], [natur_email], [natur_livrare_gratuita], [natur_cost_livrare], [natur_an].' );
	}
}

class NT_Widget_Hero_Art extends NT_Elementor_Widget {
	const SECTION = 'hero_art';
	const TITLE   = 'Natur: Colaj foto (hero)';
	const ICON    = 'eicon-gallery-masonry';

	protected function register_controls() {
		$this->section( 'images', 'Imagini' );
		$this->ctl( $this, 'main', 'Imaginea mare', 'media', array( 'description' => 'O imagine lipsă se înlocuiește cu fotografia unui produs recomandat.' ) );
		$this->ctl( $this, 'second', 'Imaginea rotundă 1', 'media' );
		$this->ctl( $this, 'third', 'Imaginea rotundă 2', 'media' );
		$this->ctl( $this, 'sticker', 'Textul insignei rotative', 'text' );
		$this->end_controls_section();

		$this->section( 'pick_sec', 'Produsul evidențiat' );
		$this->ctl( $this, 'pick', 'Produs', 'select2', array( 'options' => nt_el_product_options( '— fără produs —' ), 'description' => 'Apare doar dacă e în stoc.' ) );
		$this->ctl( $this, 'pick_tag', 'Eticheta', 'text' );
		$this->end_controls_section();
	}
}

class NT_Widget_Hero_Proof extends NT_Elementor_Widget {
	const SECTION = 'hero_proof';
	const TITLE   = 'Natur: Dovezi (recenzii, avantaje)';
	const ICON    = 'eicon-review';

	protected function register_controls() {
		$this->section( 'reviews_sec', 'Recenzii' );
		$this->ctl( $this, 'reviews', 'Afișează numărul de recenzii', 'switch' );
		$this->ctl( $this, 'reviews_one', 'Eticheta pentru 1 recenzie', 'text', array( 'condition' => array( 'reviews' => 'yes' ) ) );
		$this->ctl( $this, 'reviews_many', 'Eticheta pentru mai multe', 'text', array( 'condition' => array( 'reviews' => 'yes' ) ) );
		$this->ctl( $this, 'reviews_sub', 'Rândul al doilea', 'text', array( 'condition' => array( 'reviews' => 'yes' ) ) );
		$this->end_controls_section();

		$this->section( 'items_sec', 'Avantaje' );
		$this->items(
			'items',
			'Avantaje',
			function ( $r ) {
				$this->ctl( $r, 'icon', 'Iconiță', 'icon', array( 'default' => 'check' ) );
				$this->ctl( $r, 'text', 'Text', 'text', array( 'default' => '' ) );
			},
			'{{{ text }}}'
		);
		$this->end_controls_section();
	}
}

class NT_Widget_Categories extends NT_Elementor_Widget {
	const SECTION = 'categories';
	const TITLE   = 'Natur: Plăci de categorii';
	const ICON    = 'eicon-gallery-grid';

	protected function register_controls() {
		$this->section( 'content', 'Categorii' );
		$this->ctl( $this, 'cats', 'Categorii alese', 'select2', array( 'multiple' => true, 'options' => nt_el_category_options(), 'default' => array(), 'description' => 'În ordinea aleasă. Dacă nu alegeți nimic, apar categoriile cu cele mai multe produse.' ) );
		$this->ctl( $this, 'number', 'Câte categorii (alegere automată)', 'number', array( 'min' => 1, 'max' => 24, 'condition' => array( 'cats' => array() ) ) );
		$this->ctl( $this, 'show_all', 'Placa „Toate produsele”', 'switch' );
		$this->ctl( $this, 'all_title', 'Placa finală — titlu', 'text', array( 'condition' => array( 'show_all' => 'yes' ) ) );
		$this->ctl( $this, 'all_sub', 'Placa finală — text', 'text', array( 'condition' => array( 'show_all' => 'yes' ), 'description' => '{produse} = numărul de produse, {categorii} = numărul de categorii.' ) );
		$this->end_controls_section();
	}
}

class NT_Widget_Steps extends NT_Elementor_Widget {
	const SECTION = 'steps';
	const TITLE   = 'Natur: Pași („Cum funcționează”)';
	const ICON    = 'eicon-number-field';

	protected function register_controls() {
		$this->section( 'content', 'Pași' );
		$this->items(
			'items',
			'Pași',
			function ( $r ) {
				$this->ctl( $r, 'icon', 'Iconiță', 'icon', array( 'default' => 'check' ) );
				$this->ctl( $r, 'tint', 'Culoare', 'tint', array( 'default' => 'mint' ) );
				$this->ctl( $r, 'title', 'Titlu', 'text', array( 'default' => 'Pas nou' ) );
				$this->ctl( $r, 'text', 'Text', 'textarea', array( 'default' => '' ) );
			},
			'{{{ title }}}'
		);
		$this->shortcodes_note( 'sc_note' );
		$this->end_controls_section();
	}
}

class NT_Widget_Product_Tabs extends NT_Elementor_Widget {
	const SECTION = 'product_tabs';
	const TITLE   = 'Natur: Produse pe file';
	const ICON    = 'eicon-tabs';

	protected function register_controls() {
		$this->section( 'content', 'File' );
		$this->ctl( $this, 'limit', 'Produse pe filă', 'number', array( 'min' => 1, 'max' => 24 ) );
		$cats = nt_el_category_options();
		$this->items(
			'tabs',
			'File',
			function ( $r ) use ( $cats ) {
				$this->ctl( $r, 'label', 'Titlul filei', 'text', array( 'default' => 'Filă nouă' ) );
				$this->ctl(
					$r,
					'source',
					'Produse',
					'select',
					array(
						'default' => 'featured',
						'options' => array(
							'featured' => 'Recomandate (marcate cu ★ în lista de produse)',
							'new'      => 'Cele mai noi',
							'best'     => 'Cele mai vândute',
							'sale'     => 'La reducere',
							'cats'     => 'Din categoriile alese',
						),
					)
				);
				$this->ctl( $r, 'cats', 'Categorii', 'select2', array( 'multiple' => true, 'options' => $cats, 'default' => array(), 'condition' => array( 'source' => 'cats' ) ) );
				$this->ctl(
					$r,
					'orderby',
					'Ordinea',
					'select',
					array(
						'default'   => 'rand',
						'options'   => array(
							'rand'       => 'Aleatorie',
							'date'       => 'Cele mai noi întâi',
							'popularity' => 'Cele mai vândute întâi',
							'price'      => 'Preț crescător',
							'title'      => 'Alfabetică',
						),
						'condition' => array( 'source' => array( 'featured', 'sale', 'cats' ) ),
					)
				);
			},
			'{{{ label }}}'
		);
		$this->ctl( $this, 'label', 'Descrierea filelor pentru cititoarele de ecran', 'text' );
		$this->end_controls_section();
	}
}

class NT_Widget_Story_Art extends NT_Elementor_Widget {
	const SECTION = 'story_art';
	const TITLE   = 'Natur: Colaj foto (poveste)';
	const ICON    = 'eicon-gallery-justified';

	protected function register_controls() {
		$this->section( 'content', 'Imagini' );
		$this->ctl( $this, 'main', 'Imaginea mare', 'media', array( 'description' => 'O imagine lipsă se înlocuiește cu fotografia unui produs recomandat.' ) );
		$this->ctl( $this, 'second', 'Imaginea mică 1', 'media' );
		$this->ctl( $this, 'third', 'Imaginea mică 2', 'media' );
		$this->ctl( $this, 'note', 'Bilețelul', 'text' );
		$this->end_controls_section();
	}
}

class NT_Widget_Stats extends NT_Elementor_Widget {
	const SECTION = 'stats';
	const TITLE   = 'Natur: Cifre';
	const ICON    = 'eicon-counter';

	protected function register_controls() {
		$this->section( 'content', 'Cifre' );
		$this->items(
			'items',
			'Cifre',
			function ( $r ) {
				$this->ctl(
					$r,
					'source',
					'Cifra',
					'select',
					array(
						'default' => 'custom',
						'options' => array(
							'products'   => 'Numărul de produse (automat)',
							'categories' => 'Numărul de categorii (automat)',
							'reviews'    => 'Numărul de recenzii (automat)',
							'orders'     => 'Comenzi finalizate (automat)',
							'custom'     => 'Scrisă manual',
						),
					)
				);
				$this->ctl( $r, 'number', 'Valoarea', 'number', array( 'default' => '', 'condition' => array( 'source' => 'custom' ) ) );
				$this->ctl( $r, 'label', 'Eticheta', 'text', array( 'default' => '' ) );
			},
			'{{{ label }}}'
		);
		$this->end_controls_section();
	}
}

class NT_Widget_Reviews extends NT_Elementor_Widget {
	const SECTION = 'reviews';
	const TITLE   = 'Natur: Recenzii (carusel)';
	const ICON    = 'eicon-testimonial-carousel';

	protected function register_controls() {
		$this->section( 'content', 'Recenzii' );
		$this->note( 'src_note', 'Recenziile aprobate ale produselor, câte una pe produs. Se moderează în Produse → Recenzii.' );
		$this->ctl( $this, 'number', 'Câte recenzii', 'number', array( 'min' => 1, 'max' => 30 ) );
		$this->ctl( $this, 'min_len', 'Lungimea minimă (caractere)', 'number', array( 'min' => 0 ) );
		$this->ctl( $this, 'max_len', 'Lungimea maximă (caractere, 0 = oricât)', 'number', array( 'min' => 0 ) );
		$this->ctl( $this, 'label', 'Descrierea pentru cititoarele de ecran', 'text' );
		$this->end_controls_section();
	}
}

class NT_Widget_Contact extends NT_Elementor_Widget {
	const SECTION = 'contact';
	const TITLE   = 'Natur: Carduri de contact';
	const ICON    = 'eicon-call-to-action';

	protected function register_controls() {
		$this->section( 'phone_sec', 'Telefon' );
		$this->note( 'data_note', 'Telefonul, e-mailul, Messenger și Facebook se schimbă în Aspect → Personalizare → Natur.MD → Date de contact. Un card fără date nu se afișează.' );
		$this->ctl( $this, 'phone_tag', 'Eticheta', 'text' );
		$this->ctl( $this, 'phone_title', 'Titlu', 'text' );
		$this->ctl( $this, 'phone_text', 'Text', 'textarea' );
		$this->ctl( $this, 'phone_btn', 'Butonul de apel', 'text' );
		$this->ctl( $this, 'phone_copy', 'Butonul de copiere', 'text' );
		$this->end_controls_section();

		$this->section( 'chat_sec', 'Messenger' );
		$this->ctl( $this, 'chat_bubble', 'Mesajul din bulă', 'text' );
		$this->ctl( $this, 'chat_label', 'Eticheta', 'text' );
		$this->ctl( $this, 'chat_title', 'Titlu', 'text' );
		$this->ctl( $this, 'chat_text', 'Text', 'textarea' );
		$this->ctl( $this, 'chat_link', 'Linkul Messenger', 'text' );
		$this->ctl( $this, 'chat_fb', 'Linkul Facebook', 'text' );
		$this->end_controls_section();

		$this->section( 'mail_sec', 'E-mail' );
		$this->ctl( $this, 'mail_stamp', 'Textul de pe timbru', 'text' );
		$this->ctl( $this, 'mail_label', 'Eticheta', 'text' );
		$this->ctl( $this, 'mail_text', 'Text', 'textarea' );
		$this->ctl( $this, 'mail_link', 'Linkul de e-mail', 'text' );
		$this->ctl( $this, 'mail_copy', 'Butonul de copiere', 'text' );
		$this->end_controls_section();

		$this->section( 'zone_sec', 'Unde livrăm' );
		$this->ctl( $this, 'zone_label', 'Eticheta', 'text' );
		$this->ctl( $this, 'zone_title', 'Titlu', 'text' );
		$this->items(
			'zone_items',
			'Rânduri',
			function ( $r ) {
				$this->ctl( $r, 'icon', 'Iconiță', 'icon', array( 'default' => 'truck' ) );
				$this->ctl(
					$r,
					'style',
					'Culoare',
					'select',
					array(
						'default' => 'city',
						'options' => array(
							'city' => 'Verde (curier)',
							'post' => 'Galben (poștă)',
							'pay'  => 'Piersică (plată)',
						),
					)
				);
				$this->ctl( $r, 'text', 'Text', 'textarea', array( 'default' => '' ) );
			},
			'{{{ text }}}'
		);
		$this->ctl( $this, 'zone_link_tx', 'Linkul — text', 'text' );
		$this->ctl( $this, 'zone_link', 'Linkul — adresa', 'url', array( 'default' => array( 'url' => '' ) ) );
		$this->shortcodes_note( 'sc_note' );
		$this->end_controls_section();

		$this->section( 'map_sec', 'Harta' );
		$this->note( 'map_note', 'Orașele se așază pe hartă după coordonatele reale (le găsiți în Google Maps: clic dreapta pe oraș).' );
		$this->ctl( $this, 'map_center', 'Orașul din centru', 'text' );
		$this->ctl( $this, 'map_lat', 'Centru — latitudine', 'number', array( 'step' => 0.0001 ) );
		$this->ctl( $this, 'map_lng', 'Centru — longitudine', 'number', array( 'step' => 0.0001 ) );
		$this->items(
			'map_towns',
			'Orașe',
			function ( $r ) {
				$this->ctl( $r, 'name', 'Oraș', 'text', array( 'default' => '' ) );
				$this->ctl( $r, 'lat', 'Latitudine', 'number', array( 'default' => '', 'step' => 0.0001 ) );
				$this->ctl( $r, 'lng', 'Longitudine', 'number', array( 'default' => '', 'step' => 0.0001 ) );
			},
			'{{{ name }}}'
		);
		$this->ctl( $this, 'map_sticker', 'Insigna rotativă', 'text', array( 'description' => 'Se ascunde dacă folosește [natur_livrare_gratuita] și nu există prag de livrare gratuită.' ) );
		$this->ctl( $this, 'map_city', 'Legenda — curier', 'text' );
		$this->ctl( $this, 'map_post', 'Legenda — poștă', 'text' );
		$this->end_controls_section();
	}
}

class NT_Widget_Faq extends NT_Elementor_Widget {
	const SECTION = 'faq';
	const TITLE   = 'Natur: Întrebări frecvente';
	const ICON    = 'eicon-accordion';

	protected function register_controls() {
		$this->section( 'intro_sec', 'Introducere' );
		$this->ctl( $this, 'eyebrow', 'Supratitlu', 'text' );
		$this->ctl( $this, 'title', 'Titlu', 'text' );
		$this->ctl( $this, 'intro', 'Text', 'textarea' );
		$this->end_controls_section();

		$this->section( 'items_sec', 'Întrebări' );
		$this->items(
			'items',
			'Întrebări',
			function ( $r ) {
				$this->ctl( $r, 'question', 'Întrebarea', 'text', array( 'default' => 'Întrebare nouă' ) );
				$this->ctl( $r, 'answer', 'Răspunsul', 'wysiwyg', array( 'default' => '' ) );
			},
			'{{{ question }}}'
		);
		$this->shortcodes_note( 'sc_note' );
		$this->end_controls_section();
	}
}

function nt_elementor_widget_classes() {
	return array(
		'NT_Widget_Hero_Art',
		'NT_Widget_Hero_Proof',
		'NT_Widget_Categories',
		'NT_Widget_Steps',
		'NT_Widget_Product_Tabs',
		'NT_Widget_Story_Art',
		'NT_Widget_Stats',
		'NT_Widget_Reviews',
		'NT_Widget_Contact',
		'NT_Widget_Faq',
	);
}
