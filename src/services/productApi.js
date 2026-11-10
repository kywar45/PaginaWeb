import { publicAsset } from 'src/utils/publicAsset'

const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'
const FALLBACK_IMAGE = publicAsset('img/logob.png')

function normalizeProduct(row) {
  const price = Number(row.precio_normal) || 0
  const finalPrice = Number(row.precio_final) || price

  return {
    ...row,
    id_producto: row.id_producto ?? row.id,
    nombre: String(row.nombre || '').trim(),
    codigo: String(row.codigo || '').trim(),
    imagen: row.imagen || FALLBACK_IMAGE,
    precio_normal: price,
    precio_final: finalPrice,
    precio_mayoreo: Number(row.precio_mayoreo) || 0,
    precio_caja: Number(row.precio_caja) || 0,
    tiene_descuento: Boolean(row.tiene_descuento),
    porcentaje_descuento: Number(row.porcentaje_descuento) || 0,
    ahorro: Number(row.ahorro) || 0,
    visitas: Number(row.visitas) || 0,
  }
}

export async function getProducts({ coleccion = 'recientes', page = 1, limit = 20, signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const params = new URLSearchParams({
    coleccion,
    page: String(page),
    limit: String(limit),
  })

  const response = await fetch(`${base}/productos?${params}`, {
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()

  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.productos)) {
    throw new Error(payload?.error?.message || 'No se pudieron cargar los productos.')
  }

  return {
    productos: payload.data.productos.map(normalizeProduct),
    pagination: payload.data.pagination,
  }
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
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()

  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.subcategorias)) {
    throw new Error(payload?.error?.message || 'No se pudieron cargar las subcategorias aleatorias.')
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
