<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de actualización de categorías.
 *
 * Responsabilidad única:
 * - Validar y actualizar una categoría concreta.
 * - No renderiza HTML.
 * - No procesa requests del admin.
 */
class GMC_Category_Update_Service {

	/**
	 * Actualiza una categoría concreta.
	 *
	 * @param int    $category_id         ID de la categoría.
	 * @param string $name                Nombre.
	 * @param string $description         Descripción.
	 * @param string $slug                Slug.
	 * @param bool   $confirm_slug_change Confirmación explícita de cambio de slug.
	 * @return array<string, mixed>
	 */
	public function update_category( $category_id, $name, $description, $slug, $confirm_slug_change = false ) {
		$category_id         = absint( $category_id );
		$name                = sanitize_text_field( $name );
		$description         = sanitize_textarea_field( $description );
		$slug                = sanitize_title( $slug );
		$confirm_slug_change = (bool) $confirm_slug_change;

		if ( $category_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'La categoría indicada no es válida.', 'gestion-masiva-categorias' ),
			);
		}

		$term = get_term( $category_id, 'category' );

		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'success' => false,
				'message' => __( 'La categoría indicada no existe.', 'gestion-masiva-categorias' ),
			);
		}

		if ( '' === $name ) {
			return array(
				'success' => false,
				'message' => __( 'El nombre de la categoría no puede estar vacío.', 'gestion-masiva-categorias' ),
			);
		}

		$current_slug = sanitize_title( $term->slug );
		$new_slug     = sanitize_title( $slug );
		$slug_changed = $current_slug !== $new_slug;

		if ( $slug_changed && ! $confirm_slug_change ) {
			return array(
				'success' => false,
				'message' => __( 'Has cambiado el slug. Debes confirmar explícitamente que quieres modificar la URL de esta categoría.', 'gestion-masiva-categorias' ),
				'slug_changed' => true,
				'old_slug'     => $current_slug,
				'new_slug'     => $new_slug,
				'term_id'      => $category_id,
			);
		}

		$args = array(
			'name'        => $name,
			'description' => $description,
			'slug'        => $new_slug,
		);

		$result = wp_update_term( $category_id, 'category', $args );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
				'slug_changed' => $slug_changed,
				'old_slug'     => $current_slug,
				'new_slug'     => $new_slug,
				'term_id'      => $category_id,
			);
		}

		return array(
			'success'      => true,
			'message'      => $slug_changed
				? __( 'Categoría actualizada correctamente. El slug ha sido modificado.', 'gestion-masiva-categorias' )
				: __( 'Categoría actualizada correctamente.', 'gestion-masiva-categorias' ),
			'slug_changed' => $slug_changed,
			'old_slug'     => $current_slug,
			'new_slug'     => $new_slug,
			'term_id'      => $category_id,
		);
	}
}