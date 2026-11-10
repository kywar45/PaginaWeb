<template>
  <q-page class="store-home">
    <div class="store-container main-banner-container">
      <q-skeleton v-if="bannersLoading" class="banner-loading" />
      <StoreBannerCarousel v-else :banners="mainBanners" />
    </div>
    <div class="store-container">
      <section class="category-showcase" aria-label="Categorías">
        <aside class="category-sidebar">
          <button
            v-for="cat in categories"
            :key="cat.slug"
            class="category-sidebar-item"
            :class="{ 'category-sidebar-item--active': activeCategory?.slug === cat.slug }"
            @mouseenter="activeCategory = cat"
            @click="activeCategory = cat"
          >
            <span class="category-sidebar-icon">
              <img v-if="cat.iconUrl" :src="cat.iconUrl" :alt="cat.name" loading="lazy" />
              <q-icon v-else name="auto_awesome" />
            </span>
            <span>{{ cat.name }}</span>
            <q-icon name="chevron_right" />
          </button>
          <button class="category-offer-link" @click="allOffers = true">
            <span class="category-offer-badge"><q-icon name="percent" /></span>
            <span><strong>Ver todas las ofertas</strong><small>Descuentos especiales cada semana</small></span>
            <q-icon name="chevron_right" />
          </button>
        </aside>
        <div class="category-main">
          <div class="category-showcase-heading">
            <div>
              <p>{{ activeCategory?.name || 'Categorías' }}</p>
              <h2>{{ categoryHeadline }}</h2>
            </div>
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              label="Ver todos los productos"
              icon-right="chevron_right"
              @click="selectedCategory = activeCategory"
            />
          </div>
          <div class="subcategory-grid">
            <button
              v-for="sub in featuredSubcategories"
              :key="sub.slug"
              class="subcategory-card"
              @click="selectedCategory = activeCategory"
            >
              <span class="subcategory-image">
                <img :src="activeCategoryImage" :alt="sub.name" loading="lazy" />
              </span>
              <strong>{{ sub.name }}</strong>
              <small>{{ activeCategory?.name }}</small>
              <q-icon name="chevron_right" />
            </button>
          </div>
          <h3>Productos destacados</h3>
          <div class="category-product-grid">
            <article v-for="product in mockOfferProducts.slice(0, 4)" :key="product.id_producto">
              <span>-{{ product.porcentaje_descuento }}%</span>
              <img :src="product.imagen" :alt="product.nombre" loading="lazy" />
              <h4>{{ product.nombre }}</h4>
              <del>{{ money(product.precio_normal) }}</del>
              <strong>{{ money(product.precio_final) }}</strong>
              <q-btn round unelevated color="primary" icon="shopping_cart" @click="selectedProduct = product" />
            </article>
          </div>
        </div>
        <aside class="category-promo">
          <div class="category-promo-card">
            <p>{{ activeCategory?.name }}</p>
            <strong>Uñas que inspiran</strong>
            <img :src="activeCategoryImage" :alt="activeCategory?.name" loading="lazy" />
            <ul>
              <li><q-icon name="check_circle" /> Calidad profesional</li>
              <li><q-icon name="check_circle" /> Resultados duraderos</li>
              <li><q-icon name="check_circle" /> Amada por expertas</li>
            </ul>
            <q-btn unelevated no-caps color="primary" label="Ver toda la línea" icon-right="arrow_forward" />
          </div>
          <div class="category-mini-benefits">
            <span v-for="item in benefits.slice(0, 3)" :key="item.title">
              <q-icon :name="item.icon" /> {{ item.title }}
            </span>
          </div>
        </aside>
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
    <q-dialog :model-value="!!selectedCategory" @update:model-value="selectedCategory = null"
      ><q-card v-if="selectedCategory" class="store-dialog"
        ><q-card-section class="row items-center"
          ><h2 class="text-h6 q-ma-none">{{ selectedCategory.name }}</h2>
          <q-space /><q-btn
            flat
            round
            icon="close"
            v-close-popup
            aria-label="Cerrar" /></q-card-section
        ><q-card-section
          ><q-list v-if="selectedCategory.subcategories?.length" dense separator>
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
          ><img class="detail-image" :src="selectedProduct.imagen" :alt="selectedProduct.nombre" />
          <h2 class="text-h6">{{ selectedProduct.nombre }}</h2>
          <p>{{ selectedProduct.codigo }}</p>
          <strong class="text-h5">{{ money(selectedProduct.precio_final) }}</strong></q-card-section
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
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import StoreBannerCarousel from 'src/components/StoreBannerCarousel.vue'
import { getBanners } from 'src/services/bannerApi'
import { getCategories } from 'src/services/categoryApi'
import { mockCategories, mockOfferProducts, mockBestSellers, mockNewestProducts } from 'src/data/mockStore'
const categoriesOpen = ref(false)
const selectedCategory = ref(null)
const selectedProduct = ref(null)
const selectedCollection = ref(null)
const allOffers = ref(false)
const banners = ref([])
const bannersLoading = ref(true)
const bannerRequest = new AbortController()
const categoryRequest = new AbortController()
const categories = ref(mockCategories.filter((cat) => cat.slug !== 'all'))
const activeCategory = ref(categories.value[0] || null)
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
    activeCategory.value = apiCategories[0] || null
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
function openCategory(cat) {
  selectedCategory.value = cat
  categoriesOpen.value = false
}
const featuredSubcategories = computed(() => {
  const subs = activeCategory.value?.subcategories || []
  if (subs.length > 0) return subs.slice(0, 10)

  return [{ slug: 'catalogo', name: 'Catálogo', id: 'catalogo' }]
})
const categoryHeadline = computed(() => {
  const name = activeCategory.value?.name || 'Belleza'
  return name.length > 16 ? name : `${name} que inspiran`
})
const activeCategoryImage = computed(() => activeCategory.value?.svgIcon || activeCategory.value?.iconUrl || '')
const benefits = [
  { icon: 'local_shipping', title: 'Envíos a todo México', description: 'Rápidos y seguros' },
  { icon: 'verified_user', title: 'Compra 100% segura', description: 'Tus datos protegidos' },
  { icon: 'handshake', title: 'Mayoreo y menudeo', description: 'Precios especiales' },
  { icon: 'category', title: '+10,000 productos', description: 'De las mejores marcas' },
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
  background-color: #fffafb;
  background-image:
    radial-gradient(
      ellipse 430px 760px at -230px 120px,
      transparent 67%,
      #edb9c74d 69%,
      #fff9fb 70%,
      #efc0ce80 72%,
      transparent 75%
    ),
    radial-gradient(
      ellipse 570px 890px at -250px 160px,
      transparent 69.8%,
      #d8ad9170 70%,
      transparent 70.3%
    ),
    radial-gradient(
      ellipse 600px 960px at calc(100% + 310px) 440px,
      transparent 63%,
      #f5d2dc80 66%,
      #ffffff 68%,
      #edbac866 70%,
      transparent 74%
    ),
    radial-gradient(
      ellipse 650px 1000px at calc(100% + 300px) 460px,
      transparent 69.8%,
      #d8ad9180 70%,
      transparent 70.3%
    ),
    radial-gradient(
      ellipse 320px 440px at -150px 950px,
      #edbdc9 20%,
      #fae1e8 48%,
      #ffffff 49%,
      #edb9c780 51%,
      transparent 53%
    ),
    linear-gradient(130deg, #fff 0%, #fae9ee 8%, #fff 15%, #fffafb 80%, #f8e2e8 94%, #fff 100%);
  background-attachment: fixed;
  color: #342a2f;
}
.store-container {
  max-width: 1280px;
  margin: auto;
  padding: 0 32px;
  background: #ffffffeb;
}

.category-showcase {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr) 250px;
  gap: 22px;
  padding: 18px 0 24px;
}
.category-sidebar {
  border-right: 1px solid #f2dfe6;
  padding-right: 12px;
}
.category-sidebar-item,
.category-offer-link,
.subcategory-card {
  border: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
}
.category-sidebar-item {
  width: 100%;
  min-height: 44px;
  display: grid;
  grid-template-columns: 30px minmax(0, 1fr) 18px;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-bottom: 1px solid #f4edf0;
  border-radius: 6px;
  color: #342a2f;
  text-align: left;
  font-size: 13px;
  font-weight: 600;
}
.category-sidebar-item--active {
  background: linear-gradient(90deg, #fde0ea, #fff4f8);
  color: #b54e70;
}
.category-sidebar-icon {
  width: 25px;
  height: 25px;
  display: grid;
  place-items: center;
}
.category-sidebar-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.category-offer-link {
  width: 100%;
  display: grid;
  grid-template-columns: 48px minmax(0, 1fr) 18px;
  gap: 12px;
  align-items: center;
  margin-top: 24px;
  padding: 15px 14px;
  border: 1px solid #f3c8d8;
  border-radius: 10px;
  background: linear-gradient(135deg, #fff4f8, #fde0ea);
  color: #b54e70;
  text-align: left;
}
.category-offer-link small,
.subcategory-card small {
  display: block;
  color: #6d6268;
  font-size: 11px;
  line-height: 1.25;
}
.category-offer-badge {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #b54e70;
  color: white;
  font-size: 24px;
}
.category-main {
  min-width: 0;
}
.category-showcase-heading {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  margin: 8px 0 22px;
}
.category-showcase-heading p {
  margin: 0;
  font-family: Georgia, serif;
  font-size: 39px;
  line-height: 1;
  color: #3b1724;
}
.category-showcase-heading h2 {
  margin: 5px 0 0;
  color: #6f5962;
  font-size: 13px;
  font-weight: 500;
}
.subcategory-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 20px 18px;
}
.subcategory-card {
  position: relative;
  min-width: 0;
  text-align: left;
  padding: 0 18px 0 0;
}
.subcategory-image {
  width: 100%;
  aspect-ratio: 1;
  display: block;
  overflow: hidden;
  border-radius: 999px;
  margin-bottom: 10px;
  background: linear-gradient(145deg, #fde5ed, #f6cbd8);
}
.subcategory-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.subcategory-card strong {
  display: block;
  color: #231d20;
  font-size: 13px;
  line-height: 1.25;
}
.subcategory-card > .q-icon {
  position: absolute;
  right: 0;
  bottom: 22px;
  color: #d73673;
  font-size: 18px;
}
.category-main > h3 {
  margin: 28px 0 10px;
  font-size: 18px;
  line-height: 1.2;
}
.category-product-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}
.category-product-grid article {
  position: relative;
  padding: 10px 10px 12px;
  border: 1px solid #f1e4e8;
  border-radius: 8px;
  background: white;
}
.category-product-grid article > span {
  position: absolute;
  left: 8px;
  top: 8px;
  padding: 2px 6px;
  border-radius: 4px;
  background: #cf4f78;
  color: white;
  font-size: 11px;
  font-weight: 800;
}
.category-product-grid img {
  width: 100%;
  height: 112px;
  object-fit: contain;
}
.category-product-grid h4 {
  min-height: 34px;
  margin: 6px 0 7px;
  font-size: 12px;
  line-height: 1.35;
}
.category-product-grid del {
  display: block;
  color: #9c9297;
  font-size: 11px;
}
.category-product-grid strong {
  display: block;
  color: #c53667;
  font-size: 17px;
}
.category-product-grid .q-btn {
  position: absolute;
  right: 10px;
  bottom: 12px;
}
.category-promo {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.category-promo-card {
  min-height: 420px;
  padding: 28px 24px;
  border-radius: 10px;
  overflow: hidden;
  background:
    linear-gradient(#fff1f6cc, #f6bed1cc),
    radial-gradient(circle at 80% 20%, #ffffff 0, transparent 35%);
  color: #8d284e;
}
.category-promo-card p {
  margin: 0;
  font-family: Georgia, serif;
  font-size: 36px;
  line-height: 1;
}
.category-promo-card > strong {
  display: block;
  margin-top: 6px;
  letter-spacing: 2px;
  text-transform: uppercase;
}
.category-promo-card img {
  width: 100%;
  height: 170px;
  margin: 18px 0;
  object-fit: contain;
}
.category-promo-card ul {
  list-style: none;
  display: grid;
  gap: 14px;
  margin: 0 0 24px;
  padding: 0;
}
.category-promo-card li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
}
.category-mini-benefits {
  display: grid;
  gap: 10px;
  padding: 18px;
  border-radius: 10px;
  background: #fff0f5;
  color: #b54e70;
}
.category-mini-benefits span {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  font-weight: 700;
}
.store-benefits {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  padding: 22px 0;
  border-top: 1px solid #f4f4f4;
  border-bottom: 1px solid #f4f4f4;
}
.store-benefit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  border-right: 1px solid #eee;
}
.store-benefit:last-child {
  border: 0;
}
.store-benefit > .q-icon {
  font-size: 31px;
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
  background: #fff;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 7px #00000006;
  border-radius: 9px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.offer-image {
  height: 160px;
  position: relative;
  padding: 17px;
  background: #fff;
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
  background: #b54e70;
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
  background: #fcf0f4;
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
  border-bottom: 1px solid #f0dbe3;
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
  background: linear-gradient(120deg, #302126, #452a34);
  border-radius: 10px;
  padding: 24px;
  color: white;
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

  .category-showcase {
    grid-template-columns: 210px minmax(0, 1fr);
    gap: 18px;
  }
  .category-sidebar {
    padding-right: 8px;
  }
  .category-sidebar-item {
    font-size: 12px;
    padding-inline: 9px;
  }
  .subcategory-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .category-promo {
    display: none;
  }
  .category-product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
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
  .category-showcase {
    display: block;
    margin: -14px -18px 0;
    padding: 15px 14px 18px;
    border-radius: 20px 20px 0 0;
    background: white;
  }
  .category-sidebar {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    border: 0;
    padding: 0 0 12px;
    scrollbar-width: none;
  }
  .category-sidebar-item {
    min-width: 116px;
    min-height: 38px;
    grid-template-columns: 22px minmax(0, 1fr);
    gap: 7px;
    border: 1px solid #f2dfe6;
    font-size: 11px;
  }
  .category-sidebar-item > .q-icon {
    display: none;
  }
  .category-offer-link {
    min-width: 132px;
    grid-template-columns: 28px minmax(0, 1fr);
    margin: 0;
    padding: 8px 10px;
    border-radius: 8px;
  }
  .category-offer-link > .q-icon,
  .category-offer-link small {
    display: none;
  }
  .category-offer-badge {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    font-size: 16px;
  }
  .category-showcase-heading {
    margin: 10px 0 14px;
  }
  .category-showcase-heading p {
    font-size: 28px;
  }
  .category-showcase-heading h2,
  .category-showcase-heading .q-btn {
    display: none;
  }
  .subcategory-grid {
    grid-auto-flow: column;
    grid-auto-columns: 86px;
    grid-template-columns: none;
    gap: 12px;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .subcategory-card {
    padding-right: 0;
    text-align: center;
  }
  .subcategory-card > .q-icon {
    display: none;
  }
  .subcategory-card strong {
    font-size: 11px;
  }
  .subcategory-card small {
    display: none;
  }
  .category-main > h3 {
    margin-top: 20px;
    font-size: 15px;
  }
  .category-product-grid {
    grid-auto-flow: column;
    grid-auto-columns: 155px;
    grid-template-columns: none;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .category-product-grid img {
    height: 90px;
  }
  .category-product-grid h4 {
    font-size: 10px;
    min-height: 40px;
  }
  .category-product-grid strong {
    font-size: 14px;
  }
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
