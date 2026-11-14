<template>
  <q-page class="checkout-page">
    <PageBreadcrumbs
      title="Checkout"
      back-to="/carrito"
      :parents="[{ label: 'Mi carrito', to: '/carrito' }]"
    />
    <h1>Resumen de tu pedido</h1>
    <LoadingSkeleton v-if="loading" variant="cart" :count="3" label="Revisando tu pedido..." />
    <div v-else-if="errorMessage" class="checkout-status" role="alert">
      <p>{{ errorMessage }}</p>
      <q-btn no-caps color="primary" label="Reintentar" @click="loadOrder" />
    </div>
    <div v-else-if="!items.length" class="checkout-status">
      <q-icon name="shopping_bag" size="40px" color="primary" />
      <p>Tu carrito está vacío.</p>
      <q-btn unelevated no-caps color="primary" label="Explorar productos" to="/categorias" />
    </div>
    <div v-else class="checkout-layout" :class="{ 'checkout-layout--summary-only': currentUser }">
      <div v-if="!currentUser" class="checkout-content" :inert="savingGuest || undefined">
        <GuestCheckoutForm ref="guestForm" v-model="guestDetails" />
        <CheckoutDeliveryOptions ref="deliveryForm" v-model="deliverySelection" />
      </div>
      <aside class="checkout-summary" aria-label="Importe de tu pedido">
        <h2>Tu compra</h2>
        <section class="checkout-products" aria-label="Productos de tu pedido">
          <div class="checkout-products__heading">
            <span
              >{{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }} ·
              {{ cartCount }} {{ cartCount === 1 ? 'pieza' : 'piezas' }}</span
            >
            <RouterLink to="/carrito">Editar carrito <q-icon name="edit" /></RouterLink>
          </div>
          <div
            class="checkout-products__list"
            tabindex="0"
            role="region"
            aria-label="Lista de productos del pedido"
          >
            <article v-for="item in items" :key="item.id_producto" class="checkout-product">
              <img
                v-image-skeleton
                :src="item.imagen || fallback"
                :alt="item.nombre"
                @error="imageFailed"
              />
              <div>
                <strong :title="item.nombre">{{ item.nombre }}</strong>
                <small>{{ item.codigo }}</small>
                <div class="checkout-product__price-row">
                  <span
                    >{{ item.quantity }} {{ item.quantity === 1 ? 'pieza' : 'piezas' }} ×
                    {{ money(item.precio_final) }}</span
                  >
                  <b>{{ money(item.subtotal) }}</b>
                </div>
                <p v-if="!item.puede_comprar" class="checkout-product__unavailable">
                  No disponible. No se incluye en el total.
                </p>
              </div>
            </article>
          </div>
        </section>
        <div v-if="savings > 0 && regularTotal !== null" class="checkout-summary__row">
          <span>Precio normal</span><del>{{ money(regularTotal) }}</del>
        </div>
        <div v-if="savings > 0" class="checkout-summary__row checkout-summary__savings">
          <span><q-icon name="savings" /> Tu ahorro</span><strong>{{ money(savings) }}</strong>
        </div>
        <div class="checkout-summary__total">
          <span>Total de productos</span><strong>{{ money(total) }}</strong>
        </div>
        <template v-if="!currentUser">
          <p v-if="processError" class="checkout-process-error" role="alert">{{ processError }}</p>
          <p v-if="readyToProcess" class="checkout-process-ready" role="status">
            <q-icon name="check_circle" /> Datos del cliente guardados correctamente.
          </p>
          <q-btn
            class="checkout-process-button"
            unelevated
            no-caps
            color="primary"
            label="Procesar pedido"
            icon-right="arrow_forward"
            :loading="validating"
            :disable="cartBusy || readyToProcess"
            @click="processOrder"
          />
        </template>
      </aside>
    </div>
  </q-page>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import GuestCheckoutForm from 'src/components/GuestCheckoutForm.vue'
import CheckoutDeliveryOptions from 'src/components/CheckoutDeliveryOptions.vue'
import { emptyGuestDetails, normalizeGuestDetails } from 'src/utils/guestCheckout'
import { registerGuestCustomer } from 'src/services/checkoutApi'
import { useCart } from 'src/services/cart'
import { useAuthSession } from 'src/services/authSession'
import { publicAsset } from 'src/utils/publicAsset'

const { items, total, savings, regularTotal, cartCount, loadCart, busy: cartBusy } = useCart()
const { currentUser, loadCurrentUser } = useAuthSession()
const guestDetails = ref(emptyGuestDetails())
const deliverySelection = ref({ envio: null, shipping_carrier_id: null, sucursal_id: null })
const guestForm = ref(null)
const deliveryForm = ref(null)
const validating = ref(false)
const processError = ref('')
const readyToProcess = ref(false)
const savingGuest = ref(false)
const savedGuest = ref(null)
let guestRequest
let disposed = false
watch(
  [guestDetails, deliverySelection, items, total, () => currentUser.value?.id],
  () => {
    processError.value = ''
    readyToProcess.value = false
  },
  { deep: true, flush: 'sync' },
)

async function processOrder() {
  if (validating.value || cartBusy.value || currentUser.value) return
  validating.value = true
  processError.value = ''
  readyToProcess.value = false
  try {
    const customerValid = await guestForm.value?.validate()
    const deliveryValid = deliveryForm.value?.validate()
    if (!customerValid || !deliveryValid) {
      processError.value = 'Completa los datos señalados para continuar con tu pedido.'
      await nextTick()
      const section = !customerValid ? guestForm.value?.$el : deliveryForm.value?.$el
      section?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      section
        ?.querySelector('input[aria-invalid="true"], .q-field--error input, .q-radio')
        ?.focus({ preventScroll: true })
      return
    }
    if (
      !items.value.length ||
      !Number.isFinite(total.value) ||
      total.value <= 0 ||
      items.value.some((item) => !item.puede_comprar)
    ) {
      processError.value =
        'Revisa tu carrito: debe contener productos disponibles y un total mayor a cero.'
      return
    }
    if (disposed || currentUser.value) return
    const customer = normalizeGuestDetails(guestDetails.value)
    const fingerprint = JSON.stringify(customer)
    if (savedGuest.value?.fingerprint === fingerprint) {
      readyToProcess.value = true
      return
    }
    savingGuest.value = true
    guestRequest = new AbortController()
    const timeout = setTimeout(() => guestRequest.abort(), 20000)
    try {
      const result = await registerGuestCustomer(customer, { signal: guestRequest.signal })
      if (
        disposed ||
        currentUser.value ||
        JSON.stringify(normalizeGuestDetails(guestDetails.value)) !== fingerprint
      )
        return
      savedGuest.value = { ...result, fingerprint }
      readyToProcess.value = true
    } finally {
      clearTimeout(timeout)
      savingGuest.value = false
    }
  } catch (error) {
    if (!disposed)
      processError.value =
        error.name === 'AbortError'
          ? 'No pudimos confirmar el guardado de tus datos. Puedes reintentar.'
          : error.message || 'No pudimos guardar tus datos. Intenta de nuevo.'
  } finally {
    validating.value = false
  }
}
watch(
  () => currentUser.value?.id,
  () => {
    guestDetails.value = emptyGuestDetails()
    deliverySelection.value = { envio: null, shipping_carrier_id: null, sucursal_id: null }
    savedGuest.value = null
  },
)
const loading = ref(true)
const errorMessage = ref('')
const fallback = publicAsset('img/product-no-image.svg')
const money = (value) =>
  Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
function imageFailed(event) {
  if (event.target.getAttribute('src') !== fallback) event.target.src = fallback
}
async function loadOrder() {
  loading.value = true
  errorMessage.value = ''
  try {
    await loadCurrentUser()
    await loadCart()
  } catch {
    errorMessage.value = 'No pudimos revisar tu pedido. Intenta nuevamente.'
  } finally {
    loading.value = false
  }
}
onMounted(loadOrder)
onBeforeUnmount(() => {
  disposed = true
  guestRequest?.abort()
})
</script>

<style scoped>
.checkout-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 16px 24px 110px;
}
.checkout-page h1 {
  margin: 8px 0 16px;
  color: #503340;
  font-size: 25px;
  font-weight: 700;
  line-height: 1.3;
}
.checkout-status {
  padding: 40px 12px;
  text-align: center;
  color: #8c6c7b;
}
.checkout-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  align-items: start;
  gap: 24px;
}
.checkout-content {
  display: grid;
  gap: 20px;
  min-width: 0;
}
.checkout-summary {
  min-width: 0;
  padding: 18px;
  border: 1px solid #e5e7eb;
  border-radius: 18px;
  background: #fff;
}
.checkout-products {
  min-width: 0;
}
.checkout-products__list {
  max-height: 320px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd0d6 transparent;
  padding-right: 3px;
  margin-bottom: 14px;
}
.checkout-products__list:focus-visible {
  outline: 2px solid #e62f66;
  outline-offset: 2px;
}
.checkout-layout--summary-only {
  grid-template-columns: minmax(0, 420px);
  justify-content: center;
}
.checkout-products__heading {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 6px;
  padding-bottom: 10px;
  color: #727780;
  font-size: 10px;
}
.checkout-products__heading a {
  color: #eb346c;
  font-weight: 600;
  text-decoration: none;
}
.checkout-product {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  align-items: start;
  column-gap: 9px;
  row-gap: 4px;
  padding: 10px 0;
  border-top: 1px solid #eef0f2;
}
.checkout-product > img {
  width: 36px;
  height: 44px;
  object-fit: contain;
  border-radius: 8px;
}
.checkout-product > div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.checkout-product strong {
  color: #30343b;
  font-size: 11px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
}
.checkout-product small {
  color: #777d86;
  font-size: 9px;
  text-transform: uppercase;
  font-weight: 700;
}
.checkout-product span {
  color: #777d86;
  font-size: 10px;
}
.checkout-product__price-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 6px;
}
.checkout-product__price-row > b {
  color: #30343b;
  font-size: 12px;
  white-space: nowrap;
}
.checkout-product__unavailable {
  margin: 3px 0 0;
  color: #b35170;
  font-size: 11px;
}
.checkout-summary {
  position: sticky;
  top: 24px;
  background: #fff;
}
.checkout-summary h2 {
  margin: 0 0 12px;
  color: #30343b;
  font-size: 18px;
  font-weight: 700;
}
.checkout-summary__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 14px 0;
  color: #727780;
  font-size: 12px;
}
.checkout-summary__row > span {
  display: flex;
  align-items: center;
  gap: 6px;
}
.checkout-summary__savings {
  padding: 10px;
  border-radius: 10px;
  background: #ecf8f0;
  color: #177345;
}
.checkout-summary__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 18px;
  border-top: 1px solid #e5e7eb;
  color: #30343b;
  font-size: 13px;
}
.checkout-summary__total strong {
  font-size: 23px;
  color: #e62f66;
}
.checkout-process-button {
  width: 100%;
  margin-top: 18px;
  min-height: 42px;
  border-radius: 10px;
}
.checkout-process-error,
.checkout-process-ready {
  margin: 14px 0 0;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.5;
}
.checkout-process-error {
  color: #b42318;
  background: #fff0f0;
}
.checkout-process-ready {
  color: #177345;
  background: #ecf8f0;
}
@media (max-width: 799px) {
  .checkout-page {
    padding: 10px 12px 110px;
  }
  .checkout-page h1 {
    font-size: 22px;
  }
  .checkout-layout {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  .checkout-summary {
    padding: 14px;
  }
  .checkout-summary {
    position: static;
  }
}
</style>
