<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Módulo base del área de administración.
 *
 * Responsabilidad única:
 * - Registrar el punto de entrada del admin.
 * - Renderizar la pantalla del plugin.
 */
class GMC_Admin {

	/**
	 * Servicio de categorías.
	 *
	 * @var GMC_Category_Service
	 */
	private $category_service;

	/**
	 * Constructor.
	 *
	 * @param GMC_Category_Service $category_service Servicio de categorías.
	 */
	public function __construct( GMC_Category_Service $category_service ) {
		$this->category_service = $category_service;
	}

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
	 * Renderiza la pantalla base con listado de categorías.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'gestion-masiva-categorias' ) );
		}

		$categories = $this->category_service->get_categories( 50 );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ); ?></h1>
			<p><?php echo esc_html__( 'Versión - visualización básica de categorías', 'gestion-masiva-categorias' ); ?></p>

			<?php if ( empty( $categories ) ) : ?>
				<p><?php echo esc_html__( 'No se encontraron categorías para mostrar.', 'gestion-masiva-categorias' ); ?></p>
			<?php else : ?>
				<p><?php echo esc_html__( 'Mostrando hasta 50 categorías.', 'gestion-masiva-categorias' ); ?></p>

				<div class="gmc-category-list">
					<?php foreach ( $categories as $category ) : ?>
						<div class="gmc-category-row" style="margin-bottom:10px;padding:8px 0;border-bottom:1px solid #ddd;">
							<label>
								<input
									type="checkbox"
									name="gmc_selected_categories[]"
									value="<?php echo (int) $category['id']; ?>"
									disabled
								/>
								<strong><?php echo esc_html( $category['name'] ); ?></strong>
							</label>

							<?php if ( ! empty( $category['parent'] ) ) : ?>
								<span>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %d: parent category ID. */
											__( ' — Padre ID: %d', 'gestion-masiva-categorias' ),
											(int) $category['parent']
										)
									);
									?>
								</span>
							<?php else : ?>
								<span>
									<?php echo esc_html__( ' — Sin padre', 'gestion-masiva-categorias' ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}