export function productLocation(product) {
  const slug =
    String(product.nombre || product.name || 'producto')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'producto'
  return { name: 'producto', params: { id: String(product.id_producto ?? product.id), slug } }
}
