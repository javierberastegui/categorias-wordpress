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
		$post_ids     = $this->normalize_post_ids( $post_ids );
		$category_ids = $this->normalize_valid_category_ids( $category_ids );

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
				'message' => __( 'Debes seleccionar al menos una categoría válida.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		$updated_count = 0;

		foreach ( $post_ids as $post_id ) {
			if ( 'post' !== get_post_type( $post_id ) ) {
				continue;
			}

			$result = wp_set_post_categories( $post_id, $category_ids, true );

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

	/**
	 * Quita categorías concretas de posts sin tocar las demás.
	 *
	 * @param array<int, mixed> $post_ids     IDs de posts.
	 * @param array<int, mixed> $category_ids IDs de categorías a quitar.
	 * @return array<string, mixed>
	 */
	public function remove_categories_from_posts( array $post_ids, array $category_ids ) {
		$post_ids     = $this->normalize_post_ids( $post_ids );
		$category_ids = $this->normalize_valid_category_ids( $category_ids );

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
				'message' => __( 'Debes seleccionar al menos una categoría válida.', 'gestion-masiva-categorias' ),
				'updated' => 0,
			);
		}

		$updated_count = 0;

		foreach ( $post_ids as $post_id ) {
			if ( 'post' !== get_post_type( $post_id ) ) {
				continue;
			}

			$current_category_ids = wp_get_post_categories( $post_id );

			if ( is_wp_error( $current_category_ids ) ) {
				continue;
			}

			$current_category_ids = array_values(
				array_unique(
					array_filter(
						array_map( 'absint', $current_category_ids )
					)
				)
			);

			$new_category_ids = array_values( array_diff( $current_category_ids, $category_ids ) );

			$result = wp_set_post_categories( $post_id, $new_category_ids, false );

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
			'message' => sprintf( __( 'Categorías quitadas correctamente de %d post(s).', 'gestion-masiva-categorias' ), $updated_count ),
			'updated' => $updated_count,
		);
	}

	/**
	 * Normaliza IDs de posts.
	 *
	 * @param array<int, mixed> $post_ids IDs brutos.
	 * @return array<int, int>
	 */
	private function normalize_post_ids( array $post_ids ) {
		return array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $post_ids )
				)
			)
		);
	}

	/**
	 * Normaliza y valida IDs de categorías.
	 *
	 * @param array<int, mixed> $category_ids IDs brutos.
	 * @return array<int, int>
	 */
	private function normalize_valid_category_ids( array $category_ids ) {
		$category_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $category_ids )
				)
			)
		);

		$valid_category_ids = array();

		foreach ( $category_ids as $category_id ) {
			$term = get_term( $category_id, 'category' );

			if ( $term && ! is_wp_error( $term ) ) {
				$valid_category_ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( $valid_category_ids ) );
	}
}