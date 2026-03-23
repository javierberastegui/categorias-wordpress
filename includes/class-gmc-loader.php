<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$this->admin = new GMC_Admin();
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
	 * En esta etapa no necesita ejecutar lógica adicional.
	 *
	 * @return void
	 */
	public function run() {
		// Base lista para crecer en siguientes etapas.
	}
}
