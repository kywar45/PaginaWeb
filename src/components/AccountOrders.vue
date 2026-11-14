<template>
  <section class="account-orders-list" aria-label="Mis pedidos">
    <header class="orders-heading">
      <h2>Mis pedidos</h2>
      <p>Consulta todos tus pedidos, pagados y pendientes.</p>
    </header>
    <LoadingSkeleton v-if="loading" variant="list" :count="3" label="Cargando tus pedidos..." />
    <div v-else-if="errorMessage" class="orders-state" role="alert">
      <q-icon name="cloud_off" aria-hidden="true" />
      <p>{{ errorMessage }}</p>
      <q-btn flat no-caps color="primary" label="Reintentar" @click="loadOrders" />
    </div>
    <div v-else-if="!orders.length" class="orders-state">
      <q-icon name="inventory_2" aria-hidden="true" />
      <h3>Aún no tienes pedidos</h3>
      <p>Cuando realices tu primera compra, la encontrarás aquí.</p>
      <q-btn unelevated no-caps color="primary" label="Explorar productos" to="/categorias" />
    </div>
    <template v-else>
      <p class="orders-count" role="status">
        Total: {{ pagination.total.toLocaleString('es-MX') }} pedidos<span
          v-if="pagination.total_pages > 1"
        >
          · Página {{ page }} de {{ pagination.total_pages }}</span
        >
      </p>
      <div class="orders-list">
        <a
          v-for="order in orders"
          :key="order.id"
          class="order-card"
          :href="orderPdfHref(order.pdf_token)"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="`Abrir PDF del pedido ${order.orden || order.id} en otra pestaña`"
        >
          <div class="order-card__icon" aria-hidden="true"><q-icon name="inventory_2" /></div>
          <div class="order-card__info">
            <h3>Pedido #{{ order.orden || order.id }}</h3>
            <p class="order-card__date">
              <q-icon name="event" aria-hidden="true" />{{ formatDate(order.fecha) }}
            </p>
            <span v-if="order.estatus" class="order-card__status">{{ order.estatus }}</span>
            <span class="order-card__pdf"
              ><q-icon name="picture_as_pdf" aria-hidden="true" /> Ver pedido en PDF
              <q-icon name="north_east" aria-hidden="true"
            /></span>
          </div>
          <div class="order-card__amount">
            <span class="order-card__payment" :class="{ 'is-paid': order.pagado }"
              ><q-icon :name="order.pagado ? 'check_circle' : 'schedule'" aria-hidden="true" />{{
                order.pagado ? 'Pago registrado' : 'Sin pago registrado'
              }}</span
            ><strong>{{ money(order.total) }}</strong
            ><small>Total del pedido</small>
          </div>
        </a>
      </div>
      <StorePagination
        v-if="pagination.total_pages > 1"
        class="orders-pagination"
        :model-value="page"
        :max="pagination.total_pages"
        aria-label="Páginas de mis pedidos"
        @update:model-value="changePage"
      />
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StorePagination from 'src/components/StorePagination.vue'
import { accountOrders } from 'src/services/authApi'
import { orderPdfLocation } from 'src/utils/orderRoutes'
import { useAuthSession } from 'src/services/authSession'

const route = useRoute()
const router = useRouter()
function orderPdfHref(token) {
  const target = orderPdfLocation(token)
  return target ? router.resolve(target).href : undefined
}
const { currentUser } = useAuthSession()
const orders = ref([])
const pagination = ref({})
const loading = ref(true)
const errorMessage = ref('')
let request
const page = computed(() => {
  const number = Number(route.query.page)
  return Number.isSafeInteger(number) && number > 0 && number <= 1000000 ? number : 1
})
async function loadOrders() {
  request?.abort()
  const current = new AbortController()
  request = current
  loading.value = true
  errorMessage.value = ''
  orders.value = []
  pagination.value = {}
  try {
    const response = await accountOrders({ page: page.value, limit: 10, signal: current.signal })
    if (current.signal.aborted) return
    if (response.pagination.total_pages > 0 && page.value > response.pagination.total_pages) {
      await router.replace({
        path: '/panel',
        query: { seccion: 'pedidos', page: String(response.pagination.total_pages) },
      })
      return
    }
    orders.value = response.pedidos
    pagination.value = response.pagination
  } catch (error) {
    if (!current.signal.aborted)
      errorMessage.value =
        error.status === 401
          ? 'Tu sesión terminó. Vuelve a iniciar sesión para consultar tus pedidos.'
          : 'No pudimos cargar tus pedidos. Intenta de nuevo.'
  } finally {
    if (request === current) loading.value = false
  }
}
function changePage(value) {
  router.push({ path: '/panel', query: { seccion: 'pedidos', page: String(value) } })
}
function formatDate(value) {
  if (!value) return 'Sin fecha registrada'
  const date = new Date(value.replace(' ', 'T'))
  return Number.isNaN(date.getTime())
    ? 'Sin fecha registrada'
    : date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' })
}
function money(value) {
  return value === null
    ? 'Por confirmar'
    : Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}
watch([() => currentUser.value?.id, page], loadOrders, { immediate: true })
onBeforeUnmount(() => request?.abort())
</script>

<style scoped>
.orders-heading h2 {
  margin: 0 0 6px;
  color: #412b37;
  font-size: 19px;
  font-weight: 700;
  line-height: 1.4;
}
.orders-heading p {
  margin: 0 0 20px;
  color: #97808c;
  font-size: 12px;
}
.orders-count {
  margin: 0 0 12px;
  color: #88717d;
  font-size: 11px;
}
.orders-list {
  display: grid;
  gap: 12px;
}
.order-card {
  color: inherit;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
  border: 1px solid #eee4e9;
  border-radius: 13px;
  background: #fff;
}
.order-card:hover {
  border-color: #d992aa;
}
.order-card:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: 3px;
}
.order-card__pdf {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 8px;
  color: #ce2d5f;
  font-size: 10px;
  font-weight: 600;
}
.order-card__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #f7f4f6;
  color: #c63665;
  font-size: 23px;
}
.order-card__info {
  flex: 1;
  min-width: 0;
}
.order-card h3 {
  margin: 0 0 7px;
  color: #59404e;
  font-size: 14px;
  font-weight: 650;
  line-height: 1.4;
  overflow-wrap: anywhere;
}
.order-card__date {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0;
  color: #97808c;
  font-size: 11px;
}
.order-card__status {
  display: block;
  margin-top: 6px;
  color: #88717d;
  font-size: 11px;
  overflow-wrap: anywhere;
}
.order-card__amount {
  display: flex;
  align-items: flex-end;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.order-card__amount strong {
  color: #412b37;
  font-size: 20px;
  line-height: 1.4;
  overflow-wrap: anywhere;
}
.order-card__amount small {
  color: #97808c;
  font-size: 10px;
}
.order-card__payment {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 4px;
  padding: 4px 7px;
  border-radius: 6px;
  background: #f4f2f3;
  color: #806b76;
  font-size: 10px;
}
.order-card__payment.is-paid {
  background: #eaf7ef;
  color: #237447;
}
.orders-pagination {
  margin-top: 24px;
}
.orders-state {
  padding: 36px 20px;
  border: 1px solid #eee4e9;
  border-radius: 14px;
  text-align: center;
  color: #88717d;
}
.orders-state > .q-icon {
  margin-bottom: 12px;
  color: #c63665;
  font-size: 34px;
}
.orders-state h3 {
  margin: 0 0 8px;
  color: #59404e;
  font-size: 17px;
  line-height: 1.4;
}
.orders-state p {
  font-size: 12px;
  line-height: 1.6;
}
@media (max-width: 599px) {
  .order-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    padding: 14px;
  }
  .order-card__icon {
    display: none;
  }
  .order-card h3 {
    font-size: 13px;
  }
  .order-card__amount strong {
    font-size: 17px;
  }
  .order-card__payment {
    max-width: 130px;
    font-size: 9px;
  }
}
@media (max-width: 359px) {
  .order-card {
    grid-template-columns: minmax(0, 1fr);
  }
  .order-card__amount {
    align-items: flex-start;
    padding-top: 10px;
    border-top: 1px solid #f1e8ed;
  }
}
</style>
