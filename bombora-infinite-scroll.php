<?php
/**
 * Plugin Name:       Bombora Infinite Scroll
 * Plugin URI:        https://github.com/bombora-tech/bombora-infinite-scroll
 * Description:       Automatické donačítání produktů ve výpisech WooCommerce (Bombora B2B, Bricks smyčky, standardní WooCommerce). Počítadlo, ukazatel průběhu, tlačítko „Načíst další“, obnovení pozice po návratu z detailu produktu. Nahrazuje ZG Load More (infinite.js).
 * Version:           1.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Bombora group s.r.o.
 * Author URI:        https://www.zgruzie.cz/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bombora-infinite-scroll
 * Update URI:        https://github.com/bombora-tech/bombora-infinite-scroll
 *
 * @package Bombora\InfiniteScroll
 */

defined( 'ABSPATH' ) || exit;

define( 'BOMBORA_IS_VERSION', '1.0.5' );
define( 'BOMBORA_IS_FILE', __FILE__ );
define( 'BOMBORA_IS_OPT', 'bombora_is_settings' );

/**
 * Výchozí nastavení (vzhled odpovídá B2C webům; na VO se barvy přepíší v nastavení).
 */
function bombora_is_defaults() {
	return array(
		'enabled'         => 1,
		'auto_pages'      => 4,
		'root_margin'     => 700,
		'list_sel'        => '',
		'item_sel'        => '',
		'next_sel'        => '',
		'hide_sel'        => '',
		'dequeue'         => '',
		'c_accent'        => '#d31014',
		'c_text'          => '#6b6b6b',
		'c_strong'        => '#212121',
		'c_track'         => '#e7e7e7',
		'btn_bg'          => '#d31014',
		'btn_text'        => '#ffffff',
		'btn_border'      => '#d31014',
		'btn_hover_bg'    => '#b00d10',
		'btn_hover_text'  => '#ffffff',
		'btn_radius'      => 8,
	);
}

function bombora_is_settings() {
	$s = get_option( BOMBORA_IS_OPT, array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), bombora_is_defaults() );
}

/**
 * Běží jen na produktových výpisech (obchod, kategorie, štítky, atributy, hledání produktů).
 */
function bombora_is_is_target() {
	if ( is_admin() || ! function_exists( 'is_shop' ) ) {
		return false;
	}
	$on = is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
	return (bool) apply_filters( 'bombora_is_enabled_here', $on );
}

/**
 * Texty podle jazyka (WPML přepíná locale).
 */
function bombora_is_i18n() {
	$loc = strtolower( substr( determine_locale(), 0, 2 ) );
	$t   = array(
		'cs' => array(
			'shown'  => 'Zobrazeno produktů: <b>%n</b> · strana %p z %m',
			'shownN' => 'Zobrazeno produktů: <b>%n</b>',
			'done'   => 'Zobrazeny všechny produkty (<b>%n</b>)',
			'more'   => 'Načíst další produkty',
			'err'    => 'Načtení se nezdařilo – zkusit znovu',
		),
		'sk' => array(
			'shown'  => 'Zobrazené produkty: <b>%n</b> · strana %p z %m',
			'shownN' => 'Zobrazené produkty: <b>%n</b>',
			'done'   => 'Zobrazené všetky produkty (<b>%n</b>)',
			'more'   => 'Načítať ďalšie produkty',
			'err'    => 'Načítanie zlyhalo – skúsiť znova',
		),
		'de' => array(
			'shown'  => 'Angezeigte Produkte: <b>%n</b> · Seite %p von %m',
			'shownN' => 'Angezeigte Produkte: <b>%n</b>',
			'done'   => 'Alle Produkte angezeigt (<b>%n</b>)',
			'more'   => 'Weitere Produkte laden',
			'err'    => 'Laden fehlgeschlagen – erneut versuchen',
		),
		'fr' => array(
			'shown'  => "Produits affichés\u{00A0}: <b>%n</b> · page %p sur %m",
			'shownN' => "Produits affichés\u{00A0}: <b>%n</b>",
			'done'   => 'Tous les produits sont affichés (<b>%n</b>)',
			'more'   => 'Charger plus de produits',
			'err'    => 'Échec du chargement – réessayer',
		),
		'nl' => array(
			'shown'  => 'Getoonde producten: <b>%n</b> · pagina %p van %m',
			'shownN' => 'Getoonde producten: <b>%n</b>',
			'done'   => 'Alle producten getoond (<b>%n</b>)',
			'more'   => 'Meer producten laden',
			'err'    => 'Laden mislukt – opnieuw proberen',
		),
		'ru' => array(
			'shown'  => 'Показано товаров: <b>%n</b> · страница %p из %m',
			'shownN' => 'Показано товаров: <b>%n</b>',
			'done'   => 'Показаны все товары (<b>%n</b>)',
			'more'   => 'Загрузить ещё товары',
			'err'    => 'Ошибка загрузки – повторить',
		),
		'en' => array(
			'shown'  => 'Products shown: <b>%n</b> · page %p of %m',
			'shownN' => 'Products shown: <b>%n</b>',
			'done'   => 'All products shown (<b>%n</b>)',
			'more'   => 'Load more products',
			'err'    => 'Loading failed – try again',
		),
	);
	$out = isset( $t[ $loc ] ) ? $t[ $loc ] : $t['en'];
	return apply_filters( 'bombora_is_i18n', $out, $loc );
}

/**
 * Vypnutí starého ZG Load More (infinite.js z child theme) a dalších zadaných skriptů.
 */
function bombora_is_dequeue_old() {
	$s = bombora_is_settings();
	if ( empty( $s['enabled'] ) || '' === trim( $s['dequeue'] ) ) {
		return;
	}
	foreach ( array_filter( array_map( 'trim', explode( ',', $s['dequeue'] ) ) ) as $h ) {
		wp_dequeue_script( $h );
		wp_deregister_script( $h );
	}
}
add_action( 'wp_enqueue_scripts', 'bombora_is_dequeue_old', 10000 );
add_action( 'wp_print_scripts', 'bombora_is_dequeue_old', 10000 );

add_action( 'wp_enqueue_scripts', function () {
	$s = bombora_is_settings();
	if ( empty( $s['enabled'] ) || ! bombora_is_is_target() ) {
		return;
	}
	$url = plugin_dir_url( BOMBORA_IS_FILE );
	wp_enqueue_style( 'bombora-infinite-scroll', $url . 'assets/bombora-infinite-scroll.css', array(), BOMBORA_IS_VERSION );

	$c   = array( 'c_accent', 'c_text', 'c_strong', 'c_track', 'btn_bg', 'btn_text', 'btn_border', 'btn_hover_bg', 'btn_hover_text' );
	$css = ':root{';
	foreach ( $c as $k ) {
		$v = sanitize_hex_color( $s[ $k ] );
		if ( $v ) {
			$css .= '--bis-' . str_replace( '_', '-', $k ) . ':' . $v . ';';
		}
	}
	$css .= '--bis-btn-radius:' . absint( $s['btn_radius'] ) . 'px}';
	wp_add_inline_style( 'bombora-infinite-scroll', $css );

	wp_enqueue_script( 'bombora-infinite-scroll', $url . 'assets/bombora-infinite-scroll.js', array(), BOMBORA_IS_VERSION, true );
	wp_localize_script( 'bombora-infinite-scroll', 'BomboraIS', array(
		'autoPages'  => absint( $s['auto_pages'] ),
		'rootMargin' => absint( $s['root_margin'] ),
		'list'       => trim( $s['list_sel'] ),
		'item'       => trim( $s['item_sel'] ),
		'next'       => trim( $s['next_sel'] ),
		'hide'       => trim( $s['hide_sel'] ),
		't'          => bombora_is_i18n(),
	) );
}, 20 );

/**
 * Diagnostika pro administrátora: ?bis_debug=1 na produktovém výpisu.
 * Vypíše do HTML komentáře, odkud se registrují skripty s „infinite“/„load more“ v názvu
 * a které funkce jsou navázané na wp_enqueue_scripts (soubor:řádek).
 */
add_action( 'wp_footer', function () {
	if ( empty( $_GET['bis_debug'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore
		return;
	}
	global $wp_scripts, $wp_filter;
	$out = array( 'Bombora Infinite Scroll – diagnostika' );
	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->registered as $h => $s ) {
			if ( preg_match( '/infinite|load.?more/i', $h . ' ' . $s->src ) ) {
				$out[] = 'SCRIPT ' . $h . ' => ' . $s->src . ' ver ' . $s->ver . ( in_array( $h, $wp_scripts->queue, true ) ? ' [ve frontě]' : '' );
			}
		}
	}
	if ( isset( $wp_filter['wp_enqueue_scripts'] ) ) {
		foreach ( $wp_filter['wp_enqueue_scripts']->callbacks as $prio => $cbs ) {
			foreach ( $cbs as $cb ) {
				$f = $cb['function'];
				try {
					if ( is_string( $f ) && function_exists( $f ) ) {
						$r = new ReflectionFunction( $f );
					} elseif ( $f instanceof Closure ) {
						$r = new ReflectionFunction( $f );
					} elseif ( is_array( $f ) ) {
						$r = new ReflectionMethod( $f[0], $f[1] );
					} else {
						continue;
					}
					$file = (string) $r->getFileName();
					$name = is_string( $f ) ? $f : ( is_array( $f ) ? ( is_object( $f[0] ) ? get_class( $f[0] ) : $f[0] ) . '::' . $f[1] : 'closure' );
					$src  = '';
					if ( $file && is_readable( $file ) ) {
						$lines = file( $file );
						$src   = implode( '', array_slice( $lines, $r->getStartLine() - 1, max( 1, $r->getEndLine() - $r->getStartLine() + 1 ) ) );
					}
					if ( preg_match( '/infinite|load.?more/i', $name . $src ) || false !== strpos( $file, 'eval()' ) ) {
						$out[] = 'HOOK p' . $prio . ' ' . $name . ' @ ' . str_replace( ABSPATH, '', $file ) . ':' . $r->getStartLine();
					}
				} catch ( Throwable $e ) {
					continue;
				}
			}
		}
	}
	echo "\n<!-- " . esc_html( implode( "\n", $out ) ) . " -->\n";
}, 9999 );

if ( is_admin() ) {
	require_once __DIR__ . '/includes/admin.php';
}
