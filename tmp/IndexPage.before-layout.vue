<template>
  <q-page class="store-home" :style="{ backgroundImage: `url(${storeBackground})` }">
    <div class="store-container main-banner-container">
      <q-skeleton v-if="bannersLoading" class="banner-loading" />
      <StoreBannerCarousel v-else :banners="mainBanners" />
    </div>
    <div class="store-container">
      <section class="category-rail" aria-label="Categorías rápidas">
        <button
          v-for="item in quickCategories"
          :key="item.label"
          type="button"
          class="category-rail__item"
        >
          <span class="category-rail__circle"
            ><img :src="item.icon" :alt="item.label" loading="lazy"
          /></span>
          <span class="category-rail__label">{{ item.label }}</span>
        </button>
        <button type="button" class="category-rail__item" @click="categoriesOpen = true">
          <span class="category-rail__circle category-rail__circle--all"
            ><q-icon name="chevron_right"
          /></span>
          <span class="category-rail__label">Ver todas</span>
        </button>
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
      <section id="ofertas" class="daily-offers">
        <div class="section-heading">
          <h2><q-icon name="bolt" color="primary" /> OFERTAS DEL DÍA</h2>
          <q-btn
            flat
            dense
            no-caps
            color="primary"
            :label="allOffers ? 'Ver menos' : 'Ver todas las ofertas'"
            icon-right="chevron_right"
            @click="allOffers = !allOffers"
          />
        </div>
        <div class="offer-grid" :class="{ 'offer-grid-all': allOffers }">
          <article
            v-for="product in allOffers ? mockOfferProducts : mockOfferProducts.slice(0, 5)"
            :key="product.id_producto"
            class="offer-card"
          >
            <div class="offer-image">
              <span class="offer-discount">-{{ product.porcentaje_descuento }}%</span
              ><img :src="product.imagen" :alt="product.nombre" loading="lazy" />
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
        </div>
      </section>
      <StoreBannerCarousel :banners="advertisingBanners" strip label="Publicidad" />
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
            <img :src="product.imagen" :alt="product.nombre" loading="lazy" /><span
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
            <img :src="product.imagen" :alt="product.nombre" /><span
              >{{ product.nombre }}<strong>{{ money(product.precio_final) }}</strong></span
            >
          </button></q-card-section
        ></q-card
      ></q-dialog
    >
  </q-page>
</template>
<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import storeBackground from 'src/assets/storefront-background.webp'
import { publicAsset } from 'src/utils/publicAsset'

import StoreMenuProductImage from 'src/components/StoreMenuProductImage.vue'
import { menuFallbackCategories } from 'src/data/categoryMenu'
import StoreBannerCarousel from 'src/components/StoreBannerCarousel.vue'
import { getBanners } from 'src/services/bannerApi'
import { getCategories } from 'src/services/categoryApi'
import { mockOfferProducts, mockBestSellers, mockNewestProducts } from 'src/data/mockStore'
const categoriesOpen = ref(false)
const route = useRoute()
const router = useRouter()
const selectedCategory = ref(null)
const selectedSubcategory = ref(null)
const selectedProduct = ref(null)
const selectedCollection = ref(null)
const allOffers = ref(false)
const banners = ref([])
const bannersLoading = ref(true)
const bannerRequest = new AbortController()
const categoryRequest = new AbortController()
const categories = ref(menuFallbackCategories)

const mainBanners = computed(() => banners.value.filter((banner) => Number(banner.tipo) === 1))
const advertisingBanners = computed(() =>
  banners.value
    .filter((banner) => Number(banner.tipo) === 2 && Number(banner.posicion) === 1)
    .slice(0, 1),
)
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
onBeforeUnmount(() => {
  bannerRequest.abort()
  categoryRequest.abort()
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
const benefits = [
  { icon: 'local_shipping', title: 'Envíos a todo México', description: 'Rápidos y seguros' },
  { icon: 'verified_user', title: 'Compra 100% segura', description: 'Tus datos protegidos' },
  { icon: 'handshake', title: 'Mayoreo y menudeo', description: 'Precios especiales' },
  { icon: 'category', title: '+10,000 productos', description: 'De las mejores marcas' },
]
const quickCategories = [
  { label: 'Uñas', icon: publicAsset('iconos/uñas.webp') },
  { label: 'Pestañas', icon: publicAsset('iconos/pestañas.webp') },
  { label: 'Cejas', icon: publicAsset('iconos/cejas.webp') },
  { label: 'Cabello', icon: publicAsset('iconos/cabello.webp') },
  { label: 'Eléctricos', icon: publicAsset('iconos/ico-electricos.webp') },
  { label: 'Cosmetiqueras', icon: publicAsset('iconos/cosmetiqueras.webp') },
  { label: 'Masajeadores', icon: publicAsset('iconos/masajeadores.webp') },
  { label: 'Accesorios', icon: publicAsset('iconos/otros.webp') },
]
const collections = [
  { title: 'MÁS VENDIDOS', products: mockBestSellers },
  { title: 'NUEVOS PRODUCTOS', products: mockNewestProducts },
]
const money = (value) => value.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
</script>
<style lang="scss" scoped>
.banner-loading {
  width: 100%;
  height: auto;
  aspect-ratio: 3 / 1;
  border-radius: 28px;
}
.main-banner-container {
  padding-top: 10px !important;
  padding-bottom: 14px !important;
}

.store-home {
  position: relative;
  background-color: #fff9fb;
  // Mantiene ambas ondas laterales visibles, incluso en pantallas estrechas.
  background-position: center top;
  background-size: 100% auto;
  background-repeat: no-repeat;
  color: var(--ink);
}
.store-home::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 0% 18%, rgba(236, 197, 214, 0.54) 0, rgba(236, 197, 214, 0) 30%),
    radial-gradient(circle at 100% 68%, rgba(239, 204, 220, 0.5) 0, rgba(239, 204, 220, 0) 33%),
    linear-gradient(
      180deg,
      rgba(255, 251, 253, 0.35) 0%,
      rgba(255, 255, 255, 0.06) 52%,
      rgba(255, 249, 252, 0.28) 100%
    );
}
.store-home > * {
  position: relative;
  z-index: 1;
}
.store-container {
  max-width: 1280px;
  margin: auto;
  padding: 0 24px;
  background: transparent;
}

.category-rail {
  display: flex;
  gap: 14px;
  justify-content: space-between;
  padding: 24px 6px 28px;
  overflow-x: auto;
  scrollbar-width: none;
}
.category-rail::-webkit-scrollbar {
  display: none;
}
@media (min-width: 900px) {
  .category-rail {
    flex-wrap: nowrap;
    overflow-x: visible;
  }
}
.category-rail__item {
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  width: 86px;
  border: 0;
  background: transparent;
  color: #342a2f;
  transition: transform 0.2s;
}
.category-rail__item:hover {
  transform: translateY(-4px);
}
.category-rail__circle {
  display: grid;
  width: 78px;
  height: 78px;
  place-items: center;
  border: 1px solid #f3e2e8;
  border-radius: 50%;
  background: #fff7fa;
  box-shadow: 0 12px 24px rgba(181, 78, 112, 0.16);
  transition:
    box-shadow 0.2s,
    border-color 0.2s;
}
.category-rail__item:hover .category-rail__circle {
  border-color: #e3a1b7;
  box-shadow: 0 16px 30px rgba(181, 78, 112, 0.26);
}
.category-rail__circle img {
  width: 46px;
  height: 46px;
  object-fit: contain;
}
.category-rail__circle .q-icon {
  color: var(--q-primary);
  font-size: 28px;
}
.category-rail__circle--all {
  background: linear-gradient(135deg, #f08aaa, var(--q-primary));
}
.category-rail__circle--all .q-icon {
  color: #fff;
  font-size: 30px;
}
.category-rail__label {
  font-size: 13px;
  font-weight: 800;
  line-height: 1.25;
  text-align: center;
}
.store-category-showcase {
  margin: 0 -16px 24px;
}
.daily-offers {
  scroll-margin-top: 200px;
}
@media (max-width: 599px) {
  .store-category-showcase {
    margin: 0 -8px 18px;
  }
}
.store-benefits {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  padding: 22px 0;
  background: rgba(255, 255, 255, 0.8);
  border-top: 1px solid var(--store-border);
  border-bottom: 1px solid var(--store-border);
}
.store-benefit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  border-right: 1px solid var(--store-border);
}
.store-benefit:last-child {
  border: 0;
}
.store-benefit > .q-icon {
  font-size: 31px;
  color: var(--q-primary);
}
.store-benefit strong,
.store-benefit span {
  display: block;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.35;
}
.store-benefit span {
  color: #666;
  font-size: 12px;
  font-weight: 400;
}
.daily-offers {
  padding: 17px 0;
  scroll-margin-top: 180px;
}
.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;
}
.section-heading h2 {
  font-size: 16px;
  line-height: 1.3;
  margin: 0;
  font-weight: 700;
}
.section-heading h2 .q-icon {
  margin-right: 8px;
  font-size: 23px;
}
.section-heading .q-btn {
  font-size: 11px;
}
.offer-grid {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 64px) / 5);
  gap: 16px;
  overflow-x: auto;
  padding: 3px 2px 6px;
}
.offer-grid-all {
  grid-auto-flow: row;
  grid-template-columns: repeat(5, minmax(0, 1fr));
}
.offer-card {
  background: #fffdfe;
  border: 1px solid var(--store-border);
  box-shadow: 0 10px 20px rgba(171, 108, 136, 0.1);
  border-radius: 9px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.offer-image {
  height: 160px;
  position: relative;
  padding: 17px;
  background: linear-gradient(180deg, #ffffff 0%, #fff6fa 100%);
}
.offer-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.offer-discount {
  position: absolute;
  left: 5px;
  top: 5px;
  background: linear-gradient(180deg, #d57697 0%, #c85f87 100%);
  color: white;
  font-size: 11px;
  font-weight: 700;
  border-radius: 4px;
  padding: 2px 5px;
}
.offer-body {
  padding: 10px 14px 13px;
  display: flex;
  flex: 1;
  flex-direction: column;
}
.offer-card h3 {
  font-size: 12px;
  line-height: 1.35;
  margin: 0 0 4px;
  font-weight: 600;
  min-height: 32px;
}
.offer-card p {
  font-size: 11px;
  color: #666;
  margin: 0 0 8px;
}
.offer-card del {
  font-size: 11px;
  color: #999;
  margin-top: auto;
}
.offer-price {
  font-size: 17px;
  line-height: 1.3;
  margin-bottom: 7px;
}
.offer-card .q-btn {
  background: var(--store-button-gradient) !important;
  width: 100%;
  min-height: 30px;
  border-radius: 5px;
  font-size: 11px;
}
.discovery-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.2fr;
  gap: 18px;
  padding-bottom: 32px;
}
.discovery-panel {
  background: linear-gradient(180deg, #fffcfe 0%, var(--store-paper) 100%);
  border: 1px solid var(--store-border);
  box-shadow: 0 10px 22px rgba(171, 108, 136, 0.1);
  border-radius: 10px;
  padding: 17px;
}
.discovery-panel h2 {
  font-size: 14px;
}
.mini-product {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border: 0;
  border-bottom: 1px solid var(--store-border);
  background: transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
}
.mini-product img {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 8px;
  background: white;
}
.mini-product span {
  flex: 1;
}
.mini-product strong {
  display: block;
  margin-top: 6px;
}
.shipping-panel {
  background: linear-gradient(125deg, #7a4158 0%, #bb6387 100%);
  border-radius: 10px;
  padding: 24px;
  color: white;
  box-shadow: 0 16px 28px rgba(96, 47, 66, 0.28);
}
.shipping-panel h2 {
  font-size: 22px;
  font-style: italic;
  line-height: 1.2;
  margin: 12px 0;
}
.shipping-panel p {
  font-size: 13px;
}
.store-dialog {
  width: 460px;
  max-width: 94vw;
}
.detail-image {
  width: 100%;
  height: 230px;
  object-fit: contain;
}
@media (max-width: 1023px) {
  .store-container {
    padding: 0 18px;
  }

  .store-benefit {
    flex-direction: column;
    gap: 7px;
    text-align: center;
  }
  .store-benefit span {
    display: none;
  }
  .store-benefit strong {
    font-size: 11px;
    max-width: 100px;
  }
  .offer-grid {
    grid-auto-columns: calc((100% - 32px) / 3);
  }
  .offer-grid-all {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .discovery-grid {
    grid-template-columns: 1fr 1fr;
  }
  .shipping-panel {
    grid-column: 1/-1;
  }
}
@media (max-width: 599px) {
  .store-benefits {
    margin: 0 -8px;
    padding: 17px 0;
  }
  .store-benefit > .q-icon {
    font-size: 24px;
  }
  .store-benefit strong {
    font-size: 10px;
    max-width: 73px;
  }
  .section-heading h2 {
    font-size: 14px;
  }
  .section-heading .q-btn {
    font-size: 10px;
  }
  .daily-offers {
    padding-top: 16px;
  }
  .offer-grid {
    gap: 10px;
    grid-auto-columns: calc((100% - 20px) / 3);
  }
  .offer-image {
    height: 112px;
    padding: 15px 6px 5px;
  }
  .offer-body {
    padding: 7px;
  }
  .offer-card h3 {
    font-size: 10px;
    min-height: 40px;
  }
  .offer-card p {
    font-size: 9px;
  }
  .offer-price {
    font-size: 14px;
  }
  .offer-card .q-btn {
    font-size: 9px;
    padding: 2px;
  }
  .offer-card :deep(.q-btn .q-icon) {
    display: none;
  }
  .discovery-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
}
</style>
