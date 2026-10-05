<?php
/**
 * Secțiunile primei pagini și ale paginii Contact.
 *
 * Fiecare secțiune are o funcție de afișare, folosită de widgetul Elementor cu același nume (inc/elementor.php,
 * categoria „Natur.MD” din editor — acolo se editează textele, imaginile și listele) și de shortcode-ul echivalent:
 *
 *   [natur_hero_art]       colaj foto + insignă rotativă + produs „la îndemână”
 *   [natur_hero_proof]     dovezi sub butoanele din hero (recenzii, livrare, plată)
 *   [natur_categories]     plăci de categorii (bento)
 *   [natur_steps]          „Cum funcționează” în pași
 *   [natur_product_tabs]   produse pe file (recomandate, noutăți, reduceri, categorii)
 *   [natur_story_art]      colaj pentru secțiunea „Povestea noastră”
 *   [natur_stats]          cifre reale din magazin
 *   [natur_reviews]        recenzii reale (carusel)
 *   [natur_contact]        pagina Contact: telefon, Messenger, e-mail, harta livrărilor
 *   [natur_faq]            întrebări frecvente
 *
 * Valorile implicite de mai jos sunt doar punctul de plecare al unui widget nou; ce se salvează în Elementor le înlocuiește.
 */

defined( 'ABSPATH' ) || exit;

/** Valorile implicite ale secțiunilor (și ale controalelor din widgeturile Elementor). */
function nt_section_defaults( $section = null ) {
	static $d = null;
	if ( null === $d ) {
		$d = array(
			'hero_art'     => array(
				'main'     => '',
				'second'   => '',
				'third'    => '',
				'sticker'  => '100% natural · fără E-uri · de la gospodari · ',
				'pick'     => '',
				'pick_tag' => 'Preferatul clienților',
			),
			'hero_proof'   => array(
				'reviews'      => 'yes',
				'reviews_one'  => 'recenzie reală',
				'reviews_many' => 'recenzii reale',
				'reviews_sub'  => 'de la clienții noștri',
				'items'        => array(
					array( 'icon' => 'clock', 'text' => 'Livrare în 1–48 ore' ),
					array( 'icon' => 'coins', 'text' => 'Plata la primire' ),
				),
			),
			'categories'   => array(
				'number'    => 7,
				'cats'      => array(),
				'show_all'  => 'yes',
				'all_title' => 'Toate produsele',
				'all_sub'   => '{produse} în {categorii}',
			),
			'steps'        => array(
				'items' => array(
					array( 'icon' => 'pointer', 'tint' => 'mint', 'title' => 'Alegi online', 'text' => 'Pui în coș ce-ți place. Fără cont, fără plată în avans.' ),
					array( 'icon' => 'phone', 'tint' => 'butter', 'title' => 'Te sunăm', 'text' => 'Confirmăm comanda la telefon și ora potrivită pentru livrare.' ),
					array( 'icon' => 'truck', 'tint' => 'peach', 'title' => 'Livrăm în 1–48 ore', 'text' => 'În Chișinău cu curierul; în restul țării prin Poșta Moldovei.' ),
					array( 'icon' => 'coins', 'tint' => 'lilac', 'title' => 'Plătești la primire', 'text' => 'Verifici produsele și achiți în numerar. Simplu.' ),
				),
			),
			'product_tabs' => array(
				'limit' => 8,
				'label' => 'Selecții de produse',
				'tabs'  => array(
					array( 'label' => 'Preferatele clienților', 'source' => 'featured', 'cats' => array(), 'orderby' => 'rand' ),
					array( 'label' => 'Proaspăt adăugate', 'source' => 'new', 'cats' => array(), 'orderby' => 'date' ),
					array( 'label' => 'Cele mai cumpărate', 'source' => 'best', 'cats' => array(), 'orderby' => 'popularity' ),
				),
			),
			'story_art'    => array(
				'main'   => '',
				'second' => '',
				'third'  => '',
				'note'   => '„Vrem unt, nu margarină!”',
			),
			'stats'        => array(
				'items' => array(
					array( 'source' => 'products', 'number' => '', 'label' => 'produse naturale' ),
					array( 'source' => 'categories', 'number' => '', 'label' => 'categorii' ),
					array( 'source' => 'reviews', 'number' => '', 'label' => 'recenzii reale' ),
				),
			),
			'reviews'      => array(
				'number'  => 10,
				'min_len' => 50,
				'max_len' => 300,
				'label'   => 'Recenzii ale clienților',
			),
			'contact'      => array(
				'phone_tag'    => 'Cel mai rapid',
				'phone_title'  => 'Sună-ne',
				'phone_text'   => 'Te ajutăm să alegi produsele potrivite, răspundem la întrebări și confirmăm comanda în câteva minute.',
				'phone_btn'    => 'Sună acum',
				'phone_copy'   => 'Copiază numărul',
				'chat_bubble'  => 'Bună! Mai aveți ouă de prepeliță?',
				'chat_label'   => 'Messenger',
				'chat_title'   => 'Scrie-ne un mesaj',
				'chat_text'    => 'Întreabă orice — despre produse, livrare sau o comandă deja făcută.',
				'chat_link'    => 'Deschide conversația',
				'chat_fb'      => 'Pagina de Facebook',
				'mail_stamp'   => 'MD',
				'mail_label'   => 'E-mail',
				'mail_text'    => 'Pentru colaborări, facturi sau întrebări mai lungi.',
				'mail_link'    => 'Scrie un e-mail',
				'mail_copy'    => 'Copiază',
				'zone_label'   => 'Unde livrăm',
				'zone_title'   => 'Din Chișinău, <em>în toată Moldova</em>',
				'zone_items'   => array(
					array( 'icon' => 'truck', 'style' => 'city', 'text' => '<strong>mun. Chișinău</strong> — cu curierul, în 1–48 de ore' ),
					array( 'icon' => 'package', 'style' => 'post', 'text' => '<strong>Restul Moldovei</strong> — prin Poșta Moldovei, pentru produsele neperisabile' ),
					array( 'icon' => 'coins', 'style' => 'pay', 'text' => '<strong>Plata la primire</strong>, în numerar — nimic în avans' ),
				),
				'zone_link'    => array( 'url' => '' ),
				'zone_link_tx' => 'Totul despre livrare și plată',
				'map_center'   => 'Chișinău',
				'map_lat'      => 47.0105,
				'map_lng'      => 28.8638,
				'map_towns'    => array(
					array( 'name' => 'Soroca', 'lat' => 48.1558, 'lng' => 28.2975 ),
					array( 'name' => 'Bălți', 'lat' => 47.7617, 'lng' => 27.9289 ),
					array( 'name' => 'Rezina', 'lat' => 47.7492, 'lng' => 28.9622 ),
					array( 'name' => 'Orhei', 'lat' => 47.3831, 'lng' => 28.8231 ),
					array( 'name' => 'Ungheni', 'lat' => 47.2108, 'lng' => 27.8006 ),
					array( 'name' => 'Căușeni', 'lat' => 46.6442, 'lng' => 29.4114 ),
					array( 'name' => 'Comrat', 'lat' => 46.2956, 'lng' => 28.6556 ),
					array( 'name' => 'Cahul', 'lat' => 45.9042, 'lng' => 28.1944 ),
				),
				'map_sticker'  => 'livrare gratuită de la [natur_livrare_gratuita] · ',
				'map_city'     => 'Curier',
				'map_post'     => 'Poșta Moldovei',
			),
			'faq'          => array(
				'eyebrow' => 'Răspunsuri rapide',
				'title'   => 'Întrebări pe care le auzim des',
				'intro'   => 'Poate răspunsul e deja aici. Dacă nu — sună-ne sau scrie-ne, ne bucurăm de fiecare întrebare.',
				'items'   => array(
					array( 'question' => 'Cât costă livrarea?', 'answer' => '<strong>Gratuit</strong> pentru comenzile de la [natur_livrare_gratuita] și <strong>[natur_cost_livrare]</strong> pentru cele mai mici — atât în Chișinău, cât și prin Poșta Moldovei.' ),
					array( 'question' => 'În cât timp ajunge comanda?', 'answer' => 'În 1–48 de ore, în funcție de produs, din momentul în care confirmăm comanda la telefon.' ),
				),
			),
		);
	}
	return null === $section ? $d : ( $d[ $section ] ?? array() );
}

/** Setările unei secțiuni completate cu valorile implicite. */
function nt_section_args( $section, $args ) {
	$args = (array) $args;
	foreach ( nt_section_defaults( $section ) as $k => $v ) {
		if ( ! array_key_exists( $k, $args ) || null === $args[ $k ] ) {
			$args[ $k ] = $v;
		}
	}
	return $args;
}

/** Afișează o secțiune (folosit de widgeturile Elementor și de shortcode-uri). */
function nt_section( $section, $args = array() ) {
	$fn = 'nt_section_' . $section;
	return function_exists( $fn ) ? $fn( nt_section_args( $section, $args ) ) : '';
}

/** Pagina curentă conține secțiunea dată (widgetul Elementor sau shortcode-ul)? */
function nt_page_has_section( $section ) {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	return has_shortcode( (string) get_post_field( 'post_content', $id ), 'natur_' . $section )
		|| false !== strpos( (string) get_post_meta( $id, '_elementor_data', true ), '"widgetType":"natur_' . $section . '"' );
}

/** Textul afișat ca text simplu (insigne rotative, atribute). */
function nt_plain( $html ) {
	return trim( html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * ID-ul unei imagini: din controlul „Imagine” Elementor ([id, url]), un ID de imagine, un ID de produs sau un SKU.
 */
function nt_image_id( $value ) {
	if ( is_array( $value ) ) {
		$value = $value['id'] ?? 0;
	}
	if ( ! $value ) {
		return 0;
	}
	if ( is_numeric( $value ) ) {
		$type = get_post_type( (int) $value );
		if ( 'attachment' === $type ) {
			return (int) $value;
		}
		return 'product' === $type ? (int) get_post_thumbnail_id( (int) $value ) : 0;
	}
	$pid = function_exists( 'wc_get_product_id_by_sku' ) ? wc_get_product_id_by_sku( (string) $value ) : 0;
	return $pid ? (int) get_post_thumbnail_id( $pid ) : 0;
}

/** ID-ul unui produs dat prin ID sau SKU. */
function nt_product_id( $value ) {
	if ( ! $value ) {
		return 0;
	}
	if ( is_numeric( $value ) ) {
		return 'product' === get_post_type( (int) $value ) ? (int) $value : 0;
	}
	return function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( (string) $value ) : 0;
}

/** Imaginile alese pentru un colaj; locurile goale se completează cu fotografiile produselor recomandate. */
function nt_collage_images( array $values, $offset = 0 ) {
	$ids = array_map( 'nt_image_id', $values );
	if ( in_array( 0, $ids, true ) && function_exists( 'wc_get_products' ) ) {
		$fill = array();
		foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 12, 'featured' => true, 'orderby' => 'date', 'return' => 'ids' ) ) as $pid ) {
			if ( get_post_thumbnail_id( $pid ) ) {
				$fill[] = (int) get_post_thumbnail_id( $pid );
			}
		}
		$fill = array_values( array_diff( array_slice( $fill, $offset ), $ids ) );
		foreach ( $ids as $i => $id ) {
			if ( ! $id && $fill ) {
				$ids[ $i ] = array_shift( $fill );
			}
		}
	}
	return $ids;
}

function nt_img( $id, $size, $attr = array() ) {
	return $id ? wp_get_attachment_image( $id, $size, false, array_merge( array( 'alt' => '' ), $attr ) ) : '';
}

/* ================================================================== Hero */

function nt_section_hero_art( array $a ) {
	[ $main, $second, $third ] = nt_collage_images( array( $a['main'], $a['second'], $a['third'] ) );
	$pid = nt_product_id( $a['pick'] );
	$p   = $pid ? wc_get_product( $pid ) : null;
	ob_start();
	?>
	<div class="nt-hart">
		<div class="nt-hart__arch"><?php echo nt_img( $main, 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 768px) 90vw, 520px' ) ); // phpcs:ignore ?></div>
		<div class="nt-hart__round nt-hart__round--1"><?php echo nt_img( $second, 'woocommerce_thumbnail', array( 'loading' => 'eager' ) ); // phpcs:ignore ?></div>
		<div class="nt-hart__round nt-hart__round--2"><?php echo nt_img( $third, 'woocommerce_thumbnail', array( 'loading' => 'eager' ) ); // phpcs:ignore ?></div>
		<?php
		$sticker = nt_plain( nt_render_text( $a['sticker'] ) );
		if ( '' !== $sticker ) {
			echo nt_sticker( $sticker ); // phpcs:ignore
		}
		?>
		<span class="nt-hart__doodle nt-hart__doodle--sun" aria-hidden="true"></span>
		<?php if ( $p && $p->is_purchasable() && $p->is_in_stock() ) : ?>
			<div class="nt-hart__pick">
				<?php echo wp_get_attachment_image( $p->get_image_id(), 'thumbnail', false, array( 'class' => 'nt-hart__pick-img', 'alt' => '' ) ); ?>
				<div class="nt-hart__pick-txt">
					<?php if ( '' !== trim( $a['pick_tag'] ) ) : ?>
						<span class="nt-hart__pick-tag"><?php echo nt_icon( 'sparkle', 12 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['pick_tag'] ); // phpcs:ignore ?></span>
					<?php endif; ?>
					<a href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
					<span class="price"><?php echo $p->get_price_html(); // phpcs:ignore ?></span>
				</div>
				<?php echo nt_card_button( $p ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function nt_section_hero_proof( array $a ) {
	$reviews = 'yes' === $a['reviews'] ? (int) get_comments( array( 'type' => 'review', 'status' => 'approve', 'count' => true ) ) : 0;
	ob_start();
	?>
	<div class="nt-proof">
		<?php
		if ( $reviews ) :
			$names = array_slice( array_unique( wp_list_pluck( get_comments( array( 'type' => 'review', 'status' => 'approve', 'number' => 12 ) ), 'comment_author' ) ), 0, 4 );
			?>
			<div class="nt-proof__people">
				<span class="nt-proof__faces" aria-hidden="true">
					<?php foreach ( $names as $n ) : ?>
						<span class="nt-avatar nt-tint--<?php echo esc_attr( nt_tint( $n ) ); ?>"><?php echo esc_html( nt_initials( $n ) ); ?></span>
					<?php endforeach; ?>
				</span>
				<span><strong><?php echo esc_html( nt_count_label( $reviews, nt_plain( $a['reviews_one'] ), nt_plain( $a['reviews_many'] ) ) ); ?></strong><br><?php echo nt_render_text( $a['reviews_sub'] ); // phpcs:ignore ?></span>
			</div>
		<?php endif; ?>
		<ul class="nt-proof__list">
			<?php foreach ( (array) $a['items'] as $item ) : ?>
				<?php if ( '' !== trim( $item['text'] ?? '' ) ) : ?>
					<li><?php echo nt_icon( $item['icon'] ?? '', 18 ); // phpcs:ignore ?><?php echo nt_render_text( $item['text'] ); // phpcs:ignore ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}

/* ================================================================== Categorii, pași, file de produse */

function nt_section_categories( array $a ) {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return '';
	}
	$ids = array_filter( array_map( 'absint', (array) $a['cats'] ) );
	if ( $ids ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'include' => $ids, 'orderby' => 'include', 'hide_empty' => false ) );
		$terms = is_wp_error( $terms ) ? array() : $terms;
	} else {
		$terms = nt_top_categories( (int) $a['number'] );
	}
	ob_start();
	echo '<div class="nt-cats">';
	foreach ( $terms as $i => $t ) {
		printf(
			'<a class="nt-cat%1$s nt-tint--%2$s" href="%3$s"><span class="nt-cat__txt"><span class="nt-cat__name">%4$s</span><span class="nt-cat__count">%5$s</span></span><span class="nt-cat__img">%6$s</span><span class="nt-cat__go" aria-hidden="true">%7$s</span></a>',
			0 === $i ? ' nt-cat--big' : '',
			esc_attr( nt_tint( $t->term_id ) ),
			esc_url( get_term_link( $t ) ),
			esc_html( $t->name ),
			esc_html( nt_count_label( nt_term_count( $t ) ) ),
			nt_term_image( $t, 0 === $i ? 'woocommerce_single' : 'woocommerce_thumbnail' ), // phpcs:ignore
			nt_icon( 'arrow-out', 18 ) // phpcs:ignore
		);
	}
	if ( 'yes' === $a['show_all'] ) {
		printf(
			'<a class="nt-cat nt-cat--all" href="%1$s"><span class="nt-cat__txt"><span class="nt-cat__name">%2$s</span><span class="nt-cat__count">%3$s</span></span><span class="nt-cat__go" aria-hidden="true">%4$s</span></a>',
			esc_url( wc_get_page_permalink( 'shop' ) ),
			nt_render_text( $a['all_title'] ), // phpcs:ignore
			nt_render_text( // phpcs:ignore
				$a['all_sub'],
				array(
					'produse'    => esc_html( nt_count_label( (int) wp_count_posts( 'product' )->publish ) ),
					'categorii'  => esc_html( nt_count_label( count( nt_top_categories() ), 'categorie', 'categorii' ) ),
				)
			),
			nt_icon( 'arrow-right', 22 ) // phpcs:ignore
		);
	}
	echo '</div>';
	return ob_get_clean();
}

function nt_section_steps( array $a ) {
	ob_start();
	echo '<ol class="nt-steps">';
	foreach ( array_values( (array) $a['items'] ) as $i => $s ) {
		printf(
			'<li class="nt-step" style="--i:%1$d"><span class="nt-step__ico nt-tint--%2$s">%3$s<span class="nt-step__num">%4$d</span></span><h3 class="nt-step__title">%5$s</h3><p>%6$s</p></li>',
			(int) $i,
			esc_attr( $s['tint'] ?? 'mint' ),
			nt_icon( $s['icon'] ?? '', 30 ), // phpcs:ignore
			(int) $i + 1,
			nt_render_text( $s['title'] ?? '' ), // phpcs:ignore
			nt_render_text( $s['text'] ?? '' ) // phpcs:ignore
		);
	}
	echo '</ol>';
	return ob_get_clean();
}

/** Interogarea unei file de produse. */
function nt_tab_query( array $tab, $limit ) {
	$q       = array( 'status' => 'publish', 'limit' => $limit, 'visibility' => 'catalog' );
	$orderby = $tab['orderby'] ?? 'rand';
	switch ( $tab['source'] ?? 'featured' ) {
		case 'new':
			$orderby = 'date';
			break;
		case 'sale':
			$q['include'] = wc_get_product_ids_on_sale() ? wc_get_product_ids_on_sale() : array( 0 );
			break;
		case 'best':
			$orderby = 'popularity';
			break;
		case 'cats':
			$slugs = array();
			foreach ( array_filter( array_map( 'absint', (array) ( $tab['cats'] ?? array() ) ) ) as $id ) {
				$t = get_term( $id, 'product_cat' );
				if ( $t && ! is_wp_error( $t ) ) {
					$slugs[] = $t->slug;
				}
			}
			$q['category'] = $slugs ? $slugs : array( '-' );
			break;
		default:
			$q['featured'] = true;
	}
	switch ( $orderby ) {
		case 'popularity':
			$q += array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ); // phpcs:ignore WordPress.DB.SlowDBQuery
			break;
		case 'price':
			$q += array( 'meta_key' => '_price', 'orderby' => 'meta_value_num', 'order' => 'ASC' ); // phpcs:ignore WordPress.DB.SlowDBQuery
			break;
		case 'title':
			$q += array( 'orderby' => 'title', 'order' => 'ASC' );
			break;
		case 'date':
			$q += array( 'orderby' => 'date', 'order' => 'DESC' );
			break;
		default:
			$q['orderby'] = 'rand';
	}
	return wc_get_products( $q );
}

function nt_section_product_tabs( array $a ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	static $n = 0;
	$uid  = ++$n > 1 ? "-$n" : '';
	$tabs = array_values( array_filter( (array) $a['tabs'], static fn( $t ) => '' !== trim( $t['label'] ?? '' ) ) );
	ob_start();
	echo '<div class="nt-ptabs" data-nt-tabs>';
	printf( '<div class="nt-ptabs__bar" role="tablist" aria-label="%s">', esc_attr( nt_plain( $a['label'] ) ) );
	foreach ( $tabs as $i => $tab ) {
		printf(
			'<button type="button" role="tab" id="nt-tab%1$s-%2$d" class="nt-ptabs__tab" aria-selected="%3$s" aria-controls="nt-panel%1$s-%2$d" tabindex="%4$s">%5$s</button>',
			esc_attr( $uid ),
			(int) $i,
			0 === $i ? 'true' : 'false',
			0 === $i ? '0' : '-1',
			esc_html( nt_plain( $tab['label'] ) )
		);
	}
	echo '</div>';
	foreach ( $tabs as $i => $tab ) {
		printf( '<div class="nt-ptabs__panel" role="tabpanel" id="nt-panel%1$s-%2$d" aria-labelledby="nt-tab%1$s-%2$d"%3$s>', esc_attr( $uid ), (int) $i, 0 === $i ? '' : ' hidden' );
		wc_set_loop_prop( 'columns', 4 );
		woocommerce_product_loop_start();
		foreach ( nt_tab_query( $tab, max( 1, (int) $a['limit'] ) ) as $prod ) {
			$GLOBALS['product'] = $prod; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$GLOBALS['post']    = get_post( $prod->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			setup_postdata( $GLOBALS['post'] );
			wc_get_template_part( 'content', 'product' );
		}
		woocommerce_product_loop_end();
		wp_reset_postdata();
		echo '</div>';
	}
	echo '</div>';
	return ob_get_clean();
}

/* ================================================================== Povestea, cifre, recenzii */

function nt_section_story_art( array $a ) {
	[ $main, $second, $third ] = nt_collage_images( array( $a['main'], $a['second'], $a['third'] ), 3 );
	ob_start();
	?>
	<div class="nt-sart">
		<div class="nt-sart__a"><?php echo nt_img( $main, 'woocommerce_single' ); // phpcs:ignore ?></div>
		<div class="nt-sart__b"><?php echo nt_img( $second, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
		<div class="nt-sart__c"><?php echo nt_img( $third, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
		<?php if ( '' !== trim( $a['note'] ) ) : ?>
			<p class="nt-sart__note"><?php echo nt_render_text( $a['note'] ); // phpcs:ignore ?></p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Valoarea unei cifre: numărată din magazin sau scrisă manual. */
function nt_stat_value( array $item ) {
	switch ( $item['source'] ?? 'custom' ) {
		case 'products':
			return (int) wp_count_posts( 'product' )->publish;
		case 'categories':
			return count( nt_top_categories() );
		case 'reviews':
			return (int) get_comments( array( 'type' => 'review', 'status' => 'approve', 'count' => true ) );
		case 'orders':
			return function_exists( 'wc_orders_count' ) ? (int) wc_orders_count( 'completed' ) : 0;
		default:
			return (int) ( $item['number'] ?? 0 );
	}
}

function nt_section_stats( array $a ) {
	$out = '<dl class="nt-stats">';
	foreach ( (array) $a['items'] as $item ) {
		$n = nt_stat_value( $item );
		if ( $n ) {
			$out .= '<div class="nt-stat"><dt data-nt-count="' . $n . '">' . $n . '</dt><dd>' . nt_render_text( $item['label'] ?? '' ) . '</dd></div>';
		}
	}
	return $out . '</dl>';
}

function nt_section_reviews( array $a ) {
	$reviews = get_comments( array( 'type' => 'review', 'status' => 'approve', 'number' => 300 ) );
	$picked  = array();
	$seen    = array();
	foreach ( $reviews as $r ) {
		$len = mb_strlen( wp_strip_all_tags( $r->comment_content ) );
		if ( $len < (int) $a['min_len'] || ( (int) $a['max_len'] && $len > (int) $a['max_len'] ) || isset( $seen[ $r->comment_post_ID ] ) ) {
			continue;
		}
		$seen[ $r->comment_post_ID ] = true;
		$picked[]                    = $r;
		if ( count( $picked ) >= (int) $a['number'] ) {
			break;
		}
	}
	if ( ! $picked ) {
		return '';
	}
	$tints = array_keys( nt_tint_choices() );
	ob_start();
	?>
	<div class="nt-reviews" data-nt-carousel>
		<div class="nt-reviews__nav">
			<button type="button" class="nt-iconbtn nt-iconbtn--line" data-dir="-1" aria-label="Recenziile anterioare"><?php echo nt_icon( 'arrow-left', 20 ); // phpcs:ignore ?></button>
			<button type="button" class="nt-iconbtn nt-iconbtn--line" data-dir="1" aria-label="Recenziile următoare"><?php echo nt_icon( 'arrow-right', 20 ); // phpcs:ignore ?></button>
		</div>
		<ul class="nt-reviews__track" tabindex="0" aria-label="<?php echo esc_attr( nt_plain( $a['label'] ) ); ?>">
			<?php
			foreach ( $picked as $i => $r ) :
				$pid = (int) $r->comment_post_ID;
				?>
				<li class="nt-review nt-tint--<?php echo esc_attr( $tints[ $i % 6 ] ); ?>">
					<span class="nt-review__q" aria-hidden="true">“</span>
					<blockquote class="nt-review__text"><?php echo esc_html( wp_strip_all_tags( $r->comment_content ) ); ?></blockquote>
					<div class="nt-review__who">
						<span class="nt-avatar"><?php echo esc_html( nt_initials( $r->comment_author ) ); ?></span>
						<span>
							<strong><?php echo esc_html( $r->comment_author ); ?></strong>
							<a href="<?php echo esc_url( get_permalink( $pid ) . '#reviews' ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a>
						</span>
						<span class="nt-review__img" aria-hidden="true"><?php echo get_the_post_thumbnail( $pid, 'thumbnail' ); ?></span>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}

/* ================================================================== Contact */

/**
 * Poziția unui oraș pe harta schematică (centrul 160,160; ~1,1 px/km), după coordonatele reale.
 * Orașele prea îndepărtate sunt aduse pe marginea hărții.
 */
function nt_map_xy( $lat, $lng, $clat, $clng ) {
	$dx = ( (float) $lng - (float) $clng ) * 111.32 * cos( deg2rad( (float) $clat ) ) * 1.1;
	$dy = ( (float) $lat - (float) $clat ) * 110.57 * 1.1;
	$d  = sqrt( $dx * $dx + $dy * $dy );
	if ( $d > 140 ) {
		$dx *= 140 / $d;
		$dy *= 140 / $d;
	}
	return array( (int) round( 160 + $dx ), (int) round( 160 - $dy ) );
}

function nt_section_contact( array $a ) {
	$phone = nt_contact( 'phone' );
	$tel   = 'tel:' . nt_contact( 'phone_raw' );
	$email = nt_contact( 'email' );
	$towns = array();
	foreach ( (array) $a['map_towns'] as $t ) {
		if ( '' !== trim( $t['name'] ?? '' ) && is_numeric( $t['lat'] ?? '' ) && is_numeric( $t['lng'] ?? '' ) ) {
			$towns[] = array_merge( array( $t['name'] ), nt_map_xy( $t['lat'], $t['lng'], $a['map_lat'], $a['map_lng'] ) );
		}
	}
	$center = nt_plain( $a['map_center'] );
	$copy   = static function ( $text, $label, $class ) {
		return sprintf(
			'<button type="button" class="%1$s" data-nt-copy="%2$s">%3$s%4$s<span class="nt-copy__label" aria-live="polite">%5$s</span></button>',
			esc_attr( $class ),
			esc_attr( $text ),
			nt_icon( 'copy', 18, 'nt-copy__ico' ),
			nt_icon( 'check', 18, 'nt-copy__ok' ),
			esc_html( nt_plain( $label ) )
		);
	};
	$link     = is_array( $a['zone_link'] ) ? ( $a['zone_link']['url'] ?? '' ) : (string) $a['zone_link'];
	$sticker  = false !== strpos( $a['map_sticker'], '[natur_livrare_gratuita' ) && ! nt_free_shipping_min() ? '' : nt_plain( nt_render_text( $a['map_sticker'] ) );
	ob_start();
	?>
	<div class="nt-contact">
		<?php if ( $phone ) : ?>
			<section class="nt-cb nt-cb--phone" style="--i:0" aria-labelledby="nt-cb-phone">
				<span class="nt-cb__ring" aria-hidden="true"><?php echo nt_icon( 'phone', 30 ); // phpcs:ignore ?></span>
				<span class="nt-cb__bloom" aria-hidden="true"><?php echo nt_icon( 'flower', 300 ); // phpcs:ignore ?></span>
				<p class="nt-cb__tag"><span class="nt-cb__live" aria-hidden="true"></span><?php echo nt_render_text( $a['phone_tag'] ); // phpcs:ignore ?></p>
				<h2 class="nt-cb__title" id="nt-cb-phone"><?php echo nt_render_text( $a['phone_title'] ); // phpcs:ignore ?></h2>
				<a class="nt-cb__phone" href="<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $phone ); ?></a>
				<p class="nt-cb__text"><?php echo nt_render_text( $a['phone_text'] ); // phpcs:ignore ?></p>
				<div class="nt-cb__actions">
					<a class="nt-btn nt-btn--leaf nt-btn--lg" href="<?php echo esc_attr( $tel ); ?>"><?php echo nt_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['phone_btn'] ); // phpcs:ignore ?></a>
					<?php echo $copy( $phone, $a['phone_copy'], 'nt-btn nt-btn--glass nt-btn--lg' ); // phpcs:ignore ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( nt_contact( 'messenger' ) || nt_contact( 'facebook' ) ) : ?>
			<section class="nt-cb nt-cb--chat nt-tint--sky" style="--i:1" aria-labelledby="nt-cb-chat">
				<div class="nt-cb__chat" aria-hidden="true">
					<span class="nt-cb__bubble"><?php echo nt_render_text( $a['chat_bubble'] ); // phpcs:ignore ?></span>
					<span class="nt-cb__bubble nt-cb__bubble--us"><i></i><i></i><i></i></span>
				</div>
				<p class="nt-cb__label"><?php echo nt_icon( 'chat', 16 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['chat_label'] ); // phpcs:ignore ?></p>
				<h2 class="nt-cb__title" id="nt-cb-chat"><?php echo nt_render_text( $a['chat_title'] ); // phpcs:ignore ?></h2>
				<p class="nt-cb__text"><?php echo nt_render_text( $a['chat_text'] ); // phpcs:ignore ?></p>
				<div class="nt-cb__row">
					<?php if ( nt_contact( 'messenger' ) ) : ?>
						<a class="nt-link-arrow" href="<?php echo esc_url( nt_contact( 'messenger' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_render_text( $a['chat_link'] ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					<?php endif; ?>
					<?php if ( nt_contact( 'facebook' ) ) : ?>
						<a class="nt-cb__social" href="<?php echo esc_url( nt_contact( 'facebook' ) ); ?>" target="_blank" rel="noopener"><?php echo nt_icon( 'facebook', 16 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['chat_fb'] ); // phpcs:ignore ?></a>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $email ) : ?>
			<section class="nt-cb nt-cb--mail nt-tint--butter" style="--i:2" aria-labelledby="nt-cb-mail">
				<span class="nt-cb__stamp" aria-hidden="true">
					<span class="nt-cb__stamp-in"><span class="nt-cb__stamp-art"><?php echo nt_icon( 'flower', 34 ); // phpcs:ignore ?><b><?php echo esc_html( nt_plain( $a['mail_stamp'] ) ); ?></b></span></span>
					<svg class="nt-cb__postmark" viewBox="0 0 120 64"><circle cx="32" cy="32" r="25"/><circle cx="32" cy="32" r="19"/><path d="M62 18c8-6 14 6 22 0s14 6 22 0s10 4 12 2M62 32c8-6 14 6 22 0s14 6 22 0s10 4 12 2M62 46c8-6 14 6 22 0s14 6 22 0s10 4 12 2"/></svg>
				</span>
				<p class="nt-cb__label"><?php echo nt_icon( 'mail', 16 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['mail_label'] ); // phpcs:ignore ?></p>
				<h2 class="nt-cb__title" id="nt-cb-mail"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></h2>
				<p class="nt-cb__text"><?php echo nt_render_text( $a['mail_text'] ); // phpcs:ignore ?></p>
				<div class="nt-cb__row">
					<a class="nt-link-arrow" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo nt_render_text( $a['mail_link'] ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					<?php echo $copy( $email, $a['mail_copy'], 'nt-cb__copy' ); // phpcs:ignore ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="nt-cb nt-cb--zone" style="--i:3" aria-labelledby="nt-cb-zone">
			<div class="nt-cb__zone">
				<p class="nt-cb__label"><?php echo nt_icon( 'pin', 16 ); // phpcs:ignore ?> <?php echo nt_render_text( $a['zone_label'] ); // phpcs:ignore ?></p>
				<h2 class="nt-cb__title" id="nt-cb-zone"><?php echo nt_render_text( $a['zone_title'] ); // phpcs:ignore ?></h2>
				<ul class="nt-cb__list">
					<?php foreach ( (array) $a['zone_items'] as $item ) : ?>
						<?php if ( '' !== trim( $item['text'] ?? '' ) ) : ?>
							<li><span class="nt-cb__key nt-cb__key--<?php echo esc_attr( $item['style'] ?? 'city' ); ?>" aria-hidden="true"><?php echo nt_icon( $item['icon'] ?? '', 18 ); // phpcs:ignore ?></span><span><?php echo nt_render_text( $item['text'] ); // phpcs:ignore ?></span></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
				<?php if ( $link && '' !== trim( $a['zone_link_tx'] ) ) : ?>
					<a class="nt-link-arrow" href="<?php echo esc_url( $link ); ?>"><?php echo nt_render_text( $a['zone_link_tx'] ); // phpcs:ignore ?> <?php echo nt_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</div>
			<div class="nt-cb__map">
				<svg class="nt-map" viewBox="0 0 320 320" role="img" aria-label="<?php echo esc_attr( sprintf( 'Hartă schematică: %1$s în %2$s, %3$s în %4$s', nt_plain( $a['map_city'] ), $center, nt_plain( $a['map_post'] ), implode( ', ', wp_list_pluck( $towns, 0 ) ) ) ); ?>">
					<circle class="nt-map__bg" cx="160" cy="160" r="152"/>
					<circle class="nt-map__ring nt-map__ring--out" cx="160" cy="160" r="140"/>
					<circle class="nt-map__ring" cx="160" cy="160" r="92"/>
					<circle class="nt-map__city" cx="160" cy="160" r="36"/>
					<?php foreach ( $towns as $i => [ $name, $x, $y ] ) : ?>
						<path class="nt-map__route" style="--i:<?php echo (int) $i; ?>" d="M160 160L<?php echo (int) $x . ' ' . (int) $y; ?>"/>
					<?php endforeach; ?>
					<?php foreach ( $towns as $i => [ $name, $x, $y ] ) : ?>
						<g class="nt-map__town" style="--i:<?php echo (int) $i; ?>">
							<circle cx="<?php echo (int) $x; ?>" cy="<?php echo (int) $y; ?>" r="5.5"/>
							<text x="<?php echo (int) $x + 10; ?>" y="<?php echo (int) $y + 4; ?>"><?php echo esc_html( $name ); ?></text>
						</g>
					<?php endforeach; ?>
					<circle class="nt-map__pulse" cx="160" cy="160" r="14"/>
					<circle class="nt-map__pin" cx="160" cy="160" r="10"/>
					<circle cx="160" cy="160" r="3.6" fill="#fff"/>
					<text class="nt-map__here" x="160" y="214"><?php echo esc_html( $center ); ?></text>
				</svg>
				<?php if ( '' !== $sticker ) : ?>
					<?php echo nt_sticker( $sticker, 'gift' ); // phpcs:ignore ?>
				<?php endif; ?>
				<p class="nt-cb__legend"><span class="nt-cb__dot nt-cb__dot--city"></span><?php echo nt_render_text( $a['map_city'] ); // phpcs:ignore ?> <span class="nt-cb__dot nt-cb__dot--post"></span><?php echo nt_render_text( $a['map_post'] ); // phpcs:ignore ?></p>
			</div>
		</section>
	</div>
	<?php
	return ob_get_clean();
}

function nt_section_faq( array $a ) {
	$items = array_filter( (array) $a['items'], static fn( $i ) => '' !== trim( $i['question'] ?? '' ) );
	if ( ! $items ) {
		return '';
	}
	ob_start();
	?>
	<div class="nt-faq">
		<div class="nt-faq__intro">
			<?php if ( '' !== trim( $a['eyebrow'] ) ) : ?>
				<p class="nt-eyebrow"><?php echo nt_render_text( $a['eyebrow'] ); // phpcs:ignore ?></p>
			<?php endif; ?>
			<h2><?php echo nt_render_text( $a['title'] ); // phpcs:ignore ?></h2>
			<?php if ( '' !== trim( $a['intro'] ) ) : ?>
				<p><?php echo nt_render_text( $a['intro'] ); // phpcs:ignore ?></p>
			<?php endif; ?>
		</div>
		<div class="nt-faq__list">
			<?php foreach ( $items as $item ) : ?>
				<details>
					<summary><?php echo esc_html( nt_plain( $item['question'] ) ); ?></summary>
					<div class="nt-faq__a"><?php echo wp_kses_post( wpautop( do_shortcode( (string) ( $item['answer'] ?? '' ) ) ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* ================================================================== Shortcode-uri */

/*
 * Aceleași secțiuni, ca shortcode-uri. Atributele simple suprascriu valorile implicite;
 * imaginile și produsul se pot da prin ID sau SKU: [natur_hero_art main="NM-57" pick="NM-58"].
 */
foreach ( array_keys( nt_section_defaults() ) as $nt_section ) {
	add_shortcode(
		'natur_' . $nt_section,
		static function ( $atts ) use ( $nt_section ) {
			$atts = array_intersect_key( (array) $atts, nt_section_defaults( $nt_section ) );
			foreach ( array( 'cats' ) as $list ) {
				if ( isset( $atts[ $list ] ) ) {
					$atts[ $list ] = wp_parse_id_list( $atts[ $list ] );
				}
			}
			return nt_section( $nt_section, $atts );
		}
	);
}
unset( $nt_section );
