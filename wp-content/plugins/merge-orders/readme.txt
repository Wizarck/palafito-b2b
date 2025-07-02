=== WooCommerce Merge Orders ===
Tags: orders, merging, admin
Requires at least: 5.5
Tested up to: 5.9
Requires PHP: 7.0
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html

Lógica de merge de pedidos (actualizada):
- Para cada pedido (incluyendo el target y los pedidos a mergear):
  - Si el pedido tiene nota de factura (_wcpdf_invoice_notes):
    - Solo se toma en cuenta la nota de factura para el merge (se ignora la nota de cliente).
  - Si el pedido NO tiene nota de factura pero sí nota de cliente:
    - Se toma en cuenta la nota de cliente para el merge.
  - Si no tiene ninguna de las dos:
    - No participa en el merge de notas, pero sí en el de productos.
- Se procesan todas las notas seleccionadas, extrayendo bloques de Feria, Obrador y CXXXXX sueltos.
- Se mantienen los duplicados (si un CXXXXX aparece varias veces, se muestra todas las veces).
- Todos los bloques se ordenan ascendentemente por CXXXXX.
- El resultado se concatena en la nueva nota de factura del pedido final.
- Si durante el merge se detectan CXXXXX duplicados (en cualquier bloque), se añade una nota interna al pedido (visible solo para admin) informando de este detalle:
  Atención: Se detectaron CXXXXX duplicados en la nota de factura durante el merge: C00262, C00270
- Si hay al menos un pedido con envío de pago:
  - Se mantienen todas las líneas de envío de pago (cada una con su coste y método).
  - Se eliminan todas las líneas de "envío gratuito".
- Si todos los pedidos tienen solo "envío gratuito":
  - Se deja solo una línea de "envío gratuito".
- Nota interna de merge:
  - Añadir una nota interna (visible solo para administradores) al pedido final indicando el número de pedido resultante y los números de los pedidos que se han mergeado:
    Merge realizado en el pedido C00300 de los pedidos: C00262, C00264, C00270
- Rollback:
  - Si ocurre un error durante el merge, el sistema debe dejar todo igual (no modificar pedidos) y añadir una nota interna visible solo para administradores indicando el fallo:
    Error al intentar mergear pedidos: [detalle del error]