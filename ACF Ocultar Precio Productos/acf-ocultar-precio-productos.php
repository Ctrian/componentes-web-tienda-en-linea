<?php
/**
 * DIFASEL - Productos WooCommerce disponibles solo mediante cotizacion.
 *
 * Este fragmento esta pensado para copiarse en el functions.php de un tema
 * hijo o instalarse mediante un administrador de snippets.
 *
 * Campos ACF requeridos en el producto:
 * - solo_cotizacion    (Verdadero / Falso)
 * - mensaje_whatsapp   (Area de texto, opcional)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Indica si un producto o su producto padre esta marcado como solo cotizacion.
 *
 * Se usa get_post_meta() en lugar de get_field() para que los productos que ya
 * fueron configurados sigan protegidos aunque ACF se desactive temporalmente.
 *
 * @param WC_Product|int $product Producto o ID de producto.
 * @return bool
 */
function difasel_es_solo_cotizacion( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$product_id = $product->is_type( 'variation' )
		? $product->get_parent_id()
		: $product->get_id();

	return '1' === (string) get_post_meta( $product_id, 'solo_cotizacion', true );
}

/**
 * Oculta el precio visual en fichas, tienda, categorias y widgets.
 */
add_filter(
	'woocommerce_get_price_html',
	function ( $price, $product ) {
		if ( difasel_es_solo_cotizacion( $product ) ) {
			return '';
		}

		return $price;
	},
	9999,
	2
);

/**
 * Marca el producto y sus variaciones como no comprables.
 */
add_filter(
	'woocommerce_is_purchasable',
	function ( $purchasable, $product ) {
		return difasel_es_solo_cotizacion( $product ) ? false : $purchasable;
	},
	9999,
	2
);

add_filter(
	'woocommerce_variation_is_purchasable',
	function ( $purchasable, $variation ) {
		return difasel_es_solo_cotizacion( $variation ) ? false : $purchasable;
	},
	9999,
	2
);

/**
 * Bloquea tambien solicitudes directas para agregar el producto al carrito.
 */
add_filter(
	'woocommerce_add_to_cart_validation',
	function ( $passed, $product_id, $quantity, $variation_id = 0 ) {
		$target_id = $variation_id ? $variation_id : $product_id;
		$product   = wc_get_product( $target_id );

		if ( difasel_es_solo_cotizacion( $product ) ) {
			wc_add_notice(
				__( 'Este producto esta disponible unicamente mediante cotizacion.', 'difasel' ),
				'error'
			);

			return false;
		}

		return $passed;
	},
	9999,
	4
);

/**
 * En productos variables quita solamente cantidad y "Agregar al carrito".
 * El formulario de variaciones permanece, por lo que Variation Swatches sigue
 * mostrando las opciones de amperaje, color, tamano, etc.
 */
add_action(
	'wp',
	function () {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( difasel_es_solo_cotizacion( $product ) ) {
			remove_action(
				'woocommerce_single_variation',
				'woocommerce_single_variation_add_to_cart_button',
				20
			);
		}
	}
);

/**
 * Elimina el boton de carrito en tienda, categorias y otros listados clasicos.
 */
add_filter(
	'woocommerce_loop_add_to_cart_link',
	function ( $html, $product ) {
		return difasel_es_solo_cotizacion( $product ) ? '' : $html;
	},
	9999,
	2
);

/**
 * Neutraliza los precios incluidos en el HTML/JSON de productos variables.
 */
add_filter(
	'woocommerce_available_variation',
	function ( $data, $product, $variation ) {
		if ( difasel_es_solo_cotizacion( $product ) ) {
			$data['display_price']         = 0;
			$data['display_regular_price'] = 0;
			$data['price_html']            = '';
			$data['is_purchasable']        = false;
		}

		return $data;
	},
	9999,
	3
);

/**
 * Retira la oferta/precio de los datos estructurados generados por WooCommerce.
 */
add_filter(
	'woocommerce_structured_data_product',
	function ( $markup, $product ) {
		if ( difasel_es_solo_cotizacion( $product ) ) {
			unset( $markup['offers'] );
		}

		return $markup;
	},
	9999,
	2
);

/**
 * Muestra el boton de WhatsApp debajo del selector de variaciones.
 */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;

		if ( ! $product instanceof WC_Product || ! difasel_es_solo_cotizacion( $product ) ) {
			return;
		}

		/* IMPORTANTE: reemplazar por el numero real, sin +, espacios ni guiones. */
		$telefono = '593999999999';

		$mensaje_personalizado = trim(
			(string) get_post_meta( $product->get_id(), 'mensaje_whatsapp', true )
		);

		$mensaje = $mensaje_personalizado;

		if ( '' === $mensaje ) {
			$mensaje = "Hola, deseo cotizar el siguiente producto:\n{producto}\n{variaciones}\n{url}";
		}

		$mensaje_respaldo = str_replace(
			array( '{producto}', '{variaciones}', '{url}' ),
			array( $product->get_name(), '', get_permalink( $product->get_id() ) ),
			$mensaje
		);

		$url = 'https://wa.me/' . $telefono . '?text=' . rawurlencode( trim( $mensaje_respaldo ) );

		printf(
			'<p class="difasel-cotizacion"><a class="button alt" href="%1$s" target="_blank" rel="noopener noreferrer" data-difasel-whatsapp data-telefono="%2$s" data-mensaje="%3$s" data-producto="%4$s" data-url="%5$s">%6$s</a><span class="difasel-cotizacion-error" role="alert" aria-live="polite" hidden></span></p>',
			esc_url( $url ),
			esc_attr( $telefono ),
			esc_attr( $mensaje ),
			esc_attr( $product->get_name() ),
			esc_url( get_permalink( $product->get_id() ) ),
			esc_html__( 'Cotizar por WhatsApp', 'difasel' )
		);
	},
	31
);

/**
 * Completa el mensaje de WhatsApp con las opciones elegidas en el formulario.
 *
 * Variation Swatches mantiene sincronizados sus botones con los selectores
 * nativos de WooCommerce. Tambien se consulta el swatch seleccionado como
 * respaldo para reflejar exactamente la opcion visible para el cliente.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! difasel_es_solo_cotizacion( $product ) ) {
			return;
		}
		?>
		<script>
		(function () {
			'use strict';

			var button = document.querySelector('[data-difasel-whatsapp]');
			var form = document.querySelector('form.variations_form');

			if (!button) {
				return;
			}

			function replaceAllText(text, search, replacement) {
				return text.split(search).join(replacement);
			}

			function getAttributeLabel(select) {
				var row = select.closest('tr');
				var label = row ? row.querySelector('th.label label') : null;

				if (label) {
					var cleanLabel = label.cloneNode(true);
					var dynamicLabels = cleanLabel.querySelectorAll('.cfvsw-selected-label');

					dynamicLabels.forEach(function (item) {
						item.remove();
					});

					if (cleanLabel.textContent.trim()) {
						return cleanLabel.textContent.trim();
					}
				}

				return select.name
					.replace(/^attribute_/, '')
					.replace(/^pa_/, '')
					.replace(/_/g, ' ');
			}

			function getSelectedValue(select) {
				var attributeName = select.getAttribute('data-attribute_name') || select.name;
				var containers = form ? form.querySelectorAll('.cfvsw-swatches-container') : [];
				var selectedSwatch = null;

				containers.forEach(function (container) {
					if (container.getAttribute('swatches-attr') === attributeName) {
						selectedSwatch = container.querySelector('.cfvsw-selected-swatch');
					}
				});

				if (selectedSwatch) {
					return (
						selectedSwatch.getAttribute('data-title') ||
						selectedSwatch.textContent
					).trim();
				}

				if (select.value && select.selectedIndex >= 0) {
					return select.options[select.selectedIndex].text.trim();
				}

				return '';
			}

			button.addEventListener('click', function (event) {
				var selectedOptions = [];
				var missingOptions = [];
				var error = button.parentElement.querySelector('.difasel-cotizacion-error');

				if (form) {
					form.querySelectorAll('select[name^="attribute_"]').forEach(function (select) {
						var label = getAttributeLabel(select);
						var value = getSelectedValue(select);

						if (!value) {
							missingOptions.push(label);
							return;
						}

						selectedOptions.push(label + ': ' + value);
					});
				}

				if (missingOptions.length) {
					event.preventDefault();

					if (error) {
						error.textContent = 'Selecciona ' + missingOptions.join(', ') + ' antes de solicitar la cotizacion.';
						error.hidden = false;
					}

					return;
				}

				if (error) {
					error.hidden = true;
					error.textContent = '';
				}

				var variationText = selectedOptions.join('\n');
				var message = button.getAttribute('data-mensaje') || '';
				var hadVariationPlaceholder = message.indexOf('{variaciones}') !== -1;

				message = replaceAllText(message, '{producto}', button.getAttribute('data-producto') || '');
				message = replaceAllText(message, '{url}', button.getAttribute('data-url') || window.location.href);
				message = replaceAllText(message, '{variaciones}', variationText);

				if (!hadVariationPlaceholder && variationText) {
					message += '\n\nOpciones seleccionadas:\n' + variationText;
				}

				button.href = 'https://wa.me/' +
					button.getAttribute('data-telefono') +
					'?text=' + encodeURIComponent(message.trim());
			});
		}());
		</script>
		<style>
			.difasel-cotizacion-error[hidden] {
				display: none;
			}

			.difasel-cotizacion-error {
				display: block;
				margin-top: 0.75rem;
				color: #b42318;
				font-size: 0.9rem;
			}
		</style>
		<?php
	},
	99
);

/**
 * Herramientas administrativas separadas del comportamiento publico.
 */
require_once __DIR__ . '/acf-acciones-masivas.php';
