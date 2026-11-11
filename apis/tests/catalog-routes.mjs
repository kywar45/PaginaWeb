import assert from 'node:assert/strict'
import { createMemoryHistory, createRouter } from 'vue-router'
import { catalogLocation, resolveCatalogSelection } from '../../src/utils/catalogRoutes.js'
import appRoutes from '../../src/router/routes.js'

const categories = [
  { id: 1, name: 'Le Mussa', subcategories: [
    { id: 64, name: 'Construcción' },
    { id: 65, name: 'Geles Creativos Le\'Mussa' },
  ] },
  { id: 2, name: 'Producto Para Uñas', subcategories: [
    { id: 6, name: 'Decoración (Foil, Stiker y Piedreria)' },
  ] },
]
const records = appRoutes[0].children
  .filter(route => route.name === 'catalogo' || route.path === 'subcategoria')
  .map(route => ({ ...route, path: `/${route.path}`, component: {} }))
const router = createRouter({ history: createMemoryHistory('/ImportadoraSupernova/'), routes: records })
const category = categories[0]
const subcategory = category.subcategories[0]
const location = catalogLocation(category, subcategory, 2, categories)
assert.equal(router.resolve(location).fullPath, '/catalogo/le-mussa/construccion?page=2')
assert.equal(router.resolve(location).href, '/ImportadoraSupernova/catalogo/le-mussa/construccion?page=2')
await router.push(location)
assert.equal(resolveCatalogSelection(categories, router.currentRoute.value).subcategory.id, 64)
assert.equal(router.resolve(catalogLocation(category, null, 1, categories)).fullPath, '/catalogo/le-mussa')
assert.equal(router.resolve(catalogLocation(category, subcategory, 1, categories)).fullPath, '/catalogo/le-mussa/construccion')
await router.push('/catalogo/producto-para-unas/decoracion-foil-stiker-y-piedreria')
assert.equal(resolveCatalogSelection(categories, router.currentRoute.value).subcategory.id, 6)
await router.push('/subcategoria?categoria=1&subcategoria=64&page=2')
const legacy = resolveCatalogSelection(categories, router.currentRoute.value)
assert.equal(legacy.category.id, 1)
assert.equal(legacy.subcategory.id, 64)
await router.replace(catalogLocation(legacy.category, legacy.subcategory, 2, categories))
assert.equal(router.currentRoute.value.fullPath, '/catalogo/le-mussa/construccion?page=2')
await router.push('/catalogo/le-mussa/no-existe')
const invalid = resolveCatalogSelection(categories, router.currentRoute.value)
assert.equal(invalid.hasSubcategory, true)
assert.equal(invalid.subcategory, undefined)
const duplicate = { id: 5, name: 'Le Mussa', subcategories: [{ id: 7, name: 'Gel' }, { id: 8, name: 'Gel' }] }
const siblings = [...categories, duplicate]
assert.equal(catalogLocation(duplicate, duplicate.subcategories[1], 1, siblings).params.categoriaSlug, 'le-mussa-5')
assert.equal(catalogLocation(duplicate, duplicate.subcategories[1], 1, siblings).params.subcategoriaSlug, 'gel-8')
console.log('Catalog URLs passed: direct links, pagination, base path, legacy links, accents and duplicate names.')
