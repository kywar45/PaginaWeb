import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'
import { emptyGuestDetails, normalizeGuestDetails } from '../../src/utils/guestCheckout.js'

let requested
const previousFetch = globalThis.fetch
const apiSource = readFileSync(new URL('../../src/services/checkoutApi.js', import.meta.url), 'utf8')
  .replace('import.meta.env.VITE_STORE_API_BASE_URL', "'https://store.example/v1'")
const { registerGuestCustomer } = await import(`data:text/javascript;base64,${Buffer.from(apiSource).toString('base64')}`)
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
const context = vm.createContext({
  ref, watch: () => {}, onMounted: () => {}, onBeforeUnmount: callback => { removeHook = callback },
  nextTick: async () => {}, emptyGuestDetails, normalizeGuestDetails,
  useCart: () => cart, useAuthSession: () => ({ currentUser: user }), publicAsset: value => value,
  AbortController, setTimeout, clearTimeout,
  registerGuestCustomer: async () => { calls++; if (fail) throw new Error('Guardado fallido'); return { cliente_invitado_id: 42 } },
})
vm.runInContext(source + '\nglobalThis.api = { guestDetails, guestForm, deliveryForm, processOrder, processError, readyToProcess, validating, savingGuest, savedGuest };', context)
const api = context.api
api.guestForm.value = { validate: async () => false }
api.deliveryForm.value = { validate: () => true }
await api.processOrder()
assert.equal(calls, 0, 'Invalid customer must not reach server')
api.guestForm.value.validate = async () => true
api.deliveryForm.value.validate = () => false
await api.processOrder()
assert.equal(calls, 0, 'Missing delivery must not reach server')
api.deliveryForm.value.validate = () => true
await Promise.all([api.processOrder(), api.processOrder()])
assert.equal(calls, 1, 'Concurrent clicks send one request')
assert.equal(api.readyToProcess.value, true)
assert.equal(api.savedGuest.value.cliente_invitado_id, 42)
await api.processOrder()
assert.equal(calls, 1, 'Unchanged saved customer reuses the response')
api.guestDetails.value.nombre = 'Otro nombre'
fail = true
await api.processOrder()
assert.equal(api.readyToProcess.value, false)
assert.equal(api.validating.value, false)
assert.equal(api.savingGuest.value, false)
assert.equal(api.processError.value, 'Guardado fallido')
assert.equal(cart.items.value.length, 1, 'Step 1 never clears the cart')
fail = false
await api.processOrder()
assert.equal(api.readyToProcess.value, true, 'Retry recovers from failed save')
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
