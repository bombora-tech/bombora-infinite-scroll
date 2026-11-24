# Bombora Infinite Scroll

Automatické donačítání produktů ve výpisech WooCommerce (Bombora group s.r.o.). Náhrada za ZG Load More (`infinite.js`) a starší snippety „Infinite Scroll products“.

- Rozpozná výpis **Bombora B2B** (`.zgb-rows`), **Bricks** smyčku (`.infinite-list` / `.infinite-post`) i standardní **WooCommerce** (`ul.products`).
- Při scrollování donačte až N stran (výchozí 4), pak tlačítko „Načíst další produkty“ – patička zůstává dostupná.
- Počítadlo + ukazatel průběhu, skeleton, obnovení načtených stran a pozice po návratu z detailu produktu.
- Texty: CS, SK, DE, FR, NL, EN, RU (podle jazyka WPML).
- Re-inicializace Bricks (množství +/−, AJAX do košíku, lazy-load), Perfmatters lazy-load, přepočet kartonů Bombora B2B.
- GA4 `load_more` (`page_number`, `items_shown`, `load_method`).
- Klasické stránkování zůstává v HTML pro roboty.
- Nastavení: **Bombora → Infinite Scroll** (bez Bombora Manageru: *Nastavení → Bombora Infinite Scroll*).

## Instalace
Nainstalujte **Bombora Manager** (https://github.com/bombora-tech/bombora-manager/releases) a v *Bombora → Moduly* klikněte u Infinite Scroll na *Instalovat* → *Aktivovat*.

## Vydání nové verze
1. Zvyšte verzi v hlavičce `bombora-infinite-scroll.php` **i** v `BOMBORA_IS_VERSION`.
2. Doplňte sekci do `CHANGELOG.md`.
3. Push do `main` → GitHub Action sestaví ZIP + `.sha256` a vytvoří Release `vX.Y.Z`.
4. Weby uvidí aktualizaci do 6 h (nebo ihned: *Bombora → Moduly → Zkontrolovat aktualizace*).

Ručně: `./build.sh` → `dist/`.

## Diagnostika
Administrátor: `?bis_debug=1` na produktovém výpisu přidá do HTML komentář se zdroji skriptů infinite/load more (soubor:řádek).

## Filtry a události
- `bombora_is_enabled_here` (bool) – zapnout/vypnout na konkrétní stránce.
- `bombora_is_i18n` (array $texts, string $lang) – upravit texty.
- JS: `bombora:infinite-loaded` (`detail.page`, `detail.items`), `zg:loadmore`.
