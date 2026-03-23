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
	 * Se mantiene como apoyo futuro.
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
	 *
	 * @param GMC_Category_Service $category_service Servicio de categorías.
	 * @param GMC_Post_Service     $post_service     Servicio de posts.
	 */
	public function __construct( GMC_Category_Service $category_service, GMC_Post_Service $post_service ) {
		$this->category_service = $category_service;
		$this->post_service     = $post_service;
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
	 * Renderiza la pantalla principal orientada a posts.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'gestion-masiva-categorias' ) );
		}

		$posts = $this->post_service->get_posts( 20 );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ); ?></h1>
			<p><?php echo esc_html__( 'Versión - listado base de posts con categorías actuales', 'gestion-masiva-categorias' ); ?></p>

			<?php if ( empty( $posts ) ) : ?>
				<p><?php echo esc_html__( 'No se encontraron posts para mostrar.', 'gestion-masiva-categorias' ); ?></p>
			<?php else : ?>
				<p><?php echo esc_html__( 'Mostrando hasta 20 posts estándar.', 'gestion-masiva-categorias' ); ?></p>

				<div class="gmc-post-list">
					<?php foreach ( $posts as $post_item ) : ?>
						<div class="gmc-post-row" style="margin-bottom:12px;padding:10px 0;border-bottom:1px solid #ddd;">
							<label style="display:block;margin-bottom:4px;">
								<input
									type="checkbox"
									name="gmc_selected_posts[]"
									value="<?php echo (int) $post_item['id']; ?>"
									disabled
								/>
								<strong><?php echo esc_html( $post_item['title'] ); ?></strong>
							</label>

							<p style="margin:0 0 4px 24px;">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: post status. */
										__( 'Estado: %s', 'gestion-masiva-categorias' ),
										(string) $post_item['status']
									)
								);
								?>
							</p>

							<p style="margin:0 0 0 24px;">
								<strong><?php echo esc_html__( 'Categorías actuales:', 'gestion-masiva-categorias' ); ?></strong>
								<?php if ( empty( $post_item['categories'] ) ) : ?>
									<?php echo esc_html__( ' Sin categorías asignadas.', 'gestion-masiva-categorias' ); ?>
								<?php else : ?>
									<?php
									$category_names = array();

									foreach ( $post_item['categories'] as $category ) {
										$category_names[] = $category['name'];
									}

									echo esc_html( ' ' . implode( ', ', $category_names ) );
									?>
								<?php endif; ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}