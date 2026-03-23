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
 * - Coordinar los handlers del formulario del admin.
 * - Cargar assets solo en la pantalla del plugin.
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

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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
	 * Carga estilos solo en la pantalla del plugin.
	 *
	 * @param string $hook_suffix Hook actual del admin.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'toplevel_page_gestion-masiva-categorias' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'gmc-admin',
			GMC_PLUGIN_URL . 'admin/css/gmc-admin.css',
			array(),
			GMC_PLUGIN_VERSION
		);
	}

	/**
	 * Procesa la acción de añadir categorías a posts.
	 *
	 * @return void
	 */
	public function handle_add_categories_action() {
		$this->process_category_action( 'add' );
	}

	/**
	 * Procesa la acción de quitar categorías de posts.
	 *
	 * @return void
	 */
	public function handle_remove_categories_action() {
		$this->process_category_action( 'remove' );
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
		<div class="wrap gmc-admin-page">
			<div class="gmc-cyber-shell">
				<div class="gmc-cyber-grid"></div>
				<div class="gmc-cyber-scanlines"></div>

				<div class="gmc-cyber-content">
					<div class="gmc-page-header">
						<h1><?php echo esc_html__( 'Gestión Masiva de Categorías', 'gestion-masiva-categorias' ); ?></h1>
						<p class="gmc-page-description">
							<?php echo esc_html__( 'Versión - capa UI sin tocar lógica', 'gestion-masiva-categorias' ); ?>
						</p>
					</div>

					<?php $this->render_notice(); ?>

					<div class="gmc-action-grid">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gmc-card gmc-action-card">
							<input type="hidden" name="action" value="gmc_add_categories" />
							<?php wp_nonce_field( 'gmc_add_categories_action', 'gmc_nonce' ); ?>

							<div class="gmc-card-header">
								<h2><?php echo esc_html__( 'Añadir categorías', 'gestion-masiva-categorias' ); ?></h2>
								<p><?php echo esc_html__( 'Suma una o varias categorías a los posts seleccionados sin eliminar las ya existentes.', 'gestion-masiva-categorias' ); ?></p>
							</div>

							<div class="gmc-card-body">
								<label for="gmc_add_category_ids" class="gmc-label">
									<?php echo esc_html__( 'Selecciona una o varias categorías', 'gestion-masiva-categorias' ); ?>
								</label>

								<select
									id="gmc_add_category_ids"
									name="gmc_category_ids[]"
									multiple="multiple"
									size="8"
									class="gmc-multiselect"
								>
									<?php foreach ( $categories as $category ) : ?>
										<option value="<?php echo (int) $category['id']; ?>">
											<?php echo esc_html( $category['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="gmc-card-footer">
								<button type="submit" class="button button-primary gmc-button-primary">
									<?php echo esc_html__( 'Añadir categorías', 'gestion-masiva-categorias' ); ?>
								</button>
							</div>
						</form>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gmc-card gmc-action-card">
							<input type="hidden" name="action" value="gmc_remove_categories" />
							<?php wp_nonce_field( 'gmc_remove_categories_action', 'gmc_nonce' ); ?>

							<div class="gmc-card-header">
								<h2><?php echo esc_html__( 'Quitar categorías', 'gestion-masiva-categorias' ); ?></h2>
								<p><?php echo esc_html__( 'Elimina solo las categorías seleccionadas de los posts marcados, manteniendo las demás.', 'gestion-masiva-categorias' ); ?></p>
							</div>

							<div class="gmc-card-body">
								<label for="gmc_remove_category_ids" class="gmc-label">
									<?php echo esc_html__( 'Selecciona una o varias categorías', 'gestion-masiva-categorias' ); ?>
								</label>

								<select
									id="gmc_remove_category_ids"
									name="gmc_category_ids[]"
									multiple="multiple"
									size="8"
									class="gmc-multiselect"
								>
									<?php foreach ( $categories as $category ) : ?>
										<option value="<?php echo (int) $category['id']; ?>">
											<?php echo esc_html( $category['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="gmc-card-footer">
								<button type="submit" class="button gmc-button-secondary">
									<?php echo esc_html__( 'Quitar categorías', 'gestion-masiva-categorias' ); ?>
								</button>
							</div>
						</form>
					</div>

					<div class="gmc-posts-section">
						<div class="gmc-section-header">
							<h2><?php echo esc_html__( 'Posts disponibles', 'gestion-masiva-categorias' ); ?></h2>
							<p>
								<?php echo esc_html__( 'Selecciona los posts sobre los que quieres aplicar la acción elegida.', 'gestion-masiva-categorias' ); ?>
							</p>
						</div>

						<?php if ( empty( $posts ) ) : ?>
							<div class="gmc-card gmc-empty-state">
								<p><?php echo esc_html__( 'No se encontraron posts para mostrar.', 'gestion-masiva-categorias' ); ?></p>
							</div>
						<?php else : ?>
							<p class="gmc-list-meta">
								<?php echo esc_html__( 'Mostrando hasta 20 posts estándar.', 'gestion-masiva-categorias' ); ?>
							</p>

							<div class="gmc-post-list">
								<?php foreach ( $posts as $post_item ) : ?>
									<div class="gmc-post-row">
										<div class="gmc-post-select">
											<input
												type="checkbox"
												class="gmc-post-checkbox"
												value="<?php echo (int) $post_item['id']; ?>"
												aria-label="<?php echo esc_attr( sprintf( __( 'Seleccionar post %s', 'gestion-masiva-categorias' ), $post_item['title'] ) ); ?>"
											/>
										</div>

										<div class="gmc-post-content">
											<div class="gmc-post-topline">
												<h3 class="gmc-post-title"><?php echo esc_html( $post_item['title'] ); ?></h3>
												<span class="gmc-post-status">
													<?php
													echo esc_html(
														sprintf(
															/* translators: %s: post status. */
															__( 'Estado: %s', 'gestion-masiva-categorias' ),
															(string) $post_item['status']
														)
													);
													?>
												</span>
											</div>

											<div class="gmc-post-categories">
												<span class="gmc-post-categories-label">
													<?php echo esc_html__( 'Categorías actuales:', 'gestion-masiva-categorias' ); ?>
												</span>

												<?php if ( empty( $post_item['categories'] ) ) : ?>
													<span class="gmc-post-category-empty">
														<?php echo esc_html__( 'Sin categorías asignadas.', 'gestion-masiva-categorias' ); ?>
													</span>
												<?php else : ?>
													<div class="gmc-category-badges">
														<?php foreach ( $post_item['categories'] as $category ) : ?>
															<span class="gmc-category-badge"><?php echo esc_html( $category['name'] ); ?></span>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>

							<script>
								document.addEventListener('DOMContentLoaded', function () {
									const checkboxes = document.querySelectorAll('.gmc-post-checkbox');
									const forms = document.querySelectorAll('form[action*="admin-post.php"]');

									function syncSelectedPosts() {
										const selected = Array.from(checkboxes)
											.filter((checkbox) => checkbox.checked)
											.map((checkbox) => checkbox.value);

										forms.forEach((form) => {
											form.querySelectorAll('input[name="gmc_selected_posts[]"]').forEach((input) => input.remove());

											selected.forEach((postId) => {
												const hiddenInput = document.createElement('input');
												hiddenInput.type = 'hidden';
												hiddenInput.name = 'gmc_selected_posts[]';
												hiddenInput.value = postId;
												form.appendChild(hiddenInput);
											});
										});
									}

									checkboxes.forEach((checkbox) => {
										checkbox.addEventListener('change', syncSelectedPosts);
									});

									syncSelectedPosts();
								});
							</script>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Procesa acción de categorías.
	 *
	 * @param string $operation Tipo de operación.
	 * @return void
	 */
	private function process_category_action( $operation ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'No tienes permisos para ejecutar esta acción.', 'gestion-masiva-categorias' ) );
		}

		$nonce_action = 'add' === $operation ? 'gmc_add_categories_action' : 'gmc_remove_categories_action';
		check_admin_referer( $nonce_action, 'gmc_nonce' );

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

		$result = 'add' === $operation
			? $this->post_category_service->add_categories_to_posts( $post_ids, $category_ids )
			: $this->post_category_service->remove_categories_from_posts( $post_ids, $category_ids );

		$this->redirect_with_notice(
			$result['success'] ? 'success' : 'error',
			$result['message']
		);
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
			'success' => 'notice notice-success gmc-notice',
			'error'   => 'notice notice-error gmc-notice',
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