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
	 * Registra la subpágina base dentro de Entradas.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'edit.php',
			__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ),
			__( 'Gestión categorías', 'gestion-masiva-categorias' ),
			'manage_categories',
			'gestion-masiva-categorias',
			array( $this, 'render_admin_page' )
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

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ) . '</h1>';
		echo '<p>' . esc_html__( 'Versión - esqueleto inicial sin lógica', 'gestion-masiva-categorias' ) . '</p>';
		echo '<p>' . esc_html__( 'La funcionalidad se añadirá en siguientes etapas.', 'gestion-masiva-categorias' ) . '</p>';
		echo '</div>';
	}
}
