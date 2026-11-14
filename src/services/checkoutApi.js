const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

export async function registerGuestCustomer(customer, { signal } = {}) {
  const base = (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
  const response = await fetch(`${base}/checkout/invitado`, {
    method: 'POST',
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({
      nombre: customer.nombre,
      apellido: customer.apellido,
      correo: customer.correo,
      estado: customer.estado,
      ciudad: customer.ciudad,
      colonia: customer.colonia,
      direccion: customer.direccion,
      codigo_postal: customer.cp,
      telefono: customer.telefono,
    }),
    signal,
  })
  const payload = await response.json()
  const id = payload?.data?.cliente_invitado_id
  if (!response.ok || payload?.success !== true || !Number.isSafeInteger(id) || id < 1) {
    throw new Error(payload?.error?.message || 'No pudimos guardar tus datos. Intenta de nuevo.')
  }
  return { cliente_invitado_id: id }
}
