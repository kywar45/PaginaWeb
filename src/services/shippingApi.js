const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

export async function getDeliveryMethods({ signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/metodos-entrega`, {
    headers: { Accept: 'application/json' },
    signal,
  })
  const payload = await response.json()
  const methods = payload?.data?.metodos_entrega
  if (
    !response.ok ||
    payload?.success !== true ||
    !Array.isArray(methods) ||
    methods.some(
      (method) =>
        !Number.isSafeInteger(method.id) ||
        method.id <= 0 ||
        typeof method.nombre !== 'string' ||
        !method.nombre.trim() ||
        ![0, 1].includes(method.envio),
    )
  )
    throw new Error('No pudimos cargar los métodos de entrega.')
  return methods
}
