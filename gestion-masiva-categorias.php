<?php
/**
 * Plugin Name: Gestión Masiva de Categorías
 * Plugin URI: https://example.com/
 * Description: Base mínima para un plugin de gestión masiva de categorías en WordPress.
 * Version: 2.0.0
 * Author: Loki
 * Author URI: https://example.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gestion-masiva-categorias
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMC_PLUGIN_VERSION', '2.0.0' );
define( 'GMC_PLUGIN_FILE', __FILE__ );
define( 'GMC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GMC_PLUGIN_PATH . 'includes/class-gmc-loader.php';

/**
 * Arranque mínimo del plugin.
 *
 * @return GMC_Loader
 */
function gmc_run_plugin() {
	$loader = new GMC_Loader();
	$loader->run();

	return $loader;
}

gmc_run_plugin();
