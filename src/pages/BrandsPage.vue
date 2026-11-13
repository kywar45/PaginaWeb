<template>
  <q-page class="brands-page">
    <PageBreadcrumbs
      :title="selectedBrand?.nombre || 'Marcas'"
      :back-to="brandId ? '/marcas' : '/'"
      :parents="brandId ? [{ label: 'Marcas', to: '/marcas' }] : []"
    />
    <div v-if="loadingBrands" class="brands-status" role="status">
      <q-spinner color="primary" size="32px" />
      <p>Cargando marcas...</p>
    </div>
    <div v-else-if="brandsError" class="brands-status" role="alert">
      <p>{{ brandsError }}</p>
      <q-btn no-caps color="primary" label="Reintentar" @click="loadBrands" />
    </div>
    <template v-else-if="!brandId">
      <div class="brands-grid">
        <RouterLink
          v-for="brand in brands"
          :key="brand.id"
          :to="`/marcas/${brand.id}`"
          :aria-label="brand.nombre"
          class="brand-card"
        >
          <span class="brand-card__visual" aria-hidden="true">
            <img
              v-if="brand.logo && !failedLogos.has(brand.id)"
              :src="brand.logo"
              alt=""
              loading="lazy"
              @error="failedLogos.add(brand.id)"
            />
            <span v-else class="brand-card__initial">{{
              brand.nombre.slice(0, 1).toUpperCase()
            }}</span>
          </span>
        </RouterLink>
      </div>
      <p v-if="!brands.length" class="brands-status">Pronto encontrarás nuestras marcas aquí.</p>
    </template>
    <p v-else-if="!selectedBrand" class="brands-status" role="status">
      Esta marca no está disponible.
    </p>
    <template v-else>
      <nav class="brand-tabs" aria-label="Elegir marca">
        <RouterLink
          v-for="brand in brands"
          :key="brand.id"
          :to="`/marcas/${brand.id}`"
          :class="{ 'is-active': brand.id === brandId }"
          :aria-current="brand.id === brandId ? 'page' : undefined"
          >{{ brand.nombre }}</RouterLink
        >
      </nav>
      <div v-if="loadingProducts" class="brands-status" role="status">
        <q-spinner color="primary" size="32px" />
        <p>Cargando productos...</p>
      </div>
      <div v-else-if="productsError" class="brands-status" role="alert">
        <p>{{ productsError }}</p>
        <q-btn no-caps color="primary" label="Reintentar" @click="loadProducts" />
      </div>
      <template v-else>
        <p class="brand-results" role="status">
          Total: {{ (pagination.total || 0).toLocaleString('es-MX') }} productos<span
            v-if="pagination.total_pages > 1"
          >
            · Página {{ page }} de {{ pagination.total_pages }}</span
          >
        </p>
        <div class="brand-products">
          <RouterLink
            v-for="product in products"
            :key="product.id_producto"
            :to="productLocation(product)"
            class="brand-product"
          >
            <div class="brand-product__image">
              <SaleBadge
                v-if="product.tiene_descuento"
                :percentage="product.porcentaje_descuento"
              />
              <img
                :src="product.imagen"
                :alt="product.nombre"
                loading="lazy"
                @error="useFallbackImage"
              />
            </div>
            <ProductCardInfo :product="product" />
          </RouterLink>
        </div>
        <p v-if="!products.length" class="brands-status">
          Todavía no hay productos publicados de esta marca.
        </p>
        <StorePagination
          v-if="pagination.total_pages > 1"
          class="brand-pagination"
          :model-value="page"
          :max="pagination.total_pages"
          aria-label="Páginas de productos de la marca"
          @update:model-value="changePage"
        />
      </template>
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
import { getBrands } from 'src/services/brandApi'
import { getProducts } from 'src/services/productApi'
import { productLocation } from 'src/utils/productRoutes'
import { publicAsset } from 'src/utils/publicAsset'

const route = useRoute()
const router = useRouter()
const brands = ref([])
const failedLogos = ref(new Set())
const products = ref([])
const pagination = ref({})
const loadingBrands = ref(true)
const loadingProducts = ref(false)
const brandsError = ref('')
const productsError = ref('')
const brandId = computed(() => Number(route.params.id) || null)
const selectedBrand = computed(() => brands.value.find((brand) => brand.id === brandId.value))
const page = computed(() => {
  const value = Number(route.query.page)
  return Number.isSafeInteger(value) && value >= 1 && value <= 1000000 ? value : 1
})
let brandsRequest
let productsRequest

async function loadBrands() {
  brandsRequest?.abort()
  const current = new AbortController()
  brandsRequest = current
  loadingBrands.value = true
  brandsError.value = ''
  try {
    const result = await getBrands({ signal: current.signal })
    if (!current.signal.aborted) {
      failedLogos.value.clear()
      brands.value = result
    }
  } catch {
    if (!current.signal.aborted)
      brandsError.value = 'No se pudieron cargar las marcas. Intenta de nuevo.'
  } finally {
    if (brandsRequest === current) loadingBrands.value = false
  }
}

async function loadProducts() {
  productsRequest?.abort()
  products.value = []
  pagination.value = {}
  productsError.value = ''
  loadingProducts.value = false
  if (!selectedBrand.value) return
  const current = new AbortController()
  productsRequest = current
  loadingProducts.value = true
  try {
    const response = await getProducts({
      id_marca: brandId.value,
      limit: 30,
      page: page.value,
      signal: current.signal,
    })
    if (current.signal.aborted) return
    if (response.pagination?.total_pages > 0 && page.value > response.pagination.total_pages) {
      await router.replace({
        path: route.path,
        query: { page: String(response.pagination.total_pages) },
      })
      return
    }
    products.value = response.productos
    pagination.value = response.pagination || {}
  } catch (error) {
    if (!current.signal.aborted)
      productsError.value = error.message || 'No se pudieron cargar los productos.'
  } finally {
    if (productsRequest === current) loadingProducts.value = false
  }
}

function changePage(value) {
  router.push({ path: route.path, query: { page: String(value) } })
}
function useFallbackImage(event) {
  if (event.target.dataset.fallbackApplied) return
  event.target.dataset.fallbackApplied = 'true'
  event.target.src = publicAsset('img/product-no-image.svg')
}
watch([selectedBrand, page], loadProducts, { immediate: true })
loadBrands()
onBeforeUnmount(() => {
  brandsRequest?.abort()
  productsRequest?.abort()
})
</script>

<style scoped lang="scss">
.brands-page {
  max-width: 1280px;
  margin: 0 auto;
  padding: 28px 48px 44px;
}
.brands-status {
  padding: 40px 12px;
  text-align: center;
  color: #88717d;
}
.brands-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-top: 18px;
}
.brand-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 110px;
  padding: 20px 56px 20px 18px;
  overflow: hidden;
  border: 1px solid #f43f6f;
  border-radius: 12px;
  background:
    linear-gradient(157deg, transparent 0 33%, #ffffff12 33.5% 38%, transparent 38.5%),
    linear-gradient(20deg, transparent 0 67%, #ffffff10 67.5% 72%, transparent 72.5%),
    radial-gradient(
      110% 90% at 5% -18%,
      transparent 64%,
      #ffffff25 65%,
      #ffffff08 67%,
      transparent 69%
    ),
    linear-gradient(112deg, #ff4f79 0%, #f43f6f 48%, #df2f61 100%);
  color: #fff;
  text-decoration: none;
  transition:
    border-color 0.18s,
    box-shadow 0.18s;
}
.brand-card:hover {
  border-color: #d42658;
  box-shadow: 0 5px 16px #df2f6126;
}
.brand-card {
  flex-direction: column;
  justify-content: center;
  gap: 12px;
  min-height: 180px;
  padding: 20px 48px;
  text-align: center;
}
.brand-card__visual {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 100%;
  max-width: 160px;
  height: 80px;
}
.brand-card__visual img {
  display: block;
  width: 100%;
  object-fit: contain;
  object-position: center;
}
.brand-card__initial {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #ffffff20;
  color: #fff;
  font-size: 23px;
  font-weight: 700;
}
.brand-tabs {
  display: flex;
  gap: 8px;
  padding: 8px 0 16px;
  margin-bottom: 14px;
  overflow-x: auto;
}
.brand-tabs a {
  flex-shrink: 0;
  padding: 8px 15px;
  border: 1px solid #eadfe4;
  border-radius: 24px;
  color: #715b66;
  text-decoration: none;
  font-size: 12px;
}
.brand-tabs a.is-active {
  border-color: #df2f61;
  background: #df2f61;
  color: #fff;
}
.brand-results {
  margin: 0 0 16px;
  color: #88717d;
  font-size: 12px;
}
.brand-products {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 12px;
}
.brand-product {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
  border: 1px solid #f0dfe6;
  border-radius: 10px;
  color: inherit;
  text-decoration: none;
}
.brand-product:hover {
  border-color: #df2f61;
}
.brand-product__image {
  position: relative;
  height: 165px;
  padding: 12px;
}
.brand-product__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.brand-product__image .sale-badge {
  position: absolute;
  z-index: 1;
  top: 8px;
  left: 8px;
}
.brand-pagination {
  margin-top: 28px;
}
.brand-card:focus-visible,
.brand-product:focus-visible,
.brand-tabs a:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: 3px;
}
@media (max-width: 1199px) {
  .brand-products {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
@media (max-width: 1023px) {
  .brands-page {
    padding: 16px 16px 100px;
  }
  .brands-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .brand-products {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
@media (max-width: 599px) {
  .brands-grid {
    gap: 10px;
  }
  .brand-card {
    min-height: 160px;
    gap: 10px;
    padding: 16px 30px;
  }
  .brand-card__visual {
    max-width: 110px;
    height: 64px;
  }
  .brand-card strong {
    font-size: 14px;
  }
  .brand-products {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
  }
  .brand-product__image {
    height: 140px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .brand-card {
    transition: none;
  }
}
</style>
