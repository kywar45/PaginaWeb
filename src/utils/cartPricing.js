export function codeKey(product) {
  const code = String(product.codigo || '').trim().toUpperCase()
  return code ? `code:${code}` : `product:${product.id_producto}`
}

// Vista previa. La API vuelve a calcular y guardar el precio definitivo.
export function previewCartPrice(product, quantity, items) {
  const previous = items.reduce((sum, item) =>
    sum + (item.puede_comprar && codeKey(item) === codeKey(product) ? item.quantity : 0), 0)
  const pieces = previous + (Number.isSafeInteger(quantity) && quantity > 0 ? quantity : 0)
  const result = { price: product.precio_final, type: 'unidad', pieces, previous }
  if (product.es_vip) result.type = 'vip'
  else if (product.tiene_descuento) result.type = 'oferta'
  else if (product.minimo_caja > 0 && pieces >= product.minimo_caja && product.precio_caja > 0) {
    result.price = product.precio_caja
    result.type = 'caja'
  } else if (product.minimo_mayoreo > 0 && pieces >= product.minimo_mayoreo && product.precio_mayoreo > 0) {
    result.price = product.precio_mayoreo
    result.type = 'mayoreo'
  }
  return result
}
