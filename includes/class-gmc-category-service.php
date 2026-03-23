<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de categorías.
 *
 * Responsabilidad única:
 * - Obtener categorías desde WordPress.
 * - No renderiza HTML.
 * - No ejecuta acciones de negocio.
 */
class GMC_Category_Service {

	/**
	 * Obtiene un listado simple de categorías.
	 *
	 * @param int $limit Límite máximo de resultados.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_categories( $limit = 50 ) {
		$limit = absint( $limit );

		if ( $limit <= 0 ) {
			$limit = 50;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'number'     => $limit,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$items = array();

		foreach ( $terms as $term ) {
			$items[] = array(
				'id'     => (int) $term->term_id,
				'name'   => $term->name,
				'parent' => (int) $term->parent,
			);
		}

		return $items;
	}
}
