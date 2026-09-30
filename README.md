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
- **Temă Astra + Elementor** — prima pagină este construită în Elementor (Pagini → Acasă → „Editează cu Elementor”); antetul, subsolul, culorile și fonturile se modifică din Aspect → Personalizare.
- **Livrare** (WooCommerce → Setări → Livrare):
  - mun. Chișinău: curier 50 lei, gratuit de la 500 lei;
  - restul Moldovei: Poșta Moldovei (produse neperisabile) 50 lei, gratuit de la 500 lei.
- **Plată**: la livrare (numerar).
- **Pagini**: Despre noi, Livrare și plată, Contact (formular Contact Form 7), Termeni și condiții, Politica de retur, Politica de confidențialitate.
- **Redirecționări 301** de la toate URL-urile vechi PrestaShop (`/17-lactate-molochnye`, `/oua-yajca/58-oua-de-prepelita.html`, `/content/1-conditii` …) către paginile noi — păstrează poziția în Google după lansare.

### Cod propriu

| Fișier | Rol |
|---|---|
| `wp-content/plugins/natur-product-media/` | Plugin „Natur – Video & 360° produs” |
| `wp-content/mu-plugins/natur-site.php` | Moneda „lei”, redirecționări, reguli livrare, traduceri lipsă Astra, ajustări CSS |
| `tools/scrape_natur.py` | Extrage categorii, produse, imagini și recenzii de pe natur.md |
| `tools/make_media.py` | Optimizează imaginile; generează cadre 360° și clipuri **demonstrative** |
| `tools/import.php` | Importă datele extrase în WooCommerce (idempotent) |
| `tools/setup.php` | Configurează tot site-ul (setări, livrare, pagini, meniuri, temă, prima pagină) |
| `tools/tests/` | Teste end-to-end automate |

`tools/` nu este accesibil din browser (blocat în `.ddev/nginx/deny-tools.conf`) și **nu se urcă pe serverul de producție**.

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

De pe natur.md au fost preluate aleatoriu până la 6 produse din fiecare categorie: **86 de produse**, 36 de categorii (cele goale pe site-ul vechi au fost omise), 390 de fotografii și 100 de recenzii reale. Notele cu stele ale recenziilor vechi nu au fost preluate: site-ul vechi le ascundea și aveau aproape toate valoarea implicită 3.

Import complet al catalogului (toate produsele):

```bash
python3 tools/scrape_natur.py 9999     # toate produsele din fiecare categorie
python3 tools/make_media.py            # optimizează imaginile
ddev wp eval-file tools/import.php     # sare peste produsele deja importate
ddev wp eval-file tools/setup.php      # reîmprospătează meniuri, imagini categorii etc.
```

## Teste

```bash
cd tools/tests && npm install
BASE=https://natur.ddev ADMIN_PASS='Natur2026!' npm test   # ~2 minute, folosește Google Chrome
npm run cleanup                                             # șterge comenzile/clienții de test
```

Suita verifică: prima pagină, galeria Foto/Video/360° (desktop + mobil), două comenzi reale (livrare 50 lei și gratuită), formularul de contact, căutarea, magazinul, categoriile, înregistrarea, redirecționările vechi, toate paginile de produs, afișarea pe mobil, editorul de produs și editorul Elementor.

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

- Textele din **Termeni și condiții**, **Politica de retur** și **Politica de confidențialitate** sunt redactate ca punct de plecare; verificați-le (eventual cu un jurist).
- Costul **Poștei Moldovei** este setat la 50 lei (gratuit de la 500 lei), ca în Chișinău; site-ul vechi nu preciza un tarif.
- Adresa fizică și programul de lucru nu apăreau pe site-ul vechi, deci nu au fost adăugate.
- E-mailurile despre comenzi noi se trimit la adresa administratorului (Setări → General); pentru altă adresă: WooCommerce → Setări → E-mailuri → Comandă nouă.
