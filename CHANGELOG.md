# Changelog

Formát podle [Keep a Changelog](https://keepachangelog.com/cs/), verze podle [SemVer](https://semver.org/lang/cs/).

## [1.0.5] - 2026-11-24

### Změněno
- Plugin je modulem **Bombora Manageru** (repo `bombora-tech/bombora-infinite-scroll`): instalace a aktualizace v *Bombora → Moduly*, nastavení v *Bombora → Infinite Scroll*.
- Hlavička `Update URI` ukazuje na GitHub repozitář.

## [1.0.4] - 2026-11-24

### Opraveno
- Po smazání starých souborů se vrací na správnou stránku nastavení (menu Bombora i Nastavení).

## [1.0.3] - 2026-11-24

### Přidáno
- Sekce **Staré skripty**: najde ZG Load More / Infinite Scroll v Code Snippets, Perfmatters → Code a v šabloně; soubory ze šablony smaže tlačítkem.

### Změněno
- Blokování starých skriptů (`infinite.js`) je ve výchozím stavu vypnuté – staré řešení se odstraňuje, ne skrývá.

## [1.0.2] - 2026-11-24

### Přidáno
- Nastavení pod menu **Bombora**, pokud je na webu Bombora Manager.
- Diagnostika pro administrátora: `?bis_debug=1` na výpisu vypíše, odkud se registrují skripty infinite/load more.

## [1.0.1] - 2026-11-24

### Opraveno
- Bricks 2.x: tlačítka +/− a AJAX „Do košíku“ u donačtených karet (funkce Bricks jsou globální `const`, ne `window.*`).

## [1.0.0] - 2026-11-24

### Přidáno
- Automatické donačítání produktů (Bombora B2B `.zgb-rows`, Bricks `.infinite-list`, WooCommerce `ul.products`), po 4 stranách tlačítko.
- Počítadlo a ukazatel průběhu (CS, SK, DE, FR, NL, EN, RU), skeleton řádky/karty, obnovení stránek a pozice po návratu z detailu.
- GA4 událost `load_more`, událost `zg:loadmore` / `bombora:infinite-loaded`.
