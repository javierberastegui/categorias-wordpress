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
	 * @param int    $category_id ID de la categoría.
	 * @param string $name        Nombre.
	 * @param string $description Descripción.
	 * @param string $slug        Slug.
	 * @return array<string, mixed>
	 */
	public function update_category( $category_id, $name, $description, $slug ) {
		$category_id = absint( $category_id );
		$name        = sanitize_text_field( $name );
		$description = sanitize_textarea_field( $description );
		$slug        = sanitize_title( $slug );

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

		$args = array(
			'name'        => $name,
			'description' => $description,
			'slug'        => $slug,
		);

		$result = wp_update_term( $category_id, 'category', $args );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Categoría actualizada correctamente.', 'gestion-masiva-categorias' ),
		);
	}
}