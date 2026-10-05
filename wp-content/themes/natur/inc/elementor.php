<?php
/**
 * Widgeturile Elementor ale temei (categoria „Natur.MD” din editor): secțiunile primei pagini și ale paginii Contact,
 * cu toate textele, imaginile și listele editabile. Afișarea e în inc/shortcodes.php.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'elementor/elements/categories_registered',
	static function ( $manager ) {
		$manager->add_category( 'natur', array( 'title' => 'Natur.MD', 'icon' => 'eicon-leaf' ) );
	}
);

/*
 * La prima editare a unei pagini în Elementor, Astra o trece pe lățime completă (conținut lipit de marginile ecranului)
 * și îi ascunde titlul. Paginile temei au antetul și containerul lor, deci păstrăm setările obișnuite.
 */
add_filter( 'astra_elementor_use_default_settings', '__return_true' );

add_action(
	'elementor/widgets/register',
	static function ( $widgets ) {
		require_once NT_DIR . '/inc/elementor-widgets.php';
		foreach ( nt_elementor_widget_classes() as $class ) {
			$widgets->register( new $class() );
		}
	}
);

/** Produsele publicate, pentru listele de selecție din widgeturi. */
function nt_el_product_options( $none = '— niciunul —' ) {
	$options = array( '' => $none );
	$ids     = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	foreach ( $ids as $id ) {
		$options[ $id ] = get_the_title( $id );
	}
	return $options;
}

/** Categoriile de produse (cu părintele în față), pentru listele de selecție. */
function nt_el_category_options() {
	$options = array();
	$terms   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( is_wp_error( $terms ) ) {
		return $options;
	}
	$names = wp_list_pluck( $terms, 'name', 'term_id' );
	foreach ( $terms as $t ) {
		$options[ $t->term_id ] = ( $t->parent && isset( $names[ $t->parent ] ) ? $names[ $t->parent ] . ' › ' : '' ) . $t->name;
	}
	asort( $options );
	return $options;
}
