# Ocultar precios en productos seleccionados con ACF y WooCommerce

Esta implementación permite convertir productos específicos de WooCommerce en productos de **solo cotización**. Los demás productos continúan mostrando su precio y funcionando normalmente.

En los productos marcados:

- se oculta el precio público;
- se elimina la cantidad y el botón **Añadir al carrito**;
- se bloquean intentos directos de añadir el producto al carrito;
- se mantienen los selectores de productos variables y los botones de **Variation Swatches for WooCommerce**;
- se muestra un botón **Cotizar por WhatsApp**;
- se elimina la oferta de los datos estructurados generados por WooCommerce.

## Archivos

- `acf-ocultar-precio-productos.php`: funcionalidad principal y carga de módulos.
- `acf-acciones-masivas.php`: acciones administrativas para varios productos.
- `README.md`: instrucciones de configuración, instalación y prueba.
- `CHANGELOG.md`: historial local de versiones.

Los dos archivos PHP deben mantenerse en la misma carpeta. El archivo principal
carga automáticamente `acf-acciones-masivas.php`; no se debe activar el segundo
archivo por separado.

## 1. Requisitos

- WordPress.
- WooCommerce.
- Advanced Custom Fields (ACF), versión gratuita o Pro.
- Un tema hijo o un administrador de fragmentos PHP.
- Variation Swatches for WooCommerce si se desean opciones visuales como 20A, 32A, 40A, colores o tamaños.

Antes de comenzar, realizar una copia de seguridad del sitio.

## 2. Crear el grupo de campos en ACF

Ir a **ACF > Grupos de campos > Añadir nuevo**.

Usar este título para el grupo:

```text
Configuración de cotización
```

### Campo 1: Solo cotización

Configurar exactamente:

| Propiedad | Valor |
|---|---|
| Tipo de campo | Verdadero / Falso |
| Etiqueta | Solo cotización |
| Nombre | `solo_cotizacion` |
| Valor predeterminado | Desactivado |

La interfaz estilizada y los textos Sí/No son opcionales; solo cambian la apariencia del interruptor en el administrador.

### Campo 2: Mensaje de WhatsApp

Configurar exactamente:

| Propiedad | Valor |
|---|---|
| Tipo de campo | Área de texto |
| Etiqueta | Mensaje de WhatsApp |
| Nombre | `mensaje_whatsapp` |
| Obligatorio | No |

Si este campo se deja vacío, el código crea automáticamente un mensaje con el nombre, las variaciones seleccionadas y la dirección del producto.

El mensaje personalizado admite estos marcadores:

- `{producto}`: nombre del producto;
- `{variaciones}`: opciones elegidas por el cliente, por ejemplo `Amperaje: 40A`;
- `{url}`: dirección de la ficha del producto.

Ejemplo recomendado:

```text
Hola, deseo cotizar el siguiente producto:
{producto}
{variaciones}
{url}
```

No escribir una opción fija como `36 kA` en este campo. Si no se incluye el marcador `{variaciones}`, el código añade las opciones seleccionadas al final del mensaje automáticamente.

### Regla de ubicación

Configurar:

```text
Tipo de contenido → es igual a → Producto
```

Guardar el grupo de campos.

## 3. Instalar el fragmento PHP

El archivo [`acf-ocultar-precio-productos.php`](./acf-ocultar-precio-productos.php) contiene el código completo.

### Opción A: functions.php de un tema hijo

1. Confirmar que se está editando un **tema hijo**. Una actualización del tema principal puede borrar los cambios.
2. Abrir `functions.php`.
3. Copiar el contenido del archivo PHP al final de `functions.php`.
4. No copiar la primera línea `<?php` si `functions.php` ya comienza con esa etiqueta.
5. No pegar el código después de un cierre `?>`.
6. Guardar el archivo.

### Opción B: administrador de snippets

1. Crear un nuevo fragmento PHP.
2. Copiar el código sin la primera línea `<?php` si el administrador no la requiere.
3. Configurarlo para ejecutarse en todo el sitio.
4. Guardar y activar el fragmento.

## 4. Configurar WhatsApp

Buscar esta línea en el fragmento:

```php
$telefono = '593999999999';
```

Reemplazarla por el número real con código de país, sin `+`, espacios, paréntesis ni guiones.

Ejemplo para Ecuador:

```php
$telefono = '593991234567';
```

## 5. Preparar los productos variables

WooCommerce necesita un precio no vacío para mantener activas y visibles las variaciones. Por ello:

1. asignar un precio ficticio, por ejemplo `1`, a todas las variaciones;
2. no guardar el precio comercial real en los campos públicos de precio de WooCommerce;
3. guardar los precios reales en una fuente privada, como el sistema administrativo, una hoja interna o un campo no expuesto públicamente.

Esto es importante porque ocultar el texto visible no garantiza que un precio real no sea publicado por otra extensión, un feed o una API.

## 6. Activar el modo de cotización en un producto

1. Ir a **Productos > Todos los productos**.
2. Editar el producto deseado.
3. Buscar la sección **Configuración de cotización**.
4. Activar **Solo cotización**.
5. Opcionalmente, escribir un mensaje personalizado en **Mensaje de WhatsApp**.
6. Actualizar el producto.

Los productos que no tengan activado **Solo cotización** conservarán su precio y funcionamiento normal.

## 7. Comprobaciones

Después de guardar:

1. vaciar la caché de WordPress, del alojamiento y de la CDN;
2. abrir el producto en una ventana privada o de incógnito;
3. comprobar que el precio no aparece;
4. comprobar que cantidad y **Añadir al carrito** no aparecen;
5. comprobar que los botones de variación siguen funcionando;
6. seleccionar distintas variaciones y verificar el cambio de imagen, si está configurado;
7. comprobar que aparece **Cotizar por WhatsApp** y que abre el número correcto;
8. intentar acceder a una dirección con `?add-to-cart=ID` y confirmar que WooCommerce rechaza la operación;
9. revisar el código fuente y buscar el precio comercial real.

## 8. Solución de problemas

### No aparecen los campos de ACF en el producto

- Revisar que el grupo esté activo.
- Confirmar la regla `Tipo de contenido → es igual a → Producto`.
- Confirmar que se guardó el grupo.

### El producto no entra en modo cotización

- Confirmar que el nombre del primer campo sea exactamente `solo_cotizacion`.
- Confirmar que el interruptor esté activado y que el producto se haya actualizado.
- Vaciar todas las cachés.
- Verificar que el fragmento PHP esté activo.

### No aparece el mensaje personalizado

- Confirmar que el nombre del campo sea exactamente `mensaje_whatsapp`.
- Actualizar el producto después de editar el mensaje.

### Desaparecen también los botones de variación

- No eliminar `woocommerce_template_single_add_to_cart` de manera global.
- No ocultar todo `form.variations_form` mediante CSS.
- Confirmar que cada variación tenga un precio ficticio no vacío y esté habilitada.

### Sigue apareciendo un precio en alguna parte

- Vaciar cachés.
- Revisar plantillas personalizadas del tema, bloques de WooCommerce, feeds y plugins SEO.
- Confirmar que no se esté guardando el precio real en los campos públicos de WooCommerce.
- Probar con el tema predeterminado en un entorno de pruebas para detectar incompatibilidades.

## 9. Desactivar el modo de cotización

Para un producto específico, desactivar **Solo cotización** y actualizarlo.

Para retirar toda la funcionalidad, desactivar el snippet o eliminar el código añadido a `functions.php`. Los valores de ACF permanecerán guardados, pero no tendrán efecto hasta volver a activar el código.

## 10. Edición masiva

1. Ir a **Productos > Todos los productos**.
2. Marcar los productos deseados.
3. En **Acciones en lote**, elegir **Activar cotización por WhatsApp** o **Desactivar cotización por WhatsApp**.
4. Pulsar **Aplicar**.

La acción modifica el campo `solo_cotizacion` de los productos principales. No
es necesario seleccionar individualmente sus variaciones.

## Nota de seguridad

ACF se usa como interfaz para marcar los productos, pero el fragmento lee los valores directamente desde los metadatos de WordPress. Esto permite que los productos ya marcados continúen en modo cotización aunque ACF se desactive temporalmente.

La medida más segura sigue siendo no almacenar precios confidenciales en campos públicos de WooCommerce. Utilizar precios ficticios en las variaciones y mantener los precios reales en una fuente privada.
