import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'
import { emptyGuestDetails, normalizeGuestDetails } from '../../src/utils/guestCheckout.js'

let requested
const previousFetch = globalThis.fetch
const apiSource = readFileSync(new URL('../../src/services/checkoutApi.js', import.meta.url), 'utf8')
  .replaceAll('import.meta.env.VITE_STORE_API_BASE_URL', "'https://store.example/v1'")
const { registerGuestCustomer, createGuestFolio, getGuestOrderPdf, getEmailOrderPdf, sendOrderEmailTest } = await import(`data:text/javascript;base64,${Buffer.from(apiSource).toString('base64')}`)
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
    return { ok: true, json: async () => ({ success: true, data: { accepted: false, code: 'MAIL_REJECTED', message: 'Rejected' } }) }
  }
  assert.equal((await sendOrderEmailTest({ ...customer, correo: 'test@example.com' })).accepted, false)
  assert.equal(requested.url, 'https://store.example/v1/checkout/invitado/correo/prueba')
  assert.deepEqual(JSON.parse(requested.body), { nombre: customer.nombre, correo: 'test@example.com' }, 'Probe sends only contact fields, never identities or an order')
  globalThis.fetch = async (url, options) => {
    requested = { url, ...options }
    return { ok: true, json: async () => ({ success: true, data: { cliente_invitado_id: 42, folio_id: 99, orden: 'INV-42', productos_registrados: 2, carrito_vaciado: true, pdf_token: 'p1.' + 'A'.repeat(54) } }) }
  }
  await createGuestFolio(customer, { envio: 0, shipping_carrier_id: 21, sucursal_id: 25 })
  assert.equal(requested.url, 'https://store.example/v1/checkout/invitado/folio')
  const folioBody = JSON.parse(requested.body)
  assert.equal(folioBody.tipo_entrega, 0)
  assert.equal(folioBody.sucursal_id, 25)
  assert.equal(folioBody.total, undefined)
  assert.equal(folioBody.cantidad, undefined)
  assert.equal(folioBody.id_cliente_invitado, undefined)
  assert.match(folioBody.request_id, /^[a-f0-9]{32}$/)
  const successFetch = globalThis.fetch
  globalThis.fetch = async (url, options) => {
    requested = { url, ...options }
    throw new Error('Network failure')
  }
  await assert.rejects(createGuestFolio(customer, { envio: 0, shipping_carrier_id: 21, sucursal_id: 25 }), /Network failure/)
  const retryKey = JSON.parse(requested.body).request_id
  assert.notEqual(retryKey, folioBody.request_id, 'A subsequent purchase has a new key even with identical contact details')
  globalThis.fetch = successFetch
  await createGuestFolio(customer, { envio: 0, shipping_carrier_id: 21, sucursal_id: 25 })
  assert.equal(JSON.parse(requested.body).request_id, retryKey, 'Network retry retains its purchase key')
  globalThis.fetch = async (url, options) => {
    requested = { url, ...options }
    return { ok: true, headers: new Headers({ 'content-type': 'application/pdf' }), blob: async () => new Blob(['%PDF-1.4\nreceipt']) }
  }
  const receipt = await getGuestOrderPdf('p1.' + 'A'.repeat(54), 42)
  assert.ok(requested.url.includes('/checkout/invitado/pdf?'))
  assert.equal(new URL(requested.url).searchParams.get('cliente'), '42')
  assert.equal(requested.credentials, 'include')
  assert.ok((await receipt.text()).startsWith('%PDF-'))
  await getEmailOrderPdf('a'.repeat(64))
  assert.equal(new URL(requested.url).pathname, '/v1/checkout/invitado/correo/pdf')
  assert.equal(new URL(requested.url).searchParams.get('token'), 'a'.repeat(64))
  assert.equal(new URL(requested.url).searchParams.has('cliente'), false, 'Email viewer requires only its private token')
  await assert.rejects(getEmailOrderPdf('invalid'), /no es válido/)
  await assert.rejects(getGuestOrderPdf('bad-token', 42), /no es válido/)
  globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: { message: 'Revisa tu correo.' } }) })
  await assert.rejects(registerGuestCustomer(customer), /Revisa tu correo/)
} finally {
  globalThis.fetch = previousFetch
}

const source = readFileSync(new URL('../../src/pages/CheckoutPage.vue', import.meta.url), 'utf8')
  .match(/<script setup>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '')
const ref = value => ({ value })
const cart = { items: ref([{ puede_comprar: true }]), total: ref(100), busy: ref(false), loadCart: async () => {}, resetCart: () => { cart.items.value = []; cart.total.value = 0 } }
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
  computed: getter => ({ get value() { return getter() } }), useRoute: () => ({ query: {} }),
  ref, watch: () => {}, onMounted: () => {}, onBeforeUnmount: callback => { removeHook = callback },
  nextTick: async () => {}, emptyGuestDetails, normalizeGuestDetails,
  useRouter: () => ({ resolve: destination => { destinations.push(destination); return { href: '/pedido/invitado/42/pdf' } }, replace: async destination => { assert.equal(routeLeave(), true); assert.equal(destination.name, 'pedido-completado'); assert.equal(cart.items.value.length, 1, 'Navigate before shrinking checkout'); }, push: () => { throw new Error('Checkout must use replace after completing the order') } }),
  rememberCompletedOrder: order => { assert.equal(order.orden, 'INV-42'); },
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
  createGuestFolio: async () => { calls++; if (fail) throw new Error('Guardado fallido'); if (hold) await hold; return { cliente_invitado_id: 42, folio_id: 99, orden: 'INV-42', productos_registrados: 2, carrito_vaciado: true, pdf_token: 'p1.' + 'A'.repeat(54) } },
})
vm.runInContext(source + '\nglobalThis.api = { guestDetails, deliverySelection, guestForm, deliveryForm, processOrder, processError, readyToProcess, validating, savingGuest, savedGuest, processingOpen, processingState, openReceipt };', context)
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
api.guestDetails.value.nombre = 'Cliente de prueba'
api.guestDetails.value.correo = 'guest@example.com'
api.deliverySelection.value = { envio: 0, shipping_carrier_id: 21, sucursal_id: 25 }
await Promise.all([api.processOrder(), api.processOrder()])
assert.equal(calls, 1, 'Concurrent clicks send one request')
assert.equal(api.readyToProcess.value, true)
assert.equal(api.savedGuest.value.cliente_invitado_id, 42)
assert.equal(api.processingOpen.value, false, 'Successful checkout closes the dialog and shows the confirmation page')
assert.equal(api.processingState.value, 'success')
assert.equal(cart.items.value.length, 0, 'Confirmed order resets shared cart and header counters')
assert.equal(cart.total.value, 0)
assert.equal(api.deliverySelection.value.envio, null, 'Delivery method is cleared after successful save')
assert.equal(api.deliverySelection.value.sucursal_id, null, 'Pickup selection is cleared after successful save')
assert.equal(api.guestDetails.value.nombre, '', 'Customer form is cleared after successful save')
assert.equal(api.guestDetails.value.correo, '', 'Customer email is cleared after successful save')
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
cart.items.value = [{ puede_comprar: true }]; cart.total.value = 100
api.guestDetails.value.nombre = 'Otro nombre'
fail = true
await api.processOrder()
assert.equal(api.readyToProcess.value, false)
assert.equal(api.validating.value, false)
assert.equal(api.savingGuest.value, false)
assert.equal(api.processError.value, 'Guardado fallido')
assert.equal(api.guestDetails.value.nombre, 'Otro nombre', 'Failed purchase preserves form data for retry')
assert.equal(api.processingState.value, 'error')
assert.equal(api.processingOpen.value, true)
assert.equal(cart.items.value.length, 1, 'Failed purchase preserves the cart')
fail = false
await api.processOrder()
assert.equal(api.readyToProcess.value, true, 'Retry recovers from failed save')
cart.items.value = [{ puede_comprar: true }]; cart.total.value = 100
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
console.log('Guest checkout checks passed: API mapping, validation, duplicate clicks, errors, retries and cart/form cleanup after confirmed purchase.')
