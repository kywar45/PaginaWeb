const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

function customerPayload(customer) {
  return {
    nombre: customer.nombre,
    apellido: customer.apellido,
    correo: customer.correo,
    estado: customer.estado,
    ciudad: customer.ciudad,
    colonia: customer.colonia,
    direccion: customer.direccion,
    codigo_postal: customer.cp,
    telefono: customer.telefono,
  }
}

export async function sendOrderEmailTest(customer, { signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/checkout/invitado/correo/prueba`, {
    method: 'POST',
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ nombre: customer.nombre, correo: customer.correo }),
    signal,
  })
  const payload = await response.json().catch(() => null)
  if (!response.ok || payload?.success !== true) {
    throw new Error(
      payload?.error?.message ||
        `No pudimos ejecutar la prueba de correo (HTTP ${response.status}).`,
    )
  }
  const result = payload.data
  if (
    typeof result?.accepted !== 'boolean' ||
    typeof result.code !== 'string' ||
    typeof result.message !== 'string'
  ) {
    throw new Error('La API devolvió un diagnóstico de correo inválido.')
  }
  return result
}

export async function registerGuestCustomer(customer, { signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/checkout/invitado`, {
    method: 'POST',
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(customerPayload(customer)),
    signal,
  })
  const payload = await response.json()
  const id = payload?.data?.cliente_invitado_id
  if (!response.ok || payload?.success !== true || !Number.isSafeInteger(id) || id < 1) {
    throw new Error(payload?.error?.message || 'No pudimos guardar tus datos. Intenta de nuevo.')
  }
  return { cliente_invitado_id: id }
}

export async function createGuestFolio(customer, delivery, { signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/checkout/invitado/folio`, {
    method: 'POST',
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({
      ...customerPayload(customer),
      tipo_entrega: delivery.envio,
      shipping_carrier_id: delivery.shipping_carrier_id,
      sucursal_id: delivery.sucursal_id,
    }),
    signal,
  })
  const payload = await response.json()
  const folio = payload?.data
  if (
    !response.ok ||
    payload?.success !== true ||
    !Number.isSafeInteger(folio?.folio_id) ||
    folio.folio_id < 1 ||
    !Number.isSafeInteger(folio?.cliente_invitado_id) ||
    folio.cliente_invitado_id < 1 ||
    folio.orden !== `INV-${folio.cliente_invitado_id}`
  ) {
    throw new Error(payload?.error?.message || 'No pudimos registrar el folio. Intenta de nuevo.')
  }
  if (!Number.isSafeInteger(folio.productos_registrados) || folio.productos_registrados < 1) {
    throw new Error('Actualiza la API para registrar los productos del pedido.')
  }
  if (typeof folio.pdf_token !== 'string' || !/^p1\.[A-Za-z0-9_-]{54}$/.test(folio.pdf_token)) {
    throw new Error('Actualiza la API para habilitar el comprobante del pedido.')
  }
  return folio
}

export async function getGuestOrderPdf(token, customerId, { signal } = {}) {
  if (!/^p1\.[A-Za-z0-9_-]{54}$/.test(token) || !/^[1-9][0-9]{0,9}$/.test(String(customerId)))
    throw new Error('El enlace del pedido no es válido.')
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(
    `${base}/checkout/invitado/pdf?${new URLSearchParams({ token, cliente: String(customerId) })}`,
    {
      credentials: 'include',
      cache: 'no-store',
      headers: { Accept: 'application/pdf, application/json' },
      signal,
    },
  )
  if (
    !response.ok ||
    !/^application\/pdf(?:;|$)/i.test(response.headers.get('content-type') || '')
  ) {
    const payload = await response.json().catch(() => null)
    const error = new Error(
      payload?.error?.message ||
        'El pedido está guardado, pero no pudimos abrir su PDF. Intenta de nuevo.',
    )
    error.status = response.status
    throw error
  }
  const blob = await response.blob()
  if ((await blob.slice(0, 5).text()) !== '%PDF-')
    throw new Error('No pudimos abrir el comprobante. Intenta de nuevo.')
  return blob
}
