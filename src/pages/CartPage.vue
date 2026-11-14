<template>
  <q-page class="cart-page">
    <PageBreadcrumbs title="Mi carrito" />
    <h1>
      Mi carrito <small>{{ cartCount }} piezas</small>
    </h1>
    <q-banner v-if="mutationError" rounded class="cart-feedback" role="alert">
      {{ mutationError }}
      <template #action>
        <q-btn flat no-caps label="Actualizar carrito" :disable="busy" @click="refreshCart" />
      </template>
    </q-banner>
    <q-linear-progress v-if="busy && !refreshing" indeterminate color="primary" aria-label="Guardando carrito" />
    <LoadingSkeleton v-if="refreshing" variant="cart" :count="3" label="Cargando carrito y precios..." />
    <div v-else-if="refreshError" class="cart-empty" role="alert">
      <p>No se pudieron actualizar los precios.</p>
      <q-btn no-caps color="primary" label="Reintentar" @click="refreshCart" />
    </div>
    <div v-else-if="!items.length" class="cart-empty">
      <q-icon name="shopping_bag" size="48px" />
      <p>Tu carrito está vacío.</p>
      <q-btn no-caps color="primary" unelevated label="Explorar productos" to="/categorias" />
    </div>
    <div v-else class="cart-layout" :style="{ '--cart-summary-top': `${summaryOffset}px` }">
      <div class="cart-items">
        <article v-for="item in items" :key="item.id_producto" class="cart-item" :class="{ 'cart-item--unavailable': !item.puede_comprar }">
          <RouterLink :to="productLocation(item)"
            ><img v-image-skeleton :src="item.imagen || fallback" :alt="item.nombre" @error="imageFailed"
          /></RouterLink>
          <div class="cart-item__details">
            <RouterLink :to="productLocation(item)">{{ item.nombre }}</RouterLink>
            <div class="cart-item__meta">
              <small class="cart-item__code">{{ item.codigo }}</small>
              <ProductBadge :product="item" />
            </div>
            <div class="cart-item__price">
              <del v-if="item.tiene_descuento && item.precio_normal > item.precio_final">{{ money(item.precio_normal) }}</del>
              <strong :class="{ 'cart-item__sale-price': item.tiene_descuento }">{{ money(item.precio_final) }}</strong>
              <span>/ pieza</span>
            </div>
            <small v-if="!item.puede_comprar">No disponible. No se incluye en el total.</small>
            <div class="cart-item__quantity">
              <q-btn
                flat
                round
                dense
                icon="remove"
                :disable="busy || item.quantity <= 1"
                :aria-label="`Quitar una pieza de ${item.nombre}`"
                @click="changeQuantity(item.id_producto, item.quantity - 1)"
              />
              <input
                class="cart-item__quantity-input"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="4"
                :value="quantityDrafts[item.id_producto] ?? item.quantity"
                :disabled="busy"
                :aria-label="`Cantidad de ${item.nombre}`"
                @focus="$event.target.select()"
                @input="quantityDrafts[item.id_producto] = $event.target.value"
                @blur="commitQuantity(item)"
                @keydown.enter.prevent="$event.target.blur()"
                @keydown.esc.prevent="cancelQuantity(item, $event)"
              />
              <q-btn
                flat
                round
                dense
                icon="add"
                :disable="busy || item.puede_comprar === false || item.quantity >= MAX_CART_QUANTITY"
                :aria-label="`Añadir una pieza de ${item.nombre}`"
                @click="changeQuantity(item.id_producto, item.quantity + 1)"
              />
            </div>
          </div>
          <div class="cart-item__end">
            <q-btn
              flat
              round
              dense
              icon="delete_outline"
              :disable="busy"
              :aria-label="`Eliminar ${item.nombre}`"
              @click="deleteProduct(item.id_producto)"
            />
            <strong :class="{ 'cart-item__sale-price': item.tiene_descuento }">{{ money(item.subtotal) }}</strong>
          </div>
        </article>
      </div>
      <div class="cart-total" role="status" aria-label="Total del carrito" :aria-busy="busy">
        <div class="cart-summary-desktop">
          <h2>Resumen de tu compra</h2>
          <div class="cart-summary-desktop__row"><span>Productos</span><strong>{{ items.length }}</strong></div>
          <div class="cart-summary-desktop__row"><span>Piezas</span><strong>{{ cartCount }}</strong></div>
          <div v-if="savings > 0" class="cart-summary-desktop__savings"><span><q-icon name="savings" aria-hidden="true" /> Tu ahorro</span><strong>{{ money(savings) }}</strong></div>
          <div class="cart-summary-desktop__total"><span>Total de tu compra</span><div><q-spinner v-if="busy" size="18px" aria-label="Actualizando total" /><strong>{{ money(total) }}</strong></div></div>
          <q-btn unelevated no-caps label="Continuar con mi pedido" icon-right="arrow_forward" to="/checkout" class="cart-summary-desktop__continue" :disable="busy || total <= 0" />
        </div>
        <div class="cart-summary-mobile">
          <div class="cart-summary-mobile__heading">
            <div>
              <span class="cart-summary-mobile__label">Total de tu compra</span>
              <p>{{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }} · {{ cartCount }} {{ cartCount === 1 ? 'pieza' : 'piezas' }}</p>
            </div>
            <div class="cart-summary-mobile__amount">
              <q-spinner v-if="busy" size="16px" aria-label="Actualizando total" />
              <strong>{{ money(total) }}</strong>
            </div>
          </div>
          <div v-if="savings > 0" class="cart-summary-mobile__savings">
            <span><q-icon name="savings" aria-hidden="true" /> Estás ahorrando</span>
            <strong>{{ money(savings) }}</strong>
          </div>
          <q-btn unelevated no-caps label="Continuar con mi pedido" icon-right="arrow_forward" to="/checkout" class="cart-summary-mobile__continue" :disable="busy || total <= 0" />
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ProductBadge from 'src/components/ProductBadge.vue'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthSession } from 'src/services/authSession'
import { useCart, MAX_CART_QUANTITY } from 'src/services/cart'
import { productLocation } from 'src/utils/productRoutes'
import { publicAsset } from 'src/utils/publicAsset'
const { items, cartCount, total, savings, busy, setQuantity, removeProduct, loadCart } = useCart()
const { loadCurrentUser } = useAuthSession()
const refreshing = ref(true)
const refreshError = ref(false)
const mutationError = ref('')
const quantityDrafts = ref({})
const summaryOffset = ref(180)
let headerObserver
onMounted(() => {
  const header = document.querySelector('.sn-header')
  const updateOffset = () => { summaryOffset.value = (header?.getBoundingClientRect().height || 0) + 16 }
  updateOffset()
  if (header) {
    headerObserver = new ResizeObserver(updateOffset)
    headerObserver.observe(header)
  }
})
onBeforeUnmount(() => headerObserver?.disconnect())
async function commitQuantity(item) {
  const draft = quantityDrafts.value[item.id_producto]
  if (draft === undefined) return
  const value = String(draft).trim()
  const quantity = Number(value)
  if (!/^[0-9]+$/.test(value) || !Number.isSafeInteger(quantity) || quantity < 1 || quantity > MAX_CART_QUANTITY) {
    mutationError.value = `Ingresa una cantidad entre 1 y ${MAX_CART_QUANTITY} piezas.`
    delete quantityDrafts.value[item.id_producto]
    return
  }
  try {
    if (!busy.value && quantity !== item.quantity) await changeQuantity(item.id_producto, quantity)
  } finally {
    delete quantityDrafts.value[item.id_producto]
  }
}
function cancelQuantity(item, event) {
  delete quantityDrafts.value[item.id_producto]
  event.target.value = item.quantity
  event.target.blur()
}
async function refreshCart() {
  refreshing.value = true
  mutationError.value = ''
  try {
    await loadCurrentUser()
    await loadCart()
    refreshError.value = false
  } catch {
    refreshError.value = true
  } finally {
    refreshing.value = false
  }
}
async function changeQuantity(id, quantity) {
  if (busy.value) return
  mutationError.value = ''
  try { await setQuantity(id, quantity) }
  catch (error) { mutationError.value = error.message }
}
async function deleteProduct(id) {
  mutationError.value = ''
  try { await removeProduct(id) }
  catch (error) { mutationError.value = error.message }
}
onMounted(refreshCart)
const fallback = publicAsset('img/product-no-image.svg')
const money = (value) =>
  Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
function imageFailed(event) {
  if (event.target.getAttribute('src') !== fallback) event.target.src = fallback
}
</script>

<style scoped>
.cart-feedback {
  margin-bottom: 16px;
  background: #fff1f6;
  color: #94224d;
}
.cart-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 16px 24px 110px;
  background: #fff;
}
.cart-page h1 {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin: 18px 0;
  color: #503340;
  font-size: 25px;
  font-weight: 700;
  line-height: 1.3;
}
.cart-page h1 small {
  color: #a18492;
  font-size: 12px;
  font-weight: 400;
}
.cart-empty {
  padding: 50px 16px;
  text-align: center;
  color: #bb8298;
}
.cart-items {
  display: grid;
  gap: 12px;
}
.cart-item {
  display: flex;
  gap: 14px;
  padding: 16px;
  border: 1px solid #f0dfe7;
  border-radius: 16px;
}
.cart-item img {
  width: 80px;
  height: 90px;
  object-fit: contain;
}
.cart-item__details {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
}
.cart-item__details > a {
  color: #59414e;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}
.cart-item__details > small {
  color: #ed396b;
  text-transform: uppercase;
  font-weight: 600;
}
.cart-item__details > span {
  color: #927784;
  font-size: 12px;
}
.cart-item__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px 9px;
}
.cart-item__code {
  color: #ed396b;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.cart-item__price {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 5px;
}
.cart-item__price del {
  color: #a18b95;
  font-size: 11px;
}
.cart-item__price strong {
  color: #ec326a;
  font-size: 16px;
  line-height: 1.3;
}
.cart-item__price > span {
  color: #927784;
  font-size: 11px;
}
.cart-item .cart-item__sale-price {
  color: #00a844;
}
.cart-item__quantity {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 5px;
  border: 1px solid #f0dfe7;
  border-radius: 10px;
  color: #c92c5b;
}
.cart-item__quantity-input {
  width: 42px;
  min-width: 0;
  height: 32px;
  padding: 0 2px;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: inherit;
  font: inherit;
  font-size: 16px;
  text-align: center;
}
.cart-item__quantity-input:focus-visible { outline: 2px solid #df2f61; outline-offset: -2px; background: #fff; }
.cart-item__quantity-input:disabled { opacity: .65; }
.cart-item__end {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: space-between;
  gap: 8px;
  color: #dd3768;
}
.cart-item__end strong {
  white-space: nowrap;
  font-size: 17px;
}
.cart-total {
  position: fixed;
  z-index: 1900;
  left: 50%;
  bottom: calc(20px + env(safe-area-inset-bottom, 0px));
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: calc(100% - 48px);
  max-width: 952px;
  min-height: 70px;
  padding: 16px 20px;
  border: 1px solid #f6cddd;
  border-radius: 18px;
  background: #fff1f6;
  box-shadow: 0 8px 32px rgba(125, 33, 68, 0.16);
  color: #743c53;
}
.cart-total > span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.cart-total strong {
  color: #df2f61;
  font-size: 24px;
}
.cart-summary-mobile { display: none; }
.cart-summary-desktop { display: none; }
@media (min-width: 1024px) {
  .cart-page { max-width: 1280px; padding-bottom: 48px; }
  .cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; align-items: start; gap: 24px; }
  .cart-items { min-width: 0; }
  .cart-total {
    position: sticky;
    top: var(--cart-summary-top);
    left: auto;
    bottom: auto;
    z-index: 1;
    transform: none;
    display: block;
    width: 100%;
    max-width: none;
    min-height: 0;
    max-height: calc(100dvh - var(--cart-summary-top) - 20px);
    overflow-y: auto;
    padding: 24px;
    border-color: #efdee6;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 6px 24px #54314009;
  }
  .cart-summary-desktop { display: block; }
  .cart-summary-desktop h2 { margin: 0 0 24px; color: #412536; font-size: 19px; font-weight: 700; line-height: 1.3; }
  .cart-summary-desktop__row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; color: #8a7280; font-size: 13px; }
  .cart-summary-desktop__row strong { color: #59414e; font-size: 14px; font-weight: 600; }
  .cart-summary-desktop__savings { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 20px; padding: 11px; border-radius: 9px; background: #ecf8f0; color: #177345; font-size: 12px; }
  .cart-summary-desktop__savings > span { display: flex; align-items: center; gap: 6px; }
  .cart-summary-desktop__savings strong { color: inherit; font-size: 15px; white-space: nowrap; }
  .cart-summary-desktop__total { margin-top: 22px; padding-top: 20px; border-top: 1px solid #f1e5eb; }
  .cart-summary-desktop__total > span { color: #8a7280; font-size: 12px; }
  .cart-summary-desktop__total > div { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 5px; }
  .cart-summary-desktop__total strong { color: #412536; font-size: 30px; font-weight: 800; line-height: 1.3; letter-spacing: -.03em; overflow-wrap: anywhere; }
  .cart-summary-desktop__continue { width: 100%; min-height: 46px; margin-top: 22px; border-radius: 10px; background: linear-gradient(110deg, #ff4f79, #e72e65); color: #fff; font-size: 12px; font-weight: 700; }
  .cart-summary-desktop__continue :deep(.q-icon) { font-size: 18px; }
}
@media (max-width: 1023px) {
  .cart-page {
    padding-bottom: calc(290px + env(safe-area-inset-bottom, 0px));
  }
  .cart-total {
    bottom: calc(94px + env(safe-area-inset-bottom, 0px));
    width: calc(100% - 24px);
    max-width: 480px;
    min-height: 0;
    padding: 0;
    border-color: #f3dce5;
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 8px 32px rgba(88, 36, 57, 0.16);
  }
  .cart-total__desktop { display: none !important; }
  .cart-summary-mobile { display: block; width: 100%; padding: 15px; }
  .cart-summary-mobile__heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .cart-summary-mobile__heading > div:first-child { min-width: 0; }
  .cart-summary-mobile__label { color: #8a7280; font-size: 11px; line-height: 1.4; }
  .cart-summary-mobile__heading p { margin: 3px 0 0; color: #654655; font-size: 12px; line-height: 1.4; }
  .cart-summary-mobile__amount { display: flex; flex-shrink: 0; align-items: center; gap: 6px; }
  .cart-summary-mobile__amount strong { color: #412536; font-size: clamp(19px, 5.5vw, 24px); font-weight: 800; letter-spacing: -0.03em; white-space: nowrap; }
  .cart-summary-mobile__savings { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; padding: 9px 11px; border-radius: 10px; background: #ecf8f0; color: #177345; }
  .cart-summary-mobile__savings > span { display: flex; align-items: center; gap: 6px; font-size: 12px; }
  .cart-summary-mobile__savings .q-icon { font-size: 17px; }
  .cart-summary-mobile__savings strong { color: #177345; font-size: 15px; font-weight: 700; white-space: nowrap; }
  .cart-summary-mobile__continue { width: 100%; min-height: 44px; margin-top: 12px; border-radius: 12px; background: linear-gradient(110deg, #ff4f79, #e72e65); color: #fff; font-size: 13px; font-weight: 700; }
  .cart-summary-mobile__continue :deep(.q-icon) { font-size: 18px; }
}
@media (max-width: 599px) {
  .cart-page {
    padding: 10px 12px calc(290px + env(safe-area-inset-bottom, 0px));
  }
  .cart-item {
    display: grid;
    grid-template-columns: 102px minmax(0, 1fr) 28px;
    gap: 3px 8px;
    padding: 8px;
    border-radius: 12px;
  }
  .cart-items { gap: 8px; }
  .cart-item > a {
    grid-column: 1;
    grid-row: 1 / 3;
    justify-self: center;
    align-self: start;
  }
  .cart-item img {
    display: block;
    width: 44px;
    height: 44px;
  }
  .cart-item__details, .cart-item__end { display: contents; }
  .cart-item__details > a {
    grid-column: 2;
    grid-row: 1;
    align-self: center;
    font-size: 12px;
    line-height: 1.35;
  }
  .cart-item__meta { grid-column: 2 / -1; grid-row: 2; gap: 3px 6px; }
  .cart-item__price { grid-column: 2 / -1; grid-row: 3; gap: 4px; }
  .cart-item__price strong { font-size: 15px; }
  .cart-item__quantity {
    grid-column: 1;
    grid-row: 3 / 5;
    justify-self: center;
    align-self: end;
    gap: 2px;
    margin-top: 0;
    border-radius: 8px;
    font-size: 12px;
  }
  .cart-item__quantity .q-btn { min-width: 28px; min-height: 32px; }
  .cart-item__quantity :deep(.q-icon) { font-size: 18px; }
  .cart-item__quantity-input { width: 40px; }
  .cart-item__end > .q-btn { grid-column: 3; grid-row: 1; justify-self: end; align-self: start; min-width: 28px; min-height: 28px; }
  .cart-item__end > .q-btn :deep(.q-icon) { font-size: 19px; }
  .cart-item__details > small { grid-column: 2 / -1; grid-row: 4; font-size: 10px; }
  .cart-item--unavailable .cart-item__quantity { grid-row: 3 / 6; }
  .cart-item--unavailable .cart-item__end strong { grid-row: 5; }
  .cart-item__end strong {
    grid-column: 2 / -1;
    grid-row: 4;
    justify-self: end;
    align-self: end;
    font-size: 13px;
    line-height: 1.4;
  }
}
</style>
