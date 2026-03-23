<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-detail-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-update-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-category-slug-change-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-post-service.php';
require_once GMC_PLUGIN_PATH . 'includes/class-gmc-post-category-service.php';
require_once GMC_PLUGIN_PATH . 'admin/class-gmc-admin.php';

/**
 * Loader principal del plugin.
 */
class GMC_Loader {

	/**
	 * Módulo admin.
	 *
	 * @var GMC_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$category_service         = new GMC_Category_Service();
		$category_detail_service  = new GMC_Category_Detail_Service();
		$category_update_service  = new GMC_Category_Update_Service();
		$slug_change_service      = new GMC_Category_Slug_Change_Service();
		$post_service             = new GMC_Post_Service();
		$post_category_service    = new GMC_Post_Category_Service();

		$this->admin = new GMC_Admin(
			$category_service,
			$category_detail_service,
			$category_update_service,
			$slug_change_service,
			$post_service,
			$post_category_service
		);
	}

	/**
	 * Arranque.
	 *
	 * @return void
	 */
	public function run() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this->admin, 'register_admin_menu' ) );
			add_action( 'admin_post_gmc_add_categories', array( $this->admin, 'handle_add_categories_action' ) );
			add_action( 'admin_post_gmc_remove_categories', array( $this->admin, 'handle_remove_categories_action' ) );
			add_action( 'admin_post_gmc_update_category', array( $this->admin, 'handle_update_category_action' ) );
		}
	}
}