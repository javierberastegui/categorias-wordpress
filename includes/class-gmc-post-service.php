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
	 * Obtiene un listado paginado de posts con sus categorías actuales.
	 *
	 * @param int $page        Página actual.
	 * @param int $per_page    Límite fijo por página.
	 * @param int $category_id ID de categoría para filtrar opcionalmente.
	 * @return array<string, mixed>
	 */
	public function get_posts( $page = 1, $per_page = 20, $category_id = 0 ) {
		$page        = absint( $page );
		$per_page    = absint( $per_page );
		$category_id = absint( $category_id );

		if ( $page <= 0 ) {
			$page = 1;
		}

		if ( $per_page <= 0 ) {
			$per_page = 20;
		}

		$args = array(
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
		);

		if ( $category_id > 0 ) {
			$term = get_term( $category_id, 'category' );

			if ( $term && ! is_wp_error( $term ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => array( $category_id ),
					),
				);
			}
		}

		$query = new WP_Query( $args );

		$items = array();

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post ) {
				$categories            = get_the_category( $post->ID );
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
		}

		wp_reset_postdata();

		return array(
			'items'      => $items,
			'pagination' => array(
				'current_page' => (int) $page,
				'per_page'     => (int) $per_page,
				'total_items'  => (int) $query->found_posts,
				'total_pages'  => (int) $query->max_num_pages,
				'has_previous' => $page > 1,
				'has_next'     => $page < (int) $query->max_num_pages,
			),
		);
	}
}