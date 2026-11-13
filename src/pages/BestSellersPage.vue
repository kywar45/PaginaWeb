<template>
  <q-page class="offers-page">
    <section class="offers-page__inner">
      <PageBreadcrumbs title="Más vendidos" />
      <h1 class="sr-only">Más vendidos</h1>

      <p v-if="!loading && !errorMessage && pagination.total !== undefined" class="offers-page__results" aria-live="polite">
        Total: {{ pagination.total.toLocaleString('es-MX') }} productos
        <span v-if="pagination.total_pages > 1"> · Página {{ page }} de {{ pagination.total_pages }}</span>
      </p>

      <div v-if="loading" class="q-pa-xl text-center" role="status">
        <q-spinner color="primary" size="36px" />
        <p>Cargando los más vendidos...</p>
      </div>
      <div v-else-if="errorMessage" class="q-pa-lg text-center" role="alert">
        <p>{{ errorMessage }}</p>
        <q-btn color="primary" no-caps label="Reintentar" @click="loadProducts" />
      </div>
      <p v-else-if="!products.length" class="q-pa-lg text-center">Todavía no hay ventas pagadas en los últimos tres meses.</p>
      <div v-else class="offers-page__grid">
        <article
          v-for="product in products"
          :key="product.id_producto"
          class="offer-card"
          role="button"
          tabindex="0"
          @click="openProduct(product)"
          @keydown.enter="openProduct(product)"
          @keydown.space.prevent="openProduct(product)"
        >
          <div class="offer-image">
            <SaleBadge v-if="product.tiene_descuento" class="card-sale-badge" :percentage="product.porcentaje_descuento" />
            <img
              :src="product.imagen"
              :alt="product.nombre"
              loading="lazy"
              @error="useFallbackImage"
            />
          </div>
          <ProductCardInfo :product="product" />
        </article>
      </div>
      <div v-if="!loading && !errorMessage && products.length" class="offers-page__pagination">
        <StorePagination
          v-if="pagination.total_pages > 1"
          :model-value="page"
          :max="pagination.total_pages"
          aria-label="Páginas de más vendidos"
          @update:model-value="changePage"
        />
        <div v-else-if="pagination.total_pages === undefined" class="row q-gutter-sm">
          <q-btn no-caps label="Anterior" :disable="page <= 1" @click="changePage(page - 1)" />
          <span class="self-center">Página {{ page }}</span>
          <q-btn
            no-caps
            label="Siguiente"
            :disable="!pagination.has_more"
            @click="changePage(page + 1)"
          />
        </div>
      </div>
    </section>


  </q-page>
</template>

<script setup>
import StorePagination from 'src/components/StorePagination.vue'
import { useProductNavigation } from 'src/services/productNavigation'
const openProduct = useProductNavigation()
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ProductCardInfo from 'src/components/ProductCardInfo.vue'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SaleBadge from 'src/components/SaleBadge.vue'

import { getProducts } from 'src/services/productApi'
import { publicAsset } from 'src/utils/publicAsset'

const route = useRoute()
const router = useRouter()
const pageSize = 30
const page = computed(() => {
  const value = Number(route.query.page)
  return Number.isSafeInteger(value) && value >= 1 && value <= 1000000 ? value : 1
})
let request
const products = ref([])
const pagination = ref({})
const loading = ref(false)
const errorMessage = ref('')
const fallbackProductImage = publicAsset('img/product-no-image.svg')

async function loadProducts() {
  request?.abort()
  const currentRequest = new AbortController()
  request = currentRequest
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await getProducts({
      coleccion: 'mas_vendidos',
      page: page.value,
      limit: pageSize,
      signal: currentRequest.signal,
    })
    if (currentRequest.signal.aborted) return
    if (response.pagination?.total_pages > 0 && page.value > response.pagination.total_pages) {
      await changePage(response.pagination.total_pages)
      return
    }
    products.value = response.productos
    pagination.value = response.pagination || {}
  } catch {
    if (!currentRequest.signal.aborted)
      errorMessage.value = 'No se pudieron cargar los más vendidos. Intenta de nuevo.'
  } finally {
    if (request === currentRequest) loading.value = false
  }
}

function changePage(value) {
  return router.push({ path: '/mas-vendidos', query: { ...route.query, page: String(value) } })
}

watch(page, loadProducts, { immediate: true })

onBeforeUnmount(() => {
  request?.abort()
})


function useFallbackImage(event) {
  const image = event?.target
  if (!image || image.dataset.fallbackApplied) return
  image.dataset.fallbackApplied = 'true'
  image.src = fallbackProductImage
  image.classList.add('product-image--fallback')
}
</script>

<style scoped lang="scss">
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
.offers-page__results { margin: 0 0 16px; color: #88717d; font-size: 12px; }
.offers-page__pagination {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 28px 0;
}

.offers-page {
  background: #fff;
}

.offers-page__inner {
  width: min(100%, 1280px);
  margin: 0 auto;
  padding: 28px 48px 44px;
}

.offers-page__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.offers-page__heading h1 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: #21191b;
  font-size: 24px;
  font-weight: 800;
  line-height: 1.2;
}

.offers-page__heading .q-icon {
  color: #de7c97;
}

.offers-page__heading .q-btn {
  color: #de7c97 !important;
}

.offers-page__grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
}

.offer-card {
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #eee7e7;
  border-radius: 9px;
  background: #fff;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.offer-card:hover {
  transform: translateY(-3px);
  border-color: #f3bdca;
  box-shadow: 0 10px 24px rgba(190, 42, 87, 0.09);
}

.offer-image {
  position: relative;
  height: clamp(120px, 12vw, 170px);
  padding: 12px 14px 0;
  background: #fff;
  overflow: hidden;
}

.offer-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.26s ease;
}

.offer-card:hover .offer-image img {
  transform: scale(1.07);
}

.product-dialog {
  width: 430px;
  max-width: 94vw;
}

.product-dialog__image {
  display: block;
  width: 240px;
  height: 240px;
  margin: 0 auto 16px;
  object-fit: contain;
}

.product-dialog h2 {
  margin: 0 0 6px;
  font-size: 20px;
}

.product-dialog p {
  margin: 0 0 8px;
  color: #8d8082;
}

.product-dialog strong {
  color: #de3a67;
  font-size: 24px;
}

.product-image--fallback {
  padding: 26px !important;
  object-fit: contain !important;
  opacity: 0.72;
  filter: saturate(0.9);
}

.offer-discount {
  position: absolute;
  z-index: 2;
  top: 9px;
  left: 9px;
  padding: 4px 8px;
  border-radius: 5px;
  background: #de7c97;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
}

.offer-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: 8px 14px 12px;
}

.offer-card h2 {
  min-height: 32px;
  margin: 0 0 5px;
  color: #20191a;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.2;
}

.offer-card p {
  margin: 0 0 4px;
  color: #8d8082;
  font-size: 10px;
}

.offer-card del {
  margin-top: auto;
  color: #9c9192;
  font-size: 10px;
}

.offer-price {
  margin: 1px 0 8px;
  color: #111;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.25;
}

.offer-card .q-btn {
  width: 100%;
  min-height: 34px;
  border-radius: 5px;
  background: #de7c97 !important;
  box-shadow: none !important;
  font-size: 10.5px;
  font-weight: 600;
}

@media (max-width: 1199px) {
  .offers-page__inner {
    padding-inline: 38px;
  }

  .offers-page__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 1023px) {
  .offers-page__inner {
    padding: 22px 16px 36px;
  }

  .offers-page__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .offers-page__heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .offers-page__heading h1 {
    font-size: 19px;
  }

  .offers-page__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .offer-image {
    height: 138px;
  }

  .offer-body {
    padding: 8px 9px 10px;
  }
}

.card-sale-badge {
  position: absolute;
  z-index: 2;
  top: 9px;
  left: 9px;
}

.offer-card:not(.offer-card--see-all) .offer-image {
  background: linear-gradient(180deg, #fff, #fff9fb) !important;
}
</style>
