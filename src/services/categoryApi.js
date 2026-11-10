import { publicAsset } from 'src/utils/publicAsset'

const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'
function slugify(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function imageUrl(filename) {
  const value = String(filename || '').trim()
  if (!value) return null
  if (/^https?:\/\//i.test(value)) return value
  if (/^iconos\//i.test(value)) return publicAsset(value)

  return publicAsset(`iconos/${value}`)
}

export function normalizeCategory(row) {
  const id = Number(row.id_categoria)
  const slug = slugify(`${row.nombre_categoria}-${id}`)
  const localIcon = imageUrl(row.icon)
  const tileImage = localIcon || imageUrl(row.icon_url)

  return {
    id,
    id_categoria: id,
    id_depa: Number(row.id_depa),
    slug,
    name: row.nombre_categoria,
    label: row.nombre_categoria,
    icon: row.icon,
    iconUrl: localIcon || imageUrl(row.icon_url),
    svgIcon: tileImage || publicAsset('iconos/otros.webp'),
    emoji: row.emoji,
    position: row.position === null ? null : Number(row.position),
    subcategories: (row.subcategorias || []).map((sub) => {
      const subId = Number(sub.id_subcategoria)
      return {
        id: subId,
        id_subcategoria: subId,
        id_categoria: id,
        slug: slugify(`${sub.nombre_subcategoria}-${subId}`),
        name: String(sub.nombre_subcategoria || '').trim(),
        label: String(sub.nombre_subcategoria || '').trim(),
        position: sub.position === null ? null : Number(sub.position),
      }
    }),
  }
}

export async function getCategories({ signal, activeOnly = true } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const params = new URLSearchParams()
  if (activeOnly) {
    params.set('estatus_categoria', '1')
    params.set('estatus_subcategoria', '1')
  }

  const query = params.toString()
  const response = await fetch(`${base}/categorias${query ? `?${query}` : ''}`, {
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()

  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.categorias)) {
    throw new Error(payload?.error?.message || 'No se pudieron cargar las categorias.')
  }

  return payload.data.categorias.map(normalizeCategory)
}
