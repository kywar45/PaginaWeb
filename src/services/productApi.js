import { publicAsset } from 'src/utils/publicAsset'

const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'
const FALLBACK_IMAGE = publicAsset('img/product-no-image.svg')

function normalizeProduct(row) {
  const price = Number(row.precio_normal) || 0
  const finalPrice =
    row.precio_final !== null &&
    row.precio_final !== undefined &&
    Number.isFinite(Number(row.precio_final))
      ? Number(row.precio_final)
      : price

  return {
    ...row,
    id_producto: row.id_producto ?? row.id,
    nombre: String(row.nombre || '').trim(),
    codigo: String(row.codigo || '').trim(),
    imagen:
      typeof row.imagen === 'string' && row.imagen.trim() ? row.imagen.trim() : FALLBACK_IMAGE,
    precio_normal: price,
    precio_final: finalPrice,
    precio_mayoreo: Number(row.precio_mayoreo) || 0,
    precio_caja: Number(row.precio_caja) || 0,
    tiene_descuento: Boolean(row.tiene_descuento),
    porcentaje_descuento: Number(row.porcentaje_descuento) || 0,
    ahorro: Number(row.ahorro) || 0,
    visitas: Number(row.visitas) || 0,
    es_vip: row.es_vip === true,
    puede_comprar: row.puede_comprar === true || Number(row.estatus) === 1,
    estatus: row.estatus === undefined || row.estatus === null ? null : Number(row.estatus),
  }
}

export async function getProducts({
  coleccion = 'recientes',
  page = 1,
  limit = 20,
  id_subcategoria,
  id_categoria,
  id_marca,
  solo_disponibles = false,
  q,
  orden,
  solo_ofertas = false,
  signal,
} = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const params = new URLSearchParams({
    coleccion,
    page: String(page),
    limit: String(limit),
  })
  if (id_subcategoria !== undefined && id_subcategoria !== null)
    params.set('id_subcategoria', String(id_subcategoria))

  if (id_categoria !== undefined && id_categoria !== null)
    params.set('id_categoria', String(id_categoria))

  if (solo_disponibles) params.set('solo_disponibles', '1')
  if (id_marca !== undefined && id_marca !== null) params.set('id_marca', String(id_marca))
  if (q !== undefined) params.set('q', q)
  if (orden !== undefined) params.set('orden', orden)
  if (solo_ofertas) params.set('solo_ofertas', '1')

  const response = await fetch(`${base}/productos?${params}`, {
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()

  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.productos)) {
    throw new Error(payload?.error?.message || 'No se pudieron cargar los productos.')
  }
  if (q !== undefined && payload.data.search_query !== q) {
    throw new Error('El buscador todavía no está habilitado en el servidor.')
  }
  if (
    id_marca !== undefined &&
    id_marca !== null &&
    Number(payload.data.marca_id) !== Number(id_marca)
  ) {
    throw new Error('Actualiza la API para habilitar los productos por marca.')
  }
  if (
    q !== undefined &&
    orden !== undefined &&
    (payload.data.search_filters?.orden !== orden ||
      payload.data.search_filters?.solo_disponibles !== Boolean(solo_disponibles) ||
      payload.data.search_filters?.solo_ofertas !== Boolean(solo_ofertas))
  )
    throw new Error('Actualiza la API del buscador para habilitar los filtros.')

  return {
    productos: payload.data.productos.map(normalizeProduct),
    pagination: payload.data.pagination,
  }
}

export async function getProduct(id, { signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(
    `${base}/productos?${new URLSearchParams({ id_producto: String(id) })}`,
    {
      credentials: 'include',
      cache: 'no-store',
      headers: { Accept: 'application/json' },
      signal,
    },
  )
  const payload = await response.json()
  if (!response.ok || payload?.success !== true || !payload.data?.producto) {
    const error = new Error(payload?.error?.message || 'No se pudo cargar el producto.')
    error.status = response.status
    throw error
  }
  const product = normalizeProduct(payload.data.producto)
  product.variantes = Array.isArray(product.variantes)
    ? product.variantes.map(normalizeProduct)
    : []
  product.relacionados = Array.isArray(product.relacionados)
    ? product.relacionados.map(normalizeProduct)
    : []
  product.imagenes =
    Array.isArray(product.imagenes) && product.imagenes.length ? product.imagenes : [product.imagen]
  return product
}

export async function getRandomSubcategoryProducts({
  subcategoryLimit = 3,
  productLimit = 6,
  signal,
} = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const params = new URLSearchParams({
    subcategory_limit: String(subcategoryLimit),
    product_limit: String(productLimit),
  })

  const response = await fetch(`${base}/productos/subcategorias-aleatorias?${params}`, {
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()

  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.subcategorias)) {
    throw new Error(
      payload?.error?.message || 'No se pudieron cargar las subcategorias aleatorias.',
    )
  }

  return payload.data.subcategorias
    .map((subcategory) => ({
      id_categoria: Number(subcategory.id_categoria),
      nombre_categoria: String(subcategory.nombre_categoria || '').trim(),
      id_subcategoria: Number(subcategory.id_subcategoria),
      nombre_subcategoria: String(subcategory.nombre_subcategoria || '').trim(),
      productos: Array.isArray(subcategory.productos)
        ? subcategory.productos.map(normalizeProduct)
        : [],
    }))
    .filter((subcategory) => subcategory.productos.length)
}
