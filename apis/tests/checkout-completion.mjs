import assert from 'node:assert/strict'
import { createMemoryHistory, createRouter } from 'vue-router'
import routes from '../../src/router/routes.js'
import { getCompletedOrder, rememberCompletedOrder } from '../../src/services/checkoutCompletion.js'

const completion = routes[0].children.find(route => route.name === 'pedido-completado')
const router = createRouter({ history: createMemoryHistory('/ImportadoraSupernova/'), routes: [
  { path: '/carrito', component: {} },
  { ...completion, path: '/' + completion.path, component: {} },
] })
assert.equal(getCompletedOrder(), null)
await router.push('/pedido/completado')
assert.equal(router.currentRoute.value.path, '/carrito', 'Direct visit without successful checkout is redirected to cart')
assert.throws(() => rememberCompletedOrder({ orden: 'INV-7' }), /confirmación/, 'Cannot seed confirmation with a partial order')
const saved = { folio_id: 99, cliente_invitado_id: 7, orden: 'INV-7', pdf_token: 'p1.' + 'A'.repeat(54), carrito_vaciado: true, total: 123, cantidad: 2, correo: 'private@example.com' }
rememberCompletedOrder(saved)
await router.replace({ name: 'pedido-completado' })
assert.equal(router.currentRoute.value.name, 'pedido-completado')
assert.equal(router.currentRoute.value.fullPath, '/pedido/completado', 'No receipt token or personal information in confirmation URL')
assert.equal(getCompletedOrder().orden, 'INV-7')
assert.equal(getCompletedOrder().correo, undefined, 'Confirmation keeps only the receipt data it needs')
assert.ok(Object.isFrozen(getCompletedOrder()))
console.log('Checkout completion checks passed: guarded navigation, confirmed receipt and no personal data in URL.')
