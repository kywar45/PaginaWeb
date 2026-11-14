import assert from 'node:assert/strict'
import { createMemoryHistory, createRouter } from 'vue-router'
import routes from '../../src/router/routes.js'
import { orderPdfLocation } from '../../src/utils/orderRoutes.js'
import { getOrderPdf } from '../../src/services/authApi.js'

const record = routes.find(route => route.name === 'pedido-pdf')
const router = createRouter({ history: createMemoryHistory('/ImportadoraSupernova/'), routes: [{ ...record, component: {} }] })
const token = 'p1.' + 'A'.repeat(54)
const target = orderPdfLocation(token)
assert.equal(router.resolve(target).href, `/ImportadoraSupernova/pedido/${token}/pdf`)
assert.ok(!router.resolve(target).href.includes('StoreApi'), 'Visible route does not use the API path')
await router.push(`/pedido/${token}/pdf`)
assert.equal(router.currentRoute.value.params.token, token, 'Supports opaque direct links and refresh')
for (const invalid of [0, -1, 1.5, 'invalid', 91439, '91439', token + 'A']) assert.equal(orderPdfLocation(invalid), null)

const previousFetch = globalThis.fetch
let response
let calls = 0
globalThis.fetch = async (url, options) => {
  calls++
  assert.equal(new URL(url).searchParams.get('token'), token)
  assert.equal(new URL(url).searchParams.has('id'), false)
  assert.equal(options.credentials, 'include', 'Preserves server session')
  assert.equal(options.cache, 'no-store', 'PDF is not cached')
  return response
}
try {
  response = new Response('%PDF-1.4\nDocument', { headers: { 'Content-Type': 'application/pdf' } })
  const blob = await getOrderPdf(token)
  assert.equal(blob.type, 'application/pdf')
  assert.ok((await blob.text()).startsWith('%PDF-'))
  response = new Response(JSON.stringify({ success: false, error: { code: 'UNAUTHENTICATED', message: 'Sesion no valida.' } }), { status: 401, headers: { 'Content-Type': 'application/json' } })
  await assert.rejects(() => getOrderPdf(token), error => error.status === 401 && error.code === 'UNAUTHENTICATED')
  response = new Response('Not a PDF', { headers: { 'Content-Type': 'application/pdf' } })
  await assert.rejects(() => getOrderPdf(token), /PDF válido/)
  response = new Response('<html>Error</html>', { headers: { 'Content-Type': 'text/html' } })
  await assert.rejects(() => getOrderPdf(token), /No pudimos abrir/)
  const before = calls
  await assert.rejects(() => getOrderPdf(91439), /no es válido/)
  assert.equal(calls, before, 'Invalid ID rejected before request')
} finally { globalThis.fetch = previousFetch }
console.log('PDF viewer route tests passed: clean paths, publicPath, session, PDF validation and expired sessions.')
