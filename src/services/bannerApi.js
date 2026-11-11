const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

export async function getBanners({ tipo, maxItems = Infinity, signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const banners = []
  let page = 1

  while (page !== null) {
    const limit = Math.min(100, maxItems - banners.length)
    if (limit <= 0) break
    const params = new URLSearchParams({ page: String(page), limit: String(limit) })
    if (tipo !== undefined && tipo !== null) params.set('tipo', String(tipo))

    const response = await fetch(`${base}/banners?${params}`, {
      headers: { Accept: 'application/json' },
      signal,
    })
    const payload = await response.json()
    if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.banners)) {
      throw new Error(payload?.error?.message || 'No se pudieron cargar los banners.')
    }
    banners.push(...payload.data.banners.slice(0, limit))
    if (banners.length >= maxItems) break
    const pagination = payload.data.pagination
    if (!pagination?.has_more) break
    if (!Number.isInteger(pagination.next_page) || pagination.next_page <= page) {
      throw new Error('La paginación de banners no es válida.')
    }
    page = pagination.next_page
  }

  return banners
}
