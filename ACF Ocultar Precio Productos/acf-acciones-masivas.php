<?php
/**
 * DIFASEL - Acciones masivas para productos de solo cotizacion.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Agrega las opciones al selector "Acciones en lote" de Productos.
 */
add_filter(
	'bulk_actions-edit-product',
	function ( $actions ) {
		$actions['difasel_activar_cotizacion']    = __( 'Activar cotizacion por WhatsApp', 'difasel' );
		$actions['difasel_desactivar_cotizacion'] = __( 'Desactivar cotizacion por WhatsApp', 'difasel' );

		return $actions;
	}
);

/**
 * Actualiza el campo ACF solo_cotizacion en todos los productos elegidos.
 */
add_filter(
	'handle_bulk_actions-edit-product',
	function ( $redirect_url, $action, $product_ids ) {
		$activar    = 'difasel_activar_cotizacion' === $action;
		$desactivar = 'difasel_desactivar_cotizacion' === $action;

		if ( ! $activar && ! $desactivar ) {
			return $redirect_url;
		}

		$valor      = $activar ? '1' : '0';
		$actualizados = 0;

		foreach ( $product_ids as $product_id ) {
			$product_id = absint( $product_id );

			if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
				continue;
			}

			update_post_meta( $product_id, 'solo_cotizacion', $valor );
			$actualizados++;
		}

		return add_query_arg(
			array(
				'difasel_cotizacion_actualizada' => $actualizados,
				'difasel_cotizacion_estado'      => $activar ? 'activada' : 'desactivada',
			),
			$redirect_url
		);
	},
	10,
	3
);

/**
 * Confirma el resultado de la edicion masiva.
 */
add_action(
	'admin_notices',
	function () {
		if (
			! current_user_can( 'edit_products' ) ||
			! isset( $_GET['difasel_cotizacion_actualizada'] )
		) {
			return;
		}

		$cantidad = absint( wp_unslash( $_GET['difasel_cotizacion_actualizada'] ) );
		$estado   = isset( $_GET['difasel_cotizacion_estado'] )
			? sanitize_text_field( wp_unslash( $_GET['difasel_cotizacion_estado'] ) )
			: 'actualizada';

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: estado aplicado, 2: cantidad de productos. */
					__( 'Cotizacion por WhatsApp %1$s en %2$d productos.', 'difasel' ),
					$estado,
					$cantidad
				)
			)
		);
	}
);
