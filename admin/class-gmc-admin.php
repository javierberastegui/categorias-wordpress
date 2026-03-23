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
 * - Coordinar el handler del formulario del admin.
 */
class GMC_Admin {

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
	 * Servicio de relación post-categoría.
	 *
	 * @var GMC_Post_Category_Service
	 */
	private $post_category_service;

	/**
	 * Constructor.
	 *
	 * @param GMC_Category_Service      $category_service      Servicio de categorías.
	 * @param GMC_Post_Service          $post_service          Servicio de posts.
	 * @param GMC_Post_Category_Service $post_category_service Servicio de relación post-categoría.
	 */
	public function __construct(
		GMC_Category_Service $category_service,
		GMC_Post_Service $post_service,
		GMC_Post_Category_Service $post_category_service
	) {
		$this->category_service      = $category_service;
		$this->post_service          = $post_service;
		$this->post_category_service = $post_category_service;
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
	 * Procesa la acción de añadir categorías a posts.
	 *
	 * @return void
	 */
	public function handle_add_categories_action() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'No tienes permisos para ejecutar esta acción.', 'gestion-masiva-categorias' ) );
		}

		check_admin_referer( 'gmc_add_categories_action', 'gmc_nonce' );

		$post_ids     = isset( $_POST['gmc_selected_posts'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['gmc_selected_posts'] ) ) : array();
		$category_ids = isset( $_POST['gmc_category_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['gmc_category_ids'] ) ) : array();

		if ( empty( $post_ids ) ) {
			$this->redirect_with_notice(
				'error',
				__( 'Debes seleccionar al menos un post.', 'gestion-masiva-categorias' )
			);
		}

		foreach ( $post_ids as $post_id ) {
			if ( 'post' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
				$this->redirect_with_notice(
					'error',
					__( 'Uno o más posts seleccionados no son válidos o no tienes permisos para editarlos.', 'gestion-masiva-categorias' )
				);
			}
		}

		if ( empty( $category_ids ) ) {
			$this->redirect_with_notice(
				'error',
				__( 'Debes seleccionar al menos una categoría.', 'gestion-masiva-categorias' )
			);
		}

		$result = $this->post_category_service->add_categories_to_posts( $post_ids, $category_ids );

		$this->redirect_with_notice(
			$result['success'] ? 'success' : 'error',
			$result['message']
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

		$posts      = $this->post_service->get_posts( 20 );
		$categories = $this->category_service->get_categories( 50 );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ); ?></h1>
			<p><?php echo esc_html__( 'Versión - primera acción real (add)', 'gestion-masiva-categorias' ); ?></p>

			<?php $this->render_notice(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gmc_add_categories" />
				<?php wp_nonce_field( 'gmc_add_categories_action', 'gmc_nonce' ); ?>

				<div style="margin:16px 0;padding:12px;background:#fff;border:1px solid #dcdcde;">
					<h2 style="margin-top:0;"><?php echo esc_html__( 'Añadir categorías a posts seleccionados', 'gestion-masiva-categorias' ); ?></h2>

					<label for="gmc_category_ids" style="display:block;margin-bottom:8px;">
						<?php echo esc_html__( 'Selecciona una o varias categorías', 'gestion-masiva-categorias' ); ?>
					</label>

					<select
						id="gmc_category_ids"
						name="gmc_category_ids[]"
						multiple="multiple"
						size="8"
						style="min-width:320px;max-width:100%;margin-bottom:12px;"
					>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo (int) $category['id']; ?>">
								<?php echo esc_html( $category['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<p style="margin:0;">
						<button type="submit" class="button button-primary">
							<?php echo esc_html__( 'Añadir categorías', 'gestion-masiva-categorias' ); ?>
						</button>
					</p>
				</div>

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
			</form>
		</div>
		<?php
	}

	/**
	 * Renderiza aviso si existe en query string.
	 *
	 * @return void
	 */
	private function render_notice() {
		$type    = isset( $_GET['gmc_notice'] ) ? sanitize_key( wp_unslash( $_GET['gmc_notice'] ) ) : '';
		$message = isset( $_GET['gmc_message'] ) ? sanitize_text_field( wp_unslash( $_GET['gmc_message'] ) ) : '';

		if ( empty( $type ) || empty( $message ) ) {
			return;
		}

		$allowed_types = array(
			'success' => 'notice notice-success',
			'error'   => 'notice notice-error',
		);

		if ( ! isset( $allowed_types[ $type ] ) ) {
			return;
		}
		?>
		<div class="<?php echo esc_attr( $allowed_types[ $type ] ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirige a la pantalla del plugin con aviso.
	 *
	 * @param string $type    Tipo de aviso.
	 * @param string $message Mensaje.
	 * @return void
	 */
	private function redirect_with_notice( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'        => 'gestion-masiva-categorias',
				'gmc_notice'  => sanitize_key( $type ),
				'gmc_message' => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}