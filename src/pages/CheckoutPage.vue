<template>
  <q-page class="checkout-page">
    <PageBreadcrumbs title="Checkout" back-to="/carrito" :parents="[{ label: 'Mi carrito', to: '/carrito' }]" />
    <h1>Resumen de tu pedido</h1>
    <LoadingSkeleton v-if="loading" variant="cart" :count="3" label="Revisando tu pedido..." />
    <div v-else-if="errorMessage" class="checkout-status" role="alert">
      <p>{{ errorMessage }}</p><q-btn no-caps color="primary" label="Reintentar" @click="loadOrder" />
    </div>
    <div v-else-if="!items.length" class="checkout-status">
      <q-icon name="shopping_bag" size="40px" color="primary" />
      <p>Tu carrito está vacío.</p><q-btn unelevated no-caps color="primary" label="Explorar productos" to="/categorias" />
    </div>
    <div v-else class="checkout-layout">
      <section class="checkout-products" aria-label="Productos de tu pedido">
        <div class="checkout-products__heading">
          <span>{{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }} · {{ cartCount }} {{ cartCount === 1 ? 'pieza' : 'piezas' }}</span>
          <RouterLink to="/carrito">Editar carrito <q-icon name="edit" /></RouterLink>
        </div>
        <article v-for="item in items" :key="item.id_producto" class="checkout-product">
          <img v-image-skeleton :src="item.imagen || fallback" :alt="item.nombre" @error="imageFailed" />
          <div>
            <strong>{{ item.nombre }}</strong>
            <small>{{ item.codigo }}</small>
            <span>{{ item.quantity }} {{ item.quantity === 1 ? 'pieza' : 'piezas' }} × {{ money(item.precio_final) }}</span>
            <p v-if="!item.puede_comprar" class="checkout-product__unavailable">No disponible. No se incluye en el total.</p>
          </div>
          <b>{{ money(item.subtotal) }}</b>
        </article>
      </section>
      <aside class="checkout-summary" aria-label="Importe de tu pedido">
        <h2>Tu compra</h2>
        <div v-if="savings > 0 && regularTotal !== null" class="checkout-summary__row">
          <span>Precio normal</span><del>{{ money(regularTotal) }}</del>
        </div>
        <div v-if="savings > 0" class="checkout-summary__row checkout-summary__savings">
          <span><q-icon name="savings" /> Tu ahorro</span><strong>{{ money(savings) }}</strong>
        </div>
        <div class="checkout-summary__total"><span>Total de productos</span><strong>{{ money(total) }}</strong></div>
      </aside>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { useCart } from 'src/services/cart'
import { useAuthSession } from 'src/services/authSession'
import { publicAsset } from 'src/utils/publicAsset'

const { items, total, savings, regularTotal, cartCount, loadCart } = useCart()
const { loadCurrentUser } = useAuthSession()
const loading = ref(true)
const errorMessage = ref('')
const fallback = publicAsset('img/product-no-image.svg')
const money = value => Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
function imageFailed(event) {
  if (event.target.getAttribute('src') !== fallback) event.target.src = fallback
}
async function loadOrder() {
  loading.value = true
  errorMessage.value = ''
  try { await loadCurrentUser(); await loadCart() }
  catch { errorMessage.value = 'No pudimos revisar tu pedido. Intenta nuevamente.' }
  finally { loading.value = false }
}
onMounted(loadOrder)
</script>

<style scoped>
.checkout-page { max-width: 1100px; margin: 0 auto; padding: 16px 24px 110px; }
.checkout-page h1 { margin: 20px 0; color: #503340; font-size: 25px; font-weight: 700; }
.checkout-status { padding: 40px 12px; text-align: center; color: #8c6c7b; }
.checkout-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; align-items: start; gap: 24px; }
.checkout-products, .checkout-summary { min-width: 0; padding: 18px; border: 1px solid #f0dfe7; border-radius: 18px; background: #fff; }
.checkout-products__heading { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding-bottom: 14px; color: #8a7280; font-size: 12px; }
.checkout-products__heading a { color: #eb346c; font-weight: 600; text-decoration: none; }
.checkout-product { display: grid; grid-template-columns: 58px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 16px 0; border-top: 1px solid #f8edf1; }
.checkout-product > img { width: 58px; height: 70px; object-fit: contain; border-radius: 8px; }
.checkout-product > div { min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.checkout-product strong { color: #503340; font-size: 13px; line-height: 1.4; }
.checkout-product small { color: #eb346c; font-size: 11px; text-transform: uppercase; font-weight: 700; }
.checkout-product span { color: #927784; font-size: 12px; }
.checkout-product > b { color: #503340; font-size: 14px; white-space: nowrap; }
.checkout-product__unavailable { margin: 3px 0 0; color: #b35170; font-size: 11px; }
.checkout-summary { position: sticky; top: 24px; background: linear-gradient(145deg, #fff, #fff7fa); }
.checkout-summary h2 { margin: 0 0 20px; color: #503340; font-size: 18px; font-weight: 700; }
.checkout-summary__row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 14px 0; color: #8a7280; font-size: 12px; }
.checkout-summary__row > span { display: flex; align-items: center; gap: 6px; }
.checkout-summary__savings { padding: 10px; border-radius: 10px; background: #ecf8f0; color: #177345; }
.checkout-summary__total { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 18px; border-top: 1px solid #f0dfe7; color: #503340; font-size: 13px; }
.checkout-summary__total strong { font-size: 23px; color: #e62f66; }
@media (max-width: 799px) {
  .checkout-page { padding: 10px 12px 110px; }
  .checkout-page h1 { font-size: 22px; }
  .checkout-layout { grid-template-columns: 1fr; gap: 16px; }
  .checkout-products, .checkout-summary { padding: 14px; }
  .checkout-summary { position: static; }
}
</style>
