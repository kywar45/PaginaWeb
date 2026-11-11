<template>
  <q-page class="product-page">
    <PageBreadcrumbs :title="product?.nombre || 'Producto'" :back-to="backTo" :parents="parents" />
    <div v-if="loading" class="product-page__status" role="status">
      <q-spinner color="primary" size="36px" />
      <p>Cargando producto...</p>
    </div>
    <div v-else-if="errorMessage" class="product-page__status" role="alert">
      <q-icon name="inventory_2" size="42px" color="primary" />
      <h1>{{ notFound ? 'Producto no disponible' : 'No pudimos cargar el producto' }}</h1>
      <p>{{ errorMessage }}</p>
      <q-btn v-if="!notFound" no-caps color="primary" label="Reintentar" @click="loadProduct" />
      <q-btn v-else flat no-caps color="primary" label="Explorar categorías" to="/categorias" />
    </div>
    <template v-else-if="product">
      <div class="product-layout">
        <section class="product-gallery" aria-label="Fotos del producto">
          <div class="product-gallery__main">
            <SaleBadge v-if="product.tiene_descuento" :percentage="product.porcentaje_descuento" />
            <button
              type="button"
              class="product-gallery__open"
              aria-label="Ampliar imagen del producto"
              @click="openViewer"
            >
              <img
                :src="imageSource(images[imageIndex])"
                :alt="`${product.nombre}, imagen ${imageIndex + 1}`"
                fetchpriority="high"
                @error="imageFailed"
              />
              <span class="product-gallery__zoom-hint"><q-icon name="zoom_in" /> Ampliar</span>
            </button>
            <template v-if="images.length > 1">
              <q-btn
                flat
                round
                icon="chevron_left"
                class="product-gallery__previous"
                aria-label="Imagen anterior"
                @click="moveImage(-1)"
              />
              <q-btn
                flat
                round
                icon="chevron_right"
                class="product-gallery__next"
                aria-label="Imagen siguiente"
                @click="moveImage(1)"
              />
              <span class="product-gallery__counter"
                >{{ imageIndex + 1 }} / {{ images.length }}</span
              >
            </template>
          </div>
          <div
            v-if="images.length > 1"
            class="product-gallery__thumbnails"
            aria-label="Seleccionar foto"
          >
            <button
              v-for="(image, index) in images"
              :key="image"
              type="button"
              :class="{ 'is-selected': index === imageIndex }"
              :aria-current="index === imageIndex ? 'true' : undefined"
              :aria-label="`Ver imagen ${index + 1}`"
              @click="imageIndex = index"
            >
              <img :src="imageSource(image)" alt="" loading="lazy" @error="imageFailed" />
            </button>
          </div>
        </section>

        <section class="product-details" aria-label="Información del producto">
          <span v-if="product.subcategoria?.name" class="product-details__category">{{
            product.subcategoria.name
          }}</span>
          <h1>{{ product.nombre }}</h1>
          <p class="product-details__code">{{ product.codigo }}</p>
          <div class="product-share" aria-label="Compartir producto">
            <q-btn flat no-caps dense icon="share" label="Compartir" @click="shareProduct" />
            <a :href="whatsappUrl" target="_blank" rel="noopener noreferrer"
              ><svg class="product-share__whatsapp" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M12 2a10 10 0 0 0-8.66 15L2 22l5.15-1.35A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.08-1.12l-.29-.17-3.05.8.81-2.97-.19-.31A8 8 0 1 1 12 20Zm4.4-5.55c-.24-.12-1.42-.7-1.64-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.92-1.18-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.19-.47-.39-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.1 3.62.57.25 1.01.4 1.35.51.57.18 1.09.16 1.5.1.46-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z" />
              </svg> WhatsApp</a
            >
            <q-btn flat no-caps dense icon="link" label="Copiar enlace" @click="copyProductLink" />
          </div>
          <p v-if="shareMessage" class="product-share__message" role="status">{{ shareMessage }}</p>
          <section v-if="description" class="product-description">
            <h2>Descripción del producto</h2>
            <p>{{ description }}</p>
          </section>
          <div class="product-details__meta">
            <ProductViews :product="product" />
            <ProductAvailability :product="product" />
            <span v-if="product.estatus === 1" class="product-details__available"
              ><q-icon name="check_circle" /> Disponible</span
            >
          </div>
          <div class="product-pricing">
            <div class="product-pricing__row">
              <del v-if="product.tiene_descuento">{{ money(product.precio_normal) }}</del>
              <strong :class="{ 'is-sale': product.tiene_descuento }">{{
                money(product.precio_final)
              }}</strong>
            </div>
            <span v-if="product.ahorro > 0" class="product-pricing__saving"
              >Ahorras {{ money(product.ahorro) }}</span
            >
          </div>
          <div v-if="priceTiers.length" class="product-tiers">
            <div v-for="tier in priceTiers" :key="tier.label">
              <span>
                <small v-if="tier.minimum > 0"
                  >{{ tier.label }} A partir de {{ tier.minimum }} piezas</small
                >
              </span>
              <strong>{{ money(tier.price) }}</strong>
            </div>
          </div>
          <div class="product-purchase">
            <div class="product-purchase__quantity">
              <label for="product-quantity">Piezas</label>
              <div>
                <q-btn
                  flat
                  dense
                  round
                  icon="remove"
                  aria-label="Quitar una pieza"
                  :disable="product.estatus !== 1 || quantity <= 1"
                  @click="quantity = Number(quantity) - 1"
                />
                <input
                  id="product-quantity"
                  v-model.number="quantity"
                  type="number"
                  min="1"
                  :max="MAX_CART_QUANTITY"
                  step="1"
                  inputmode="numeric"
                  :disabled="product.estatus !== 1"
                  @input="cartMessage = ''"
                />
                <q-btn
                  flat
                  dense
                  round
                  icon="add"
                  aria-label="Añadir una pieza"
                  :disable="product.estatus !== 1 || quantity >= MAX_CART_QUANTITY"
                  @click="quantity = Number(quantity || 0) + 1"
                />
              </div>
            </div>
            <q-btn
              unelevated
              no-caps
              icon="add_shopping_cart"
              :label="product.estatus === 0 ? 'Agotado' : 'Agregar al carrito'"
              class="product-purchase__add"
              :disable="product.estatus !== 1"
              @click="addToCart"
            />
          </div>
          <div
            v-if="cartMessage"
            class="product-purchase__message"
            :class="{ 'is-error': cartError }"
            :role="cartError ? 'alert' : 'status'"
          >
            <span>{{ cartMessage }}</span>
            <RouterLink v-if="!cartError" to="/carrito"
              >Ver carrito <q-icon name="arrow_forward"
            /></RouterLink>
          </div>
          <p class="product-details__help">¿Tienes dudas? Llámanos al 55 2495 4670.</p>
        </section>
      </div>
      <section
        v-if="product.variantes?.length > 1"
        class="product-variants"
        aria-labelledby="variants-title"
      >
        <div class="product-variants__heading">
          <h2 id="variants-title">Otros colores</h2>
          <span>{{ product.variantes.length }} opciones</span>
        </div>
        <div class="product-variants__list">
          <RouterLink
            v-for="variant in product.variantes"
            :key="variant.id_producto"
            :to="productLocation(variant)"
            class="product-variant"
            :class="{ 'is-selected': variant.id_producto === product.id_producto }"
            :aria-current="variant.id_producto === product.id_producto ? 'page' : undefined"
          >
            <img
              :src="imageSource(variant.imagen)"
              :alt="variant.nombre"
              loading="lazy"
              @error="imageFailed"
            />
            <span class="product-variant__info">
              <strong>{{ variant.nombre }}</strong>
              <span
                class="product-variant__price"
                :class="{ 'is-sale': variant.tiene_descuento }"
                >{{ money(variant.precio_final) }}</span
              >
              <ProductAvailability :product="variant" />
            </span>
            <q-icon
              v-if="variant.id_producto === product.id_producto"
              name="check_circle"
              class="product-variant__check"
              aria-label="Seleccionada"
            />
          </RouterLink>
        </div>
      </section>
      <section
        v-if="product.relacionados?.length"
        class="related-products"
        aria-labelledby="related-title"
      >
        <div class="related-products__heading">
          <h2 id="related-title">Productos relacionados</h2>
          <div>
            <q-btn
              flat
              round
              dense
              icon="chevron_left"
              aria-label="Productos anteriores"
              @click="scrollRelated(-1)"
            />
            <q-btn
              flat
              round
              dense
              icon="chevron_right"
              aria-label="Productos siguientes"
              @click="scrollRelated(1)"
            />
          </div>
        </div>
        <div
          ref="relatedRail"
          class="related-products__rail"
          tabindex="0"
          aria-label="Productos de la misma subcategoría"
        >
          <RouterLink
            v-for="related in product.relacionados"
            :key="related.id_producto"
            :to="productLocation(related)"
            class="related-product-card"
          >
            <div class="related-product-card__image">
              <SaleBadge
                v-if="related.tiene_descuento"
                :percentage="related.porcentaje_descuento"
              />
              <img
                :src="imageSource(related.imagen)"
                :alt="related.nombre"
                loading="lazy"
                @error="imageFailed"
              />
            </div>
            <ProductCardInfo :product="related" compact />
          </RouterLink>
        </div>
      </section>
    </template>
    <q-dialog v-model="viewerOpen" maximized>
      <q-card
        class="image-viewer"
        @keydown.left.prevent="moveViewer(-1)"
        @keydown.right.prevent="moveViewer(1)"
      >
        <div class="image-viewer__toolbar">
          <span>{{ product?.nombre }}</span>
          <q-btn
            flat
            round
            dense
            icon="remove"
            aria-label="Reducir imagen"
            :disable="zoom <= 1"
            @click="zoom = Math.max(1, zoom - 0.5)"
          />
          <span>{{ Math.round(zoom * 100) }}%</span>
          <q-btn
            flat
            round
            dense
            icon="add"
            aria-label="Ampliar imagen"
            :disable="zoom >= 3"
            @click="zoom = Math.min(3, zoom + 0.5)"
          />
          <q-btn flat round icon="close" aria-label="Cerrar visor" @click="viewerOpen = false" />
        </div>
        <div ref="viewerStage" class="image-viewer__stage">
          <img
            v-if="viewerOpen"
            :src="imageSource(images[imageIndex])"
            :alt="product?.nombre"
            :style="{ width: `${zoom * 100}%`, height: `${zoom * 100}%` }"
            @error="imageFailed"
          />
        </div>
        <div class="image-viewer__navigation">
          <q-btn
            flat
            round
            icon="chevron_left"
            aria-label="Imagen anterior"
            :disable="images.length < 2"
            @click="moveViewer(-1)"
          />
          <span>{{ imageIndex + 1 }} / {{ images.length }}</span>
          <q-btn
            flat
            round
            icon="chevron_right"
            aria-label="Imagen siguiente"
            :disable="images.length < 2"
            @click="moveViewer(1)"
          />
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ProductAvailability from 'src/components/ProductAvailability.vue'
import ProductViews from 'src/components/ProductViews.vue'
import ProductCardInfo from 'src/components/ProductCardInfo.vue'
import SaleBadge from 'src/components/SaleBadge.vue'
import { getProduct } from 'src/services/productApi'
import { getCategories } from 'src/services/categoryApi'
import { catalogLocation } from 'src/utils/catalogRoutes'
import { productLocation } from 'src/utils/productRoutes'
import { publicAsset } from 'src/utils/publicAsset'
import { useCart, MAX_CART_QUANTITY } from 'src/services/cart'
import { copyToClipboard } from 'quasar'

const route = useRoute()
const router = useRouter()
const { addProduct } = useCart()
const quantity = ref(1)
const cartMessage = ref('')
const cartError = ref(false)
function addToCart() {
  try {
    addProduct(product.value, Number(quantity.value))
    cartError.value = false
    cartMessage.value = `${quantity.value} ${Number(quantity.value) === 1 ? 'pieza agregada' : 'piezas agregadas'} al carrito.`
  } catch (error) {
    cartError.value = true
    cartMessage.value = error.message
  }
}
const product = ref(null)
const categories = ref([])
const loading = ref(true)
const errorMessage = ref('')
const notFound = ref(false)
const imageIndex = ref(0)
const viewerOpen = ref(false)
const viewerStage = ref(null)
const zoom = ref(1)
const shareMessage = ref('')
const shareUrl = computed(() =>
  product.value
    ? new URL(router.resolve(productLocation(product.value)).href, window.location.origin).href
    : '',
)
const whatsappUrl = computed(
  () =>
    `https://wa.me/?text=${encodeURIComponent(`${product.value?.nombre || ''}\n${shareUrl.value}`)}`,
)
function openViewer() {
  zoom.value = 1
  viewerOpen.value = true
}
function moveViewer(direction) {
  if (images.value.length < 2) return
  zoom.value = 1
  if (viewerStage.value) viewerStage.value.scrollTo(0, 0)
  moveImage(direction)
}
async function copyProductLink() {
  try {
    await copyToClipboard(shareUrl.value)
    shareMessage.value = 'Enlace copiado.'
  } catch {
    shareMessage.value = 'No se pudo copiar. Puedes copiar la dirección desde tu navegador.'
  }
}
async function shareProduct() {
  shareMessage.value = ''
  const data = { title: product.value.nombre, text: product.value.nombre, url: shareUrl.value }
  if (typeof navigator.share !== 'function' || (navigator.canShare && !navigator.canShare(data))) {
    await copyProductLink()
    return
  }
  try {
    await navigator.share(data)
  } catch (error) {
    if (error.name !== 'AbortError') await copyProductLink()
  }
}
const relatedRail = ref(null)
function scrollRelated(direction) {
  relatedRail.value?.scrollBy({
    left: direction * relatedRail.value.clientWidth * 0.85,
    behavior: 'smooth',
  })
}
const failedImages = reactive(new Set())
const fallback = publicAsset('img/product-no-image.svg')
let request
const images = computed(() =>
  product.value?.imagenes?.length ? product.value.imagenes : [fallback],
)
const category = computed(() => {
  const data = product.value
  if (!data?.categoria) return null
  return (
    categories.value.find((item) => item.id === data.categoria.id) || {
      ...data.categoria,
      subcategories: data.subcategoria ? [data.subcategoria] : [],
    }
  )
})
const subcategory = computed(() =>
  category.value?.subcategories.find((item) => item.id === product.value?.subcategoria?.id),
)
const parents = computed(() =>
  category.value
    ? [
        {
          label: category.value.name,
          to: catalogLocation(category.value, null, 1, categories.value),
        },
        ...(subcategory.value
          ? [
              {
                label: subcategory.value.name,
                to: catalogLocation(category.value, subcategory.value, 1, categories.value),
              },
            ]
          : []),
      ]
    : [],
)
const backTo = computed(() => {
  const previous = router.options.history.state.back
  if (
    typeof previous === 'string' &&
    previous.startsWith('/') &&
    !previous.startsWith('//') &&
    !previous.startsWith('/producto/')
  )
    return previous
  return parents.value.at(-1)?.to || '/'
})
const priceTiers = computed(() =>
  product.value && !product.value.tiene_descuento
    ? [
        {
          label: 'Mayoreo',
          price: product.value.precio_mayoreo,
          minimum: product.value.minimo_mayoreo,
        },
        { label: 'Por caja', price: product.value.precio_caja, minimum: product.value.minimo_caja },
      ].filter((tier) => tier.price > 0)
    : [],
)
const description = computed(() =>
  String(product.value?.descripcion || '')
    .replace(/<br\s*\/?\s*>/gi, '\n')
    .replace(/<\/p>/gi, '\n')
    .replace(/<[^>]*>/g, '')
    .trim(),
)
const money = (value) =>
  Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
function imageSource(url) {
  return failedImages.has(url) ? fallback : url
}
function imageFailed(event) {
  const url = event.target.getAttribute('src')
  if (url !== fallback) failedImages.add(url)
}
function moveImage(direction) {
  imageIndex.value = (imageIndex.value + direction + images.value.length) % images.value.length
}
async function loadProduct() {
  viewerOpen.value = false
  shareMessage.value = ''
  quantity.value = 1
  cartMessage.value = ''
  request?.abort()
  const current = new AbortController()
  request = current
  loading.value = true
  errorMessage.value = ''
  notFound.value = false
  product.value = null
  imageIndex.value = 0
  failedImages.clear()
  try {
    const data = await getProduct(route.params.id, { signal: current.signal })
    if (current.signal.aborted) return
    product.value = data
    // Names are decorative; the ID always resolves the product after a rename.
    const canonical = productLocation(data)
    if (route.params.slug !== canonical.params.slug) await router.replace(canonical)
    if (!categories.value.length) {
      getCategories({ signal: current.signal })
        .then((items) => {
          if (!current.signal.aborted) categories.value = items
        })
        .catch(() => {
          /* Category navigation must not block the product. */
        })
    }
  } catch (error) {
    if (!current.signal.aborted) {
      notFound.value = error.status === 404
      errorMessage.value = error.message || 'Intenta de nuevo en un momento.'
    }
  } finally {
    if (request === current) loading.value = false
  }
}
watch(() => route.params.id, loadProduct, { immediate: true })
onBeforeUnmount(() => request?.abort())
</script>

<style scoped lang="scss">
.product-gallery__open {
  position: relative;
  display: block;
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: zoom-in;
}
.product-gallery__open img {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 0;
  padding: 30px;
  object-fit: contain;
}
.product-gallery__zoom-hint {
  position: absolute;
  right: 12px;
  bottom: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 8px;
  border-radius: 20px;
  background: #fff;
  color: #c72d5e;
  font-size: 11px;
}
.product-gallery__open:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: -3px;
}
.product-share {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px 12px;
  margin: 4px 0 18px;
  color: #bf3f69;
}
.product-share .q-btn {
  padding: 4px 7px;
  font-size: 11px;
}
.product-share > a {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 0;
  color: inherit;
  text-decoration: none;
  font-size: 11px;
}
.product-share > a .q-icon {
  font-size: 17px;
}
.product-share__whatsapp {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}
.product-share__message {
  margin: -8px 0 16px;
  color: #8b6575;
  font-size: 11px;
}
.image-viewer {
  display: flex;
  flex-direction: column;
  height: 100dvh;
  background: #211b22;
  color: #fff;
}
.image-viewer__toolbar {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
  padding: max(10px, env(safe-area-inset-top)) 12px 10px;
}
.image-viewer__toolbar > span:first-child {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}
.image-viewer__toolbar > span {
  font-size: 12px;
}
.image-viewer__stage {
  flex: 1;
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
}
.image-viewer__stage img {
  display: block;
  max-width: none;
  object-fit: contain;
}
.image-viewer__navigation {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  gap: 18px;
  padding: 8px 12px max(8px, env(safe-area-inset-bottom));
  font-size: 12px;
}
.product-purchase {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  margin-top: 22px;
}
.product-purchase__quantity {
  flex-shrink: 0;
}
.product-purchase__quantity label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #806675;
}
.product-purchase__quantity > div {
  display: flex;
  align-items: center;
  height: 46px;
  padding: 3px;
  border: 1px solid #f0cedc;
  border-radius: 13px;
  color: #df2f61;
}
.product-purchase__quantity input {
  width: 42px;
  padding: 0;
  border: 0;
  outline-offset: 3px;
  background: transparent;
  color: #503340;
  text-align: center;
  font: inherit;
  font-weight: 600;
  appearance: textfield;
}
.product-purchase__quantity input::-webkit-inner-spin-button,
.product-purchase__quantity input::-webkit-outer-spin-button {
  appearance: none;
  margin: 0;
}
.product-purchase__add {
  flex: 1;
  min-height: 46px;
  border-radius: 13px;
  background: linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
}
.product-purchase__message {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 6px;
  padding: 12px 0 0;
  color: #15803d;
  font-size: 12px;
}
.product-purchase__message a {
  color: #d52c60;
  font-weight: 600;
  text-decoration: none;
}
.product-purchase__message.is-error {
  color: #b91c1c;
}
@media (max-width: 370px) {
  .product-purchase {
    flex-direction: column;
    align-items: stretch;
  }
  .product-purchase__quantity > div {
    width: fit-content;
  }
}
.product-variants {
  margin: 26px 0 18px;
  padding-top: 20px;
  border-top: 1px solid #f2e1e8;
}
.product-variants__heading {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}
.product-variants__heading h2 {
  margin: 0;
  color: #61424f;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.4;
}
.product-variants__heading > span {
  color: #a18090;
  font-size: 11px;
}
.product-variants__list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
  overscroll-behavior-y: contain;
  padding: 2px;
  scrollbar-width: thin;
  scrollbar-color: #f2bfd0 transparent;
}
.product-variant {
  position: relative;
  display: flex;
  align-items: center;
  gap: 9px;
  min-width: 0;
  padding: 10px;
  border: 1px solid #eedce5;
  border-radius: 13px;
  background: #fff;
  color: #69515e;
  text-decoration: none;
}
.product-variant.is-selected {
  border-color: #ed396b;
  background: #fff4f8;
  box-shadow: inset 0 0 0 1px #ed396b;
}
.product-variant > img {
  flex-shrink: 0;
  width: 48px;
  height: 58px;
  border-radius: 8px;
  background: #fff;
  object-fit: contain;
}
.product-variant__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
  min-width: 0;
}
.product-variant__info > strong {
  font-size: 11px;
  line-height: 1.4;
  font-weight: 600;
  overflow-wrap: anywhere;
}
.product-variant__price {
  color: #e53268;
  font-size: 12px;
  font-weight: 700;
}
.product-variant__price.is-sale {
  color: #16a34a;
}
.product-variant__check {
  position: absolute;
  top: 5px;
  right: 5px;
  color: #ed396b;
  font-size: 14px;
}
.product-variant.is-selected .product-variant__info {
  padding-top: 8px;
}
.product-variant:focus-visible {
  outline: 2px solid #ed396b;
  outline-offset: 2px;
}
@media (max-width: 370px) {
  .product-variants__list {
    grid-template-columns: minmax(0, 1fr);
  }
}
.related-products {
  margin-top: 36px;
  padding-top: 24px;
  border-top: 1px solid #f2e1e8;
}
.related-products__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}
.related-products__heading h2 {
  margin: 0;
  color: #4b3541;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.3;
}
.related-products__heading > div {
  display: flex;
  flex-shrink: 0;
  color: #e33269;
}
.related-products__rail {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc((100% - 60px) / 6);
  gap: 12px;
  overflow-x: auto;
  padding: 3px 1px 12px;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
  scrollbar-color: #f2bfd0 transparent;
}
.related-product-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
  border: 1px solid #f0dfe7;
  border-radius: 12px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  scroll-snap-align: start;
}
.related-product-card__image {
  position: relative;
  aspect-ratio: 1;
  padding: 10px;
  background: linear-gradient(#fff, #fff9fb);
}
.related-product-card__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.related-product-card__image .sale-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 1;
}
.related-product-card:focus-visible {
  outline: 2px solid #e33269;
  outline-offset: -2px;
}
@media (max-width: 1023px) {
  .product-variants__list {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .related-products__rail {
    grid-auto-columns: calc((100% - 24px) / 3);
  }
}
@media (max-width: 599px) {
  .product-variants__list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .related-products {
    margin-top: 24px;
    padding-top: 18px;
  }
  .related-products__heading h2 {
    font-size: 16px;
  }
  .related-products__rail {
    grid-auto-columns: calc((100% - 12px) / 2);
  }
}
.product-page {
  --product-gallery-width: 500px;
  max-width: 1280px;
  margin: 0 auto;
  padding: 18px 48px 48px;
  background: #fff;
}
.product-page__status {
  padding: 60px 16px;
  text-align: center;
  color: #89717d;
}
.product-page__status h1 {
  font-size: 22px;
  line-height: 1.3;
}
.product-layout {
  display: grid;
  grid-template-columns: minmax(0, var(--product-gallery-width)) minmax(0, 1fr);
  gap: 32px;
  margin-top: 20px;
}
.product-gallery {
  min-width: 0;
  width: 100%;
  max-width: var(--product-gallery-width);
  align-self: start;
}
.product-gallery__main {
  position: relative;
  display: grid;
  place-items: center;
  aspect-ratio: 1;
  overflow: hidden;
  border: 1px solid #f1dfe7;
  border-radius: 22px;
  background: linear-gradient(#fff, #fff9fb);
}
.product-gallery__main > img {
  width: 100%;
  height: 100%;
  min-height: 0;
  object-fit: contain;
  padding: 30px;
}
.product-gallery__main > .sale-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 1;
}
.product-gallery__previous,
.product-gallery__next {
  position: absolute;
  top: calc(50% - 20px);
  background: #fff;
  color: #e33269;
  box-shadow: 0 3px 12px #8d3b5c15;
}
.product-gallery__previous {
  left: 10px;
}
.product-gallery__next {
  right: 10px;
}
.product-gallery__counter {
  position: absolute;
  bottom: 12px;
  padding: 4px 10px;
  border-radius: 20px;
  background: #fff;
  color: #967687;
  font-size: 11px;
}
.product-gallery__thumbnails {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding: 12px 2px;
}
.product-gallery__thumbnails button {
  flex-shrink: 0;
  width: 66px;
  height: 66px;
  padding: 5px;
  overflow: hidden;
  border: 1px solid #efdee6;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
}
.product-gallery__thumbnails button.is-selected {
  border: 2px solid #ef396e;
}
.product-gallery__thumbnails img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.product-details {
  min-width: 0;
  padding: 12px 0;
}
.product-details__category {
  color: #a17a8b;
  font-size: 12px;
}
.product-details h1 {
  margin: 10px 0;
  color: #392733;
  font-size: 30px;
  font-weight: 700;
  line-height: 1.25;
  overflow-wrap: anywhere;
}
.product-details__code {
  margin: 0 0 12px;
  color: #f4456f;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
}
.product-details__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}
.product-details__available {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #15803d;
  font-size: 12px;
}
.product-pricing {
  display: flex;
  flex-direction: column;
  gap: 7px;
  padding: 20px;
  border: 1px solid #f5e4eb;
  border-radius: 18px;
  background: #fff8fb;
}
.product-pricing__row {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 12px;
}
.product-pricing__row del {
  color: #a18a96;
  font-size: 16px;
}
.product-pricing__row strong {
  color: #ec326a;
  font-size: 36px;
  font-weight: 750;
  line-height: 1.2;
}
.product-pricing__row strong.is-sale {
  color: #16a34a;
}
.product-pricing__saving {
  color: #15803d;
  font-size: 12px;
  font-weight: 600;
}
.product-pricing__currency {
  color: #a28a96;
  font-size: 10px;
}
.product-tiers {
  margin-top: 16px;
}
.product-tiers > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #f4e8ee;
  color: #674b59;
  font-size: 13px;
}
.product-tiers small {
  display: inline-block;
  margin-top: 6px;
  padding: 5px 8px;
  border: 1px solid #f3c5d5;
  border-radius: 7px;
  background: #fff0f5;
  color: #c72658;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.4;
}
.product-tiers strong {
  color: #d82e60;
  font-size: 17px;
}
.product-details__minimum {
  color: #947a87;
  font-size: 12px;
  margin: 16px 0;
}
.product-details__contact {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 22px;
  padding: 15px 12px;
  border-radius: 15px;
  background:
    linear-gradient(158deg, transparent 0 30%, #ffffff15 30.4% 36%, transparent 36.4%),
    linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}
.product-details__contact .q-icon {
  font-size: 20px;
}
.product-details__help {
  margin-top: 10px;
  text-align: center;
  color: #a28a96;
  font-size: 11px;
}
.product-description {
  margin: 0 0 18px;
  padding: 8px 0;
}
.product-description h2 {
  margin: 0 0 12px;
  color: #4a3340;
  font-size: 19px;
  font-weight: 600;
  line-height: 1.3;
}
.product-description p {
  margin: 0;
  white-space: pre-line;
  overflow-wrap: anywhere;
  color: #806a76;
  font-size: 14px;
  line-height: 1.8;
}
@media (max-width: 1023px) {
  .product-page {
    padding: 10px 16px 110px;
  }
  .product-layout {
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
    gap: 24px;
  }
  .product-details h1 {
    font-size: 24px;
  }
}
@media (max-width: 599px) {
  .product-layout {
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
    margin-top: 12px;
  }
  .product-gallery__main {
    max-height: 320px;
  }
  .product-gallery__main > img {
    padding: 20px;
  }
  .product-details {
    padding-top: 4px;
  }
  .product-details h1 {
    font-size: 23px;
  }
  .product-pricing {
    padding: 16px;
  }
  .product-pricing__row strong {
    font-size: 32px;
  }
  .product-description {
    margin-top: 0;
  }
}
</style>
