<?php
/**
 * Conținutul editabil al temei: Aspect → Personalizare → „Natur.MD — texte și contact”.
 *
 * Fiecare text are aici o valoare implicită; ce se salvează în Personalizare o înlocuiește
 * (un câmp golit ascunde elementul). Culorile principale vin din Elementor → Setări site → Culori globale.
 *
 * În texte se pot folosi etichetele <strong>, <em>, <br>, <a> și shortcode-urile:
 *   [natur_telefon]  [natur_email]  [natur_livrare_gratuita]  [natur_cost_livrare]  [natur_an]
 */

defined( 'ABSPATH' ) || exit;

/** Iconițele care pot fi alese în setări și în widgeturile Elementor. */
function nt_icon_choices() {
	return array(
		'gift'    => 'Cadou',
		'clock'   => 'Ceas',
		'coins'   => 'Monede',
		'truck'   => 'Camion',
		'package' => 'Colet',
		'leaf'    => 'Frunză',
		'sprout'  => 'Răsad',
		'flower'  => 'Floare',
		'egg'     => 'Ou',
		'milk'    => 'Lapte',
		'heart'   => 'Inimă',
		'star'    => 'Stea',
		'sparkle' => 'Sclipire',
		'shield'  => 'Scut',
		'check'   => 'Bifă',
		'phone'   => 'Telefon',
		'mail'    => 'Plic',
		'chat'    => 'Mesaj',
		'pin'     => 'Locație',
		'home'    => 'Casă',
		'pointer' => 'Cursor',
		'bag'     => 'Sacoșă',
		'search'  => 'Lupă',
		'user'    => 'Persoană',
	);
}

/** Nuanțele pastelate ale temei (plăci, iconițe). */
function nt_tint_choices() {
	return array(
		'mint'   => 'Mentă',
		'peach'  => 'Piersică',
		'butter' => 'Unt',
		'sky'    => 'Cer',
		'lilac'  => 'Liliac',
		'rose'   => 'Trandafir',
		'sage'   => 'Salvie',
	);
}

/**
 * Secțiunile din Personalizare și câmpurile lor: cheie => [etichetă, tip, valoare implicită, descriere].
 * Tipuri: text, textarea, icon, page, number, checkbox, url, email, attribute, product_cat.
 */
function nt_settings() {
	static $s = null;
	if ( null !== $s ) {
		return $s;
	}
	$fs  = 'Folosiți [natur_livrare_gratuita] pentru pragul livrării gratuite din WooCommerce → Setări → Livrare; mesajul se ascunde dacă nu există prag.';
	$sum = 'Folosiți {suma} pentru suma care mai lipsește.';
	$s   = array(
		'contact'  => array(
			'title'       => 'Date de contact',
			'description' => 'Apar în antet, subsol, pagina produsului, pagina Contact și oriunde se folosesc shortcode-urile [natur_telefon] și [natur_email].',
			'option'      => 'natur_contact',
			'fields'      => array(
				'phone'     => array( 'Telefon', 'text', '', 'Așa cum se afișează, ex. „060 89 30 00”. Linkul de apel se formează automat cu prefixul țării magazinului.' ),
				'email'     => array( 'E-mail', 'email', '' ),
				'facebook'  => array( 'Pagina de Facebook', 'url', '' ),
				'messenger' => array( 'Link Messenger', 'url', '', 'Ex. https://m.me/numele-paginii' ),
				'instagram' => array( 'Pagina de Instagram', 'url', '' ),
				'area'      => array( 'Adresa / zona', 'text', '' ),
			),
		),
		'topbar'   => array(
			'title'       => 'Bara de anunțuri',
			'description' => 'Mesajele din bara de sus se schimbă singure. Un mesaj gol nu se afișează. ' . $fs,
			'fields'      => array(
				'topbar_1_icon' => array( 'Mesajul 1 — iconiță', 'icon', 'gift' ),
				'topbar_1_text' => array( 'Mesajul 1', 'text', 'Livrare gratuită în Chișinău de la <strong>[natur_livrare_gratuita]</strong>' ),
				'topbar_2_icon' => array( 'Mesajul 2 — iconiță', 'icon', 'clock' ),
				'topbar_2_text' => array( 'Mesajul 2', 'text', 'Livrăm în <strong>1–48 de ore</strong> de la confirmare' ),
				'topbar_3_icon' => array( 'Mesajul 3 — iconiță', 'icon', 'coins' ),
				'topbar_3_text' => array( 'Mesajul 3', 'text', 'Plătești <strong>la primire</strong>, fără avans' ),
				'topbar_4_icon' => array( 'Mesajul 4 — iconiță', 'icon', 'leaf' ),
				'topbar_4_text' => array( 'Mesajul 4', 'text', '' ),
			),
		),
		'header'   => array(
			'title'       => 'Antet și panoul „Magazin”',
			'description' => 'Panoul se deschide din elementul de meniu care duce la pagina Magazin; categoriile din el se aleg în Aspect → Meniuri.',
			'fields'      => array(
				'search_label'        => array( 'Butonul de căutare', 'text', 'Caută produse…' ),
				'mega_eyebrow'        => array( 'Panou — supratitlu', 'text', 'Ce găsești la noi' ),
				'mega_all'            => array( 'Panou — linkul spre magazin', 'text', 'Toate produsele' ),
				'mega_promo_icon'     => array( 'Caseta de livrare — iconiță', 'icon', 'truck' ),
				'mega_promo_title'    => array( 'Caseta de livrare — titlu', 'text', 'Livrare gratuită de la [natur_livrare_gratuita]', $fs ),
				'mega_promo_title_no' => array( 'Caseta de livrare — titlu fără prag de livrare gratuită', 'text', 'Livrare rapidă în Chișinău' ),
				'mega_promo_text'     => array( 'Caseta de livrare — text', 'textarea', 'În Chișinău în 1–48 de ore. Restul țării — prin Poșta Moldovei. Plătești la primire.' ),
				'mega_promo_btn'      => array( 'Caseta de livrare — buton', 'text', 'Cum livrăm' ),
				'mega_promo_page'     => array( 'Caseta de livrare — pagina butonului', 'page', 0 ),
			),
		),
		'search'   => array(
			'title'  => 'Căutare',
			'fields' => array(
				'search_placeholder' => array( 'Textul din câmpul de căutare', 'text', 'Ce cauți azi? Ouă, brânză, ghee…' ),
				'search_popular_lbl' => array( 'Titlul căutărilor populare', 'text', 'Căutări populare' ),
				'search_popular'     => array( 'Căutări populare', 'textarea', "Ouă de prepeliță\nUnt GHEE\nBrânză\nCarne de prepeliță\nSemințe pentru germinare\nCiuperci", 'Câte una pe rând.' ),
				'search_none'        => array( 'Niciun rezultat — titlu', 'text', 'Nimic pentru „{cautare}”', '{cautare} = cuvintele căutate.' ),
				'search_empty'       => array( 'Niciun rezultat — sfat', 'text', 'Încearcă un cuvânt mai scurt sau una dintre căutările populare.' ),
				'search_found_1'     => array( 'Numărul de rezultate — la singular', 'text', 'produs găsit' ),
				'search_found_n'     => array( 'Numărul de rezultate — la plural', 'text', 'produse găsite' ),
				'search_all'         => array( 'Linkul spre toate rezultatele', 'text', 'Vezi toate rezultatele' ),
			),
		),
		'shop'     => array(
			'title'       => 'Magazin și categorii',
			'description' => 'Antetul paginilor Magazin, categorie și căutare. Descrierea unei categorii se scrie în Produse → Categorii; textul de mai jos apare doar la categoriile fără descriere.',
			'fields'      => array(
				'shop_desc'     => array( 'Descrierea magazinului', 'textarea', '{numar} naturale, de la gospodari și mici producători din Moldova. Fără aditivi, fără E-uri.', '{numar} = numărul de produse („86 de produse”).' ),
				'cat_desc'      => array( 'Descrierea categoriilor fără descriere', 'textarea', '{numar} din categoria „{categorie}”, de la gospodari și mici producători verificați de noi.', '{numar} = produsele din categorie, {categorie} = numele ei.' ),
				'search_title'  => array( 'Căutare — titlu', 'text', 'Rezultate pentru „{cautare}”', '{cautare} = cuvintele căutate.' ),
				'search_desc'   => array( 'Căutare — descriere', 'textarea', 'Am găsit {numar}. Nu e ce căutai? Încearcă un cuvânt mai scurt, de exemplu „prepeli”.' ),
				'shop_fact_1_icon' => array( 'Avantajul 1 — iconiță', 'icon', 'leaf' ),
				'shop_fact_1'      => array( 'Avantajul 1', 'text', '100% natural' ),
				'shop_fact_2_icon' => array( 'Avantajul 2 — iconiță', 'icon', 'clock' ),
				'shop_fact_2'      => array( 'Avantajul 2', 'text', 'Livrare 1–48 ore' ),
				'shop_fact_3_icon' => array( 'Avantajul 3 — iconiță', 'icon', 'coins' ),
				'shop_fact_3'      => array( 'Avantajul 3', 'text', 'Plata la primire' ),
				'shop_sticker'     => array( 'Insigna rotundă', 'text', 'natural · fără E-uri · de la gospodari · ' ),
				'card_btn'         => array( 'Card produs — butonul de adăugare', 'text', 'Adaugă' ),
				'card_btn_added'   => array( 'Card produs — după adăugare', 'text', 'Adăugat' ),
				'card_pack_attr'   => array( 'Card produs — atributul afișat lângă preț', 'attribute', '', 'Ex. „Ambalare” → „720 ml”, „per kg”.' ),
				'card_cat_skip'    => array( 'Card produs — categorie care nu se afișează ca etichetă', 'product_cat', 0, 'Pentru categorii transversale (ex. HoReCa): pe card apare cealaltă categorie a produsului.' ),
				'noprod_title'     => array( 'Niciun produs — titlu', 'text', 'N-am găsit nimic aici… deocamdată' ),
				'noprod_text'      => array( 'Niciun produs — text', 'textarea', 'Încearcă un cuvânt mai scurt sau alege o categorie. Sau sună-ne la [natur_telefon] — te ajutăm să găsești ce-ți trebuie.' ),
				'noprod_btn'       => array( 'Niciun produs — buton', 'text', 'Vezi toate produsele' ),
			),
		),
		'product'  => array(
			'title'  => 'Pagina produsului',
			'fields' => array(
				'prod_avail'        => array( 'Disponibilitate', 'text', 'În stoc · gata de livrare' ),
				'prod_buy_now'      => array( 'Butonul „Comandă acum”', 'text', 'Comandă acum', 'Lângă „Adaugă în coș”: adaugă produsul și deschide direct pagina de comandă. Gol = butonul nu apare.' ),
				'perk_1_icon'       => array( 'Avantajul 1 — iconiță', 'icon', 'truck' ),
				'perk_1_title'      => array( 'Avantajul 1 — titlu', 'text', 'Livrare în 1–48 de ore' ),
				'perk_1_text'       => array( 'Avantajul 1 — text', 'text', 'în Chișinău; în restul țării — prin Poșta Moldovei' ),
				'perk_2_icon'       => array( 'Livrare gratuită — iconiță', 'icon', 'gift' ),
				'perk_2_title'      => array( 'Livrare gratuită — titlu', 'text', 'Livrare gratuită de la [natur_livrare_gratuita]', 'Sub titlu apare automat cât mai lipsește până la livrarea gratuită (textele din secțiunea „Coș și comandă”).' ),
				'perk_3_icon'       => array( 'Avantajul 3 — iconiță', 'icon', 'coins' ),
				'perk_3_title'      => array( 'Avantajul 3 — titlu', 'text', 'Plătești la primire' ),
				'perk_3_text'       => array( 'Avantajul 3 — text', 'text', 'în numerar, după ce verifici produsele' ),
				'prod_ask'          => array( 'Întrebări despre produs', 'text', 'Ai întrebări despre produs? Sună-ne: [natur_telefon]' ),
				'related_title'     => array( 'Titlul produselor similare', 'text', 'Te-ar mai putea interesa' ),
				'upsells_title'     => array( 'Titlul produselor recomandate', 'text', 'Îți recomandăm și' ),
			),
		),
		'cart'     => array(
			'title'       => 'Coș și comandă',
			'description' => $fs,
			'fields'      => array(
				'fs_empty'         => array( 'Bara de livrare gratuită — coș gol', 'text', 'Livrare <strong>gratuită</strong> pentru comenzile de la <strong>[natur_livrare_gratuita]</strong>' ),
				'fs_left'          => array( 'Bara de livrare gratuită — sub prag', 'text', 'Mai adaugă <strong>{suma}</strong> pentru livrare <strong>gratuită</strong>', $sum ),
				'fs_done'          => array( 'Bara de livrare gratuită — prag atins', 'text', 'Super! Ai livrare <strong>gratuită</strong> în Chișinău' ),
				'note_empty'       => array( 'Pagina produsului — coș gol', 'text', 'Comenzile de la [natur_livrare_gratuita] se livrează gratuit' ),
				'note_left'        => array( 'Pagina produsului — sub prag', 'text', 'Îți mai lipsesc {suma} până la livrarea gratuită', $sum ),
				'note_done'        => array( 'Pagina produsului — prag atins', 'text', 'Comanda ta are deja livrare gratuită' ),
				'note_prod_left'   => array( 'Pagina produsului — cu acest produs, sub prag', 'text', 'Cu acest produs îți mai lipsesc {suma} până la livrarea gratuită', $sum ),
				'note_prod_done'   => array( 'Pagina produsului — acest produs atinge pragul', 'text', 'Doar cu acest produs ai deja livrare gratuită' ),
				'toast_title'      => array( 'Notificarea „adăugat în coș”', 'text', 'Adăugat în coș' ),
				'toast_btn'        => array( 'Notificare — buton', 'text', 'Vezi coșul' ),
				'mc_note'          => array( 'Coșul lateral — notă', 'text', 'Livrarea se calculează la finalizare. Plătești la primirea comenzii.' ),
				'mc_checkout'      => array( 'Coșul lateral — butonul de finalizare', 'text', 'Finalizează comanda' ),
				'mc_empty_title'   => array( 'Coș gol — titlu', 'text', 'Coșul tău e gol' ),
				'mc_empty_text'    => array( 'Coș gol — text', 'textarea', 'Hai să-l umplem cu ceva bun — ouă de casă, brânză proaspătă sau o conservă ca la bunica.' ),
				'mc_empty_btn'     => array( 'Coș gol — buton', 'text', 'Descoperă produsele' ),
				// Pagina de comandă (un singur pas): nume, telefon, unde livrăm, adresă.
				'ck_intro'           => array( 'Comanda — text sub titlu', 'text', 'Doar nume, telefon și adresă. Te sunăm să confirmăm, plătești la livrare.' ),
				'ck_fields_title'    => array( 'Comanda — titlul datelor', 'text', 'Unde trimitem comanda?' ),
				'ck_name'            => array( 'Câmpul „Nume”', 'text', 'Nume și prenume' ),
				'ck_name_ph'         => array( 'Câmpul „Nume” — exemplu', 'text', 'Ana Popescu' ),
				'ck_phone'           => array( 'Câmpul „Telefon”', 'text', 'Telefon' ),
				'ck_phone_prefix'    => array( 'Câmpul „Telefon” — prefixul țării', 'text', '+373', 'Afișat în fața câmpului și adăugat automat la număr (clientul scrie doar „69 123 456”). Gol = fără prefix.' ),
				'ck_phone_ph'        => array( 'Câmpul „Telefon” — exemplu', 'text', '69 123 456' ),
				'ck_phone_bad'       => array( 'Telefon incomplet — mesaj', 'text', 'Verifică numărul de telefon — pare incomplet.' ),
				'ck_zone'            => array( '„Unde livrăm?” — titlu', 'text', 'Unde livrăm?', 'Prima variantă e orașul magazinului (WooCommerce → Setări → Adresa magazinului); alegerea stabilește zona și costul livrării.' ),
				'ck_zone_city_note'  => array( 'Orașul magazinului — detalii', 'text', 'curier, 1–48 ore' ),
				'ck_zone_other'      => array( 'Altă localitate — titlu', 'text', 'Altă localitate' ),
				'ck_zone_other_note' => array( 'Altă localitate — detalii', 'text', 'prin Poșta Moldovei' ),
				'ck_address'         => array( 'Câmpul „Adresa”', 'text', 'Adresa' ),
				'ck_address_ph'      => array( 'Adresa — exemplu (orașul magazinului)', 'text', 'Strada, nr. casei, apartamentul' ),
				'ck_address_ph_other' => array( 'Adresa — exemplu (altă localitate)', 'text', 'Localitatea, strada, nr. casei' ),
				'ck_more'            => array( 'Linkul spre câmpurile opționale', 'text', 'Adaugă un comentariu sau e-mail' ),
				'ck_email'           => array( 'Câmpul „E-mail”', 'text', 'E-mail' ),
				'ck_email_ph'        => array( 'Câmpul „E-mail” — exemplu', 'text', 'pentru confirmarea pe e-mail' ),
				'ck_note'            => array( 'Câmpul „Comentariu”', 'text', 'Comentariu' ),
				'ck_note_ph'         => array( 'Câmpul „Comentariu” — exemplu', 'text', 'Ex.: sunați după ora 18' ),
				'ck_sum_title'       => array( 'Comanda — titlul listei de produse', 'text', 'Comanda ta' ),
				'ck_ship_free'       => array( 'Livrare gratuită — în total', 'text', 'gratuit' ),
				'ck_btn'             => array( 'Butonul de trimitere', 'text', 'Trimite comanda', 'Totalul comenzii se adaugă automat: „Trimite comanda · 560 lei”.' ),
				'ck_guard_error'     => array( 'Comanda refuzată de protecția anti-bot — mesaj', 'text', 'Nu am putut trimite comanda. Reîncarcă pagina și încearcă din nou sau sună-ne la [natur_telefon].', 'Protecția ascunsă blochează comenzile trimise de roboți. Un om îl vede doar dacă a trimis formularul în primele 3 secunde, fără JavaScript sau după 2 zile cu pagina deschisă.' ),
				'ty_title'           => array( 'Comandă primită — titlu', 'text', 'Mulțumim, {nume}!', '{nume} = prenumele clientului.' ),
				'ty_text'            => array( 'Comandă primită — text', 'textarea', 'Comanda <strong>nr. {numar}</strong> a ajuns la noi. Te sunăm în curând la <strong>{telefon}</strong> ca să confirmăm livrarea. La primire plătești <strong>{total}</strong>.', '{numar} = numărul comenzii, {telefon} = telefonul clientului, {total} = totalul.' ),
				'ty_btn'             => array( 'Comandă primită — buton', 'text', 'Înapoi la magazin' ),
			),
		),
		'footer'   => array(
			'title'       => 'Subsol',
			'description' => 'Linkurile coloanei „Informații” se aleg în Aspect → Meniuri → „Meniu subsol”; coloana „Magazin” folosește meniul din locația „Subsol — Magazin” sau, dacă nu e setat, categoriile cu cele mai multe produse. Politica de confidențialitate și Politica de cookies apar mereu în bara de jos, pe toate paginile (inclusiv coșul și finalizarea comenzii); paginile lor se aleg la sfârșitul acestei secțiuni.',
			'fields'      => array(
				'help_show'        => array( 'Afișează banda „Comandă la telefon”', 'checkbox', true ),
				'help_eyebrow'     => array( 'Banda — supratitlu', 'text', 'Suntem aici pentru tine' ),
				'help_title'       => array( 'Banda — titlu', 'text', 'Preferi să comanzi <em>la telefon?</em>' ),
				'help_text'        => array( 'Banda — text', 'textarea', 'Te ajutăm să alegi produsele potrivite și confirmăm comanda în câteva minute.' ),
				'help_bubble_1'    => array( 'Banda — mesajul clientului', 'text', 'Bună! Ce-mi recomandați pentru micul dejun?' ),
				'help_bubble_2'    => array( 'Banda — răspunsul nostru', 'text', 'Ouă de casă și brânză proaspătă!' ),
				'help_chat'        => array( 'Banda — butonul Messenger', 'text', 'Scrie-ne pe Messenger' ),
				'footer_about'     => array( 'Textul de sub logo', 'textarea', 'Produse naturale de la gospodari și mici producători din Moldova. Fără aditivi, fără E-uri — mâncare adevărată, ca pentru propria familie.' ),
				'footer_shop'      => array( 'Coloana 1 — titlu', 'text', 'Magazin' ),
				'footer_shop_num'  => array( 'Coloana 1 — câte categorii (fără meniu)', 'number', 7 ),
				'footer_shop_all'  => array( 'Coloana 1 — linkul spre magazin', 'text', 'Toate produsele' ),
				'footer_info'      => array( 'Coloana 2 — titlu', 'text', 'Informații' ),
				'footer_contact'   => array( 'Coloana 3 — titlu', 'text', 'Contact' ),
				'footer_pay'       => array( 'Coloana 3 — plata', 'text', 'Plata la livrare, în numerar' ),
				'footer_word'      => array( 'Textul mare decorativ', 'text', 'natur.md', 'Partea de după ultimul punct apare în verde.' ),
				'footer_copy'      => array( 'Drepturi de autor', 'text', '© [natur_an] Natur.MD — produse naturale din Moldova.' ),
				'footer_love'      => array( 'Mesajul din dreapta jos', 'text', 'Făcut cu {inima} în Moldova', '{inima} = iconița inimă.' ),
				'cookie_page'      => array( 'Pagina „Politica de cookies”', 'page', 0, 'Linkul apare în bara de jos, cu titlul paginii.' ),
			),
		),
		'notfound' => array(
			'title'  => 'Pagina 404',
			'fields' => array(
				'e404_title'       => array( 'Titlu', 'text', 'Ups! Pagina asta a zburat din cuib.' ),
				'e404_text'        => array( 'Text', 'textarea', 'Linkul pare greșit sau pagina a fost mutată. Caută produsul dorit sau întoarce-te în magazin — te așteaptă ceva bun.' ),
				'e404_placeholder' => array( 'Câmpul de căutare', 'text', 'Caută ouă, brânză, ghee…' ),
				'e404_shop'        => array( 'Butonul spre magazin', 'text', 'Mergi în magazin' ),
				'e404_home'        => array( 'Butonul spre prima pagină', 'text', 'Prima pagină' ),
			),
		),
	);
	return $s;
}

/** Specificația unui câmp (cu cheile sale normalizate). */
function nt_setting_field( $key ) {
	foreach ( nt_settings() as $sec ) {
		if ( isset( $sec['fields'][ $key ] ) ) {
			$f = $sec['fields'][ $key ];
			return array(
				'label'   => $f[0],
				'type'    => $f[1],
				'default' => $f[2],
				'desc'    => $f[3] ?? '',
				'option'  => $sec['option'] ?? '',
			);
		}
	}
	return null;
}

/** Valoarea brută a unei setări (cu valoarea implicită din nt_settings()). */
function nt_opt( $key ) {
	$f = nt_setting_field( $key );
	if ( ! $f ) {
		return '';
	}
	if ( $f['option'] ) {
		$opt = (array) get_option( $f['option'], array() );
		return $opt[ $key ] ?? $f['default'];
	}
	return get_theme_mod( $key, $f['default'] );
}

/** Etichetele HTML permise în texte. */
function nt_kses_tags() {
	return array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
		'span'   => array( 'class' => true ),
		'a'      => array( 'href' => true, 'target' => true, 'rel' => true, 'class' => true ),
	);
}

/**
 * Un text gata de afișat (HTML sigur): înlocuiește {etichetele} date, rulează shortcode-urile, filtrează HTML-ul.
 *
 * @param string $text   Textul (de regulă din nt_opt() sau dintr-un widget Elementor).
 * @param array  $tokens [ 'suma' => '120 lei', … ] — valori deja sigure (HTML).
 */
function nt_render_text( $text, array $tokens = array() ) {
	$text = (string) $text;
	if ( '' === trim( $text ) ) {
		return '';
	}
	$map = array();
	foreach ( $tokens as $k => $v ) {
		$map[ '{' . $k . '}' ] = $v;
	}
	// Shortcode-urile rulează doar pe textul din setări, înainte de valorile introduse ({cautare} etc.).
	return strtr( do_shortcode( wp_kses( $text, nt_kses_tags() ) ), $map );
}

/** Textul unei setări, gata de afișat. Gol dacă textul cere pragul livrării gratuite și acesta nu există. */
function nt_txt( $key, array $tokens = array() ) {
	$text = (string) nt_opt( $key );
	if ( false !== strpos( $text, '[natur_livrare_gratuita' ) && ! nt_free_shipping_min() ) {
		return '';
	}
	return nt_render_text( $text, $tokens );
}

/** Varianta fără HTML (pentru atribute: placeholder, aria-label, data-*). */
function nt_txt_plain( $key, array $tokens = array() ) {
	return trim( html_entity_decode( wp_strip_all_tags( nt_txt( $key, $tokens ) ), ENT_QUOTES, 'UTF-8' ) );
}

/** Linii nevide dintr-un câmp de tip listă. */
function nt_opt_lines( $key ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) nt_opt( $key ) ) ), 'strlen' ) );
}

/* ================================================================== Shortcode-uri pentru valori comune */

add_shortcode(
	'natur_telefon',
	static function ( $atts ) {
		$a     = shortcode_atts( array( 'link' => 'da' ), $atts );
		$phone = nt_contact( 'phone' );
		if ( ! $phone || 'nu' === $a['link'] ) {
			return esc_html( $phone );
		}
		return '<a href="tel:' . esc_attr( nt_contact( 'phone_raw' ) ) . '">' . esc_html( $phone ) . '</a>';
	}
);

add_shortcode(
	'natur_email',
	static function ( $atts ) {
		$a     = shortcode_atts( array( 'link' => 'da' ), $atts );
		$email = nt_contact( 'email' );
		if ( ! $email || 'nu' === $a['link'] ) {
			return esc_html( $email );
		}
		return '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}
);

add_shortcode( 'natur_livrare_gratuita', static fn() => nt_free_shipping_min() ? esc_html( nt_money( nt_free_shipping_min() ) ) : '' );
add_shortcode( 'natur_cost_livrare', static fn() => esc_html( nt_shipping_cost() ) );
add_shortcode( 'natur_an', static fn() => esc_html( wp_date( 'Y' ) ) );

/* Subtitlul paginilor (câmpul „Rezumat”) și descrierile categoriilor pot conține aceleași shortcode-uri. */
add_filter( 'get_the_excerpt', 'do_shortcode', 20 );
add_filter( 'term_description', 'do_shortcode', 20 );

/* ================================================================== Personalizare (Customizer) */

/** Validarea valorii unui câmp, după tip. */
function nt_sanitize_setting( $value, $type ) {
	switch ( $type ) {
		case 'checkbox':
			return (bool) $value;
		case 'number':
		case 'page':
		case 'product_cat':
			return absint( $value );
		case 'url':
			return esc_url_raw( $value );
		case 'email':
			return sanitize_email( $value );
		case 'icon':
			return isset( nt_icon_choices()[ $value ] ) ? $value : '';
		case 'attribute':
			return sanitize_key( $value );
		default:
			return wp_kses( (string) $value, nt_kses_tags() );
	}
}

add_action(
	'customize_register',
	static function ( WP_Customize_Manager $wp ) {
		$wp->add_panel(
			'nt',
			array(
				'title'       => 'Natur.MD — texte și contact',
				'priority'    => 1,
				'description' => 'Textele și datele de contact ale temei. În texte puteți folosi <strong>, <em> și shortcode-urile [natur_telefon], [natur_email], [natur_livrare_gratuita], [natur_cost_livrare], [natur_an]. Culorile se schimbă în Elementor → Setări site → Culori globale.',
			)
		);

		$choices = array(
			'icon'        => nt_icon_choices(),
			'attribute'   => array( '' => '— niciunul —' ),
			'product_cat' => array( 0 => '— niciuna —' ),
		);
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $tax ) {
				$choices['attribute'][ wc_attribute_taxonomy_name( $tax->attribute_name ) ] = $tax->attribute_label;
			}
		}
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		foreach ( is_wp_error( $cats ) ? array() : $cats as $t ) {
			$choices['product_cat'][ $t->term_id ] = $t->name;
		}

		$priority = 0;
		foreach ( nt_settings() as $sid => $sec ) {
			$wp->add_section(
				"nt_$sid",
				array(
					'title'       => $sec['title'],
					'panel'       => 'nt',
					'description' => $sec['description'] ?? '',
					'priority'    => $priority += 10,
				)
			);
			foreach ( $sec['fields'] as $key => $f ) {
				[ $label, $type, $default ] = $f;
				$id = isset( $sec['option'] ) ? "{$sec['option']}[$key]" : $key;
				$wp->add_setting(
					$id,
					array(
						'type'              => isset( $sec['option'] ) ? 'option' : 'theme_mod',
						'default'           => $default,
						'sanitize_callback' => static fn( $v ) => nt_sanitize_setting( $v, $type ),
					)
				);
				$control = array(
					'label'       => $label,
					'section'     => "nt_$sid",
					'settings'    => $id,
					'description' => $f[3] ?? '',
					'type'        => $type,
				);
				if ( isset( $choices[ $type ] ) ) {
					$control['type']    = 'select';
					$control['choices'] = $choices[ $type ];
				} elseif ( 'page' === $type ) {
					$control['type'] = 'dropdown-pages';
				} elseif ( 'number' === $type ) {
					$control['input_attrs'] = array( 'min' => 0, 'max' => 50 );
				}
				$wp->add_control( $id, $control );
			}
		}

		// Pagina de confidențialitate e setarea WordPress (Setări → Confidențialitate), aleasă aici lângă cea de cookies.
		$wp->add_setting(
			'wp_page_for_privacy_policy',
			array(
				'type'              => 'option',
				'capability'        => 'manage_privacy_options',
				'sanitize_callback' => 'absint',
			)
		);
		$wp->add_control(
			'wp_page_for_privacy_policy',
			array(
				'label'       => 'Pagina „Politica de confidențialitate”',
				'description' => 'Linkul apare în bara de jos și în formularele de comandă și de cont.',
				'section'     => 'nt_footer',
				'type'        => 'dropdown-pages',
			)
		);

		// Logo pentru fundal închis (subsol), lângă logo-ul principal din „Identitatea site-ului”.
		$wp->add_setting( 'nt_logo_light', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
		$wp->add_control(
			new WP_Customize_Media_Control(
				$wp,
				'nt_logo_light',
				array(
					'label'       => 'Logo pentru fundal închis',
					'description' => 'Folosit în subsol. Dacă lipsește, se folosește logo-ul principal.',
					'section'     => 'title_tagline',
					'mime_type'   => 'image',
					'priority'    => 9,
				)
			)
		);
	},
	20
);

/* Scurtătură în meniul Aspect. */
add_action(
	'admin_menu',
	static function () {
		add_theme_page( 'Texte și contact', 'Texte și contact', 'edit_theme_options', 'customize.php?autofocus[panel]=nt' );
	}
);

/* ================================================================== Culorile temei din Elementor (Setări site → Culori globale) */

/**
 * Variabilele CSS ale temei, legate de culorile globale Elementor: cele 4 culori de sistem
 * și culorile personalizate cu ID-urile de mai jos (create de tools/setup.php).
 */
function nt_palette_map() {
	return array(
		'primary'   => array( '--nt-forest', '#1F3D2B' ),
		'secondary' => array( '--nt-leaf', '#8DC63F' ),
		'text'      => array( '--nt-ink-2', '#4E5C52' ),
		'accent'    => array( '--nt-yolk', '#FFC94A' ),
		'ntbg'      => array( '--nt-bg', '#FBF7EF' ),
		'ntink'     => array( '--nt-ink', '#1E2B22' ),
		'ntmoss'    => array( '--nt-moss', '#3C6B27' ),
	);
}

/** Culorile din kitul Elementor activ care diferă de paleta implicită: [ variabilă => culoare ]. */
function nt_palette() {
	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit_id ) {
		return array();
	}
	$kit    = (array) get_post_meta( $kit_id, '_elementor_page_settings', true );
	$map    = nt_palette_map();
	$colors = array();
	foreach ( array_merge( (array) ( $kit['system_colors'] ?? array() ), (array) ( $kit['custom_colors'] ?? array() ) ) as $c ) {
		$id    = $c['_id'] ?? '';
		$color = sanitize_hex_color( $c['color'] ?? '' );
		if ( isset( $map[ $id ] ) && $color && strcasecmp( $color, $map[ $id ][1] ) ) {
			$colors[ $map[ $id ][0] ] = $color;
		}
	}
	return $colors;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$colors = nt_palette();
		if ( ! $colors ) {
			return;
		}
		$css = '';
		foreach ( $colors as $var => $color ) {
			$css .= "$var:$color;";
		}
		// Nuanțele derivate urmează culoarea de bază.
		if ( isset( $colors['--nt-forest'] ) ) {
			$css .= '--nt-forest-2:color-mix(in srgb,var(--nt-forest) 85%,var(--nt-leaf));';
		}
		if ( isset( $colors['--nt-leaf'] ) ) {
			$css .= '--nt-leaf-d:color-mix(in srgb,var(--nt-leaf) 55%,#000);';
		}
		wp_add_inline_style( 'nt', ":root{{$css}}" );
	},
	30
);

/** Culoarea de fundal (pentru <meta name="theme-color">). */
function nt_bg_color() {
	return nt_palette()['--nt-bg'] ?? nt_palette_map()['ntbg'][1];
}
