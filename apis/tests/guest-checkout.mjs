import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'
import { emptyGuestDetails, normalizeGuestDetails } from '../../src/utils/guestCheckout.js'

let requested
const previousFetch = globalThis.fetch
const apiSource = readFileSync(new URL('../../src/services/checkoutApi.js', import.meta.url), 'utf8')
  .replaceAll('import.meta.env.VITE_STORE_API_BASE_URL', "'https://store.example/v1'")
const { registerGuestCustomer, createGuestFolio, getGuestOrderPdf } = await import(`data:text/javascript;base64,${Buffer.from(apiSource).toString('base64')}`)
globalThis.fetch = async (url, options) => {
  requested = { url, ...options }
  return { ok: true, json: async () => ({ success: true, data: { cliente_invitado_id: 42 } }) }
}
try {
  const customer = { ...emptyGuestDetails(), cp: '06050', id: 999, precio: 1 }
  assert.deepEqual(await registerGuestCustomer(customer), { cliente_invitado_id: 42 })
  assert.equal(requested.url, 'https://store.example/v1/checkout/invitado')
  assert.equal(requested.credentials, 'include')
  const body = JSON.parse(requested.body)
  assert.equal(body.codigo_postal, '06050')
  assert.equal(body.id, undefined)
  assert.equal(body.precio, undefined)
  assert.equal(Object.keys(body).length, 9)
  globalThis.fetch = async (url, options) => {
    requested = { url, ...options }
    return { ok: true, json: async () => ({ success: true, data: { cliente_invitado_id: 42, folio_id: 99, orden: 'INV-42', productos_registrados: 2, pdf_token: 'p1.' + 'A'.repeat(54) } }) }
  }
  await createGuestFolio(customer, { envio: 0, shipping_carrier_id: 21, sucursal_id: 25 })
  assert.equal(requested.url, 'https://store.example/v1/checkout/invitado/folio')
  const folioBody = JSON.parse(requested.body)
  assert.equal(folioBody.tipo_entrega, 0)
  assert.equal(folioBody.sucursal_id, 25)
  assert.equal(folioBody.total, undefined)
  assert.equal(folioBody.cantidad, undefined)
  assert.equal(folioBody.id_cliente_invitado, undefined)
  globalThis.fetch = async (url, options) => {
    requested = { url, ...options }
    return { ok: true, headers: new Headers({ 'content-type': 'application/pdf' }), blob: async () => new Blob(['%PDF-1.4\nreceipt']) }
  }
  const receipt = await getGuestOrderPdf('p1.' + 'A'.repeat(54), 42)
  assert.ok(requested.url.includes('/checkout/invitado/pdf?'))
  assert.equal(new URL(requested.url).searchParams.get('cliente'), '42')
  assert.equal(requested.credentials, 'include')
  assert.ok((await receipt.text()).startsWith('%PDF-'))
  await assert.rejects(getGuestOrderPdf('bad-token', 42), /no es válido/)
  globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: { message: 'Revisa tu correo.' } }) })
  await assert.rejects(registerGuestCustomer(customer), /Revisa tu correo/)
} finally {
  globalThis.fetch = previousFetch
}

const source = readFileSync(new URL('../../src/pages/CheckoutPage.vue', import.meta.url), 'utf8')
  .match(/<script setup>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '')
const ref = value => ({ value })
const cart = { items: ref([{ puede_comprar: true }]), total: ref(100), busy: ref(false) }
const user = ref(null)
let calls = 0
let fail = false
let removeHook
let routeLeave
let hold = null
const destinations = []
const popupWindows = []
const openedReceipts = []
let blockPopups = false
const context = vm.createContext({
  ref, watch: () => {}, onMounted: () => {}, onBeforeUnmount: callback => { removeHook = callback },
  nextTick: async () => {}, emptyGuestDetails, normalizeGuestDetails,
  useRouter: () => ({ resolve: destination => { destinations.push(destination); return { href: '/pedido/invitado/42/pdf' } }, push: () => { throw new Error('Checkout must not navigate away') } }),
  window: { open: (href, target) => {
    if (blockPopups) return null
    assert.equal(target, '_blank')
    openedReceipts.push(href)
    const popup = { closed: false, opener: 'original', document: { title: '', body: { textContent: '', style: {} } },
      location: { replace: href => openedReceipts.push(href) }, focus: () => {}, close() { this.closed = true } }
    popupWindows.push(popup)
    return popup
  } },
  onBeforeRouteLeave: callback => { routeLeave = callback },
  useCart: () => cart, useAuthSession: () => ({ currentUser: user }), publicAsset: value => value,
  AbortController, setTimeout, clearTimeout,
  createGuestFolio: async () => { calls++; if (fail) throw new Error('Guardado fallido'); if (hold) await hold; return { cliente_invitado_id: 42, folio_id: 99, orden: 'INV-42', productos_registrados: 2, pdf_token: 'p1.' + 'A'.repeat(54) } },
})
vm.runInContext(source + '\nglobalThis.api = { guestDetails, guestForm, deliveryForm, processOrder, processError, readyToProcess, validating, savingGuest, savedGuest, processingOpen, processingState, openReceipt };', context)
const api = context.api
api.guestForm.value = { validate: async () => false }
api.deliveryForm.value = { validate: () => true }
await api.processOrder()
assert.equal(calls, 0, 'Invalid customer must not reach server')
assert.equal(api.processingOpen.value, false, 'Validation errors keep the form available')
assert.equal(popupWindows.length, 0, 'Validation must not open a blank window')
api.guestForm.value.validate = async () => true
api.deliveryForm.value.validate = () => false
await api.processOrder()
assert.equal(calls, 0, 'Missing delivery must not reach server')
api.deliveryForm.value.validate = () => true
await Promise.all([api.processOrder(), api.processOrder()])
assert.equal(calls, 1, 'Concurrent clicks send one request')
assert.equal(api.readyToProcess.value, true)
assert.equal(api.savedGuest.value.cliente_invitado_id, 42)
assert.equal(api.processingOpen.value, true)
assert.equal(api.processingState.value, 'success')
assert.equal(routeLeave(), true)
assert.equal(popupWindows.length, 0, 'Completing the order must not automatically open the PDF')
await api.openReceipt()
assert.equal(destinations[0].name, 'pedido-invitado-pdf')
assert.equal(destinations[0].params.cliente, '42')
assert.equal(destinations[0].query, undefined, 'Viewing the receipt does not automatically open the print dialog')
assert.equal(openedReceipts.length, 1, 'Receipt is loaded in a separate window')
assert.equal(popupWindows.at(-1).opener, null, 'The new window cannot control checkout')
await api.processOrder()
assert.equal(calls, 1, 'Unchanged saved customer reuses the response')
blockPopups = true
popupWindows.at(-1).closed = true
await api.openReceipt()
assert.ok(api.processError.value.includes('ventanas emergentes'), 'Blocked popups show a manual print action without resubmitting')
assert.equal(calls, 1)
blockPopups = false
api.guestDetails.value.nombre = 'Otro nombre'
fail = true
await api.processOrder()
assert.equal(api.readyToProcess.value, false)
assert.equal(api.validating.value, false)
assert.equal(api.savingGuest.value, false)
assert.equal(api.processError.value, 'Guardado fallido')
assert.equal(api.processingState.value, 'error')
assert.equal(api.processingOpen.value, true)
assert.equal(cart.items.value.length, 1, 'Folio creation does not clear the cart yet')
fail = false
await api.processOrder()
assert.equal(api.readyToProcess.value, true, 'Retry recovers from failed save')
api.guestDetails.value.nombre = 'En espera'
const windowCountBeforeSave = popupWindows.length
let finishSave
hold = new Promise(resolve => { finishSave = resolve })
const saving = api.processOrder()
await new Promise(resolve => setImmediate(resolve))
assert.equal(api.processingOpen.value, true)
assert.equal(api.processingState.value, 'processing', 'Modal remains busy while server has not answered')
assert.equal(popupWindows.length, windowCountBeforeSave, 'Processing opens only the modal, never another window')
assert.equal(api.savingGuest.value, true)
assert.equal(routeLeave(), false, 'Navigation is blocked while the server saves')
const beforeRepeatedClick = calls
await api.processOrder()
assert.equal(calls, beforeRepeatedClick, 'Blocked UI cannot generate a second save request')
finishSave()
await saving
hold = null
assert.equal(api.processingState.value, 'success')
assert.equal(routeLeave(), true)
const beforeRegistered = calls
user.value = { id: 23 }
await api.processOrder()
assert.equal(calls, beforeRegistered, 'Registered customers do not use guest flow')
user.value = null
removeHook()
api.guestDetails.value.nombre = 'Después de salir'
await api.processOrder()
assert.equal(calls, beforeRegistered, 'Leaving checkout stops further writes')
console.log('Guest checkout checks passed: API mapping, validation, duplicate clicks, errors, retries and unchanged cart.')
