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
	 * Servicio de detalle de categoría.
	 *
	 * @var GMC_Category_Detail_Service
	 */
	private $category_detail_service;

	/**
	 * Servicio de actualización de categoría.
	 *
	 * @var GMC_Category_Update_Service
	 */
	private $category_update_service;

	/**
	 * Servicio de posts.
	 *
	 * @var GMC_Post_Service
	 */
	private $post_service;

	/**
	 * Servicio de relación post-categoría.
	 *
	 * @var GMC_Post_Category_Service
	 */
	private $post_category_service;

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
		$this->category_service        = new GMC_Category_Service();
		$this->category_detail_service = new GMC_Category_Detail_Service();
		$this->category_update_service = new GMC_Category_Update_Service();
		$this->post_service            = new GMC_Post_Service();
		$this->post_category_service   = new GMC_Post_Category_Service();

		$this->admin = new GMC_Admin(
			$this->category_service,
			$this->category_detail_service,
			$this->category_update_service,
			$this->post_service,
			$this->post_category_service
		);
	}

	/**
	 * Registra hooks del área admin.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this->admin, 'register_admin_menu' ) );
			add_action( 'admin_post_gmc_add_categories', array( $this->admin, 'handle_add_categories_action' ) );
			add_action( 'admin_post_gmc_remove_categories', array( $this->admin, 'handle_remove_categories_action' ) );
			add_action( 'admin_post_gmc_update_category', array( $this->admin, 'handle_update_category_action' ) );
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