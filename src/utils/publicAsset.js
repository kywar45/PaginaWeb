export function publicAsset(path) {
  const baseUrl = import.meta.env.BASE_URL || '/'
  const normalizedBaseUrl = baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`
  const normalizedPath = String(path).replace(/^\/+/, '')

  return `${normalizedBaseUrl}${normalizedPath}`
}
