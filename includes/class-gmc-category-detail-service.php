<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de detalle de categoría.
 *
 * Responsabilidad única:
 * - Obtener datos detallados de una categoría concreta.
 * - No renderiza HTML.
 * - No modifica datos.
 */
class GMC_Category_Detail_Service {

	/**
	 * Obtiene el detalle de una categoría concreta.
	 *
	 * @param int $category_id ID de la categoría.
	 * @return array<string, mixed>|null
	 */
	public function get_category_detail( $category_id ) {
		$category_id = absint( $category_id );

		if ( $category_id <= 0 ) {
			return null;
		}

		$term = get_term( $category_id, 'category' );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$parent_name = '';
		$parent_id   = (int) $term->parent;

		if ( $parent_id > 0 ) {
			$parent_term = get_term( $parent_id, 'category' );

			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent_name = $parent_term->name;
			}
		}

		return array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent_id'   => $parent_id,
			'parent_name' => $parent_name,
		);
	}
}