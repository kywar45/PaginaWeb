<template>
  <q-page class="search-page">
    <PageBreadcrumbs title="Resultados de búsqueda" back-to="/" />
    <h1>Resultados para <span>“{{ query }}”</span></h1>
    <div v-if="query" class="search-filters" aria-label="Filtros de búsqueda">
      <div class="search-filters__sort-group">
        <span class="search-filters__caption">Ordenar por</span>
        <q-select :model-value="sort" :options="sortOptions" emit-value map-options outlined dense options-dense hide-bottom-space aria-label="Ordenar resultados" dropdown-icon="expand_more" class="search-filters__sort" @update:model-value="updateFilters({ orden: $event })">
          <template #prepend><q-icon name="sort" /></template>
        </q-select>
      </div>
      <div class="search-filters__toggles">
        <button type="button" class="search-filter" :class="{ 'is-active': onlyAvailable }" :aria-pressed="onlyAvailable" @click="updateFilters({ solo_disponibles: onlyAvailable ? undefined : '1' })">
          <q-icon :name="onlyAvailable ? 'check_circle' : 'inventory_2'" aria-hidden="true" /><span><span class="search-filter__prefix">Solo </span>disponibles</span>
        </button>
        <button type="button" class="search-filter" :class="{ 'is-active': offersOnly }" :aria-pressed="offersOnly" @click="updateFilters({ solo_ofertas: offersOnly ? undefined : '1' })">
          <q-icon :name="offersOnly ? 'check_circle' : 'local_offer'" aria-hidden="true" /><span><span class="search-filter__prefix">Solo </span>ofertas</span>
        </button>
      </div>
      <button v-if="sort !== 'relevancia' || onlyAvailable || offersOnly" type="button" class="search-filters__reset" @click="updateFilters({ orden: undefined, solo_disponibles: undefined, solo_ofertas: undefined })"><q-icon name="restart_alt" aria-hidden="true" /> Limpiar</button>
    </div>
    <div v-if="!query" class="search-page__status">Escribe un nombre o código en el buscador para encontrar productos.</div>
    <div v-else-if="loading" class="search-page__status" role="status"><q-spinner color="primary" size="32px" /><p>Buscando productos...</p></div>
    <div v-else-if="error" class="search-page__status" role="alert"><p>{{ error }}</p><q-btn no-caps color="primary" label="Reintentar" @click="loadResults" /></div>
    <template v-else>
      <p class="search-page__count" role="status">{{ pagination.total || 0 }} productos encontrados</p>
      <div v-if="!products.length" class="search-page__status"><q-icon name="search_off" size="36px" /><p>No encontramos productos con esa búsqueda y esos filtros.</p><span>Prueba con otra palabra o ajusta los filtros.</span></div>
      <div v-else class="search-page__grid">
        <RouterLink v-for="product in products" :key="product.id_producto" :to="productLocation(product)" class="search-product">
          <div class="search-product__image">
            <SaleBadge v-if="product.tiene_descuento" :percentage="product.porcentaje_descuento" />
            <img :src="product.imagen" :alt="product.nombre" loading="lazy" @error="useFallbackImage" />
          </div>
          <ProductCardInfo :product="product" />
        </RouterLink>
      </div>
      <StorePagination v-if="pagination.total_pages > 1" class="search-page__pagination" :model-value="page" :max="pagination.total_pages" aria-label="Páginas de resultados" @update:model-value="changePage" />
    </template>
  </q-page>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ProductCardInfo from 'src/components/ProductCardInfo.vue'
import SaleBadge from 'src/components/SaleBadge.vue'
import StorePagination from 'src/components/StorePagination.vue'
import { getProducts } from 'src/services/productApi'
import { productLocation } from 'src/utils/productRoutes'
import { publicAsset } from 'src/utils/publicAsset'

const route = useRoute()
const router = useRouter()
const query = computed(() => typeof route.query.q === 'string' ? route.query.q.trim().replace(/\s+/g, ' ') : '')
const sortOptions = [
  { label: 'Relevancia', value: 'relevancia' },
  { label: 'Precio: menor a mayor', value: 'precio_asc' },
  { label: 'Precio: mayor a menor', value: 'precio_desc' },
  { label: 'Más recientes', value: 'recientes' },
]
const sort = computed(() => sortOptions.some((option) => option.value === route.query.orden) ? route.query.orden : 'relevancia')
const onlyAvailable = computed(() => route.query.solo_disponibles === '1')
const offersOnly = computed(() => route.query.solo_ofertas === '1')
const page = computed(() => {
  const value = Number(route.query.page)
  return Number.isSafeInteger(value) && value > 0 && value <= 1000000 ? value : 1
})
const products = ref([])
const pagination = ref({})
const loading = ref(false)
const error = ref('')
let request

async function loadResults() {
  request?.abort()
  const current = new AbortController()
  request = current
  products.value = []
  pagination.value = {}
  error.value = ''
  loading.value = !!query.value
  if (!query.value) return
  try {
    if ([...query.value].length > 80) throw new Error('Escribe una búsqueda de hasta 80 caracteres.')
    const result = await getProducts({ q: query.value, orden: sort.value, solo_disponibles: onlyAvailable.value, solo_ofertas: offersOnly.value, page: page.value, limit: 30, signal: current.signal })
    if (current.signal.aborted) return
    if (page.value > Math.max(1, result.pagination.total_pages)) {
      await router.replace({ path: '/buscar', query: { ...route.query, q: query.value, page: String(Math.max(1, result.pagination.total_pages)) } })
      return
    }
    products.value = result.productos
    pagination.value = result.pagination
  } catch (failure) {
    if (!current.signal.aborted) error.value = failure.message || 'No se pudo realizar la búsqueda.'
  } finally {
    if (request === current) loading.value = false
  }
}
function changePage(value) { router.push({ path: '/buscar', query: { ...route.query, q: query.value, page: String(value) } }) }
function updateFilters(changes) { router.push({ path: '/buscar', query: { ...route.query, ...changes, page: undefined } }) }
function useFallbackImage(event) {
  if (event.target.dataset.fallbackApplied) return
  event.target.dataset.fallbackApplied = 'true'
  event.target.src = publicAsset('img/product-no-image.svg')
}
watch(() => [query.value, page.value, sort.value, onlyAvailable.value, offersOnly.value], loadResults, { immediate: true })
onBeforeUnmount(() => request?.abort())
</script>

<style scoped>
.search-page { width: 100%; max-width: 1280px; margin: 0 auto; padding: 18px 48px 40px; background: #fff; }
h1 { margin: 18px 0 8px; color: #30232a; font-size: 21px; font-weight: 750; line-height: 1.35; overflow-wrap: anywhere; }
h1 span { color: #d62d61; }
.search-page__count { margin: 0 0 18px; color: #8a7480; font-size: 12px; }
.search-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 20px; margin: 20px 0 16px; padding: 14px 0; border-top: 1px solid #f1e5eb; border-bottom: 1px solid #f1e5eb; }
.search-filters__sort-group { display: flex; align-items: center; gap: 10px; }
.search-filters__caption { color: #97818c; font-size: 11px; font-weight: 600; white-space: nowrap; }
.search-filters__sort { width: 225px; max-width: 100%; }
.search-filters__sort :deep(.q-field__control) { min-height: 40px; border-radius: 10px; background: #fff; }
.search-filters__sort :deep(.q-field__control:before) { border-color: #eadfe4; }
.search-filters__sort :deep(.q-field__native) { color: #44343d; font-size: 12px; font-weight: 600; }
.search-filters__sort :deep(.q-field__prepend) { padding-right: 8px; color: #d32a60; font-size: 19px; }
.search-filters__sort :deep(.q-field__append) { color: #af8e9b; font-size: 20px; }
.search-filters__toggles { display: flex; flex-wrap: wrap; gap: 8px; }
.search-filter { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 40px; padding: 8px 13px; border: 1px solid #e8dce2; border-radius: 10px; background: #fff; color: #715b66; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; transition: border-color .18s, box-shadow .18s; }
.search-filter .q-icon { color: #c7466f; font-size: 17px; }
.search-filter:hover { border-color: #d67a98; }
.search-filter.is-active { border-color: #df2d64; background: linear-gradient(112deg, #fb447a, #df2d64); color: #fff; box-shadow: 0 3px 8px rgba(209, 39, 94, .14); }
.search-filter.is-active .q-icon { color: #fff; }
.search-filters__reset { display: inline-flex; align-items: center; gap: 4px; margin-left: auto; padding: 8px 0 8px 6px; border: 0; background: transparent; color: #987782; font: inherit; font-size: 11px; cursor: pointer; }
.search-filters__reset .q-icon { font-size: 17px; }
.search-filters__reset:hover { color: #d32a60; }
.search-filter:focus-visible, .search-filters__reset:focus-visible { outline: 2px solid #d32a60; outline-offset: 3px; }
.search-page__status { padding: 40px 12px; text-align: center; color: #7d6571; }
.search-page__grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
.search-product { display: flex; flex-direction: column; min-width: 0; overflow: hidden; border: 1px solid #f0dfe6; border-radius: 10px; color: inherit; text-decoration: none; }
.search-product:hover { border-color: #e8a8bd; }
.search-product:focus-visible { outline: 2px solid #df2f61; outline-offset: 2px; }
.search-product__image { position: relative; height: 165px; padding: 12px; }
.search-product__image img { width: 100%; height: 100%; object-fit: contain; }
.search-product__image .sale-badge { position: absolute; z-index: 1; top: 8px; left: 8px; }
.search-page__pagination { margin-top: 28px; }
@media (max-width: 1199px) { .search-page__grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 1023px) { .search-page { padding: 10px 16px 100px; } .search-page__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 599px) { .search-page__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; } .search-product__image { height: 140px; } h1 { font-size: 18px; } }
@media (max-width: 599px) {
  .search-filters { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px 6px; margin-top: 14px; }
  .search-filters__sort-group { display: block; min-width: 0; }
  .search-filters__caption { display: none; }
  .search-filters__sort { width: 100%; }
  .search-filters__sort :deep(.q-field__control) { padding: 0 7px; }
  .search-filters__sort :deep(.q-field__native) { font-size: 10px; }
  .search-filters__sort :deep(.q-field__prepend) { padding-right: 4px; font-size: 15px; }
  .search-filters__sort :deep(.q-field__append) { padding-left: 2px; font-size: 16px; }
  .search-filters__toggles { display: flex; flex-wrap: nowrap; gap: 6px; }
  .search-filter { gap: 4px; padding: 8px 7px; font-size: 10px; white-space: nowrap; }
  .search-filter .q-icon { font-size: 15px; }
  .search-filter__prefix { display: none; }
  .search-filters__reset { grid-column: 1 / -1; justify-self: end; margin: 0; padding: 2px 0; }
}
@media (prefers-reduced-motion: reduce) { .search-filter { transition: none; } }
</style>
