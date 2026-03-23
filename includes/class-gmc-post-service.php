<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de posts.
 *
 * Responsabilidad única:
 * - Obtener posts estándar con datos simples.
 * - No renderiza HTML.
 * - No ejecuta cambios de negocio.
 */
class GMC_Post_Service {

	/**
	 * Obtiene un listado simple de posts con sus categorías actuales.
	 *
	 * @param int $limit Límite máximo de resultados.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_posts( $limit = 20 ) {
		$limit = absint( $limit );

		if ( $limit <= 0 ) {
			$limit = 20;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
			)
		);

		if ( empty( $query->posts ) ) {
			return array();
		}

		$items = array();

		foreach ( $query->posts as $post ) {
			$categories = get_the_category( $post->ID );
			$normalized_categories = array();

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				foreach ( $categories as $category ) {
					$normalized_categories[] = array(
						'id'   => (int) $category->term_id,
						'name' => $category->name,
					);
				}
			}

			$items[] = array(
				'id'         => (int) $post->ID,
				'title'      => get_the_title( $post->ID ),
				'status'     => get_post_status( $post->ID ),
				'categories' => $normalized_categories,
			);
		}

		wp_reset_postdata();

		return $items;
	}
}