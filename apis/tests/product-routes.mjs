import assert from 'node:assert/strict'
import { createMemoryHistory, createRouter } from 'vue-router'
import routes from '../../src/router/routes.js'
import { productLocation } from '../../src/utils/productRoutes.js'

const record = routes[0].children.find(route => route.name === 'producto')
const router = createRouter({
  history: createMemoryHistory('/ImportadoraSupernova/'),
  routes: [{ ...record, path: `/${record.path}`, component: {} }],
})
const location = productLocation({ id_producto: 17, nombre: 'Gel para uñas / Rosa' })
assert.equal(router.resolve(location).fullPath, '/producto/17/gel-para-unas-rosa')
assert.equal(router.resolve(location).href, '/ImportadoraSupernova/producto/17/gel-para-unas-rosa')
await router.push('/producto/17')
assert.equal(router.currentRoute.value.params.id, '17')
await router.replace(location)
assert.equal(router.currentRoute.value.params.slug, 'gel-para-unas-rosa')
assert.equal(productLocation({ id: 42, nombre: 'Producto renombrado' }).params.id, '42')
console.log('Product route tests passed: direct links, slug, stable ID and publicPath.')
