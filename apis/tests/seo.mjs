import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import vm from 'node:vm'
import { buildPageSeo, serializeSchema, HOME_TITLE, SHARE_IMAGE } from '../../src/utils/seo.js'

const homeUrl = 'https://www.importadorasupernova.com/ImportadoraSupernova/'
const product = { id_producto: 42, nombre: 'Gel Rosa - Brillo', descripcion: '<p>Gel para uñas</p>', codigo: 'GEL-42',
  imagen: 'https://www.importadorasupernova.com/imagenes/42/foto.png', imagenes: ['https://www.importadorasupernova.com/imagenes/42/foto.png'],
  precio_publico: 80, precio_final: 57.5, es_vip: true, estatus: 0, visitas: 9999 }
const meta = buildPageSeo('/producto/42/otro', { homeUrl, product })
assert.equal(meta.title, 'Gel Rosa - Brillo | Importadora Supernova')
assert.equal(meta.url, `${homeUrl}producto/42/gel-rosa-brillo`)
assert.equal(meta.graph[0].offers.price, '80.00')
assert.equal(meta.graph[0].offers.availability, 'https://schema.org/OutOfStock')
assert.equal(meta.graph[0].aggregateRating, undefined)
assert.equal(buildPageSeo('/', { homeUrl }).title, HOME_TITLE)
assert.equal(buildPageSeo('/', { homeUrl }).image, SHARE_IMAGE)
assert.deepEqual(buildPageSeo('/producto/999', { homeUrl, notFound: true }).graph, [])
assert.equal(buildPageSeo('/checkout', { homeUrl }).robots, 'noindex, follow')
assert.equal(buildPageSeo('/ofertas', { homeUrl }).robots, 'index, follow')
const withoutPublicPrice = { ...product }
delete withoutPublicPrice.precio_publico
assert.equal(buildPageSeo('/producto/42', { homeUrl, product: withoutPublicPrice }).graph[0].offers, undefined)
assert.equal(serializeSchema([{ name: '</script><script>alert(1)</script>' }]).includes('</script>'), false)
const phpMeta = JSON.parse(execFileSync('php', ['apis/tests/seo.php', '--json'], { encoding: 'utf8' }))
assert.deepEqual(meta, phpMeta, 'Server and client describe the same product')
const html = readFileSync('dist/spa/index.html', 'utf8')
assert.ok(html.includes('store-structured-data'))
assert.ok(html.includes(SHARE_IMAGE))
assert.ok(html.includes('send_page_view: false'))
assert.ok(existsSync('dist/spa/index.php') && existsSync('dist/spa/seo/PageMetadata.php'))
assert.ok(readFileSync('dist/spa/.htaccess', 'utf8').includes('DirectoryIndex index.php'))
const bootSource = readFileSync('src/boot/seo.js', 'utf8').replace(/^import .*$/gm, '')
  .replace('import.meta.env.BASE_URL', "'/ImportadoraSupernova/'")
  .replace('export default defineBoot', 'globalThis.install = defineBoot')
const updates = []
const pageViews = []
let navigation
const context = vm.createContext({
  defineBoot: callback => callback, URL,
  SITE_ORIGIN: 'https://www.importadorasupernova.com', ANALYTICS_ID: 'G-9BG23LE340',
  buildPageSeo, applyPageSeo: meta => updates.push(meta),
  window: { gtag: (...args) => pageViews.push(args) },
})
vm.runInContext(bootSource, context)
context.install({ router: { afterEach: callback => { navigation = callback } } })
const emptyRoute = { path: '', name: undefined, params: {}, matched: [] }
const productRoute = { path: '/producto/42/otro', name: 'producto', params: { id: '42' }, matched: [] }
navigation(productRoute, emptyRoute)
assert.equal(updates.length, 0, 'Initial product navigation preserves PHP metadata')
navigation({ ...productRoute, path: '/producto/42/gel-rosa-brillo' }, productRoute)
assert.equal(updates.length, 0, 'Canonical slug update does not erase loaded product metadata')
assert.equal(pageViews.length, 1, 'Canonical slug update does not duplicate Analytics page views')
navigation({ ...emptyRoute, path: '/ofertas' }, productRoute)
assert.equal(updates.at(-1).type, 'website')
assert.deepEqual(updates.at(-1).graph, [], 'Leaving a product removes its structured offer')
navigation({ ...emptyRoute, path: '/checkout' }, { ...emptyRoute, path: '/ofertas' })
assert.equal(updates.at(-1).robots, 'noindex, follow')
console.log('SEO checks passed: browser/server parity, canonical URLs, public pricing, metadata assets and PHP deployment files.')
