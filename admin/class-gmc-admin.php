<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Módulo base del área de administración.
 *
 * Responsabilidad única:
 * - Registrar el punto de entrada del admin.
 * - Renderizar una pantalla mínima sin lógica de negocio.
 */
class GMC_Admin {

	/**
	 * Registra la página principal del plugin en el menú lateral.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ),
			__( 'Gestión categorías', 'gestion-masiva-categorias' ),
			'manage_categories',
			'gestion-masiva-categorias',
			array( $this, 'render_admin_page' ),
			'dashicons-category',
			58
		);
	}

	/**
	 * Renderiza una pantalla mínima de placeholder.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'gestion-masiva-categorias' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ); ?></h1>
			<p><?php echo esc_html__( 'Versión - pantalla vacía del plugin', 'gestion-masiva-categorias' ); ?></p>
			<p><?php echo esc_html__( 'Aquí vivirá la funcionalidad del plugin en siguientes etapas.', 'gestion-masiva-categorias' ); ?></p>
		</div>
		<?php
	}
}