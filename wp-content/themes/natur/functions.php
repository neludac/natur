<?php
/**
 * Tema Natur.MD (copil Astra).
 *
 * inc/options.php      — textele și datele de contact editabile (Aspect → Personalizare → Natur.MD), culori din Elementor
 * inc/setup.php        — fonturi, stiluri, scripturi, setări generale
 * inc/icons.php        — iconițe SVG
 * inc/template-tags.php — date de contact, logo, meniuri, prag livrare gratuită
 * inc/woocommerce.php  — card produs, magazin, pagina produsului, coș lateral
 * inc/shortcodes.php   — secțiunile primei pagini și ale paginii Contact (afișare + shortcode-uri)
 * inc/elementor.php    — widgeturile Elementor „Natur.MD” pentru aceste secțiuni
 * inc/recipes.php      — „Rețete video” (widget Elementor pe prima pagină)
 */

defined( 'ABSPATH' ) || exit;

define( 'NT_DIR', get_stylesheet_directory() );
define( 'NT_URI', get_stylesheet_directory_uri() );

require NT_DIR . '/inc/icons.php';
require NT_DIR . '/inc/template-tags.php';
require NT_DIR . '/inc/options.php';
require NT_DIR . '/inc/setup.php';
require NT_DIR . '/inc/woocommerce.php';
require NT_DIR . '/inc/shortcodes.php';
require NT_DIR . '/inc/elementor.php';
require NT_DIR . '/inc/recipes.php';
