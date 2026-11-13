const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

export async function getBrands({ signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/marcas`, { headers: { Accept: 'application/json' }, signal })
  const payload = await response.json()
  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.marcas)) {
    throw new Error('No se pudieron cargar las marcas.')
  }
  return payload.data.marcas.map((brand) => ({
    id: Number(brand.id),
    nombre: String(brand.nombre || '').trim(),
    logo: typeof brand.logo === 'string' && /^https?:\/\//i.test(brand.logo.trim()) ? brand.logo.trim() : null,
    total_productos: Number(brand.total_productos) || 0,
  }))
}
