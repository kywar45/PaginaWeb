<template>
  <q-page class="offers-page">
    <section class="offers-page__inner">
      <div class="offers-page__heading">
        <h1><q-icon name="bolt" /> Ofertas del dia</h1>
        <q-btn flat dense no-caps icon="arrow_back" label="Volver" to="/" />
      </div>

      <div v-if="loading" class="q-pa-xl text-center" role="status">
        <q-spinner color="primary" size="36px" />
        <p>Cargando ofertas...</p>
      </div>
      <div v-else-if="errorMessage" class="q-pa-lg text-center" role="alert">
        <p>{{ errorMessage }}</p>
        <q-btn color="primary" no-caps label="Reintentar" @click="loadProducts" />
      </div>
      <p v-else-if="!products.length" class="q-pa-lg text-center">No hay ofertas disponibles.</p>
      <div v-else class="offers-page__grid">
        <article
          v-for="product in products"
          :key="product.id_producto"
          class="offer-card"
          role="button"
          tabindex="0"
          @click="selectedProduct = product"
          @keydown.enter="selectedProduct = product"
          @keydown.space.prevent="selectedProduct = product"
        >
          <div class="offer-image">
            <SaleBadge class="card-sale-badge" :percentage="product.porcentaje_descuento" />
            <img
              :src="product.imagen"
              :alt="product.nombre"
              loading="lazy"
              @error="useFallbackImage"
            />
          </div>
          <div class="offer-body">
            <h2>{{ product.nombre }}</h2>
            <p>{{ product.codigo }}</p>
            <del v-if="product.tiene_descuento">{{ money(product.precio_normal) }}</del>
            <strong class="offer-price price--sale">
              {{ money(product.precio_final) }}
            </strong>
          </div>
        </article>
      </div>
      <div v-if="!loading && !errorMessage && products.length" class="offers-page__pagination">
        <p v-if="pagination.total !== undefined">
          {{ (page - 1) * pageSize + 1 }}–{{ (page - 1) * pageSize + products.length }} de
          {{ pagination.total }} ofertas
        </p>
        <q-pagination
          v-if="pagination.total_pages > 1"
          :model-value="page"
          :max="pagination.total_pages"
          :max-pages="5"
          direction-links
          boundary-links
          color="primary"
          aria-label="Páginas de ofertas"
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

    <q-dialog :model-value="!!selectedProduct" @update:model-value="selectedProduct = null">
      <q-card v-if="selectedProduct" class="product-dialog">
        <q-card-section class="row justify-end">
          <q-btn flat round icon="close" v-close-popup aria-label="Cerrar" />
        </q-card-section>
        <q-card-section>
          <img
            class="product-dialog__image"
            :src="selectedProduct.imagen"
            :alt="selectedProduct.nombre"
            @error="useFallbackImage"
          />
          <h2>{{ selectedProduct.nombre }}</h2>
          <p>{{ selectedProduct.codigo }}</p>
          <strong class="price--sale">
            {{ money(selectedProduct.precio_final) }}
          </strong>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
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
const selectedProduct = ref(null)
const fallbackProductImage = publicAsset('img/logob.png')

async function loadProducts() {
  request?.abort()
  const currentRequest = new AbortController()
  request = currentRequest
  loading.value = true
  errorMessage.value = ''
  selectedProduct.value = null
  try {
    const response = await getProducts({
      coleccion: 'ofertas',
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
      errorMessage.value = 'No se pudieron cargar las ofertas. Intenta de nuevo.'
  } finally {
    if (request === currentRequest) loading.value = false
  }
}

function changePage(value) {
  return router.push({ path: '/ofertas', query: { ...route.query, page: String(value) } })
}

watch(page, loadProducts, { immediate: true })

onBeforeUnmount(() => {
  request?.abort()
})

const money = (value) => value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })

function useFallbackImage(event) {
  const image = event?.target
  if (!image || image.dataset.fallbackApplied) return
  image.dataset.fallbackApplied = 'true'
  image.src = fallbackProductImage
  image.classList.add('product-image--fallback')
}
</script>

<style scoped lang="scss">
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
</style>
