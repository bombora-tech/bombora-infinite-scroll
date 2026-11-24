<?php
/**
 * Nastavení → Bombora Infinite Scroll
 */
defined( 'ABSPATH' ) || exit;

/**
 * Menu: pod „Bombora“ (Bombora Manager), pokud na webu je; jinak Nastavení.
 */
function bombora_is_has_hub() {
	global $admin_page_hooks;
	if ( ! empty( $admin_page_hooks['bombora'] ) ) {
		return true;
	}
	// admin-post.php nespouští admin_menu – rozhodnout podle aktivního Bombora Manageru.
	return in_array( 'bombora-manager/bombora-manager.php', (array) get_option( 'active_plugins', array() ), true );
}

function bombora_is_admin_url() {
	return bombora_is_has_hub()
		? admin_url( 'admin.php?page=bombora-infinite-scroll' )
		: admin_url( 'options-general.php?page=bombora-infinite-scroll' );
}

add_filter( 'plugin_action_links_' . plugin_basename( BOMBORA_IS_FILE ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( bombora_is_admin_url() ) . '">Nastavení</a>' );
	return $links;
} );

add_action( 'admin_menu', function () {
	global $admin_page_hooks;
	if ( ! empty( $admin_page_hooks['bombora'] ) ) {
		add_submenu_page( 'bombora', 'Bombora Infinite Scroll', 'Infinite Scroll', 'manage_options', 'bombora-infinite-scroll', 'bombora_is_render_page' );
	} else {
		add_options_page( 'Bombora Infinite Scroll', 'Bombora Infinite Scroll', 'manage_options', 'bombora-infinite-scroll', 'bombora_is_render_page' );
	}
}, 99 );

add_action( 'admin_init', function () {
	register_setting( 'bombora_is', BOMBORA_IS_OPT, array(
		'type'              => 'array',
		'sanitize_callback' => 'bombora_is_sanitize',
		'default'           => bombora_is_defaults(),
	) );
} );

function bombora_is_sanitize( $in ) {
	$d   = bombora_is_defaults();
	$out = array();
	$in  = is_array( $in ) ? $in : array();

	$out['enabled']     = empty( $in['enabled'] ) ? 0 : 1;
	$out['auto_pages']  = isset( $in['auto_pages'] ) ? min( 50, absint( $in['auto_pages'] ) ) : $d['auto_pages'];
	$out['root_margin'] = isset( $in['root_margin'] ) ? min( 3000, absint( $in['root_margin'] ) ) : $d['root_margin'];
	$out['btn_radius']  = isset( $in['btn_radius'] ) ? min( 60, absint( $in['btn_radius'] ) ) : $d['btn_radius'];

	foreach ( array( 'list_sel', 'item_sel', 'next_sel', 'hide_sel', 'dequeue' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? trim( wp_strip_all_tags( wp_unslash( $in[ $k ] ) ) ) : $d[ $k ];
	}
	foreach ( array( 'c_accent', 'c_text', 'c_strong', 'c_track', 'btn_bg', 'btn_text', 'btn_border', 'btn_hover_bg', 'btn_hover_text' ) as $k ) {
		$v         = isset( $in[ $k ] ) ? sanitize_hex_color( trim( $in[ $k ] ) ) : '';
		$out[ $k ] = $v ? $v : $d[ $k ];
	}
	return $out;
}

function bombora_is_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = bombora_is_settings();
	$n = BOMBORA_IS_OPT;

	$text = function ( $k, $label, $help = '', $type = 'text', $attrs = '' ) use ( $s, $n ) {
		printf(
			'<tr><th scope="row"><label for="bis-%1$s">%2$s</label></th><td><input type="%3$s" id="bis-%1$s" name="%4$s[%1$s]" value="%5$s" class="%6$s" %7$s>%8$s</td></tr>',
			esc_attr( $k ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $n ),
			esc_attr( $s[ $k ] ),
			'text' === $type ? 'regular-text code' : 'small-text',
			$attrs, // phpcs:ignore -- statické atributy
			$help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : ''
		);
	};
	$color = function ( $k, $label ) use ( $s, $n ) {
		printf(
			'<tr><th scope="row"><label for="bis-%1$s">%2$s</label></th><td><input type="color" id="bis-%1$s" name="%3$s[%1$s]" value="%4$s"> <code>%4$s</code></td></tr>',
			esc_attr( $k ),
			esc_html( $label ),
			esc_attr( $n ),
			esc_attr( $s[ $k ] )
		);
	};
	?>
	<div class="wrap">
		<h1>Bombora Infinite Scroll</h1>
		<p>Automatické donačítání produktů ve výpisech (obchod, kategorie, atributy, hledání). Plugin sám rozpozná výpis Bombora B2B (<code>.zgb-rows</code>), Bricks smyčku (<code>.infinite-list</code>) i standardní WooCommerce (<code>ul.products</code>). Klasické stránkování zůstává v HTML pro roboty.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'bombora_is' ); ?>
			<h2 class="title">Chování</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zapnuto</th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> Donačítat produkty při scrollování</label></td></tr>
				<?php
				$text( 'auto_pages', 'Automaticky donačíst stran', 'Kolik dalších stran se načte samo při scrollování. Potom už jen tlačítkem (aby byla dostupná patička). 0 = jen tlačítko.', 'number', 'min="0" max="50"' );
				$text( 'root_margin', 'Předstih načítání (px)', 'Jak daleko před koncem výpisu se začne načítat další strana.', 'number', 'min="0" max="3000" step="50"' );
				$text( 'dequeue', 'Vypnout staré skripty', 'Nepovinné: handle skriptů oddělené čárkou, které se na webu nemají načítat. Staré řešení odstraňte v sekci Staré skripty níže.' );
				?>
			</table>
			<h2 class="title">Selektory (nepovinné)</h2>
			<p>Nechte prázdné pro automatické rozpoznání.</p>
			<table class="form-table" role="presentation">
				<?php
				$text( 'list_sel', 'Kontejner výpisu', 'např. <code>.infinite-list</code>' );
				$text( 'item_sel', 'Položka', 'např. <code>.infinite-post</code>' );
				$text( 'next_sel', 'Odkaz na další stranu', 'např. <code>.brxe-pagination a.next</code>' );
				$text( 'hide_sel', 'Skrýt prvky', 'Stránkování / staré tlačítko, např. <code>.brxe-pagination, .infinite-load-more</code>' );
				?>
			</table>
			<h2 class="title">Vzhled</h2>
			<table class="form-table" role="presentation">
				<?php
				$color( 'c_accent', 'Ukazatel průběhu' );
				$color( 'c_track', 'Podklad ukazatele' );
				$color( 'c_text', 'Text počítadla' );
				$color( 'c_strong', 'Zvýrazněné číslo' );
				$color( 'btn_bg', 'Tlačítko – pozadí' );
				$color( 'btn_text', 'Tlačítko – text' );
				$color( 'btn_border', 'Tlačítko – rámeček' );
				$color( 'btn_hover_bg', 'Tlačítko – pozadí (hover)' );
				$color( 'btn_hover_text', 'Tlačítko – text (hover)' );
				$text( 'btn_radius', 'Zaoblení tlačítka (px)', '', 'number', 'min="0" max="60"' );
				?>
			</table>
			<?php submit_button( 'Uložit nastavení' ); ?>
		</form>
		<?php bombora_is_render_legacy(); ?>
	</div>
	<?php
}

/**
 * Vyhledání starých řešení infinite scroll / load more (snippety, soubory šablony).
 */
function bombora_is_legacy_scan() {
	global $wpdb;
	$rx    = '/infinite|load.?more|zg_lm|zg-lm/i';
	$found = array( 'files' => array(), 'cs' => array(), 'pm' => array() );

	// Soubory v child / parent šabloně (assets/js, js).
	$dirs = array_unique( array( get_stylesheet_directory(), get_template_directory() ) );
	foreach ( $dirs as $dir ) {
		foreach ( array( '/assets/js/*.js', '/js/*.js', '/*.js' ) as $pat ) {
			foreach ( (array) glob( $dir . $pat ) as $f ) {
				if ( ! $f || ! is_file( $f ) || ! preg_match( $rx, basename( $f ) ) ) {
					continue;
				}
				$head                   = (string) file_get_contents( $f, false, null, 0, 600 ); // phpcs:ignore
				$found['files'][ $f ] = (bool) preg_match( '/ZG Load More|Infinite Scroll/i', $head );
			}
		}
	}

	// Code Snippets (tabulka {prefix}snippets).
	$t = $wpdb->prefix . 'snippets';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) === $t ) { // phpcs:ignore
		$rows = $wpdb->get_results( "SELECT id, name, active, code FROM {$t}" ); // phpcs:ignore
		foreach ( (array) $rows as $r ) {
			if ( preg_match( '/infinite\.js|infinite-list|zg_lm|ZG Load More|Infinite Scroll/i', $r->name . ' ' . $r->code ) ) {
				$found['cs'][] = sprintf( '#%d %s (%s)', $r->id, $r->name, $r->active ? 'aktivní' : 'neaktivní' );
			}
		}
	}

	// Perfmatters → Code (soubory uploads/perfmatters/code-snippets).
	$up = wp_upload_dir( null, false );
	foreach ( (array) glob( trailingslashit( $up['basedir'] ) . 'perfmatters/code-snippets/*.php' ) as $f ) {
		if ( $f && is_file( $f ) && preg_match( '/infinite\.js|infinite-list|zg_lm|ZG Load More|Infinite Scroll/i', (string) file_get_contents( $f ) ) ) { // phpcs:ignore
			$found['pm'][] = basename( $f );
		}
	}
	return $found;
}

function bombora_is_render_legacy() {
	$f = bombora_is_legacy_scan();
	echo '<hr><h2 class="title">Staré skripty</h2>';
	if ( isset( $_GET['bis_deleted'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success inline"><p>Smazáno souborů: ' . absint( $_GET['bis_deleted'] ) . '</p></div>'; // phpcs:ignore
	}
	if ( ! $f['files'] && ! $f['cs'] && ! $f['pm'] ) {
		echo '<p><span class="dashicons dashicons-yes" style="color:#008a20"></span> Žádné staré řešení infinite scroll / load more nenalezeno. Výpis řídí jen tento plugin.</p>';
		return;
	}
	echo '<table class="widefat striped" style="max-width:900px"><tbody>';
	foreach ( $f['files'] as $path => $ok ) {
		echo '<tr><td>Soubor šablony</td><td><code>' . esc_html( str_replace( ABSPATH, '', $path ) ) . '</code>' . ( $ok ? '' : ' <em>(neobsahuje hlavičku ZG Load More – smažte ručně)</em>' ) . '</td></tr>';
	}
	foreach ( $f['cs'] as $s ) {
		echo '<tr><td>Code Snippets</td><td>' . esc_html( $s ) . ' – smažte v <a href="' . esc_url( admin_url( 'admin.php?page=snippets' ) ) . '">Snippets</a></td></tr>';
	}
	foreach ( $f['pm'] as $s ) {
		echo '<tr><td>Perfmatters → Code</td><td><code>' . esc_html( $s ) . '</code> – smažte v <a href="' . esc_url( admin_url( 'options-general.php?page=perfmatters#code' ) ) . '">Perfmatters → Code</a></td></tr>';
	}
	echo '</tbody></table>';
	if ( array_filter( $f['files'] ) ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px">';
		wp_nonce_field( 'bombora_is_delete_legacy' );
		echo '<input type="hidden" name="action" value="bombora_is_delete_legacy">';
		submit_button( 'Smazat staré soubory ZG Load More ze šablony', 'delete', 'submit', false );
		echo '</form>';
	}
}

add_action( 'admin_post_bombora_is_delete_legacy', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nedostatečná oprávnění.' );
	}
	check_admin_referer( 'bombora_is_delete_legacy' );
	$n    = 0;
	$dirs = array( wp_normalize_path( get_stylesheet_directory() ), wp_normalize_path( get_template_directory() ) );
	foreach ( bombora_is_legacy_scan()['files'] as $path => $ok ) {
		$p      = wp_normalize_path( $path );
		$inside = false;
		foreach ( $dirs as $d ) {
			if ( 0 === strpos( $p, trailingslashit( $d ) ) ) {
				$inside = true;
			}
		}
		if ( $ok && $inside && is_file( $p ) ) {
			wp_delete_file( $p );
			if ( ! file_exists( $p ) ) {
				$n++;
			}
		}
	}
	wp_safe_redirect( add_query_arg( 'bis_deleted', $n, bombora_is_admin_url() ) );
	exit;
} );
