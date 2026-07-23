<?php
/**
 * WPDash Child Plugin
 *
 * @package MainWP\Child
 */

/**
 * Plugin Name: WPDash Child
 * Description: Integração segura com o WP Dash - A plataforma de gestão de sites WordPress da Apiki
 * Plugin URI: https://wpdash.apiki.com/
 * Author: Apiki
 * Author URI: https://apiki.com
 * Text Domain: mainwp-child
 * Version: 6.3.2
 * Requires at least: 5.4
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Acesso direto negado.
}

/**
 * Desativa este plugin automaticamente.
 *
 * Esta versão não contém mais funcionalidade. Ao ser atualizada, o plugin
 * se desativa sozinho no primeiro carregamento do admin.
 *
 * @return void
 */
function wpdash_child_self_deactivate() {
    if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php'; // NOSONAR - WP compatible.
    }

    deactivate_plugins( plugin_basename( __FILE__ ), true );
}

add_action( 'admin_init', 'wpdash_child_self_deactivate' );
