<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Módulo base del área de administración.
 *
 * Responsabilidad única:
 * - Registrar el punto de entrada del admin.
 * - Renderizar pantallas del plugin.
 * - Coordinar los handlers del admin.
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
	 *
	 * @param GMC_Category_Service        $category_service        Servicio de categorías.
	 * @param GMC_Category_Detail_Service $category_detail_service Servicio de detalle de categoría.
	 * @param GMC_Category_Update_Service $category_update_service Servicio de actualización de categoría.
	 * @param GMC_Post_Service            $post_service            Servicio de posts.
	 * @param GMC_Post_Category_Service   $post_category_service   Servicio de relación post-categoría.
	 */
	public function __construct(
		GMC_Category_Service $category_service,
		GMC_Category_Detail_Service $category_detail_service,
		GMC_Category_Update_Service $category_update_service,
		GMC_Post_Service $post_service,
		GMC_Post_Category_Service $post_category_service
	) {
		$this->category_service        = $category_service;
		$this->category_detail_service = $category_detail_service;
		$this->category_update_service = $category_update_service;
		$this->post_service            = $post_service;
		$this->post_category_service   = $post_category_service;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Registra las páginas del plugin en el menú lateral.
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

		add_submenu_page(
			'gestion-masiva-categorias',
			__( 'Mantenimiento de categorías', 'gestion-masiva-categorias' ),
			__( 'Mantenimiento categorías', 'gestion-masiva-categorias' ),
			'manage_categories',
			'gmc-category-maintenance',
			array( $this, 'render_category_maintenance_page' )
		);
	}

	/**
	 * Carga estilos solo en pantallas del plugin.
	 *
	 * @param string $hook_suffix Hook actual del admin.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$allowed_hooks = array(
			'toplevel_page_gestion-masiva-categorias',
			'gestion-categorias_page_gmc-category-maintenance',
		);

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
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
	 * Procesa la acción de actualización de categoría.
	 *
	 * @return void
	 */
	public function handle_update_category_action() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'No tienes permisos para ejecutar esta acción.', 'gestion-masiva-categorias' ) );
		}

		check_admin_referer( 'gmc_update_category_action', 'gmc_category_nonce' );

		$category_id         = isset( $_POST['gmc_category_id'] ) ? absint( wp_unslash( $_POST['gmc_category_id'] ) ) : 0;
		$name                = isset( $_POST['gmc_category_name'] ) ? wp_unslash( $_POST['gmc_category_name'] ) : '';
		$description         = isset( $_POST['gmc_category_description'] ) ? wp_unslash( $_POST['gmc_category_description'] ) : '';
		$slug                = isset( $_POST['gmc_category_slug'] ) ? wp_unslash( $_POST['gmc_category_slug'] ) : '';
		$confirm_slug_change = ! empty( $_POST['gmc_confirm_slug_change'] );

		$result = $this->category_update_service->update_category(
			$category_id,
			$name,
			$description,
			$slug,
			$confirm_slug_change
		);

		$this->redirect_to_category_maintenance(
			$category_id,
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

		$current_page      = isset( $_GET['gmc_page_num'] ) ? absint( wp_unslash( $_GET['gmc_page_num'] ) ) : 1;
		$current_page      = max( 1, $current_page );
		$per_page          = 20;
		$selected_category = isset( $_GET['gmc_filter_category'] ) ? absint( wp_unslash( $_GET['gmc_filter_category'] ) ) : 0;

		$post_data   = $this->post_service->get_posts( $current_page, $per_page, $selected_category );
		$posts       = isset( $post_data['items'] ) ? $post_data['items'] : array();
		$pagination  = isset( $post_data['pagination'] ) ? $post_data['pagination'] : array();
		$categories  = $this->category_service->get_categories( 50 );
		$total_pages = isset( $pagination['total_pages'] ) ? (int) $pagination['total_pages'] : 1;
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

					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="gmc-card" style="margin-bottom:18px;padding:16px;">
						<input type="hidden" name="page" value="gestion-masiva-categorias" />

						<label for="gmc_filter_category" class="gmc-label">
							<?php echo esc_html__( 'Filtrar posts por categoría', 'gestion-masiva-categorias' ); ?>
						</label>

						<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
							<select id="gmc_filter_category" name="gmc_filter_category" class="gmc-multiselect" style="min-height:auto;height:42px;max-width:360px;">
								<option value="0"><?php echo esc_html__( 'Todas las categorías', 'gestion-masiva-categorias' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo (int) $category['id']; ?>" <?php selected( $selected_category, (int) $category['id'] ); ?>>
										<?php echo esc_html( $category['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>

							<button type="submit" class="button gmc-button-secondary">
								<?php echo esc_html__( 'Aplicar filtro', 'gestion-masiva-categorias' ); ?>
							</button>

							<?php if ( $selected_category > 0 ) : ?>
								<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=gestion-masiva-categorias' ) ); ?>">
									<?php echo esc_html__( 'Quitar filtro', 'gestion-masiva-categorias' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>

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
								<?php
								echo esc_html(
									sprintf(
										__( 'Página %1$d de %2$d.', 'gestion-masiva-categorias' ),
										(int) $pagination['current_page'],
										max( 1, $total_pages )
									)
								);
								?>
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

							<?php $this->render_pagination( $pagination, $selected_category ); ?>

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
	 * Renderiza pantalla separada de mantenimiento de categorías.
	 *
	 * @return void
	 */
	public function render_category_maintenance_page() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'gestion-masiva-categorias' ) );
		}

		$selected_category_id = isset( $_GET['gmc_category_id'] ) ? absint( wp_unslash( $_GET['gmc_category_id'] ) ) : 0;
		$categories           = $this->category_service->get_categories( 50 );
		$category_detail      = null;

		if ( $selected_category_id > 0 ) {
			$category_detail = $this->category_detail_service->get_category_detail( $selected_category_id );
		}
		?>
		<div class="wrap gmc-admin-page">
			<div class="gmc-cyber-shell">
				<div class="gmc-cyber-grid"></div>
				<div class="gmc-cyber-scanlines"></div>

				<div class="gmc-cyber-content">
					<div class="gmc-page-header">
						<h1><?php echo esc_html__( 'Mantenimiento de categorías', 'gestion-masiva-categorias' ); ?></h1>
						<p class="gmc-page-description">
							<?php echo esc_html__( 'Edición básica de nombre, descripción y slug de una categoría concreta.', 'gestion-masiva-categorias' ); ?>
						</p>
					</div>

					<?php $this->render_notice(); ?>

					<div class="gmc-card" style="margin-bottom:18px;padding:16px;">
						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
							<input type="hidden" name="page" value="gmc-category-maintenance" />

							<label for="gmc_category_id" class="gmc-label">
								<?php echo esc_html__( 'Selecciona una categoría', 'gestion-masiva-categorias' ); ?>
							</label>

							<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
								<select id="gmc_category_id" name="gmc_category_id" class="gmc-multiselect" style="min-height:auto;height:42px;max-width:360px;">
									<option value="0"><?php echo esc_html__( 'Elige una categoría', 'gestion-masiva-categorias' ); ?></option>
									<?php foreach ( $categories as $category ) : ?>
										<option value="<?php echo (int) $category['id']; ?>" <?php selected( $selected_category_id, (int) $category['id'] ); ?>>
											<?php echo esc_html( $category['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>

								<button type="submit" class="button gmc-button-secondary">
									<?php echo esc_html__( 'Ver detalle', 'gestion-masiva-categorias' ); ?>
								</button>
							</div>
						</form>
					</div>

					<?php if ( $selected_category_id > 0 && ! $category_detail ) : ?>
						<div class="gmc-card gmc-empty-state">
							<p><?php echo esc_html__( 'La categoría seleccionada no existe o no está disponible.', 'gestion-masiva-categorias' ); ?></p>
						</div>
					<?php elseif ( $category_detail ) : ?>
						<div class="gmc-card" style="padding:18px;">
							<h2 style="margin-top:0;"><?php echo esc_html__( 'Editar categoría', 'gestion-masiva-categorias' ); ?></h2>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="gmc_update_category" />
								<input type="hidden" name="gmc_category_id" value="<?php echo (int) $category_detail['id']; ?>" />
								<?php wp_nonce_field( 'gmc_update_category_action', 'gmc_category_nonce' ); ?>

								<div style="margin-bottom:16px;">
									<label class="gmc-label" for="gmc_category_name">
										<?php echo esc_html__( 'Nombre', 'gestion-masiva-categorias' ); ?>
									</label>
									<input
										type="text"
										id="gmc_category_name"
										name="gmc_category_name"
										class="regular-text"
										value="<?php echo esc_attr( $category_detail['name'] ); ?>"
									/>
								</div>

								<div style="margin-bottom:16px;">
									<label class="gmc-label" for="gmc_category_slug">
										<?php echo esc_html__( 'Slug', 'gestion-masiva-categorias' ); ?>
									</label>
									<input
										type="text"
										id="gmc_category_slug"
										name="gmc_category_slug"
										class="regular-text"
										value="<?php echo esc_attr( $category_detail['slug'] ); ?>"
									/>
								</div>

								<div style="margin-bottom:16px;padding:12px;border:1px solid rgba(217,70,239,0.25);border-radius:10px;background:rgba(217,70,239,0.08);">
									<p style="margin-top:0;margin-bottom:10px;">
										<strong><?php echo esc_html__( 'Aviso sobre cambio de slug', 'gestion-masiva-categorias' ); ?></strong>
									</p>
									<p style="margin-top:0;margin-bottom:10px;">
										<?php echo esc_html__( 'Cambiar el slug modifica la URL de esta categoría. Hazlo solo si realmente quieres alterar su ruta pública.', 'gestion-masiva-categorias' ); ?>
									</p>
									<label>
										<input type="checkbox" name="gmc_confirm_slug_change" value="1" />
										<?php echo esc_html__( 'Confirmo explícitamente que quiero permitir el cambio de slug si lo he modificado.', 'gestion-masiva-categorias' ); ?>
									</label>
								</div>

								<div style="margin-bottom:16px;">
									<label class="gmc-label" for="gmc_category_description">
										<?php echo esc_html__( 'Descripción', 'gestion-masiva-categorias' ); ?>
									</label>
									<textarea
										id="gmc_category_description"
										name="gmc_category_description"
										rows="6"
										class="large-text"
									><?php echo esc_textarea( $category_detail['description'] ); ?></textarea>
								</div>

								<div style="margin-bottom:16px;">
									<p><strong><?php echo esc_html__( 'ID:', 'gestion-masiva-categorias' ); ?></strong> <?php echo (int) $category_detail['id']; ?></p>
									<p><strong><?php echo esc_html__( 'Parent:', 'gestion-masiva-categorias' ); ?></strong>
										<?php
										if ( (int) $category_detail['parent_id'] > 0 ) {
											echo esc_html( $category_detail['parent_name'] . ' (#' . (int) $category_detail['parent_id'] . ')' );
										} else {
											echo esc_html__( 'Sin parent.', 'gestion-masiva-categorias' );
										}
										?>
									</p>
								</div>

								<p style="margin-bottom:0;">
									<button type="submit" class="button button-primary gmc-button-primary">
										<?php echo esc_html__( 'Guardar cambios', 'gestion-masiva-categorias' ); ?>
									</button>
								</p>
							</form>
						</div>
					<?php else : ?>
						<div class="gmc-card gmc-empty-state">
							<p><?php echo esc_html__( 'Selecciona una categoría para editar su detalle.', 'gestion-masiva-categorias' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renderiza paginación simple.
	 *
	 * @param array<string, mixed> $pagination        Datos de paginación.
	 * @param int                  $selected_category Categoría filtrada actual.
	 * @return void
	 */
	private function render_pagination( array $pagination, $selected_category = 0 ) {
		$current_page      = isset( $pagination['current_page'] ) ? (int) $pagination['current_page'] : 1;
		$total_pages       = isset( $pagination['total_pages'] ) ? (int) $pagination['total_pages'] : 1;
		$has_previous      = ! empty( $pagination['has_previous'] );
		$has_next          = ! empty( $pagination['has_next'] );
		$selected_category = absint( $selected_category );

		if ( $total_pages <= 1 ) {
			return;
		}

		$base_args = array(
			'page' => 'gestion-masiva-categorias',
		);

		if ( $selected_category > 0 ) {
			$base_args['gmc_filter_category'] = $selected_category;
		}

		$previous_url = add_query_arg(
			array_merge(
				$base_args,
				array(
					'gmc_page_num' => max( 1, $current_page - 1 ),
				)
			),
			admin_url( 'admin.php' )
		);

		$next_url = add_query_arg(
			array_merge(
				$base_args,
				array(
					'gmc_page_num' => $current_page + 1,
				)
			),
			admin_url( 'admin.php' )
		);
		?>
		<div class="gmc-pagination">
			<?php if ( $has_previous ) : ?>
				<a class="button gmc-button-secondary" href="<?php echo esc_url( $previous_url ); ?>">
					<?php echo esc_html__( '← Anterior', 'gestion-masiva-categorias' ); ?>
				</a>
			<?php endif; ?>

			<span class="gmc-pagination-info">
				<?php
				echo esc_html(
					sprintf(
						__( 'Página %1$d de %2$d', 'gestion-masiva-categorias' ),
						$current_page,
						$total_pages
					)
				);
				?>
			</span>

			<?php if ( $has_next ) : ?>
				<a class="button gmc-button-secondary" href="<?php echo esc_url( $next_url ); ?>">
					<?php echo esc_html__( 'Siguiente →', 'gestion-masiva-categorias' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Procesa acción de categorías para posts.
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
	 * Redirige a la pantalla principal del plugin con aviso.
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

	/**
	 * Redirige a la pantalla de mantenimiento de categorías con aviso.
	 *
	 * @param int    $category_id ID de categoría.
	 * @param string $type        Tipo de aviso.
	 * @param string $message     Mensaje.
	 * @return void
	 */
	private function redirect_to_category_maintenance( $category_id, $type, $message ) {
		$args = array(
			'page'        => 'gmc-category-maintenance',
			'gmc_notice'  => sanitize_key( $type ),
			'gmc_message' => rawurlencode( $message ),
		);

		$category_id = absint( $category_id );

		if ( $category_id > 0 ) {
			$args['gmc_category_id'] = $category_id;
		}

		$url = add_query_arg(
			$args,
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}