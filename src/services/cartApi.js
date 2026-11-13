import { publicAsset } from 'src/utils/publicAsset'

const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'
const fallback = publicAsset('img/product-no-image.svg')

async function request(body) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 20000)
  try {
    const response = await fetch(`${base}/carrito`, {
      method: body ? 'POST' : 'GET',
      credentials: 'include',
      cache: 'no-store',
      headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}) },
      ...(body ? { body: JSON.stringify(body) } : {}),
      signal: controller.signal,
    })
    const payload = await response.json()
    if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.items)) {
      const error = new Error(payload?.error?.message || 'No pudimos actualizar tu carrito.')
      error.status = response.status
      throw error
    }
    return {
      ...payload.data,
      items: payload.data.items.map((item) => ({ ...item, imagen: item.imagen || fallback })),
    }
  } catch (error) {
    if (error.name === 'AbortError')
      throw new Error('Se interrumpió la conexión. Revisa tu carrito antes de intentar otra vez.')
    throw error
  } finally {
    clearTimeout(timeout)
  }
}

export default {
  load: () => request(),
  update: (accion, id_producto, cantidad) => request({ accion, id_producto, cantidad }),
}
