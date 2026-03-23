<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-post-service.php';
require_once GMC_PLUGIN_PATH . 'admin/class-gmc-admin.php';

/**
 * Loader principal del plugin.
 *
 * Responsabilidad única:
 * - Cargar módulos.
 * - Registrar hooks base.
 */
class GMC_Loader {

	/**
	 * Módulo de administración.
	 *
	 * @var GMC_Admin
	 */
	private $admin;

	/**
	 * Servicio de categorías.
	 *
	 * @var GMC_Category_Service
	 */
	private $category_service;

	/**
	 * Servicio de posts.
	 *
	 * @var GMC_Post_Service
	 */
	private $post_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Carga dependencias mínimas.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$this->category_service = new GMC_Category_Service();
		$this->post_service     = new GMC_Post_Service();
		$this->admin            = new GMC_Admin( $this->category_service, $this->post_service );
	}

	/**
	 * Registra hooks del área admin.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this->admin, 'register_admin_menu' ) );
		}
	}

	/**
	 * Método de arranque.
	 *
	 * @return void
	 */
	public function run() {
		// Base lista para crecer en siguientes etapas.
	}
}