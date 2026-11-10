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
            <q-icon name="bolt" color="primary" /> OFERTAS DEL DIA
            <RouterLink to="/ofertas" class="section-heading__link">
              Ver todas las ofertas <q-icon name="chevron_right" />
            </RouterLink>
          </h2>
          <div class="section-heading__actions">
            <div v-if="offerPageCount > 1" class="offer-carousel-dots">
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
        <div class="offer-carousel-shell">
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
              v-for="product in offerProducts.slice(0, 15)"
              :key="product.id_producto"
              class="offer-card"
            >
              <div class="offer-image">
                <span class="offer-discount">-{{ product.porcentaje_descuento }}%</span
                ><img
                  :src="product.imagen"
                  :alt="product.nombre"
                  loading="lazy"
                  @error="useFallbackImage"
                />
              </div>
              <div class="offer-body">
                <h3>{{ product.nombre }}</h3>
                <p>{{ product.codigo }}</p>
                <del>{{ money(product.precio_normal) }}</del
                ><strong class="offer-price">{{ money(product.precio_final) }}</strong
                ><q-btn
                  unelevated
                  no-caps
                  color="primary"
                  label="Ver producto"
                  icon="shopping_cart"
                  @click="selectedProduct = product"
                />
              </div>
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
            <q-icon name="new_releases" color="primary" /> PRODUCTOS NUEVOS
            <RouterLink to="/nuevos" class="section-heading__link">
              Ver todos los nuevos <q-icon name="chevron_right" />
            </RouterLink>
          </h2>
          <div class="section-heading__actions">
            <div v-if="newPageCount > 1" class="offer-carousel-dots">
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
        <div class="offer-carousel-shell">
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
              v-for="product in newestProducts.slice(0, 15)"
              :key="product.id_producto"
              class="offer-card"
            >
              <div class="offer-image">
                <span v-if="product.porcentaje_descuento" class="offer-discount"
                  >-{{ product.porcentaje_descuento }}%</span
                ><img
                  :src="product.imagen"
                  :alt="product.nombre"
                  loading="lazy"
                  @error="useFallbackImage"
                />
              </div>
              <div class="offer-body">
                <h3>{{ product.nombre }}</h3>
                <p>{{ product.codigo }}</p>
                <del v-if="product.tiene_descuento">{{ money(product.precio_normal) }}</del
                ><strong class="offer-price">{{ money(product.precio_final) }}</strong
                ><q-btn
                  unelevated
                  no-caps
                  color="primary"
                  label="Ver producto"
                  icon="shopping_cart"
                  @click="selectedProduct = product"
                />
              </div>
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
        <div class="section-heading">
          <h2><q-icon name="visibility" color="primary" />MÁS VISTOS ACTUALMENTE</h2>
        </div>
        <div v-if="featuredViewedProduct" class="featured-products__layout">
          <article class="featured-product-main">
            <div class="featured-product-main__copy">
              <h3>{{ featuredViewedProduct.nombre }}</h3>
              <p>{{ featuredViewedProduct.descripcion || featuredViewedProduct.codigo }}</p>
              <strong>{{ money(featuredViewedProduct.precio_final) }}</strong>
              <del v-if="featuredViewedProduct.tiene_descuento">
                {{ money(featuredViewedProduct.precio_normal) }}
              </del>
              <q-btn
                unelevated
                no-caps
                color="primary"
                icon="shopping_cart"
                label="Ver producto"
                @click="selectedProduct = featuredViewedProduct"
              />
            </div>
            <div class="featured-product-main__image">
              <img
                :src="featuredViewedProduct.imagen"
                :alt="featuredViewedProduct.nombre"
                loading="lazy"
                @error="useFallbackImage"
              />
            </div>
          </article>
          <div class="featured-products__grid">
            <article
              v-for="product in secondaryViewedProducts"
              :key="product.id_producto"
              class="featured-mini-card"
            >
              <div class="featured-mini-card__image">
                <img
                  :src="product.imagen"
                  :alt="product.nombre"
                  loading="lazy"
                  @error="useFallbackImage"
                />
              </div>
              <div>
                <h3>{{ product.nombre }}</h3>
                <p>{{ product.codigo }}</p>
                <strong>{{ money(product.precio_final) }}</strong>
              </div>
              <button type="button" aria-label="Ver producto" @click="selectedProduct = product">
                <q-icon name="add" />
              </button>
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
      <details class="store-discovery">
        <summary>Explora más productos y envíos <q-icon name="expand_more" /></summary>
        <section class="discovery-grid">
          <div v-for="collection in collections" :key="collection.title" class="discovery-panel">
            <div class="section-heading">
              <h2>{{ collection.title }}</h2>
              <q-btn
                flat
                dense
                no-caps
                color="primary"
                label="Ver más"
                icon-right="chevron_right"
                @click="selectedCollection = collection"
              />
            </div>
            <button
              v-for="product in collection.products.slice(0, 2)"
              :key="product.id_producto"
              class="mini-product"
              @click="selectedProduct = product"
            >
              <img
                :src="product.imagen"
                :alt="product.nombre"
                loading="lazy"
                @error="useFallbackImage"
              /><span
                >{{ product.nombre }}<strong>{{ money(product.precio_final) }}</strong></span
              ><q-icon name="chevron_right" color="primary" />
            </button>
          </div>
          <div class="shipping-panel">
            <q-icon name="local_shipping" size="34px" />
            <h2>ENVÍOS A TODO MÉXICO</h2>
            <p>Rápidos y seguros, hasta la puerta de tu negocio.</p>
            <q-btn outline no-caps color="white" label="Contáctanos" href="tel:+525545564443" />
          </div>
        </section>
      </details>
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
    <q-dialog :model-value="!!selectedProduct" @update:model-value="selectedProduct = null"
      ><q-card v-if="selectedProduct" class="store-dialog"
        ><q-card-section class="row justify-end"
          ><q-btn flat round icon="close" v-close-popup aria-label="Cerrar" /></q-card-section
        ><q-card-section
          ><StoreMenuProductImage
            v-if="selectedProduct.spriteIndex !== undefined"
            :index="selectedProduct.spriteIndex"
            :label="selectedProduct.nombre"
            style="width: 220px; margin: auto"
          />
          <img
            v-else
            class="detail-image"
            :src="selectedProduct.imagen"
            :alt="selectedProduct.nombre"
            @error="useFallbackImage"
          />
          <h2 class="text-h6">{{ selectedProduct.nombre }}</h2>
          <template v-if="selectedProduct.spriteIndex === undefined"
            ><p>{{ selectedProduct.codigo }}</p>
            <strong class="text-h5">{{ money(selectedProduct.precio_final) }}</strong></template
          >
          <p v-else>
            Consulta la disponibilidad y el precio vigente con nuestro equipo.
          </p></q-card-section
        ><q-card-actions align="right"
          ><q-btn
            no-caps
            color="primary"
            label="Consultar disponibilidad"
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
            @click="selectedProduct = product"
          >
            <img :src="product.imagen" :alt="product.nombre" @error="useFallbackImage" /><span
              >{{ product.nombre }}<strong>{{ money(product.precio_final) }}</strong></span
            >
          </button></q-card-section
        ></q-card
      ></q-dialog
    >
  </q-page>
</template>
<script setup>
import { computed, nextTick, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import StoreMenuProductImage from 'src/components/StoreMenuProductImage.vue'
import { menuFallbackCategories } from 'src/data/categoryMenu'
import StoreBannerCarousel from 'src/components/StoreBannerCarousel.vue'
import { getBanners } from 'src/services/bannerApi'
import { getCategories } from 'src/services/categoryApi'
import { getProducts } from 'src/services/productApi'
import { mockOfferProducts, mockMostViewed, mockNewestProducts } from 'src/data/mockStore'
import { publicAsset } from 'src/utils/publicAsset'
const categoriesOpen = ref(false)
const route = useRoute()
const router = useRouter()
const selectedCategory = ref(null)
const selectedSubcategory = ref(null)
const selectedProduct = ref(null)
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
const productRequest = new AbortController()
const categories = ref(menuFallbackCategories)
const offerProducts = ref(mockOfferProducts)
const newestProducts = ref(mockNewestProducts)
const mostViewedProducts = ref(mockMostViewed)
const fallbackProductImage = publicAsset('img/logob.png')

const mainBanners = computed(() => banners.value.filter((banner) => Number(banner.tipo) === 1))
const advertisingBanners = computed(() =>
  banners.value
    .filter((banner) => Number(banner.tipo) === 2 && Number(banner.posicion) === 1)
    .slice(0, 1),
)
const featuredViewedProduct = computed(() => mostViewedProducts.value[0] || null)
const secondaryViewedProducts = computed(() => mostViewedProducts.value.slice(1, 7))
onMounted(async () => {
  const timeout = setTimeout(() => bannerRequest.abort(), 15000)
  try {
    banners.value = await getBanners({ signal: bannerRequest.signal })
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
onMounted(async () => {
  const timeout = setTimeout(() => productRequest.abort(), 15000)
  try {
    const [offers, newest, viewed] = await Promise.all([
      getProducts({ coleccion: 'ofertas', limit: 20, signal: productRequest.signal }),
      getProducts({ coleccion: 'nuevos', limit: 12, signal: productRequest.signal }),
      getProducts({ coleccion: 'vistos', limit: 12, signal: productRequest.signal }),
    ])
    offerProducts.value = offers.productos
    newestProducts.value = newest.productos
    mostViewedProducts.value = viewed.productos
    await nextTick()
    updateOfferPagination()
    updateNewPagination()
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar los productos.')
  } finally {
    clearTimeout(timeout)
  }
})
onMounted(() => {
  updateCarouselsPagination()
  window.addEventListener('resize', updateCarouselsPagination)
})
onBeforeUnmount(() => {
  bannerRequest.abort()
  categoryRequest.abort()
  productRequest.abort()
  window.removeEventListener('resize', updateCarouselsPagination)
})
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
const collections = computed(() => [{ title: 'NUEVOS PRODUCTOS', products: newestProducts.value }])
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
  background: #d8d8d8;
  cursor: pointer;
}

.offer-carousel-dots button[aria-current='true'] {
  background: #3483fa;
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
  transform: scale(1.025);
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
  min-height: 385px;
  max-height: 385px;
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
  min-height: 185px;
  max-height: 185px;
  grid-template-rows: 88px minmax(0, 1fr) auto;
  padding: 10px 12px 12px;
  border: 1px solid #f0dce1;
  border-radius: 10px;
  background: #fff;
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
}

.featured-mini-card h3 {
  display: -webkit-box;
  min-height: 30px;
  margin: 7px 30px 3px 0;
  overflow: hidden;
  color: #161115;
  font-size: 10.5px;
  font-weight: 750;
  line-height: 1.22;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
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
    max-height: 165px;
    grid-template-rows: 78px minmax(0, 1fr) auto;
    padding: 9px;
  }

  .featured-mini-card__image {
    height: 78px;
  }

  .featured-mini-card h3 {
    margin-right: 28px;
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
</style>
