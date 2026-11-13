import assert from 'node:assert/strict'
import { createCartStore } from '../../src/services/cartStore.js'
import { previewCartPrice } from '../../src/utils/cartPricing.js'

const normal = { id_producto: 1, codigo: 'GEL-01', precio_final: 100,
  precio_mayoreo: 80, precio_caja: 60, minimo_mayoreo: 6, minimo_caja: 20,
  es_vip: false, tiene_descuento: false, puede_comprar: true }
const existing = [{ ...normal, id_producto: 2, codigo: 'gel-01 ', quantity: 4 }]
assert.equal(previewCartPrice(normal, 2, existing).price, 80)
assert.equal(previewCartPrice(normal, 16, existing).price, 60)
assert.equal(previewCartPrice(normal, 1, existing).price, 100)
assert.equal(previewCartPrice({ ...normal, tiene_descuento: true, precio_final: 90 }, 20, existing).price, 90)
assert.equal(previewCartPrice({ ...normal, es_vip: true, precio_final: 57.5 }, 20, existing).price, 57.5)
assert.equal(previewCartPrice(normal, 2, [{ ...existing[0], puede_comprar: false }]).price, 100)
assert.equal(previewCartPrice({ ...normal, codigo: '' }, 2, [{ ...existing[0], codigo: '' }]).pieces, 2)

const snapshot = (quantity = 0, price = 100) => ({ items: quantity ? [{ ...normal, quantity, precio_final: price }] : [],
  cantidad_total: quantity, total: quantity * price, ahorro_total: Math.max(0, 100-price)*quantity,
  total_sin_descuentos: quantity*100 })
let saved = snapshot(4)
const calls = []
const api = {
  async load() { calls.push('load'); return saved },
  async update(action, id, quantity) {
    calls.push([action, id, quantity])
    const next = action === 'agregar' ? saved.cantidad_total + quantity : action === 'eliminar' ? 0 : quantity
    saved = snapshot(next, next >= 6 ? 80 : 100)
    return saved
  },
}
const cart = createCartStore(api)
await cart.addProduct({ ...normal, precio_final: 0.01 }, 2)
assert.deepEqual(calls[0], 'load', 'Guest cookie bootstrap precedes first write')
assert.equal(cart.total.value, 480, 'Uses server price, never client supplied price')
assert.equal(cart.savings.value, 120, 'Display server-calculated savings')
assert.equal(cart.regularTotal.value, 600)
await Promise.all([cart.addProduct(normal, 1), cart.addProduct(normal, 1)])
assert.equal(cart.cartCount.value, 8, 'Queued increments are not lost')
assert.equal(cart.busy.value, false)
await cart.setQuantity(1, 2)
assert.equal(cart.total.value, 200, 'Server response reprices after decrease')
assert.equal(cart.savings.value, 0, 'Savings update when wholesale discount is lost')
const reloaded = createCartStore(api)
await reloaded.loadCart()
assert.equal(reloaded.cartCount.value, 2, 'New store recovers DB cart rather than localStorage')
for (const quantity of [0, -1, 1.5, NaN, 10000]) await assert.rejects(cart.addProduct(normal, quantity))
const oldUpdate = api.update
api.update = async () => { throw new Error('Sin conexión') }
await assert.rejects(cart.removeProduct(1))
assert.equal(cart.items.value.length, 1, 'Failed save does not pretend it removed the DB row')
assert.equal(cart.error.value, 'Sin conexión')
api.update = oldUpdate
await cart.removeProduct(1)
assert.equal(cart.total.value, 0)

let finishOldRequest
const switched = createCartStore({
  load: () => new Promise(resolve => { finishOldRequest = resolve }),
  update: async () => { throw new Error('Stale mutation must never be sent') },
})
const stale = switched.loadCart()
await Promise.resolve()
const queuedOldAdd = switched.addProduct(normal, 1)
switched.resetCart()
finishOldRequest(snapshot(99))
await assert.rejects(stale)
await assert.rejects(queuedOldAdd)
assert.equal(switched.items.value.length, 0, 'Old identity response cannot populate new account cart')
assert.equal(switched.cartCount.value, 0)

cart.setMergeNotice({ fusionado: true, no_disponibles: 1 })
assert.match(cart.notice.value.message, /Tus productos ya están en tu cuenta/)
assert.match(cart.notice.value.message, /no disponibles/)
cart.resetCart()
assert.equal(cart.savings.value, 0, 'Savings do not leak across sessions')
assert.equal(cart.notice.value, null, 'Notice does not leak to a different account')
cart.setMergeNotice({ pendiente: true, mensaje: 'Error de guardado' })
assert.equal(cart.notice.value.retry, true)
api.update = async (action) => {
  assert.equal(action, 'fusionar')
  return { ...snapshot(6, 80), fusion: { fusionado: true, piezas: 2, no_disponibles: 0 } }
}
await cart.mergeCart()
assert.equal(cart.total.value, 480)
assert.equal(cart.notice.value.retry, false)
cart.clearNotice()
assert.equal(cart.notice.value, null)
console.log('Cart client tests passed: preview tiers, server authority, queueing, failure handling and session isolation.')
