# Natur.MD — magazin WordPress + WooCommerce

Versiune nouă, curată, a magazinului natur.md (înlocuiește vechiul site PrestaShop).

| | |
|---|---|
| Local | https://natur.ddev (DDEV) |
| Producție | https://natur.md |
| Admin local | https://natur.ddev/wp-admin — utilizator `admin`, parolă `Natur2026!` (schimbați-o înainte de producție) |
| E-mailuri locale | https://natur.ddev:8026 (Mailpit — toate e-mailurile trimise local ajung aici) |

## Pornire

```bash
cd ~/Desktop/natur
ddev start          # prima dată cere parola Mac-ului, pentru a adăuga natur.ddev în /etc/hosts
ddev launch         # deschide site-ul
ddev launch wp-admin
```

## Ce conține

- **WordPress 7.1 (ro_RO) + WooCommerce** — monedă MDL afișată „lei”, vânzare doar în Republica Moldova.
- **Tema „Natur.MD”** (`wp-content/themes/natur`, temă-copil Astra) — design propriu: antet cu panou de categorii, căutare live, coș lateral, bară de navigare pe mobil, carduri de produs, pagini de magazin, produs și conținut. Prima pagină e construită în Elementor (Pagini → Acasă → „Editează cu Elementor”). Detalii mai jos, la [Design](#design-tema-natur).
- **Livrare** (WooCommerce → Setări → Livrare):
  - mun. Chișinău: curier 50 lei, gratuit de la 500 lei;
  - restul Moldovei: Poșta Moldovei (produse neperisabile) 50 lei, gratuit de la 500 lei.
- **Plată**: la livrare (numerar).
- **Pagini**: Despre noi, Livrare și plată, Contact (telefon, Messenger, e-mail, harta livrărilor și întrebări frecvente — fără formular), Termeni și condiții, Politica de retur, Politica de confidențialitate, Politica de cookies (ultimele două au linkuri permanente în bara de jos a subsolului, pe toate paginile).
- **Redirecționări 301** de la toate URL-urile vechi PrestaShop (`/17-lactate-molochnye`, `/oua-yajca/58-oua-de-prepelita.html`, `/content/1-conditii` …) către paginile noi — păstrează poziția în Google după lansare.

### Cod propriu

| Fișier | Rol |
|---|---|
| `wp-content/themes/natur/` | Tema site-ului (copil Astra): antet, subsol, card produs, magazin, produs, pagini, prima pagină; textele editabile (`inc/options.php`), widgeturile Elementor „Natur.MD” (`inc/elementor-widgets.php`) |
| `wp-content/plugins/natur-product-media/` | Plugin „Natur – Video & 360° produs” |
| `wp-content/mu-plugins/natur-site.php` | Simbolul monedei (setare în WooCommerce → General), redirecționări, reguli livrare, traduceri lipsă Astra |
| `wp-content/mu-plugins/natur-shop.php` | Stare de stoc „În curând”, insigna „-X%” |
| `tools/scrape_natur.py` | Extrage categorii, produse, imagini și recenzii de pe natur.md |
| `tools/make_media.py` | Optimizează imaginile; generează cadre 360° și clipuri **demonstrative** |
| `tools/import.php` | Importă datele extrase în WooCommerce (idempotent) |
| `tools/verify_products.py` | Verifică dacă toate produsele de pe natur.md sunt pe site, cu toate detaliile |
| `tools/setup.php` | Configurează tot site-ul (setări, livrare, pagini, meniuri, temă, prima pagină) |
| `tools/tests/` | Teste end-to-end automate |

`tools/` nu este accesibil din browser (blocat în `.ddev/nginx/deny-tools.conf`) și **nu se urcă pe serverul de producție**.

## Design (tema „natur”)

Paleta: crem cald `#FBF7EF`, verde pădure `#1F3D2B`, verdele din logo `#8DC63F`, galben gălbenuș `#FFC94A`, plus nuanțe pastelate pentru plăci. Fonturi: **Fraunces** (titluri) și **Manrope** (text), găzduite local în temă (fără Google Fonts); chirilica din descrieri și recenzii folosește Lora.

**Ce se editează și unde:** nimic din conținut nu e scris în codul temei. Textele și datele magazinului se schimbă din panoul de administrare sau din Elementor; codul păstrează doar valori de pornire, folosite până la prima salvare.

| Ce | Unde |
|---|---|
| Telefon, e-mail, Facebook, Messenger, Instagram, adresa | Aspect → Personalizare → **Natur.MD — texte și contact** → Date de contact (sau Aspect → „Texte și contact”). Linkul de apel `tel:` se formează automat cu prefixul țării magazinului. |
| Bara de anunțuri, panoul „Magazin” din meniu, căutarea (inclusiv „Căutări populare”), antetul paginilor de magazin și categorii, butonul de pe card, pagina produsului (disponibilitate, avantaje, „Ai întrebări?”), coșul lateral, bara „livrare gratuită”, pagina de comandă (etichetele câmpurilor, „Unde livrăm?”, butonul) și confirmarea „Mulțumim”, butonul „Comandă acum”, subsolul, pagina 404 | Aspect → Personalizare → **Natur.MD — texte și contact**, câte o secțiune pentru fiecare. Un câmp golit ascunde elementul. |
| Logo-ul (antet) și logo-ul pentru fundal închis (subsol) | Aspect → Personalizare → Identitatea site-ului |
| Culorile principale ale temei | Elementor → Setări site → Culori globale: Pădure, Frunză, Text, Gălbenuș, Fundal, Titluri, Linkuri |
| Prima pagină: titluri, texte, butoane | Pagini → Acasă → „Editează cu Elementor” (widgeturile standard) |
| Prima pagină: colajele foto, dovezile din hero, plăcile de categorii, pașii, filele de produse, cifrele, recenziile | Widgeturile temei din Elementor, categoria **„Natur.MD”**: „Natur: Colaj foto (hero)”, „Natur: Dovezi”, „Natur: Plăci de categorii”, „Natur: Pași”, „Natur: Produse pe file” (fiecare filă: recomandate, noi, cele mai vândute, la reducere sau categoriile alese), „Natur: Colaj foto (poveste)”, „Natur: Cifre”, „Natur: Recenzii” |
| „Rețete și idei delicioase” (cartonașele video) | Widgetul **„Natur: Rețete video”**: pentru fiecare cartonaș — copertă (verticală, 3:4), titlu (Enter = rând nou), link spre reel/postare de Instagram sau clip YouTube (rulează pe site, într-o fereastră) ori un MP4 din Media, plus produsul din rețetă (apare sub cartonaș, cu „Adaugă în coș”). Coperțile actuale sunt demonstrative, iar linkurile duc la profilul de Instagram — înlocuiți-le cu reelurile reale. |
| Pagina Contact: cardurile (texte, harta livrărilor cu orașele după coordonate) și întrebările frecvente | Pagini → Contact → „Editează cu Elementor”: widgeturile „Natur: Carduri de contact” și „Natur: Întrebări frecvente” |
| Meniul principal și panoul „Magazin” | Aspect → Meniuri → „Meniu principal” (categoriile puse sub „Magazin” apar în panou, cu imaginea categoriei) |
| Subsolul: coloana „Informații” / coloana „Magazin” | Aspect → Meniuri → „Meniu subsol” / meniul pus în locația „Subsol — coloana Magazin” (fără meniu: categoriile cu cele mai multe produse) |
| Subsolul: linkurile „Politica de confidențialitate” și „Politica de cookies” (bara de jos) | Aspect → Personalizare → Natur.MD → Subsol, ultimele două câmpuri (titlul linkului = titlul paginii) |
| Subtitlul de sub titlul unei pagini | Câmpul „Rezumat” al paginii |
| Imaginea unei categorii (plăci, panou, antetul categoriei) | Produse → Categorii → Miniatură |
| Ambalajul afișat pe card („720 ml”, „per kg”) | Atributul ales în Personalizare → Natur.MD → Magazin și categorii (acum „Ambalare”) |
| Pragul livrării gratuite și costul livrării | WooCommerce → Setări → Livrare; toate textele care le pomenesc se actualizează singure |
| Simbolul monedei („lei”) și formatul prețului | WooCommerce → Setări → General |

În orice text (Personalizare, widgeturile Elementor, conținutul paginilor, rezumate, descrierile categoriilor) se pot folosi shortcode-urile **`[natur_telefon]`**, **`[natur_email]`**, **`[natur_livrare_gratuita]`** (pragul, ex. „500 lei”), **`[natur_cost_livrare]`** („50 lei”) și **`[natur_an]`** — așa, un telefon sau un prag schimbat apare corect peste tot. Paginile Livrare, Termeni, Retur, Confidențialitate și Cookies le folosesc deja.

Secțiunile primei pagini există și ca shortcode-uri, pentru alte pagini: `[natur_categories number="8"]`, `[natur_steps]`, `[natur_product_tabs limit="8"]`, `[natur_reviews]`, `[natur_stats]`, `[natur_contact]`, `[natur_faq]` … (lista completă în `inc/shortcodes.php`).

Rămân în cod doar etichetele de interfață (ex. „Subtotal”, „Coș”, „Cont”, textele pentru cititoarele de ecran) și stilul (spațieri, animații, nuanțele pastelate, în `assets/css/natur.css`).

**Comanda într-un singur pas** (`inc/checkout.php`, `woocommerce/checkout/`): cumpărătorul completează doar **numele, telefonul și adresa** și alege „Chișinău” sau „Altă localitate” (stabilește zona și costul livrării). Fără e-mail obligatoriu, cod poștal, cont, bifă de termeni sau alegerea plății (plata e la livrare); e-mailul și un comentariu sunt opționale, sub „Adaugă un comentariu sau e-mail”. Produsele și cantitățile se schimbă pe aceeași pagină, iar butonul arată totalul („Trimite comanda · 560 lei”). Drumuri spre comandă: butonul **„Comandă acum”** de pe pagina produsului, butonul din coșul lateral, iar pagina „Coș” cu produse trimite direct la comandă. Clientul autentificat primește confirmarea pe e-mailul contului; numele scris („Ana Popescu”) se salvează ca prenume + nume.

**Detalii pentru cumpărători:** bara de anunțuri cu mesaje care se schimbă; antet lipicios care se micșorează la derulare; panoul „Magazin” cu toate categoriile și imaginile lor; căutare live în timp ce scrii (tasta `/` o deschide, săgețile aleg rezultatul); butonul „+” de pe card adaugă în coș fără reîncărcare, cu notificare și contor animat; coș lateral cu − / +, ștergere și bară „Mai adaugă X lei pentru livrare gratuită”; pe pagina produsului: cantitate − / +, ambalaj, „În stoc”, cât mai lipsește până la livrarea gratuită, bară „Adaugă în coș” lipită jos pe telefon; bară de navigare jos pe telefon; pagină 404 ilustrată. Animațiile se opresc automat dacă utilizatorul a cerut „mișcare redusă” în sistem.

## Video și prezentare 360° pe pagina produsului

În editorul produsului (Produse → editează) există caseta **„Video & prezentare 360°”**:

- **Clipuri video** — lipiți un link YouTube / Vimeo sau apăsați „Din Media” pentru un fișier MP4 încărcat. Se pot adăuga mai multe clipuri.
- **Prezentare 360°** — „Selectează cadre 360°” și alegeți toate fotografiile făcute în jurul produsului (recomandat 24–72 de cadre, aceeași dimensiune, fundal identic). „Sortează după nume” le pune în ordinea fișierelor (ex. `produs-01.jpg … produs-36.jpg`).

Pe site apar butoanele **Foto / Video / 360°** peste imaginea produsului; în listări produsele primesc insigne „360°” / „Video”. Vizualizatorul 360° se rotește automat, se trage cu mouse-ul sau degetul, are butoane și ecran complet.

Folosire în alte pagini:
- shortcode `[natur_360 id="123"]` și `[natur_video id="123"]`;
- în Elementor, widgetul **„Produs: Video / 360°”**.

> Cadrele 360° (Unt topit GHEE, Pate de prepeliță, Ulei de cocos) și clipurile video (6 produse) existente sunt **generate automat din fotografiile vechi, doar ca demonstrație**. Înlocuiți-le cu fotografii 360° și filmări reale.

## Conținut importat

Tot catalogul de pe natur.md (verificat la 5 octombrie 2026): **130 de produse**, 42 de categorii cu produse (cele goale pe site-ul vechi au fost omise), 515 fotografii, 117 recenzii reale și descrierile lungi complete (tabul „Detalii”), cu cele 108 imagini din ele urcate în Media. Fiecare produs are toate categoriile pe care le avea pe natur.md, iar „Produse recomandate” au devenit produse recomandate (up-sells) WooCommerce. Notele cu stele ale recenziilor vechi nu au fost preluate: site-ul vechi le ascundea și aveau aproape toate valoarea implicită 3.

Șase categorii erau dezactivate în meniul vechi, iar produsele lor apăreau doar în căutare: Apă, Cadou, Condimente, Fructe uscate, Miere și magiun, Murături și oțet. Ele există și aici, tot în afara meniului (se pot adăuga din Aspect → Meniuri). „Carne de prepeliță tartinabilă” nu avea categorie pe natur.md, așa că e în „Diverse”.

Actualizare și verificare (pot fi rulate oricând; importul nu dublează nimic și nu suprascrie descrierile editate în admin):

```bash
python3 tools/scrape_natur.py          # tot catalogul (cu un număr, ex. 6: eșantion pe categorie)
python3 tools/make_media.py            # optimizează imaginile
ddev wp eval-file tools/import.php     # adaugă produsele noi, completează descrieri/categorii/recomandate
python3 tools/verify_products.py       # compară natur.md cu site-ul, câmp cu câmp (--offline: fără natur.md)
```

## Teste

```bash
cd tools/tests && npm install
BASE=https://natur.ddev ADMIN_PASS='Natur2026!' npm test   # ~2 minute, folosește Google Chrome
npm run cleanup                                             # șterge comenzile/clienții de test
```

Suita verifică: prima pagină, tema (panoul de categorii, căutarea live, adăugarea în coș cu notificare, coșul lateral cu − / +), galeria Foto/Video/360° (desktop + mobil), trei comenzi reale în pagina de comandă simplificată (doar nume + telefon cu livrare 50 lei, livrare gratuită, altă localitate prin Poșta Moldovei), „Comandă acum”, cantitățile și validarea telefonului, pagina de contact (telefon, „Copiază”, întrebări frecvente), căutarea, magazinul, categoriile, înregistrarea, redirecționările vechi, toate paginile de produs, afișarea pe mobil, editorul de produs și editorul Elementor.

## Lansare pe natur.md

1. Hosting: PHP 8.2+ (8.3 recomandat), MariaDB 10.6+ / MySQL 8, HTTPS, `upload_max_filesize` ≥ 64M (pentru clipuri).
2. Export: `ddev export-db --file=natur.sql.gz`, apoi urcați tot proiectul **fără** `tools/`, `.ddev/`, `README.md` și `wp-config-ddev.php`.
3. Pe server: creați `wp-config.php` cu datele bazei de date, importați `natur.sql.gz`, apoi:
   ```bash
   wp search-replace 'https://natur.ddev' 'https://natur.md' --all-tables
   wp option update blog_public 1        # permite indexarea Google
   wp user update admin --user_pass='<parolă nouă puternică>'
   wp rewrite flush
   ```
4. E-mail: instalați un plugin SMTP (ex. WP Mail SMTP) cu o căsuță reală `contact@natur.md`, altfel e-mailurile comenzilor pot ajunge în spam.
5. Îndreptați DNS-ul natur.md către noul server. Redirecționările de la URL-urile vechi funcționează automat.
6. Rulați testele pe producție: `BASE=https://natur.md ADMIN_PASS=… npm test`, apoi `npm run cleanup` (sau ștergeți manual comenzile de test din WooCommerce).

## De verificat de către proprietar

- Textele din **Termeni și condiții**, **Politica de retur**, **Politica de confidențialitate** și **Politica de cookies** sunt redactate ca punct de plecare; verificați-le (eventual cu un jurist). Politica de cookies listează cookie-urile setate efectiv de site; la adăugarea unui serviciu nou (Google Analytics, Facebook Pixel, chat) actualizați-o.
- Costul **Poștei Moldovei** este setat la 50 lei (gratuit de la 500 lei), ca în Chișinău; site-ul vechi nu preciza un tarif.
- Adresa fizică și programul de lucru nu apăreau pe site-ul vechi, deci nu au fost adăugate.
- E-mailurile despre comenzi noi se trimit la adresa administratorului (Setări → General); pentru altă adresă: WooCommerce → Setări → E-mailuri → Comandă nouă.
