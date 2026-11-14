export function isOrderPdfToken(token) {
  return typeof token === 'string' && /^p1\.[A-Za-z0-9_-]{54}$/.test(token)
}

export function orderPdfLocation(token) {
  return isOrderPdfToken(token) ? { name: 'pedido-pdf', params: { token } } : null
}
