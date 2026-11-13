const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

export async function getBranches({ signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/sucursales`, { headers: { Accept: 'application/json' }, signal })
  const payload = await response.json()
  if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.sucursales)) {
    throw new Error('No se pudieron cargar las sucursales.')
  }
  return payload.data.sucursales
}
