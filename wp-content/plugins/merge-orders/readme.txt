=== WooCommerce Merge Orders ===
Tags: orders, merging, admin
Requires at least: 5.5
Tested up to: 5.9
Requires PHP: 7.0
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html

Lógica de merge de pedidos:
- Si hay al menos un pedido con envío de pago:
  - Se mantienen todas las líneas de envío de pago (cada una con su coste y método).
  - Se eliminan todas las líneas de "envío gratuito".
- Si todos los pedidos tienen solo "envío gratuito":
  - Se deja solo una línea de "envío gratuito".
- Notas de cliente → Notas de factura:
  - Analizar todas las notas de cliente de los pedidos originales.
  - Buscar referencias a "Feria" y "Obrador" (case-insensitive, tolerante a tildes, espacios extra y errores de tipeo comunes, pero no símbolos raros entre letras).
  - Agrupar los números de pedido (formato Cxxxxx) asociados a cada grupo y concatenar en líneas separadas:
    Feria: C00262, C00264, C00265
    Obrador: C00270, C00274, C00275
  - El texto "Feria" y "Obrador" debe salir siempre con mayúscula inicial.
  - Las notas que no contienen "Feria" ni "Obrador" se concatenan en una sola línea, separadas por coma.
  - El resultado se añade al campo _wcpdf_invoice_notes del pedido combinado.
  - No hay límite de longitud.
  - Si algún pedido no tiene nota de cliente, simplemente se ignora.
- Nota interna de merge:
  - Añadir una nota interna (visible solo para administradores) al pedido final indicando el número de pedido resultante y los números de los pedidos que se han mergeado:
    Merge realizado en el pedido C00300 de los pedidos: C00262, C00264, C00270
- Rollback:
  - Si ocurre un error durante el merge, el sistema debe dejar todo igual (no modificar pedidos) y añadir una nota interna visible solo para administradores indicando el fallo:
    Error al intentar mergear pedidos: [detalle del error]