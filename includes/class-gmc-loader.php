<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-detail-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-update-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-post-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-post-category-service.php';
require_once GMC_PLUGIN_PATH . 'admin/class-gmc-admin.php';

/**
 * Loader principal del plugin.
 */
class GMC_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Solo carga archivos. Sin instancias todavía.
	}

	/**
	 * Arranque.
	 *
	 * @return void
	 */
	public function run() {
		// Base mínima.
	}
}