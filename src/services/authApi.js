import { isOrderPdfToken } from '../utils/orderRoutes.js'

const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

function apiBaseUrl() {
  return (import.meta.env?.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
}

async function readJson(response) {
  try {
    return await response.json()
  } catch {
    return null
  }
}

function apiError(response, payload, fallbackMessage) {
  const message = payload?.error?.message || fallbackMessage

  const error = new Error(message)
  error.status = response.status
  error.code = payload?.error?.code || 'REQUEST_FAILED'

  return error
}

async function request(path, options, fallbackMessage) {
  const response = await fetch(`${apiBaseUrl()}${path}`, {
    credentials: 'include',
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options?.headers || {}),
    },
  })

  const payload = await readJson(response)

  if (!response.ok || payload?.success !== true) {
    throw apiError(response, payload, fallbackMessage)
  }

  return payload.data
}

export async function login({ correo, password, remember }) {
  return request(
    '/auth/login',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        correo,
        password,
        remember,
      }),
    },
    'No pudimos iniciar sesion. Intenta de nuevo.',
  )
}

export async function me() {
  return request('/auth/me', { method: 'GET' }, 'Sesion no valida.')
}

export async function accountSummary({ signal } = {}) {
  const data = await request(
    '/auth/resumen',
    { method: 'GET', cache: 'no-store', signal },
    'No pudimos consultar tus pedidos.',
  )
  if (!Number.isSafeInteger(data?.pedidos_pagados) || data.pedidos_pagados < 0) {
    throw new Error('No pudimos consultar tus pedidos.')
  }
  return data
}

export async function accountOrders({ page = 1, limit = 10, signal } = {}) {
  const query = new URLSearchParams({ page: String(page), limit: String(limit) })
  const data = await request(
    `/auth/pedidos?${query}`,
    { method: 'GET', cache: 'no-store', signal },
    'No pudimos consultar tus pedidos.',
  )
  if (
    !Array.isArray(data?.pedidos) ||
    !Number.isSafeInteger(data?.pagination?.total) ||
    !Number.isSafeInteger(data?.pagination?.total_pages) ||
    data.pedidos.some((order) => !isOrderPdfToken(order.pdf_token))
  ) {
    throw new Error('No pudimos consultar tus pedidos.')
  }
  return data
}

function orderPdfUrl(token) {
  if (!isOrderPdfToken(token)) return null
  return `${apiBaseUrl()}/auth/pedido-pdf?${new URLSearchParams({ token })}`
}

export async function getOrderPdf(token, { signal } = {}) {
  const url = orderPdfUrl(token)
  if (!url) throw new Error('El enlace del pedido no es válido. Ábrelo desde Mis pedidos.')
  const response = await fetch(url, {
    method: 'GET',
    credentials: 'include',
    cache: 'no-store',
    signal,
    headers: { Accept: 'application/pdf, application/json' },
  })
  if (
    !response.ok ||
    !/^application\/pdf(?:;|$)/i.test(response.headers.get('content-type') || '')
  ) {
    throw apiError(response, await readJson(response), 'No pudimos abrir el PDF de este pedido.')
  }
  const blob = await response.blob()
  if ((await blob.slice(0, 5).text()) !== '%PDF-')
    throw new Error('El servidor no devolvió un PDF válido. Intenta de nuevo.')
  return blob
}

export async function accountAddresses({ signal } = {}) {
  return addressResult(
    await request(
      '/auth/direcciones',
      { method: 'GET', cache: 'no-store', signal },
      'No pudimos consultar tus direcciones.',
    ),
  )
}

export async function changeAddress(data) {
  return addressResult(
    await request(
      '/auth/direcciones',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      },
      'No pudimos actualizar tus direcciones.',
    ),
  )
}

function addressResult(data) {
  if (
    !Array.isArray(data?.direcciones) ||
    data.direcciones.some(
      (address) =>
        !Number.isSafeInteger(address?.id) ||
        address.id < 1 ||
        typeof address.defecto !== 'boolean',
    )
  ) {
    throw new Error('No pudimos leer las direcciones. Intenta de nuevo.')
  }
  return data
}

export async function logout() {
  return request('/auth/logout', { method: 'POST' }, 'No pudimos cerrar sesion.')
}

export default {
  login,
  me,
  logout,
}
