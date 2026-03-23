<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de relación post-categoría.
 *
 * Responsabilidad única:
 * - Aplicar cambios de categorías sobre posts.
 * - No renderiza HTML.
 * - No gestiona requests del admin.
 */
class GMC_Post_Category_Service {

	/**
	 * Añade categorías a posts sin eliminar las existentes.
	 *
	 * @param array<int, mixed> $post_ids     IDs de posts.
	 * @param array<int, mixed> $category_ids IDs de categorías.
	 * @return array<string, mixed>
	 */
	public function add_categories_to_posts( array $post_ids, array $category_ids ) {
		$post_ids     = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
		$category_ids = array_values( array_unique( array_filter( array_map( 'absint', $category_ids ) ) ) );

		if ( empty( $post_ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'Debes seleccionar al menos un post.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		if ( empty( $category_ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'Debes seleccionar al menos una categoría.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		$valid_category_ids = array();

		foreach ( $category_ids as $category_id ) {
			$term = get_term( $category_id, 'category' );

			if ( $term && ! is_wp_error( $term ) ) {
				$valid_category_ids[] = (int) $term->term_id;
			}
		}

		$valid_category_ids = array_values( array_unique( $valid_category_ids ) );

		if ( empty( $valid_category_ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'Las categorías seleccionadas no son válidas.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		$updated_count = 0;

		foreach ( $post_ids as $post_id ) {
			if ( 'post' !== get_post_type( $post_id ) ) {
				continue;
			}

			$result = wp_set_post_categories( $post_id, $valid_category_ids, true );

			if ( ! is_wp_error( $result ) && false !== $result ) {
				$updated_count++;
			}
		}

		if ( 0 === $updated_count ) {
			return array(
				'success' => false,
				'message' => __( 'No se pudo actualizar ningún post.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		return array(
			'success' => true,
			/* translators: %d: number of updated posts. */
			'message' => sprintf( __( 'Categorías añadidas correctamente a %d post(s).', 'gestion-masiva-categorias' ), $updated_count ),
			'updated' => $updated_count,
		);
	}
}