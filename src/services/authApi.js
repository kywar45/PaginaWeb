const DEFAULT_API_BASE_URL = 'https://www.importadorasupernova.com/StoreApi/v1'

function apiBaseUrl() {
  return (import.meta.env.VITE_STORE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, '')
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

export async function logout() {
  return request('/auth/logout', { method: 'POST' }, 'No pudimos cerrar sesion.')
}

export default {
  login,
  me,
  logout,
}
