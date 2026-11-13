<template>
  <q-page class="store-home" :style="{ backgroundImage: `url(${storeBackground})` }">
    <div class="store-container main-banner-container">
      <q-skeleton v-if="bannersLoading" class="banner-loading" />
      <StoreBannerCarousel v-else :banners="mainBanners" />
    </div>
    <div class="store-container store-container--content">
      <section id="ofertas" class="daily-offers">
        <div class="section-heading">
          <h2>
            <q-icon name="bolt" color="primary" />
            <span class="gt-xs">OFERTAS DEL DIA</span>
            <span class="lt-sm">Ofertas del día</span>
            <RouterLink to="/ofertas" class="section-heading__link">
              Ver todas las ofertas <q-icon name="chevron_right" />
            </RouterLink>
          </h2>
          <div class="section-heading__actions">
            <div v-if="!productsLoading.offers && offerPageCount > 1" class="offer-carousel-dots">
              <button
                v-for="page in offerPageCount"
                :key="page"
                type="button"
                :aria-label="`Ir a ofertas ${page}`"
                :aria-current="offerPage === page - 1 ? 'true' : undefined"
                @click="goToOfferPage(page - 1)"
              />
            </div>
          </div>
        </div>
        <LoadingSkeleton v-if="productsLoading.offers" variant="rail" label="Cargando ofertas..." />
        <div v-else-if="productErrors.offers" class="home-load-error" role="alert">
          <p>No pudimos cargar las ofertas.</p><q-btn flat no-caps color="primary" label="Reintentar" @click="loadHomeProducts" />
        </div>
        <div v-else-if="offerProducts.length" class="offer-carousel-shell">
          <q-btn
            unelevated
            round
            icon="chevron_left"
            class="offer-carousel-arrow offer-carousel-arrow--previous"
            aria-label="Ofertas anteriores"
            @click="scrollOffers(-1)"
          />
          <div ref="offerRail" class="offer-grid" @scroll="updateOfferPagination">
            <article
              v-for="product in offerProducts.slice(0, 20)"
              :key="product.id_producto"
              class="offer-card"
              role="button"
              tabindex="0"
              @click="openProduct(product)"
              @keydown.enter="openProduct(product)"
              @keydown.space.prevent="openProduct(product)"
            >
              <div class="offer-image">
                <ProductBadge class="card-sale-badge" :product="product" />
                <img v-image-skeleton
                  :src="product.imagen"
                  :alt="product.nombre"
                  loading="lazy"
                  @error="useFallbackImage"
                />
              </div>
              <ProductCardInfo :product="product" />
            </article>
            <RouterLink to="/ofertas" class="offer-card offer-card--see-all">
              <span class="offer-card--see-all__icon"><q-icon name="arrow_forward" /></span>
              <strong>Ver todos</strong>
              <small>Todas las ofertas</small>
            </RouterLink>
          </div>
          <q-btn
            unelevated
            round
            icon="chevron_right"
            class="offer-carousel-arrow offer-carousel-arrow--next"
            aria-label="Ofertas siguientes"
            @click="scrollOffers(1)"
          />
        </div>
      </section>
      <section id="nuevos" class="daily-offers">
        <div class="section-heading">
          <h2>
            <q-icon name="new_releases" color="primary" />
            <span class="gt-xs">PRODUCTOS NUEVOS</span>
            <span class="lt-sm">Productos nuevos</span>
            <RouterLink to="/nuevos" class="section-heading__link">
              Ver todos los nuevos <q-icon name="chevron_right" />
            </RouterLink>
          </h2>
          <div class="section-heading__actions">
            <div v-if="!productsLoading.newest && newPageCount > 1" class="offer-carousel-dots">
              <button
                v-for="page in newPageCount"
                :key="page"
                type="button"
                :aria-label="`Ir a productos nuevos ${page}`"
                :aria-current="newPage === page - 1 ? 'true' : undefined"
                @click="goToNewPage(page - 1)"
              />
            </div>
          </div>
        </div>
        <LoadingSkeleton v-if="productsLoading.newest" variant="rail" label="Cargando productos nuevos..." />
        <div v-else-if="productErrors.newest" class="home-load-error" role="alert">
          <p>No pudimos cargar los productos nuevos.</p><q-btn flat no-caps color="primary" label="Reintentar" @click="loadHomeProducts" />
        </div>
        <div v-else-if="newestProducts.length" class="offer-carousel-shell">
          <q-btn
            unelevated
            round
            icon="chevron_left"
            class="offer-carousel-arrow offer-carousel-arrow--previous"
            aria-label="Productos nuevos anteriores"
            @click="scrollNewProducts(-1)"
          />
          <div ref="newRail" class="offer-grid" @scroll="updateNewPagination">
            <article
              v-for="product in newestProducts.slice(0, 20)"
              :key="product.id_producto"
              class="offer-card"
              role="button"
              tabindex="0"
              @click="openProduct(product)"
              @keydown.enter="openProduct(product)"
              @keydown.space.prevent="openProduct(product)"
            >
              <div class="offer-image">
                <div class="card-badges">
                  <NewBadge />
                  <ProductBadge
                    :product="product"
                  />
                </div>
                <img v-image-skeleton
                  :src="product.imagen"
                  :alt="product.nombre"
                  loading="lazy"
                  @error="useFallbackImage"
                />
              </div>
              <ProductCardInfo :product="product" />
            </article>
            <RouterLink to="/nuevos" class="offer-card offer-card--see-all">
              <span class="offer-card--see-all__icon"><q-icon name="arrow_forward" /></span>
              <strong>Ver todos</strong>
              <small>Productos nuevos</small>
            </RouterLink>
          </div>
          <q-btn
            unelevated
            round
            icon="chevron_right"
            class="offer-carousel-arrow offer-carousel-arrow--next"
            aria-label="Productos nuevos siguientes"
            @click="scrollNewProducts(1)"
          />
        </div>
      </section>
      <section class="featured-products" aria-label="Productos mas vistos">
        <div class="section-heading featured-heading">
          <div>
            <h2><q-icon name="visibility" color="primary" /> Más vistos actualmente</h2>
            <p>Descubre los favoritos de nuestra comunidad.</p>
          </div>
        </div>
        <LoadingSkeleton v-if="productsLoading.viewed" variant="featured" :count="8" label="Cargando productos más vistos..." />
        <div v-else-if="productErrors.viewed" class="home-load-error" role="alert">
          <p>No pudimos cargar los productos más vistos.</p><q-btn flat no-caps color="primary" label="Reintentar" @click="loadHomeProducts" />
        </div>
        <div v-else-if="featuredViewedProduct" class="featured-products__layout">
          <article class="featured-product-main" role="button" tabindex="0"
            @click="openProduct(featuredViewedProduct)"
            @keydown.enter="openProduct(featuredViewedProduct)"
            @keydown.space.prevent="openProduct(featuredViewedProduct)">
            <div class="featured-product-main__copy">
              <span class="featured-product-main__eyebrow"><q-icon name="auto_awesome" /> DESTACADO</span>
              <h3 :title="featuredViewedProduct.nombre">{{ featuredViewedProduct.nombre }}</h3>
              <p class="featured-product-main__code">{{ featuredViewedProduct.codigo }}</p>
              <ProductViews :product="featuredViewedProduct" class="featured-product-main__visits" />
              <div class="featured-product-main__prices">
                <strong :class="{ 'price--sale': featuredViewedProduct.tiene_descuento }">{{ money(featuredViewedProduct.precio_final) }}</strong>
                <del v-if="featuredViewedProduct.tiene_descuento">{{ money(featuredViewedProduct.precio_normal) }}</del>
              </div>
              <span class="featured-product-main__hint">Descubrir producto <q-icon name="arrow_forward" /></span>
            </div>
            <div class="featured-product-main__image">
              <ProductBadge
                class="card-sale-badge"
                :product="featuredViewedProduct"
              />
              <img v-image-skeleton :src="featuredViewedProduct.imagen" :alt="featuredViewedProduct.nombre" loading="lazy" @error="useFallbackImage" />
            </div>
          </article>
          <div class="featured-products__grid">
            <article v-for="product in secondaryViewedProducts" :key="product.id_producto"
              class="featured-mini-card" role="button" tabindex="0"
              @click="openProduct(product)"
              @keydown.enter="openProduct(product)"
              @keydown.space.prevent="openProduct(product)">
              <div class="featured-mini-card__image">
                <ProductBadge class="card-sale-badge" :product="product" />
                <img v-image-skeleton :src="product.imagen" :alt="product.nombre" loading="lazy" @error="useFallbackImage" />
              </div>
              <ProductCardInfo :product="product" compact />
            </article>
          </div>
        </div>
      </section>
      <section class="store-benefits" aria-label="Beneficios">
        <div v-for="item in benefits" :key="item.title" class="store-benefit">
          <q-icon :name="item.icon" />
          <div>
            <strong>{{ item.title }}</strong
            ><span>{{ item.description }}</span>
          </div>
        </div>
      </section>
      <div class="store-promotions">
        <StoreBannerCarousel :banners="advertisingBanners" strip label="Publicidad" />
      </div>
      <TikTokCarousel />

      <section class="random-categories" aria-labelledby="random-categories-title">
        <div class="random-categories__heading">
          <div>
            <span>SELECCIONADO PARA TI</span>
            <h2 id="random-categories-title">Quizás te pueda interesar</h2>
          </div>
        </div>

        <div
          v-if="randomSubcategoriesLoading && !randomSubcategoryGroups.length"
          class="category-groups"
        >
          <div
            v-for="item in 3"
            :key="item"
            class="category-products-row category-products-row--loading"
          >
            <q-skeleton class="category-products-row__intro" />
            <div class="category-products-grid">
              <q-skeleton v-for="card in 6" :key="card" class="category-product-card" />
            </div>
          </div>
        </div>

        <div v-else class="category-groups">
          <article
            v-for="group in randomSubcategoryGroups"
            :key="group.id_subcategoria"
            class="category-products-row"
          >
            <header class="category-products-row__intro">
              <div>
                <small>{{ group.nombre_categoria }}</small>
                <h3>{{ group.nombre_subcategoria }}</h3>
                <span class="category-products-row__rule" aria-hidden="true" />
                <p>6 productos seleccionados</p>
              </div>
            </header>

            <div class="category-products-grid">
              <article
                v-for="product in group.productos"
                :key="product.id_producto"
                role="button"
                tabindex="0"
                class="category-product-card"
                @click="openProduct(product)"
                @keydown.enter="openProduct(product)"
                @keydown.space.prevent="openProduct(product)"
              >
                <ProductBadge
                  class="card-sale-badge"
                  :product="product"
                />
                <span class="category-product-card__image">
                  <img v-image-skeleton
                    :src="product.imagen"
                    :alt="product.nombre"
                    loading="lazy"
                    @error="useFallbackImage"
                  />
                </span>
                <ProductCardInfo :product="product" compact />
              </article>
            </div>
          </article>
        </div>
      </section>
    </div>
    <q-dialog v-model="categoriesOpen"
      ><q-card class="store-dialog"
        ><q-card-section class="row items-center"
          ><h2 class="text-h6 q-ma-none">Todas las categorías</h2>
          <q-space /><q-btn
            flat
            round
            icon="close"
            v-close-popup
            aria-label="Cerrar" /></q-card-section
        ><q-list
          ><q-item v-for="cat in categories" :key="cat.slug" clickable @click="openCategory(cat)"
            ><q-item-section>{{ cat.name }}</q-item-section
            ><q-item-section side
              ><q-icon name="chevron_right" /></q-item-section></q-item></q-list></q-card
    ></q-dialog>
    <q-dialog :model-value="!!selectedCategory" @update:model-value="closeCategoryDialog"
      ><q-card v-if="selectedCategory" class="store-dialog"
        ><q-card-section class="row items-center"
          ><h2 class="text-h6 q-ma-none">
            {{ selectedSubcategory?.name || selectedCategory.name }}
          </h2>
          <q-space /><q-btn
            flat
            round
            icon="close"
            v-close-popup
            aria-label="Cerrar" /></q-card-section
        ><q-card-section
          ><q-list
            v-if="!selectedSubcategory && selectedCategory.subcategories?.length"
            dense
            separator
          >
            <q-item v-for="sub in selectedCategory.subcategories" :key="sub.slug">
              <q-item-section>{{ sub.name }}</q-item-section>
            </q-item>
          </q-list>
          <p class="q-mb-none q-mt-md">
            Consulta los productos disponibles y precios de esta categoría con nuestro equipo.
          </p></q-card-section
        ><q-card-actions align="right"
          ><q-btn
            no-caps
            color="primary"
            label="Consultar catálogo"
            href="tel:+525524954670" /></q-card-actions></q-card
    ></q-dialog>
    <q-dialog :model-value="!!selectedCollection" @update:model-value="selectedCollection = null"
      ><q-card v-if="selectedCollection" class="store-dialog"
        ><q-card-section class="row items-center"
          ><h2 class="text-h6 q-ma-none">{{ selectedCollection.title }}</h2>
          <q-space /><q-btn
            flat
            round
            icon="close"
            v-close-popup
            aria-label="Cerrar" /></q-card-section
        ><q-card-section
          ><button
            v-for="product in selectedCollection.products"
            :key="product.id_producto"
            class="mini-product"
            @click="openProduct(product)"
          >
            <img v-image-skeleton :src="product.imagen" :alt="product.nombre" @error="useFallbackImage" /><span
              >{{ product.nombre }}<ProductViews :product="product" /><NewBadge /><ProductBadge
                :product="product"
              /><strong :class="{ 'price--sale': product.tiene_descuento }">{{
                money(product.precio_final)
              }}</strong></span
            >
          </button></q-card-section
        ></q-card
      ></q-dialog
    >
  </q-page>
</template>
<script setup>
import { useProductNavigation } from 'src/services/productNavigation'
const openProduct = useProductNavigation()
import ProductCardInfo from 'src/components/ProductCardInfo.vue'
import ProductViews from 'src/components/ProductViews.vue'
import { computed, nextTick, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ProductBadge from 'src/components/ProductBadge.vue'
import NewBadge from 'src/components/NewBadge.vue'
import TikTokCarousel from 'src/components/TikTokCarousel.vue'
import StoreBannerCarousel from 'src/components/StoreBannerCarousel.vue'
import { getBanners } from 'src/services/bannerApi'
import { getCategories } from 'src/services/categoryApi'
import { getProducts, getRandomSubcategoryProducts } from 'src/services/productApi'
import { publicAsset } from 'src/utils/publicAsset'
const categoriesOpen = ref(false)
const route = useRoute()
const router = useRouter()
const selectedCategory = ref(null)
const selectedSubcategory = ref(null)
const selectedCollection = ref(null)
const banners = ref([])
const bannersLoading = ref(true)
const offerRail = ref(null)
const offerPage = ref(0)
const offerPageCount = ref(1)
const newRail = ref(null)
const newPage = ref(0)
const newPageCount = ref(1)
const bannerRequest = new AbortController()
const categoryRequest = new AbortController()
let productRequest = null
const categories = ref([])
const offerProducts = ref([])
const newestProducts = ref([])
const mostViewedProducts = ref([])
const productsLoading = ref({ offers: true, newest: true, viewed: true })
const productErrors = ref({})
const featuredViewedProduct = computed(() => mostViewedProducts.value[0] || null)
const secondaryViewedProducts = computed(() => mostViewedProducts.value.slice(1, 9))
const randomSubcategoryGroups = ref([])
const randomSubcategoriesLoading = ref(true)
const fallbackProductImage = publicAsset('img/product-no-image.svg')
let randomCategoryRequest = null

const mainBanners = computed(() => banners.value.filter((banner) => Number(banner.tipo) === 1).slice(0, 5))
const advertisingBanners = computed(() =>
  banners.value
    .filter((banner) => Number(banner.tipo) === 2 && Number(banner.posicion) === 1)
    .slice(0, 1),
)
onMounted(async () => {
  const timeout = setTimeout(() => bannerRequest.abort(), 15000)
  try {
    const results = await Promise.allSettled([
      getBanners({ tipo: 1, maxItems: 5, signal: bannerRequest.signal }),
      getBanners({ tipo: 2, maxItems: 1, signal: bannerRequest.signal }),
    ])
    banners.value = results.flatMap((result) => result.status === 'fulfilled' ? result.value : [])
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar los banners.')
  } finally {
    clearTimeout(timeout)
    bannersLoading.value = false
  }
})
onMounted(async () => {
  const timeout = setTimeout(() => categoryRequest.abort(), 15000)
  try {
    const apiCategories = await getCategories({ signal: categoryRequest.signal })
    categories.value = apiCategories
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar las categorias.')
  } finally {
    clearTimeout(timeout)
  }
})
async function loadHomeProducts() {
  productRequest?.abort()
  const current = new AbortController()
  productRequest = current
  productsLoading.value = { offers: true, newest: true, viewed: true }
  productErrors.value = {}
  const timeout = setTimeout(() => current.abort(), 15000)
  const collections = [
    ['offers', 'ofertas', 20, offerProducts],
    ['newest', 'nuevos', 20, newestProducts],
    ['viewed', 'vistos', 12, mostViewedProducts],
  ]
  try {
    await Promise.allSettled(collections.map(async ([key, coleccion, limit, target]) => {
      try {
        const result = await getProducts({ solo_disponibles: true, coleccion, limit, signal: current.signal })
        if (productRequest === current) target.value = result.productos
      } catch {
        if (productRequest === current) productErrors.value[key] = true
      } finally {
        if (productRequest === current) {
          productsLoading.value[key] = false
          await nextTick()
          updateOfferPagination()
          updateNewPagination()
        }
      }
    }))
  } finally { clearTimeout(timeout) }
}
onMounted(loadHomeProducts)
onMounted(() => {
  loadRandomSubcategories()
})
onMounted(() => {
  updateCarouselsPagination()
  window.addEventListener('resize', updateCarouselsPagination)
})
onBeforeUnmount(() => {
  bannerRequest.abort()
  categoryRequest.abort()
  productRequest?.abort()
  randomCategoryRequest?.abort()
  window.removeEventListener('resize', updateCarouselsPagination)
})

async function loadRandomSubcategories() {
  randomCategoryRequest?.abort()
  randomCategoryRequest = new AbortController()
  randomSubcategoriesLoading.value = true

  try {
    randomSubcategoryGroups.value = await getRandomSubcategoryProducts({
      subcategoryLimit: 3,
      productLimit: 6,
      signal: randomCategoryRequest.signal,
    })
  } catch (error) {
    if (error.name === 'AbortError') return

    try {
      const [apiCategories, ...recentPages] = await Promise.all([
        getCategories({ signal: randomCategoryRequest.signal }),
        ...[1, 2, 3, 4].map((page) =>
          getProducts({
            solo_disponibles: true,
            coleccion: 'recientes',
            page,
            limit: 100,
            signal: randomCategoryRequest.signal,
          }),
        ),
      ])
      const recentProducts = [
        ...new Map(
          recentPages
            .flatMap((response) => response.productos)
            .map((product) => [product.id_producto, product]),
        ).values(),
      ]
      randomSubcategoryGroups.value = buildRandomSubcategoryGroups(
        apiCategories,
        recentProducts,
        3,
        6,
      )
    } catch (fallbackError) {
      if (fallbackError.name !== 'AbortError') {
        console.warn('No se pudieron cargar los productos por categoria.')
      }
    }
  } finally {
    randomSubcategoriesLoading.value = false
  }
}

function buildRandomSubcategoryGroups(categoryList, products, subcategoryLimit, productLimit) {
  const subcategories = categoryList.flatMap((category) =>
    (category.subcategories || []).map((subcategory) => ({
      ...subcategory,
      category,
    })),
  )

  return shuffleItems(subcategories)
    .map((subcategory) => {
      return {
        id_categoria: subcategory.category.id_categoria,
        nombre_categoria: subcategory.category.name,
        id_subcategoria: subcategory.id_subcategoria,
        nombre_subcategoria: subcategory.name,
        productos: shuffleItems(
          products.filter(
            (product) => Number(product.sub_categoria) === Number(subcategory.id_subcategoria),
          ),
        ).slice(0, productLimit),
      }
    })
    .filter((subcategory) => subcategory.productos.length === productLimit)
    .slice(0, subcategoryLimit)
}

function shuffleItems(items) {
  const shuffled = [...items]
  for (let index = shuffled.length - 1; index > 0; index -= 1) {
    const randomIndex = Math.floor(Math.random() * (index + 1))
    ;[shuffled[index], shuffled[randomIndex]] = [shuffled[randomIndex], shuffled[index]]
  }
  return shuffled
}

function openCategory(category) {
  selectedSubcategory.value = null
  selectedCategory.value = category
  categoriesOpen.value = false
}
function openSubcategory(category, subcategory) {
  selectedSubcategory.value = subcategory
  selectedCategory.value = category
}

function closeCategoryDialog(value) {
  if (value) return
  selectedSubcategory.value = null
  selectedCategory.value = null
  const { category, subcategory, ...query } = route.query
  if (category || subcategory) router.replace({ path: route.path, query, hash: route.hash })
}

function scrollOffers(direction) {
  scrollRail(offerRail.value, direction)
}

function scrollNewProducts(direction) {
  scrollRail(newRail.value, direction)
}

function goToOfferPage(page) {
  goToRailPage(offerRail.value, page)
}

function goToNewPage(page) {
  goToRailPage(newRail.value, page)
}

function scrollRail(rail, direction) {
  if (!rail) return
  rail.scrollBy({
    left: direction * rail.clientWidth,
    behavior: 'smooth',
  })
}

function goToRailPage(rail, page) {
  if (!rail) return
  rail.scrollTo({
    left: page * rail.clientWidth,
    behavior: 'smooth',
  })
}

function updateRailPagination(rail, pageRef, countRef) {
  if (!rail) return
  const maxScroll = Math.max(0, rail.scrollWidth - rail.clientWidth)
  countRef.value = Math.max(1, Math.ceil(rail.scrollWidth / rail.clientWidth))
  pageRef.value =
    maxScroll === 0 ? 0 : Math.round((rail.scrollLeft / maxScroll) * (countRef.value - 1))
}

function updateOfferPagination() {
  updateRailPagination(offerRail.value, offerPage, offerPageCount)
}

function updateNewPagination() {
  updateRailPagination(newRail.value, newPage, newPageCount)
}

function updateCarouselsPagination() {
  updateOfferPagination()
  updateNewPagination()
}

function useFallbackImage(event) {
  const image = event?.target
  if (!image || image.dataset.fallbackApplied) return
  image.dataset.fallbackApplied = 'true'
  image.src = fallbackProductImage
  image.classList.add('product-image--fallback')
}

watch(
  [() => route.query, categories],
  ([query]) => {
    const category = categories.value.find((item) => item.slug === query.category)
    if (!category) return
    const subcategory = category.subcategories?.find((item) => item.slug === query.subcategory)
    if (subcategory) openSubcategory(category, subcategory)
    else openCategory(category)
  },
  { immediate: true },
)
watch(offerProducts, async () => {
  await nextTick()
  updateOfferPagination()
})
watch(newestProducts, async () => {
  await nextTick()
  updateNewPagination()
})
const benefits = [
  { icon: 'local_shipping', title: 'Envíos a todo México', description: 'Rápidos y seguros' },
  { icon: 'verified_user', title: 'Compra 100% segura', description: 'Tus datos protegidos' },
  { icon: 'handshake', title: 'Mayoreo y menudeo', description: 'Precios especiales' },
  { icon: 'category', title: '+10,000 productos', description: 'De las mejores marcas' },
]
const money = (value) => value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
</script>
<style lang="scss" scoped>
.store-home {
  color: var(--ink);
  background-color: #fffaf9;
  background-position: center top;
  background-size: 100% auto;
  background-repeat: repeat-y;
}

.store-container {
  width: min(100%, 1500px);
  margin: 0 auto;
  padding-inline: 28px;
  background: transparent;
}

.main-banner-container {
  padding-top: 10px !important;
  padding-bottom: 16px !important;
}

.banner-loading {
  width: 100%;
  aspect-ratio: 3 / 1;
  min-height: 380px;
  border-radius: 0;
  background: #f4e4e5;
}

.main-banner-container :deep(.sn-hero-carousel),
.main-banner-container :deep(.q-carousel) {
  overflow: hidden;
  border-radius: 0 0 18px 18px;
}

/* Categorías: tarjetas rectangulares blush, como la referencia */
.category-showcase {
  margin-top: 0;
  padding: 2px 0 16px;
}

.category-rail {
  display: grid;
  grid-template-columns: repeat(9, minmax(92px, 1fr));
  gap: 18px;
  overflow-x: auto;
  padding: 0 2px 4px;
  scrollbar-width: none;
}

.category-rail::-webkit-scrollbar {
  display: none;
}

.category-rail__item {
  min-width: 0;
  padding: 0;
  border: 0;
  background: transparent;
  color: #21191b;
  cursor: pointer;
  font: inherit;
  text-align: center;
}

.category-rail__tile {
  display: grid;
  width: 100%;
  aspect-ratio: 1.18 / 1;
  place-items: center;
  overflow: hidden;
  border: 1px solid #eedbdd;
  border-radius: 14px;
  background: linear-gradient(145deg, #f9e8e9, #f4dfe1);
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease,
    border-color 0.18s ease;
}

.category-rail__tile img {
  width: 74%;
  height: 74%;
  object-fit: contain;
  filter: saturate(0.88);
}

.category-rail__tile .q-icon {
  color: #9d4e58;
  font-size: 34px;
}

.category-rail__item:hover .category-rail__tile {
  transform: translateY(-3px);
  border-color: #dbaeb3;
  box-shadow: 0 10px 22px rgba(105, 47, 56, 0.1);
}

.category-rail__tile--more {
  background: #fbf0f0;
}

.category-rail__label {
  display: block;
  margin-top: 9px;
  overflow: hidden;
  color: #2b2224;
  font-size: 12px;
  font-weight: 650;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Beneficios */
.store-benefits {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 0;
  padding: 18px 0;
  border-top: 1px solid #eadcdd;
  border-bottom: 1px solid #eadcdd;
  background: rgba(255, 255, 255, 0.72);
}

.store-benefit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  min-width: 0;
  padding: 0 18px;
  border-right: 1px solid #eadcdd;
}

.store-benefit:last-child {
  border-right: 0;
}

.store-benefit > .q-icon {
  flex: 0 0 auto;
  color: #a9545f;
  font-size: 31px;
}

.store-benefit strong,
.store-benefit span {
  display: block;
  line-height: 1.3;
}

.store-benefit strong {
  color: #21191b;
  font-size: 13px;
  font-weight: 750;
}

.store-benefit span {
  margin-top: 2px;
  color: #6e6264;
  font-size: 11px;
  font-weight: 400;
}

/* Ofertas */
.daily-offers {
  padding: 20px 0 16px;
  scroll-margin-top: 185px;
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.section-heading h2 {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin: 0;
  color: #21191b;
  font-size: 17px;
  font-weight: 800;
  line-height: 1.25;
  letter-spacing: -0.2px;
}

.section-heading h2 .q-icon {
  color: #a9545f !important;
  font-size: 22px;
}

.section-heading__link {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  margin-left: 8px;
  color: #dc3965;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.2;
  text-decoration: none;
  text-transform: none;
}

.section-heading__link .q-icon {
  margin: 0;
  color: currentColor !important;
  font-size: 16px;
}

.section-heading__link:hover {
  color: #de7c97;
}

.section-heading .q-btn {
  color: #984650 !important;
  font-size: 11px;
  font-weight: 700;
}

.section-heading__actions {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-left: auto;
}

.offer-carousel-dots {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: 4px;
}

.offer-carousel-dots button {
  width: 7px;
  height: 7px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: #f8c6d4;
  cursor: pointer;
}

.offer-carousel-dots button[aria-current='true'] {
  background: #f43f6f;
}

.offer-carousel-shell {
  position: relative;
}

.offer-carousel-arrow {
  position: absolute;
  z-index: 5;
  top: 42%;
  width: 58px;
  height: 58px;
  min-height: 58px;
  transform: translateY(-50%);
  background: #fff !important;
  color: #de7c97 !important;
  box-shadow: 0 8px 24px rgba(54, 31, 37, 0.16) !important;
}

.offer-carousel-arrow :deep(.q-icon) {
  font-size: 32px;
}

.offer-carousel-arrow--previous {
  left: -29px;
}

.offer-carousel-arrow--next {
  right: -29px;
}

.offer-carousel-arrow:hover {
  color: #dc3965 !important;
  box-shadow: 0 10px 28px rgba(54, 31, 37, 0.22) !important;
}

.offer-grid {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 80px) / 6);
  gap: 16px;
  overflow-x: auto;
  padding: 14px 2px 22px;
  scroll-behavior: smooth;
  scroll-snap-type: x proximity;
  scrollbar-width: none;
}

.offer-grid::-webkit-scrollbar {
  display: none;
}

.offer-grid-all {
  grid-auto-flow: row;
  grid-template-columns: repeat(6, minmax(0, 1fr));
}

.offer-card {
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #e8dddd;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 7px rgba(63, 35, 39, 0.035);
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease,
    border-color 0.18s ease;
  scroll-snap-align: start;
}

.offer-card:hover {
  transform: translateY(-3px);
  border-color: #dec4c7;
  box-shadow: 0 10px 26px rgba(63, 35, 39, 0.09);
}

.offer-card--see-all {
  display: flex;
  min-height: 100%;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 22px 16px;
  border-color: #f3c5d0;
  background:
    radial-gradient(circle at 78% 18%, #ffffff70, transparent 28%),
    linear-gradient(145deg, #fff6f8 0%, #f8d7df 100%);
  color: #dc3965;
  text-align: center;
  text-decoration: none;
}

.offer-card--see-all__icon {
  display: grid;
  width: 54px;
  height: 54px;
  place-items: center;
  border-radius: 50%;
  background: #de7c97;
  color: #fff;
  box-shadow: 0 12px 24px rgba(220, 57, 101, 0.2);
}

.offer-card--see-all__icon .q-icon {
  font-size: 28px;
}

.offer-card--see-all strong,
.offer-card--see-all small {
  display: block;
}

.offer-card--see-all strong {
  color: #21191b;
  font-size: 17px;
  font-weight: 800;
}

.offer-card--see-all small {
  color: #dc3965;
  font-size: 11px;
  font-weight: 700;
}

.offer-card--see-all:hover {
  border-color: #de7c97;
  background:
    radial-gradient(circle at 78% 18%, #ffffff80, transparent 28%),
    linear-gradient(145deg, #fff 0%, #f6c9d4 100%);
}

.offer-image {
  position: relative;
  height: 188px;
  padding: 18px 18px 10px;
  background: linear-gradient(180deg, #fff 0%, #fffafa 100%);
}

.offer-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.22s ease;
}

.product-image--fallback {
  padding: 26px !important;
  object-fit: contain !important;
  opacity: 0.72;
  filter: saturate(0.9);
}

.offer-card:hover .offer-image img {
  transform: scale(1.07);
}

.offer-discount {
  position: absolute;
  z-index: 2;
  top: 9px;
  left: 9px;
  padding: 4px 8px;
  border-radius: 6px;
  background: #a9545f;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  box-shadow: none;
}

.offer-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: 10px 14px 14px;
}

.offer-card h3 {
  min-height: 36px;
  margin: 0 0 5px;
  color: #20191a;
  font-size: 12px;
  font-weight: 750;
  line-height: 1.35;
}

.offer-card p {
  margin: 0 0 8px;
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
  color: #221a1c;
  font-size: 17px;
  font-weight: 850;
  line-height: 1.25;
}

.offer-card .q-btn {
  width: 100%;
  min-height: 34px;
  border-radius: 7px;
  background: #a9545f !important;
  box-shadow: none !important;
  font-size: 10.5px;
  font-weight: 700;
}

.offer-card .q-btn:hover {
  background: #8e444e !important;
}

.featured-products {
  padding: 8px 0 18px;
}

.featured-products__layout {
  display: grid;
  grid-template-columns: minmax(0, 1.18fr) minmax(520px, 1fr);
  gap: 12px;
  overflow: hidden;
  padding: 10px;
  border: 1px solid #f3d1d8;
  border-radius: 14px;
  background: linear-gradient(135deg, #fff8fa 0%, #fff 54%, #ffeef3 100%);
}

.featured-product-main {
  position: relative;
  display: grid;
  cursor: pointer;
  min-height: 385px;
  max-height: none;
  grid-template-columns: minmax(0, 0.9fr) minmax(240px, 1fr);
  gap: 12px;
  overflow: hidden;
  padding: 28px;
  border-radius: 10px;
  background:
    radial-gradient(circle at 78% 70%, rgba(222, 124, 151, 0.18), transparent 34%),
    linear-gradient(145deg, #fff 0%, #fff2f5 100%);
}

.featured-product-main__copy {
  position: relative;
  z-index: 1;
  display: grid;
  min-width: 0;
  align-items: flex-start;
  grid-template-rows: auto auto auto minmax(0, 1fr) auto auto auto;
}

.featured-product-main__badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
  padding: 8px 12px;
  border-radius: 7px;
  background: #de7c97;
  color: #fff;
  font-size: 12px;
  font-weight: 750;
}

.featured-product-main__copy > small {
  margin-bottom: 8px;
  color: #6c5c61;
  font-size: 11px;
}

.featured-product-main__views {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 999px;
  background: #fff;
  color: #dc3965 !important;
  font-size: 11px !important;
  font-weight: 800;
  box-shadow: 0 8px 18px rgba(222, 124, 151, 0.16);
}

.featured-product-main__views .q-icon {
  color: #de7c97 !important;
  font-size: 15px;
}

.featured-product-main h3 {
  display: -webkit-box;
  max-width: 380px;
  margin: 0 0 8px;
  overflow: hidden;
  color: #161115;
  font-size: clamp(24px, 2.4vw, 38px);
  font-weight: 850;
  line-height: 1.05;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.featured-product-main p {
  display: -webkit-box;
  max-width: 310px;
  margin: 0;
  overflow: hidden;
  color: #5e5357;
  font-size: 13px;
  line-height: 1.35;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.featured-product-main strong {
  margin-top: 12px;
  color: #08070a;
  font-size: 34px;
  font-weight: 850;
  line-height: 1.05;
}

.featured-product-main del {
  margin-top: 4px;
  color: #95878c;
  font-size: 14px;
}

.featured-product-main .q-btn {
  min-width: 220px;
  min-height: 46px;
  margin-top: 14px;
  align-self: end;
  border-radius: 8px;
  background: #de7c97 !important;
  box-shadow: none !important;
  font-weight: 750;
}

.featured-product-main__image {
  display: grid;
  width: 100%;
  max-width: 390px;
  height: 315px;
  align-self: center;
  justify-self: center;
  place-items: center;
  overflow: hidden;
  border: 1px solid #f4e3e7;
  border-radius: 12px;
  background: radial-gradient(circle at 50% 100%, rgba(222, 124, 151, 0.14), transparent 44%), #fff;
  box-shadow:
    0 22px 38px rgba(222, 124, 151, 0.13),
    inset 0 0 0 10px #fff8fa;
}

.featured-product-main__image img {
  width: 100%;
  height: 100%;
  padding: 20px;
  object-fit: contain;
  transition: transform 0.28s ease;
}

.featured-product-main:hover .featured-product-main__image img {
  transform: scale(1.065);
}

.featured-products__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.featured-mini-card {
  position: relative;
  display: grid;
  min-width: 0;
  overflow: hidden;
  cursor: pointer;
  min-height: 185px;
  grid-template-rows: 88px auto;
  padding: 10px 12px 12px;
  border: 1px solid #f0dce1;
  border-radius: 10px;
  background: #fff;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.featured-mini-card__image {
  display: grid;
  width: 100%;
  height: 88px;
  place-items: center;
  overflow: hidden;
  border-radius: 8px;
  background: linear-gradient(180deg, #fff 0%, #fff8fa 100%);
}

.featured-mini-card__image img {
  width: 100%;
  height: 100%;
  padding: 6px;
  object-fit: contain;
  transition: transform 0.25s ease;
}

.featured-mini-card:hover {
  transform: translateY(-2px);
  border-color: #f3bdca;
  box-shadow: 0 9px 20px rgba(190, 42, 87, 0.09);
}

.featured-mini-card:hover .featured-mini-card__image img {
  transform: scale(1.08);
}

.featured-mini-card h3 {
  margin: 7px 0 3px;
  color: #161115;
  font-size: 10.5px;
  font-weight: 750;
  line-height: 1.22;
  overflow-wrap: anywhere;
}

.featured-mini-card p {
  overflow: hidden;
  margin: 0;
  color: #7d7074;
  font-size: 9.5px;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.featured-mini-card strong {
  display: block;
  margin-top: 5px;
  padding-right: 34px;
  color: #de3a67;
  font-size: 16px;
  font-weight: 850;
  line-height: 1.15;
}

.featured-mini-card button {
  position: absolute;
  right: 11px;
  bottom: 11px;
  display: grid;
  width: 30px;
  height: 30px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: #de7c97;
  color: #fff;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease;
}

.featured-mini-card button:hover {
  transform: translateY(-2px);
  background: #dc3965;
}

/* Descubrimiento */
.discovery-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.15fr;
  gap: 18px;
  padding: 18px 0 36px;
}

.discovery-panel {
  overflow: hidden;
  border: 1px solid #eadcdd;
  border-radius: 12px;
  background: linear-gradient(145deg, #fff 0%, #fff8f8 100%);
  padding: 18px;
}

.discovery-panel h2 {
  font-size: 14px;
}

.mini-product {
  display: flex;
  width: 100%;
  align-items: center;
  cursor: pointer;
  gap: 12px;
  padding: 9px 0;
  border: 0;
  border-bottom: 1px solid #eee3e4;
  background: transparent;
  color: #21191b;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  text-align: left;
}

.mini-product:last-child {
  border-bottom: 0;
}

.mini-product img {
  width: 70px;
  height: 70px;
  flex: 0 0 auto;
  border: 1px solid #f1e6e7;
  border-radius: 10px;
  background: #fff;
  object-fit: contain;
  transition: transform 0.22s ease;
}

.mini-product:hover img {
  transform: scale(1.08);
}

.mini-product span {
  flex: 1;
}

.mini-product strong {
  display: block;
  margin-top: 6px;
  color: #8f414b;
}

.shipping-panel {
  overflow: hidden;
  border-radius: 12px;
  background:
    radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.16), transparent 34%),
    linear-gradient(135deg, #a9545f 0%, #743740 100%);
  padding: 28px;
  color: #fff;
  box-shadow: 0 12px 28px rgba(92, 41, 48, 0.12);
}

.shipping-panel h2 {
  margin: 12px 0 8px;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 24px;
  font-style: normal;
  font-weight: 700;
  line-height: 1.15;
}

/* Productos aleatorios agrupados por categoria */
.random-categories {
  margin-top: 10px;
  padding: 34px 0 48px;
  border-top: 1px solid #f0e5e8;
  background: linear-gradient(180deg, #fff 0%, #fffafc 52%, #fff 100%);
}

.random-categories__heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f2e4e8;
}

.random-categories__heading span {
  display: block;
  margin-bottom: 5px;
  color: #de3a67;
  font-size: 10px;
  font-weight: 850;
}

.random-categories__heading h2 {
  margin: 0;
  color: #171315;
  font-size: clamp(22px, 2.4vw, 30px);
  font-weight: 850;
  line-height: 1.1;
}

.random-categories__shuffle {
  color: #d73764 !important;
  font-size: 12px;
  font-weight: 750;
}

.category-groups {
  display: grid;
  gap: 28px;
}

.category-products-row {
  display: grid;
  grid-template-columns: 190px minmax(0, 1fr);
  gap: 12px;
  min-width: 0;
}

.category-products-row__intro {
  position: relative;
  display: flex;
  min-height: 280px;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
  padding: 24px 20px;
  border: 1px solid #e9e1e3;
  border-left: 3px solid #de7c97;
  border-radius: 8px;
  background: linear-gradient(145deg, #fff 0%, #f7f5f6 100%);
  box-shadow: 0 4px 14px rgba(55, 35, 42, 0.045);
}

.category-products-row__intro small {
  display: block;
  margin-bottom: 12px;
  color: #c94c70;
  font-size: 10px;
  font-weight: 850;
  line-height: 1.25;
  text-transform: uppercase;
}

.category-products-row__intro h3 {
  margin: 0;
  overflow-wrap: anywhere;
  color: #21191c;
  font-size: 23px;
  font-weight: 850;
  line-height: 1.08;
}

.category-products-row__rule {
  display: block;
  width: 38px;
  height: 3px;
  margin: 18px 0 13px;
  border-radius: 2px;
  background: #de7c97;
}

.category-products-row__intro p {
  margin: 0;
  color: #766b6e;
  font-size: 10px;
  font-weight: 700;
}

.category-products-grid {
  display: grid;
  min-width: 0;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
}

.category-product-card {
  position: relative;
  display: grid;
  min-width: 0;
  min-height: 280px;
  overflow: hidden;
  grid-template-rows: 162px minmax(0, 1fr);
  padding: 0;
  border: 1px solid #eee3e6;
  border-radius: 8px;
  background: #fff;
  color: #21191b;
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.category-product-card:hover {
  transform: translateY(-4px);
  border-color: #f0b9c7;
  box-shadow: 0 12px 26px rgba(170, 43, 82, 0.1);
}

.category-product-card__discount {
  position: absolute;
  z-index: 2;
  top: 10px;
  left: 10px;
  padding: 4px 7px;
  border-radius: 5px;
  background: #eb6f91;
  color: #fff;
  font-size: 9px;
  font-weight: 850;
}

.category-product-card__image {
  display: grid;
  width: 100%;
  height: 162px;
  place-items: center;
  overflow: hidden;
  padding: 12px;
  background: linear-gradient(180deg, #fff, #fff9fb);
}

.category-product-card__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.28s ease;
}

.category-product-card:hover .category-product-card__image img {
  transform: scale(1.08);
}

.category-product-card__body {
  position: relative;
  display: flex;
  min-width: 0;
  flex-direction: column;
  padding: 11px 42px 12px 13px;
  border-top: 1px solid #f5ecee;
}

.category-product-card__body > strong {
  display: -webkit-box;
  min-height: 32px;
  overflow: hidden;
  font-size: 12px;
  font-weight: 750;
  line-height: 1.3;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.category-product-card__body > small {
  overflow: hidden;
  margin-top: 5px;
  color: #8e8285;
  font-size: 9px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-product-card__body > del {
  margin-top: auto;
  padding-top: 7px;
  color: #a99ca0;
  font-size: 9px;
}

.category-product-card__price {
  margin-top: 3px;
  padding-top: 8px;
  color: #df3565;
  font-size: 18px;
  font-weight: 850;
  line-height: 1;
}

.category-product-card__body > small + .category-product-card__price {
  margin-top: auto;
}

.category-product-card__action {
  position: absolute;
  right: 12px;
  bottom: 12px;
  display: grid;
  width: 28px;
  height: 28px;
  place-items: center;
  border-radius: 50%;
  background: #f9dce4;
  color: #d73764;
  transition:
    background 0.2s ease,
    color 0.2s ease;
}

.category-product-card:hover .category-product-card__action {
  background: #e95f84;
  color: #fff;
}

.category-products-row--loading .category-products-row__intro,
.category-products-row--loading .category-product-card {
  border: 0;
}

@media (max-width: 1199px) {
  .category-products-grid {
    grid-template-columns: repeat(6, minmax(145px, 1fr));
    overflow-x: auto;
    padding: 4px 0 10px;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
  }

  .category-products-grid::-webkit-scrollbar {
    display: none;
  }

  .category-product-card {
    scroll-snap-align: start;
  }
}

@media (max-width: 900px) {
  .category-products-row {
    grid-template-columns: 1fr;
  }

  .category-products-row__intro {
    min-height: 104px;
    padding: 18px 20px;
  }

  .category-products-row__intro h3 {
    font-size: 21px;
  }

  .category-products-row__rule {
    margin: 12px 0 9px;
  }
}

@media (max-width: 599px) {
  .random-categories {
    padding: 24px 0 32px;
  }

  .random-categories__heading {
    align-items: flex-start;
  }

  .random-categories__shuffle :deep(.q-btn__content span) {
    display: none;
  }

  .category-groups {
    gap: 26px;
  }

  .category-products-grid {
    grid-template-columns: repeat(6, minmax(145px, 1fr));
  }

  .category-product-card {
    min-height: 252px;
    grid-template-rows: 140px minmax(0, 1fr);
  }

  .category-product-card__image {
    height: 140px;
    padding: 9px;
  }

  .category-product-card__body {
    padding: 9px 36px 10px 10px;
  }

  .category-product-card__price {
    font-size: 16px;
  }

  .category-product-card__action {
    right: 8px;
    bottom: 9px;
  }
}

.shipping-panel p {
  max-width: 330px;
  color: rgba(255, 255, 255, 0.84);
  font-size: 12px;
}

.shipping-panel .q-btn {
  border-radius: 8px;
}

.store-dialog {
  width: 460px;
  max-width: 94vw;
  border-radius: 16px;
}

.detail-image {
  width: 100%;
  height: 230px;
  object-fit: contain;
}

@media (max-width: 1199px) {
  .store-container {
    padding-inline: 20px;
  }

  .category-rail {
    grid-template-columns: repeat(9, 106px);
  }

  .offer-grid {
    grid-auto-columns: calc((100% - 48px) / 4);
  }

  .offer-grid-all {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .featured-products__layout {
    grid-template-columns: 1fr;
  }

  .featured-products__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .featured-product-main {
    min-height: 330px;
    max-height: none;
  }
}

@media (max-width: 1023px) {
  .store-container {
    padding-inline: 16px;
  }

  .main-banner-container {
    padding-inline: 0;
    padding-bottom: 12px !important;
  }

  .main-banner-container :deep(.sn-hero-carousel),
  .main-banner-container :deep(.q-carousel) {
    border-radius: 0;
  }

  .category-rail {
    display: flex;
    gap: 12px;
  }

  .category-rail__item {
    flex: 0 0 102px;
  }

  .store-benefit {
    flex-direction: column;
    gap: 6px;
    padding: 0 8px;
    text-align: center;
  }

  .store-benefit span {
    display: none;
  }

  .store-benefit strong {
    max-width: 110px;
    font-size: 10.5px;
  }

  .offer-grid {
    grid-auto-columns: calc((100% - 32px) / 3);
  }

  .offer-grid-all {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .featured-product-main {
    grid-template-columns: 1fr;
    max-height: none;
  }

  .featured-product-main__image {
    height: 230px;
  }

  .featured-products__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .discovery-grid {
    grid-template-columns: 1fr 1fr;
  }

  .shipping-panel {
    grid-column: 1 / -1;
  }
}

@media (max-width: 599px) {
  .store-home {
    background-image: none !important;
    background: #fffaf9;
  }

  .store-container {
    padding-inline: 12px;
  }

  .main-banner-container {
    padding-inline: 0;
  }

  .banner-loading {
    min-height: 180px;
    border-radius: 0;
  }

  .category-showcase {
    margin-inline: -12px;
    padding: 12px 0 14px;
    background: #fff;
  }

  .category-rail {
    padding-inline: 12px;
  }

  .category-rail__item {
    flex-basis: 82px;
  }

  .category-rail__tile {
    border-radius: 11px;
  }

  .category-rail__label {
    margin-top: 7px;
    font-size: 10.5px;
  }

  .store-benefits {
    margin-inline: -12px;
    padding: 14px 0;
  }

  .store-benefit > .q-icon {
    font-size: 23px;
  }

  .store-benefit strong {
    max-width: 76px;
    font-size: 9px;
  }

  .section-heading h2 {
    font-size: 14px;
  }

  .section-heading .q-btn {
    font-size: 9.5px;
  }

  .daily-offers {
    padding-top: 16px;
  }

  .offer-carousel-arrow {
    width: 42px;
    height: 42px;
    min-height: 42px;
  }

  .offer-carousel-arrow :deep(.q-icon) {
    font-size: 25px;
  }

  .offer-carousel-arrow--previous {
    left: -10px;
  }

  .offer-carousel-arrow--next {
    right: -10px;
  }

  .offer-grid {
    gap: 10px;
    grid-auto-columns: calc((100% - 10px) / 2);
    padding-top: 14px;
    padding-bottom: 22px;
  }

  .offer-grid-all {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .featured-products__layout {
    margin-inline: -2px;
    padding: 8px;
  }

  .featured-product-main {
    min-height: 0;
    max-height: none;
    padding: 18px;
  }

  .featured-product-main h3 {
    font-size: 23px;
  }

  .featured-product-main strong {
    font-size: 28px;
  }

  .featured-product-main .q-btn {
    width: 100%;
    min-width: 0;
  }

  .featured-products__grid {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .featured-mini-card {
    min-height: 165px;
    max-height: none;
    grid-template-rows: 78px auto;
    padding: 9px;
  }

  .featured-mini-card__image {
    height: 78px;
  }

  .featured-mini-card h3 {
    margin-right: 0;
    font-size: 10.5px;
  }

  .featured-mini-card button {
    width: 28px;
    height: 28px;
    right: 8px;
    bottom: 10px;
  }

  .offer-image {
    height: 138px;
    padding: 14px 8px 6px;
  }

  .offer-body {
    padding: 8px 9px 10px;
  }

  .offer-card h3 {
    min-height: 38px;
    font-size: 10.5px;
  }

  .offer-card p,
  .offer-card del {
    font-size: 9px;
  }

  .offer-price {
    font-size: 14px;
  }

  .offer-card .q-btn {
    min-height: 31px;
    padding-inline: 4px;
    font-size: 9px;
  }

  .offer-card :deep(.q-btn .q-icon) {
    display: none;
  }

  .discovery-grid {
    grid-template-columns: 1fr;
    gap: 12px;
    padding-bottom: 24px;
  }

  .shipping-panel {
    grid-column: auto;
  }
}

/* Proporciones de la referencia: hero a sangre y cinco ofertas por fila. */
.store-home {
  background-color: #fff;
}
.store-container {
  max-width: 1280px;
  padding-inline: 48px;
}
.main-banner-container {
  width: min(100%, 1280px);
  max-width: 1280px;
  padding-inline: 48px;
  padding-bottom: 18px !important;
}
.main-banner-container :deep(.q-carousel),
.main-banner-container :deep(.sn-hero-carousel),
.main-banner-container :deep(.banner-carousel:not(.banner-carousel--strip)) {
  border-radius: 10px;
  height: auto !important;
  aspect-ratio: 3 / 1;
  max-height: 395px;
}
.main-banner-container :deep(.q-carousel__slide) {
  background-size: cover;
  background-position: center;
}
.banner-loading {
  aspect-ratio: 3 / 1;
  max-height: 395px;
  min-height: 0;
}
.category-rail {
  grid-template-columns: repeat(9, minmax(0, 1fr));
  gap: 26px;
}
.category-rail__tile {
  border: 0;
  border-radius: 11px;
  background: linear-gradient(135deg, #f0cfd4, #f7e3e6);
}
.category-rail__label {
  font-weight: 500;
}
.store-benefits {
  padding: 18px 0;
  background: rgba(255, 255, 255, 0.94);
  border-color: #f1e7e8;
}
.store-benefit {
  gap: 14px;
  padding: 0 12px;
}
.store-benefit strong {
  font-weight: 600;
}
.store-benefit span {
  color: #383334;
}
.store-benefit > .q-icon {
  color: #914851;
  font-size: 34px;
}
.daily-offers {
  padding: 18px 0 12px;
}
.section-heading {
  margin-bottom: 12px;
}
.section-heading h2 {
  font-size: 18px;
  font-weight: 650;
}
.offer-grid {
  grid-auto-columns: calc((100% - 50px) / 6);
  gap: 10px;
}
.offer-grid-all {
  grid-template-columns: repeat(6, minmax(0, 1fr));
}
.offer-card {
  border-radius: 9px;
  border-color: #eee7e7;
  box-shadow: none;
}
.offer-image {
  height: clamp(120px, 12vw, 170px);
  padding: 12px 14px 0;
  background: #fff;
}
.offer-body {
  padding: 8px 14px 12px;
}
.offer-card h3 {
  font-weight: 600;
  min-height: 32px;
  line-height: 1.2;
}
.offer-card p {
  margin-bottom: 4px;
}
.offer-price {
  color: #111;
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 8px;
}
.offer-card .q-btn {
  border-radius: 5px;
  min-height: 34px;
  background: linear-gradient(180deg, #de3a67, #f45372) !important;
  font-weight: 500;
}
.offer-discount {
  border-radius: 5px;
  font-weight: 500;
}
.store-promotions {
  overflow: hidden;
  border-radius: 9px;
  margin: 0 0 12px;
}
.store-promotions :deep(.q-carousel) {
  border-radius: 9px;
}
.store-discovery {
  border-top: 1px solid #f0e5e6;
  margin-bottom: 12px;
}
.store-discovery > summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  padding: 12px 0;
  color: #8d434f;
  font-size: 12px;
}
.store-discovery[open] > summary .q-icon {
  transform: rotate(180deg);
}
@media (max-width: 1199px) and (min-width: 1024px) {
  .store-container--content {
    padding-inline: 38px;
  }
  .category-rail {
    gap: 22px;
  }
}
@media (max-width: 1023px) {
  .store-container--content {
    padding-inline: 16px;
  }
  .category-rail {
    gap: 12px;
  }
  .offer-grid {
    grid-auto-columns: calc((100% - 20px) / 3);
  }
  .offer-grid-all {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .main-banner-container :deep(.q-carousel),
  .main-banner-container :deep(.sn-hero-carousel),
  .main-banner-container :deep(.banner-carousel:not(.banner-carousel--strip)) {
    max-height: 370px;
  }
}
@media (max-width: 599px) {
  .store-container--content {
    padding-inline: 12px;
  }
  .offer-grid {
    grid-auto-columns: calc((100% - 10px) / 2);
  }
  .offer-grid-all {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .offer-image {
    height: 138px;
  }
  .offer-body {
    padding: 8px 9px 10px;
  }
  .section-heading h2 {
    font-size: 14px;
  }
  .store-benefit {
    padding: 0 5px;
  }
  .store-benefit > .q-icon {
    font-size: 25px;
  }
}

/* Paleta final: rosa Supernova, sin acentos marron/vino. */
.category-rail__tile .q-icon,
.store-benefit > .q-icon,
.section-heading h2 .q-icon,
.mini-product strong,
.store-discovery > summary {
  color: #de7c97 !important;
}

.section-heading .q-btn {
  color: #dc3965 !important;
}

.offer-discount,
.offer-card .q-btn {
  background: #de7c97 !important;
}

.offer-card .q-btn:hover {
  background: #dc3965 !important;
}

.shipping-panel {
  background:
    radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.18), transparent 34%),
    linear-gradient(135deg, #ff4679 0%, #de7c97 100%) !important;
}

/* ========================================================================
   SUPERNOVA — ROSA PREMIUM FINAL (referencia aprobada)
   ======================================================================== */
.store-home {
  background: #fff !important;
  color: #171717 !important;
}

.store-container,
.main-banner-container {
  max-width: 1280px !important;
}

.main-banner-container {
  padding-top: 10px !important;
  padding-bottom: 18px !important;
}

.main-banner-container :deep(.q-carousel),
.main-banner-container :deep(.sn-hero-carousel),
.main-banner-container :deep(.banner-carousel:not(.banner-carousel--strip)) {
  overflow: hidden;
  border-radius: 10px !important;
  box-shadow: 0 10px 28px rgba(202, 48, 94, 0.065) !important;
}

.daily-offers {
  padding: 18px 0 13px !important;
}

.section-heading {
  margin-bottom: 12px !important;
}

.section-heading h2 {
  color: #171717 !important;
  font-size: 18px !important;
  font-weight: 800 !important;
}

.section-heading h2 .q-icon,
.section-heading__link,
.section-heading .q-btn,
.store-benefit > .q-icon,
.store-discovery > summary {
  color: #f43f6f !important;
}

.section-heading__link:hover {
  color: #df2f61 !important;
}

.offer-grid {
  grid-auto-columns: calc((100% - 50px) / 6) !important;
  gap: 10px !important;
  padding-top: 12px !important;
}

.offer-card {
  border: 1px solid #f0e5e8 !important;
  border-radius: 9px !important;
  background: #fff !important;
  box-shadow: 0 2px 8px rgba(74, 34, 48, 0.025) !important;
}

.offer-card:hover {
  transform: translateY(-3px);
  border-color: #f5c8d4 !important;
  box-shadow: 0 10px 24px rgba(190, 42, 87, 0.08) !important;
}

.offer-image {
  background: #fff !important;
}

.offer-discount {
  background: linear-gradient(180deg, #ff6b90 0%, #ee4a75 100%) !important;
  color: #fff !important;
  box-shadow: 0 3px 10px rgba(244, 63, 111, 0.14) !important;
}

.offer-price {
  color: #171717 !important;
}

// En esta colección el precio final siempre representa una oferta.
#ofertas .offer-price {
  color: #16a34a !important;
}

.offer-card .q-btn {
  border-radius: 5px !important;
  background: linear-gradient(180deg, #ee7091 0%, #df6485 100%) !important;
  color: #fff !important;
  box-shadow: none !important;
}

.offer-card .q-btn:hover {
  background: linear-gradient(180deg, #f43f6f 0%, #df2f61 100%) !important;
}

.offer-card--see-all {
  border-color: #ffb8cc !important;
  background:
    radial-gradient(circle at 80% 18%, rgba(255, 255, 255, 0.82), transparent 30%),
    linear-gradient(145deg, #fff6f9 0%, #ffe4ed 100%) !important;
  color: #f43f6f !important;
}

.offer-card--see-all__icon {
  background: #f5658e !important;
  box-shadow: 0 10px 22px rgba(244, 63, 111, 0.16) !important;
}

.offer-card--see-all small {
  color: #f43f6f !important;
}

.offer-carousel-arrow {
  background: #fff !important;
  color: #f06b8e !important;
  box-shadow: 0 8px 24px rgba(93, 48, 62, 0.13) !important;
}

.offer-carousel-arrow:hover {
  color: #f43f6f !important;
}

.featured-products__layout {
  border-color: #f4dce3 !important;
  background: linear-gradient(135deg, #fff9fb 0%, #fff 54%, #fff1f5 100%) !important;
}

.featured-product-main {
  background:
    radial-gradient(circle at 78% 70%, rgba(244, 63, 111, 0.1), transparent 36%),
    linear-gradient(145deg, #fff 0%, #fff5f8 100%) !important;
}

.featured-product-main .q-btn {
  background: linear-gradient(180deg, #ee7091 0%, #df6485 100%) !important;
  color: #fff !important;
}

.featured-mini-card {
  border-color: #f3e1e6 !important;
  background: #fff !important;
}

.featured-mini-card strong,
.featured-mini-card button {
  color: #f43f6f !important;
}

.featured-mini-card button {
  background: #eb7897 !important;
  color: #fff !important;
}

.featured-mini-card button:hover {
  background: #f43f6f !important;
}

.store-benefits {
  border-color: #f1e6e8 !important;
  background: #fff !important;
}

.store-benefit {
  border-color: #f1e6e8 !important;
}

.store-benefit strong {
  color: #171717 !important;
}

.store-benefit span {
  color: #61595c !important;
}

.store-promotions {
  box-shadow: 0 7px 18px rgba(56, 28, 37, 0.04);
}

.store-discovery {
  border-top-color: #f0e5e8 !important;
}

.shipping-panel {
  background:
    radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.18), transparent 34%),
    linear-gradient(135deg, #ff5c83 0%, #f43f6f 50%, #df2f61 100%) !important;
}

@media (max-width: 1023px) {
  .offer-grid {
    grid-auto-columns: calc((100% - 20px) / 3) !important;
  }
}

@media (max-width: 599px) {
  .offer-grid {
    grid-auto-columns: calc((100% - 10px) / 2) !important;
  }
}

.card-sale-badge {
  position: absolute;
  z-index: 2;
  top: 9px;
  left: 9px;
}
.card-badges {
  position: absolute;
  z-index: 2;
  top: 9px;
  left: 9px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}
.featured-mini-card__image {
  position: relative;
}
.offer-price.price--sale,
.featured-mini-card .price--sale,
.mini-product .price--sale,
.featured-product-main .price--sale,
.category-product-card .price--sale {
  color: #16a34a !important;
}

/* Más vistos: protagonista compacto y productos en una cuadrícula equilibrada. */
.featured-products {
  padding: 24px 0;
}
.featured-heading {
  align-items: end;
  gap: 12px;
  margin-bottom: 15px;
}
.featured-heading__eyebrow {
  display: block;
  margin-bottom: 5px;
  color: #d34269;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
}
.featured-heading h2 {
  gap: 7px;
  font-size: clamp(20px, 2vw, 25px);
  text-transform: none;
}
.featured-heading p {
  margin: 5px 0 0;
  color: #82767a;
  font-size: 12px;
}
.featured-heading__mark {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 10px;
  border-radius: 20px;
  background: #fff0f4;
  color: #d33c68;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.06em;
  white-space: nowrap;
}
.featured-heading__mark .q-icon {
  font-size: 16px;
}
.featured-products__layout {
  grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
  gap: 12px;
  overflow: visible;
  padding: 0;
  border: 0 !important;
  border-radius: 0;
  background: transparent !important;
}
.featured-product-main {
  isolation: isolate;
  min-width: 0;
  min-height: 390px;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  align-items: center;
  gap: 12px;
  padding: 24px;
  border: 1px solid #ed4773;
  border-radius: 14px;
  background:
    radial-gradient(
      ellipse 100% 130% at 47% -20%,
      rgba(255, 126, 155, 0.68),
      rgba(255, 90, 131, 0.25) 43%,
      transparent 70%
    ),
    linear-gradient(112deg, #ff4f79 0%, #f43f6f 48%, #df2f61 100%) !important;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.featured-product-main::before {
  content: '';
  position: absolute;
  z-index: 0;
  inset: -12% -8% -18%;
  pointer-events: none;
  background:
    linear-gradient(
      158deg,
      transparent 0 17%,
      rgba(255, 255, 255, 0.06) 17.4% 20.4%,
      transparent 20.8% 34%,
      rgba(255, 255, 255, 0.05) 34.4% 37%,
      transparent 37.4%
    ),
    linear-gradient(
      20deg,
      transparent 0 70%,
      rgba(255, 255, 255, 0.05) 70.4% 73.5%,
      transparent 74%
    ),
    radial-gradient(
      110% 72% at -2% 10%,
      transparent 63%,
      rgba(255, 255, 255, 0.12) 64%,
      rgba(255, 255, 255, 0.035) 66%,
      transparent 68%
    ),
    radial-gradient(
      115% 76% at 103% 94%,
      transparent 60%,
      rgba(255, 255, 255, 0.1) 61%,
      rgba(255, 255, 255, 0.03) 63%,
      transparent 65%
    );
}
.featured-product-main::after {
  content: '';
  position: absolute;
  z-index: 0;
  left: -20%;
  bottom: -35%;
  width: 110%;
  height: 70%;
  pointer-events: none;
  transform: rotate(8deg);
  background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.14), transparent 70%);
  filter: blur(18px);
}
.featured-product-main:hover {
  border-color: #ecaec0;
  box-shadow: 0 10px 25px rgba(167, 76, 103, 0.1);
}
.featured-product-main__copy {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
}
.featured-product-main__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.09em;
}
.featured-product-main__eyebrow .q-icon {
  font-size: 16px;
}
.featured-product-main h3 {
  max-width: none;
  margin: 2px 0 0;
  color: #fff;
  font-size: clamp(20px, 1.9vw, 28px);
  font-weight: 800;
  line-height: 1.14;
  -webkit-line-clamp: 3;
}
.featured-product-main p {
  margin: 0;
  color: #fff;
  font-size: 12px;
  line-height: 1.4;
}
.featured-product-main__prices {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 8px;
  margin-top: 3px;
}
.featured-product-main__prices strong {
  margin: 0;
  padding: 4px 8px;
  border-radius: 7px;
  background: #fff;
  color: #a51e47;
  font-size: clamp(24px, 2.3vw, 31px);
}
.featured-product-main__prices strong.price--sale {
  color: #08742e !important;
}
.featured-product-main__prices del {
  margin: 0;
  color: #fff;
  font-size: 13px;
}
.featured-product-main__hint {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 7px;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
}
.featured-product-main__hint .q-icon {
  font-size: 16px;
}
.featured-product-main__image {
  position: relative;
  z-index: 1;
  height: auto;
  max-width: none;
  aspect-ratio: 1;
  border: 1px solid #f3e3e8;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 8px 22px rgba(150, 30, 64, 0.2);
}
.featured-product-main__image img {
  padding: 12px;
}
.featured-products__grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}
.featured-mini-card {
  min-height: 190px;
  grid-template-rows: 100px 1fr;
  gap: 7px;
  padding: 9px;
  border-color: #f1e3e7 !important;
  border-radius: 12px;
}
.featured-mini-card__image {
  height: 100px;
  background: #fff9fa;
}
.featured-mini-card__image img {
  padding: 8px;
}
.featured-mini-card__body {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
}
.featured-mini-card h3 {
  display: -webkit-box;
  overflow: hidden;
  margin: 0;
  font-size: 11px;
  line-height: 1.25;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
.featured-mini-card p {
  margin-top: 3px;
  color: #9b8d92;
}
.featured-mini-card__prices {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 5px;
  margin-top: auto;
  padding-top: 7px;
}
.featured-mini-card__prices strong {
  margin: 0;
  padding: 0;
  font-size: 15px;
}
.featured-mini-card__prices del {
  color: #aa9ca1;
  font-size: 10px;
}
.featured-product-main:focus-visible,
.featured-mini-card:focus-visible {
  outline: 2px solid #df3c70;
  outline-offset: 3px;
}
@media (max-width: 1023px) {
  .featured-products__layout {
    grid-template-columns: 1fr;
  }
  .featured-product-main {
    min-height: 260px;
  }
  .featured-product-main__image {
    width: min(100%, 250px);
  }
}
@media (max-width: 599px) {
  .featured-products {
    padding: 18px 0;
  }
  .featured-heading__mark {
    font-size: 0;
    padding: 7px;
  }
  .featured-heading__mark .q-icon {
    font-size: 18px;
  }
  .featured-product-main {
    min-height: 0;
    grid-template-columns: minmax(0, 1fr) minmax(0, 0.9fr);
    gap: 10px;
    padding: 16px;
  }
  .featured-product-main__copy {
    gap: 7px;
  }
  .featured-product-main h3 {
    font-size: 19px;
  }
  .featured-product-main p {
    font-size: 11px;
  }
  .featured-product-main__prices strong {
    font-size: 23px;
  }
  .featured-product-main__image {
    height: auto;
  }
  .featured-products__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  .featured-mini-card {
    min-height: 180px;
    grid-template-rows: 95px 1fr;
  }
  .featured-mini-card__image {
    height: 95px;
  }
}
@media (max-width: 599px) {
  .main-banner-container {
    padding-inline: 12px;
  }
  .main-banner-container :deep(.banner-carousel:not(.banner-carousel--strip)) {
    aspect-ratio: var(--mobile-banner-ratio, 16 / 9);
    max-height: none;
  }
}

@media (max-width: 599px) {
  .daily-offers > .section-heading {
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
  }
  .daily-offers > .section-heading h2 {
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    gap: 3px 10px;
    margin: 0;
    color: #343039 !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    line-height: 1.3;
  }
  .daily-offers > .section-heading h2 > .q-icon {
    grid-column: 1;
    grid-row: 1 / 3;
    align-self: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #fff0f5;
    color: #e62e64 !important;
    font-size: 22px;
  }
  .daily-offers > .section-heading h2 > span {
    grid-column: 2;
  }
  .daily-offers > .section-heading .section-heading__link {
    grid-column: 2;
    margin: 0;
    padding: 0;
    color: #d52a5c !important;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.4;
  }
  .daily-offers > .section-heading .section-heading__actions {
    min-width: 0;
    max-width: 84px;
  }
  .daily-offers > .section-heading .offer-carousel-dots {
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 5px;
  }
}

/* Presentación de categorías con los reflejos de la identidad Supernova. */
.offer-card--see-all,
.category-products-row:not(.category-products-row--loading) .category-products-row__intro {
  isolation: isolate;
  overflow: hidden;
  border: 1px solid #ed4773;
  background:
    radial-gradient(ellipse 100% 130% at 47% -20%, rgba(255, 126, 155, .68), rgba(255, 90, 131, .25) 43%, transparent 70%),
    linear-gradient(112deg, #ff4f79 0%, #f43f6f 48%, #df2f61 100%);
  box-shadow: 0 8px 22px rgba(180, 42, 80, .14);
}
.offer-card--see-all::before,
.category-products-row:not(.category-products-row--loading) .category-products-row__intro::before {
  position: absolute;
  z-index: 0;
  inset: -12% -8% -18%;
  background:
    linear-gradient(158deg, transparent 0 17%, rgba(255, 255, 255, .06) 17.4% 20.4%, transparent 20.8% 34%, rgba(255, 255, 255, .05) 34.4% 37%, transparent 37.4%),
    linear-gradient(20deg, transparent 0 70%, rgba(255, 255, 255, .05) 70.4% 73.5%, transparent 74%),
    radial-gradient(110% 72% at -2% 10%, transparent 63%, rgba(255, 255, 255, .12) 64%, rgba(255, 255, 255, .035) 66%, transparent 68%),
    radial-gradient(115% 76% at 103% 94%, transparent 60%, rgba(255, 255, 255, .1) 61%, rgba(255, 255, 255, .03) 63%, transparent 65%);
  pointer-events: none;
  content: '';
}
.category-products-row:not(.category-products-row--loading) .category-products-row__intro > div { position: relative; z-index: 1; }
.category-products-row:not(.category-products-row--loading) .category-products-row__intro small,
.category-products-row:not(.category-products-row--loading) .category-products-row__intro h3,
.category-products-row:not(.category-products-row--loading) .category-products-row__intro p { color: #fff; }
.category-products-row:not(.category-products-row--loading) .category-products-row__rule { background: rgba(255, 255, 255, .85); }

.offer-card.offer-card--see-all {
  position: relative;
  border-color: #ed4773 !important;
  background:
    radial-gradient(ellipse 100% 130% at 47% -20%, rgba(255, 126, 155, .68), rgba(255, 90, 131, .25) 43%, transparent 70%),
    linear-gradient(112deg, #ff4f79 0%, #f43f6f 48%, #df2f61 100%) !important;
}

.offer-card--see-all > * {
  position: relative;
  z-index: 1;
}

.offer-card--see-all strong,
.offer-card--see-all small {
  color: #fff !important;
}

.offer-card--see-all__icon {
  background: rgba(255, 255, 255, .2) !important;
  box-shadow: none !important;
}

/* Tarjetas compactas de producto, compartidas por las tres colecciones. */
.offer-card:not(.offer-card--see-all) .offer-image {
  height: auto;
  aspect-ratio: 1 / 1;
  padding: 12px;
  border-radius: 0;
}
.offer-card:not(.offer-card--see-all) .offer-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.offer-card:not(.offer-card--see-all) .offer-image {
  background: linear-gradient(180deg, #fff, #fff9fb) !important;
}

.featured-mini-card {
  gap: 0;
  padding: 0;
  grid-template-rows: 110px minmax(0, 1fr);
}
.featured-mini-card__image {
  height: 110px;
  background: linear-gradient(180deg, #fff, #fff9fb);
}
.featured-product-main__code {
  text-transform: uppercase;
  font-weight: 700;
}
.featured-product-main__visits,
.featured-product-main__visits :deep(.q-icon) {
  color: #fff;
}
</style>
