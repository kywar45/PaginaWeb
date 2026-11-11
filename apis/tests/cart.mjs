import assert from 'node:assert/strict'

const storage = new Map()
globalThis.window = {}
globalThis.localStorage = {
  getItem: key => storage.get(key) ?? null,
  setItem: (key, value) => storage.set(key, value),
}
const { useCart } = await import('../../src/services/cart.js')
const cart = useCart()
const product = { id_producto: 17, estatus: 1, nombre: 'Lámpara blanca', codigo: 'LA-02', precio_final: 10.1 }
cart.addProduct(product, 2)
assert.equal(cart.cartCount.value, 2)
assert.equal(cart.total.value, 20.2)
cart.addProduct(product, 1)
assert.equal(cart.items.value.length, 1)
assert.equal(cart.items.value[0].quantity, 3)
cart.addProduct({ ...product, id_producto: 18, nombre: 'Lámpara rosa' }, 1)
assert.equal(cart.items.value.length, 2, 'Same code variants remain distinct by ID')
assert.equal(cart.total.value, 40.4)
assert.throws(() => cart.addProduct({ ...product, estatus: 0 }, 1))
for (const quantity of [0, -1, 1.5, NaN, 10000]) assert.throws(() => cart.addProduct(product, quantity))
cart.setQuantity(17, 5)
assert.equal(cart.cartCount.value, 6)
cart.setQuantity(17, 0)
assert.equal(cart.cartCount.value, 6)
const reloaded = (await import('../../src/services/cart.js?reload')).useCart()
assert.equal(reloaded.cartCount.value, 6, 'Reload restores quantities')
assert.equal(reloaded.items.value[1].id_producto, 18)
cart.removeProduct(18)
assert.equal(cart.items.value.length, 1)
cart.removeProduct(17)
assert.equal(cart.total.value, 0)
localStorage.setItem('supernova-cart-v1', 'broken json')
assert.equal((await import('../../src/services/cart.js?broken')).useCart().items.value.length, 0)
console.log('Cart tests passed: quantities, variant IDs, sold-out prevention, totals, removal and persistence.')
