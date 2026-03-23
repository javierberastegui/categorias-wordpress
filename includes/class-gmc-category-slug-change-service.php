<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de cambios de slug de categoría.
 *
 * Responsabilidad única:
 * - Registrar la decisión del usuario cuando cambia un slug.
 * - No renderiza HTML.
 * - No ejecuta todavía redirecciones reales.
 */
class GMC_Category_Slug_Change_Service {

	/**
	 * Registra la decisión asociada a un cambio de slug.
	 *
	 * @param int    $category_id    ID de la categoría.
	 * @param string $old_slug       Slug anterior.
	 * @param string $new_slug       Slug nuevo.
	 * @param string $redirect_type  none|301|302
	 * @return array<string, mixed>
	 */
	public function register_slug_change_decision( $category_id, $old_slug, $new_slug, $redirect_type ) {
		$category_id   = absint( $category_id );
		$old_slug      = sanitize_title( $old_slug );
		$new_slug      = sanitize_title( $new_slug );
		$redirect_type = in_array( $redirect_type, array( 'none', '301', '302' ), true ) ? $redirect_type : 'none';

		if ( $category_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'La categoría indicada no es válida para registrar el cambio de slug.', 'gestion-masiva-categorias' ),
			);
		}

		if ( '' === $old_slug || '' === $new_slug || $old_slug === $new_slug ) {
			return array(
				'success' => false,
				'message' => __( 'No hay un cambio de slug válido que registrar.', 'gestion-masiva-categorias' ),
			);
		}

		$payload = array(
			'old_slug'      => $old_slug,
			'new_slug'      => $new_slug,
			'redirect_type' => $redirect_type,
			'recorded_at'   => current_time( 'mysql' ),
		);

		update_term_meta( $category_id, '_gmc_last_slug_change', $payload );

		return array(
			'success' => true,
			'message' => 'none' === $redirect_type
				? __( 'Cambio de slug registrado sin preparar redirección.', 'gestion-masiva-categorias' )
				: sprintf(
					/* translators: %s: redirect type. */
					__( 'Cambio de slug registrado con decisión de preparar redirección %s.', 'gestion-masiva-categorias' ),
					$redirect_type
				),
		);
	}
}