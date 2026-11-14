<template>
  <q-page class="account-page">
    <PageBreadcrumbs
      :title="section ? selectedSection.label : 'Mi cuenta'"
      :back-to="section ? '/panel' : '/'"
      :parents="section ? [{ label: 'Mi cuenta', to: '/panel' }] : []"
    />
    <LoadingSkeleton
      v-if="sessionLoading || !sessionChecked"
      variant="account"
      :count="3"
      label="Cargando tu cuenta..."
    />
    <template v-else-if="currentUser">
      <header class="account-welcome">
        <div class="account-welcome__avatar" aria-hidden="true">{{ initials || '♡' }}</div>
        <div class="account-welcome__copy">
          <span>MI CUENTA SUPERNOVA</span>
          <h1>Hola, {{ firstName }}</h1>
          <p>Tu cuenta, tus compras y todo lo que necesitas.</p>
        </div>
        <div class="account-welcome__badge">
          <VipBadge v-if="isVip" /><span v-else
            ><q-icon name="person_outline" aria-hidden="true" /> Cliente Supernova</span
          >
        </div>
      </header>
      <div class="account-layout">
        <aside class="account-sidebar">
          <span class="account-sidebar__label">TU ESPACIO</span>
          <nav class="account-nav" aria-label="Secciones de mi cuenta">
            <RouterLink
              v-for="item in accountSections"
              :key="item.id"
              :to="sectionLocation(item.id)"
              :class="{ 'is-active': section === item.id }"
              :aria-current="section === item.id ? 'page' : undefined"
              ><q-icon :name="item.icon" aria-hidden="true" /><span>{{ item.label }}</span
              ><q-icon name="chevron_right" class="account-nav__arrow" aria-hidden="true"
            /></RouterLink>
          </nav>
          <RouterLink to="/carrito" class="account-sidebar__cart"
            ><q-icon name="shopping_bag" aria-hidden="true" /> Ir a mi carrito<q-icon
              name="north_east"
              aria-hidden="true"
          /></RouterLink>
          <q-btn
            flat
            no-caps
            icon="logout"
            label="Cerrar sesión"
            class="account-logout"
            :loading="logoutLoading"
            :disable="logoutLoading"
            @click="handleLogout"
          />
        </aside>
        <div class="account-content">
          <q-banner v-if="logoutError" rounded class="account-error" role="alert">{{
            logoutError
          }}</q-banner>
          <template v-if="!section">
            <div class="account-section-heading">
              <h2>Un vistazo a tu cuenta</h2>
              <span>Todo a la mano</span>
            </div>
            <section class="account-profile-preview account-box">
              <div class="account-section-heading">
                <h2>Tu información</h2>
              </div>
              <dl class="account-data account-data--summary">
                <div>
                  <dt>Nombre completo</dt>
                  <dd>{{ fullName }}</dd>
                </div>
                <div>
                  <dt>Correo electrónico</dt>
                  <dd>{{ currentUser.correo }}</dd>
                </div>
                <div>
                  <dt>Mis pedidos</dt>
                  <dd class="account-orders" aria-live="polite">
                    <LoadingSkeleton
                      v-if="ordersLoading"
                      variant="list"
                      :count="1"
                      label="Consultando tus pedidos..."
                      class="account-orders__loading"
                    />
                    <div v-else-if="ordersError" class="account-orders__error" role="alert">
                      <span>No disponible</span
                      ><button type="button" @click="loadSummary">Reintentar</button>
                    </div>
                    <template v-else
                      ><strong>{{ paidOrders.toLocaleString('es-MX') }}</strong
                      ><span>{{
                        paidOrders === 1 ? 'pedido pagado' : 'pedidos pagados'
                      }}</span></template
                    >
                  </dd>
                </div>
              </dl>
            </section>
            <div class="account-section-heading account-section-heading--spaced">
              <h2>¿Qué quieres hacer?</h2>
            </div>
            <div class="account-shortcuts">
              <RouterLink
                v-for="item in shortcuts"
                :key="item.id"
                :to="sectionLocation(item.id)"
                class="account-shortcut"
                ><span class="account-icon"><q-icon :name="item.icon" aria-hidden="true" /></span
                ><span
                  ><strong>{{ item.label }}</strong
                  ><small>{{ item.description }}</small></span
                ><q-icon name="arrow_forward" aria-hidden="true"
              /></RouterLink>
            </div>

            <div class="account-help-strip">
              <q-icon name="support_agent" aria-hidden="true" />
              <div>
                <strong>Estamos para ayudarte</strong
                ><span>¿Tienes dudas sobre tu cuenta o una compra?</span>
              </div>
              <a href="tel:+525524954670"
                >Llámanos <q-icon name="north_east" aria-hidden="true"
              /></a>
            </div>
          </template>
          <section v-else-if="section === 'perfil'" class="account-box account-section">
            <div class="account-section-heading">
              <h2>Mi perfil</h2>
              <q-icon name="person_outline" aria-hidden="true" />
            </div>
            <p class="account-section__intro">Estos son los datos asociados a tu cuenta.</p>
            <dl class="account-data account-data--profile">
              <div>
                <dt>Nombre</dt>
                <dd>{{ currentUser.nombre }}</dd>
              </div>
              <div>
                <dt>Apellidos</dt>
                <dd>{{ currentUser.apellido || 'Sin registrar' }}</dd>
              </div>
              <div>
                <dt>Correo electrónico</dt>
                <dd>{{ currentUser.correo }}</dd>
              </div>
              <div>
                <dt>Tipo de cuenta</dt>
                <dd>{{ accountTypeLabel }} <VipBadge v-if="isVip" /></dd>
              </div>
            </dl>
            <div class="account-note">
              <q-icon name="info_outline" aria-hidden="true" />
              <p>
                ¿Necesitas corregir algún dato?
                <a href="tel:+525524954670">Comunícate con nosotros</a> y te ayudamos.
              </p>
            </div>
          </section>
          <AccountOrders v-else-if="section === 'pedidos'" />
          <AccountAddresses v-else-if="section === 'direcciones'" />
          <section v-else-if="section === 'beneficios'" class="account-box account-section">
            <div class="account-section-heading">
              <h2>Mis beneficios</h2>
              <VipBadge v-if="isVip" />
            </div>
            <p class="account-section__intro">
              {{
                isVip
                  ? 'Tu cuenta tiene beneficios VIP activos.'
                  : 'Estos son los beneficios de comprar con tu cuenta.'
              }}
            </p>
            <ul class="account-benefits">
              <li>
                <q-icon name="shopping_bag" aria-hidden="true" />
                <div>
                  <strong>Tu carrito contigo</strong>
                  <p>Recupera tu carrito al iniciar sesión con tu cuenta.</p>
                </div>
              </li>
              <li>
                <q-icon name="sell" aria-hidden="true" />
                <div>
                  <strong>{{ isVip ? 'Precios VIP' : 'Precios por volumen' }}</strong>
                  <p>
                    {{
                      isVip
                        ? 'Tus precios especiales se aplican automáticamente en productos y carrito.'
                        : 'Combina piezas del mismo código para alcanzar los precios de mayoreo o caja que apliquen.'
                    }}
                  </p>
                </div>
              </li>
              <li>
                <q-icon :name="isVip ? 'inventory_2' : 'local_offer'" aria-hidden="true" />
                <div>
                  <strong>{{ isVip ? 'Productos agotados' : 'Ofertas disponibles' }}</strong>
                  <p>
                    {{
                      isVip
                        ? 'Tu cuenta VIP permite agregar productos agotados al carrito.'
                        : 'Consulta las promociones y descuentos vigentes de la tienda.'
                    }}
                  </p>
                </div>
              </li>
            </ul>
            <RouterLink to="/categorias" class="account-primary-link"
              >Explorar productos <q-icon name="arrow_forward" aria-hidden="true"
            /></RouterLink>
          </section>
          <section v-else-if="section === 'ayuda'" class="account-box account-section">
            <div class="account-section-heading">
              <h2>Ayuda y contacto</h2>
              <q-icon name="support_agent" aria-hidden="true" />
            </div>
            <p class="account-section__intro">
              Cuéntanos qué necesitas y te ayudamos con tu cuenta o tu compra.
            </p>
            <div class="account-contact">
              <q-icon name="call" aria-hidden="true" />
              <div>
                <span>Atención a clientes</span><a href="tel:+525524954670">55 2495 4670</a>
              </div>
            </div>
            <RouterLink to="/sucursales" class="account-shortcut"
              ><span class="account-icon"><q-icon name="storefront" aria-hidden="true" /></span
              ><span
                ><strong>Visita nuestras sucursales</strong
                ><small>Consulta direcciones y horarios</small></span
              ><q-icon name="arrow_forward" aria-hidden="true"
            /></RouterLink>
          </section>
          <q-btn
            flat
            no-caps
            icon="logout"
            label="Cerrar sesión"
            class="account-logout account-logout--mobile"
            :loading="logoutLoading"
            :disable="logoutLoading"
            @click="handleLogout"
          />
        </div>
      </div>
    </template>
    <div v-else class="account-box account-signin">
      <q-icon name="lock_outline" size="32px" />
      <p>Inicia sesión para consultar tu cuenta.</p>
      <q-btn unelevated no-caps color="primary" label="Iniciar sesión" to="/login" />
    </div>
  </q-page>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import VipBadge from 'src/components/VipBadge.vue'
import AccountOrders from 'src/components/AccountOrders.vue'
import AccountAddresses from 'src/components/AccountAddresses.vue'
import { useAuthSession } from 'src/services/authSession'
import { accountSummary } from 'src/services/authApi'

const router = useRouter()
const route = useRoute()
const accountSections = [
  { id: '', label: 'Mi resumen', icon: 'dashboard', description: 'Un vistazo a tu cuenta' },
  {
    id: 'perfil',
    label: 'Mi perfil',
    icon: 'person_outline',
    description: 'Consulta tus datos personales',
  },
  {
    id: 'pedidos',
    label: 'Mis pedidos',
    icon: 'inventory_2',
    description: 'Tus compras y su seguimiento',
  },
  {
    id: 'direcciones',
    label: 'Mis direcciones',
    icon: 'location_on',
    description: 'Tus lugares de entrega',
  },
  {
    id: 'beneficios',
    label: 'Mis beneficios',
    icon: 'workspace_premium',
    description: 'Conoce los beneficios de tu cuenta',
  },
  {
    id: 'ayuda',
    label: 'Ayuda y contacto',
    icon: 'support_agent',
    description: 'Estamos para ayudarte',
  },
]
const section = computed(() =>
  accountSections.some((item) => item.id === route.query.seccion) ? route.query.seccion : '',
)
const selectedSection = computed(() => accountSections.find((item) => item.id === section.value))
const shortcuts = accountSections.filter((item) =>
  ['perfil', 'pedidos', 'direcciones'].includes(item.id),
)
const sectionLocation = (id) => ({ path: '/panel', query: id ? { seccion: id } : {} })
const { currentUser, isVip, sessionChecked, sessionLoading, loadCurrentUser, logout } =
  useAuthSession()
const logoutLoading = ref(false)
const logoutError = ref('')
const paidOrders = ref(null)
const ordersLoading = ref(true)
const ordersError = ref(false)
let summaryRequest
async function loadSummary() {
  summaryRequest?.abort()
  const current = new AbortController()
  summaryRequest = current
  ordersLoading.value = true
  ordersError.value = false
  paidOrders.value = null
  try {
    const summary = await accountSummary({ signal: current.signal })
    if (!current.signal.aborted) paidOrders.value = summary.pedidos_pagados
  } catch {
    if (!current.signal.aborted) ordersError.value = true
  } finally {
    if (summaryRequest === current) ordersLoading.value = false
  }
}
onBeforeUnmount(() => summaryRequest?.abort())
const accountTypeLabel = computed(() => (isVip.value ? 'Cliente VIP' : 'Cliente Supernova'))
const firstName = computed(
  () =>
    String(currentUser.value?.nombre || '')
      .trim()
      .split(/\s+/)[0] || 'qué gusto verte',
)
const fullName = computed(() =>
  [currentUser.value?.nombre, currentUser.value?.apellido].filter(Boolean).join(' ').trim(),
)
const initials = computed(() =>
  [currentUser.value?.nombre, currentUser.value?.apellido]
    .map((value) => Array.from(String(value || '').trim())[0] || '')
    .join('')
    .toLocaleUpperCase('es-MX'),
)
onMounted(async () => {
  const user = await loadCurrentUser()
  if (!user) {
    router.replace('/login')
    return
  }
  await loadSummary()
})
async function handleLogout() {
  logoutError.value = ''
  logoutLoading.value = true
  try {
    await logout()
    router.replace('/login')
  } catch (error) {
    logoutError.value = error?.message || 'No pudimos cerrar sesión. Intenta de nuevo.'
  } finally {
    logoutLoading.value = false
  }
}
</script>

<style scoped lang="scss">
.account-page {
  max-width: 1280px;
  margin: 0 auto;
  padding: 20px 32px 48px;
  color: #44343d;
}
.account-welcome {
  display: flex;
  align-items: center;
  gap: 18px;
  margin: 14px 0 24px;
  padding: 28px;
  overflow: hidden;
  border-radius: 18px;
  background:
    linear-gradient(157deg, transparent 0 33%, #ffffff10 33.5% 38%, transparent 38.5%),
    radial-gradient(
      110% 90% at 5% -18%,
      transparent 64%,
      #ffffff25 65%,
      #ffffff08 67%,
      transparent 69%
    ),
    linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
}
.account-welcome__avatar {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 64px;
  height: 64px;
  border: 1px solid #ffffff70;
  border-radius: 50%;
  background: #ffffff20;
  font-size: 24px;
  font-weight: 600;
}
.account-welcome__copy {
  flex: 1;
  min-width: 0;
}
.account-welcome__copy > span {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.12em;
}
.account-welcome h1 {
  margin: 5px 0 6px;
  font-size: 30px;
  font-weight: 750;
  line-height: 1.2;
  overflow-wrap: anywhere;
}
.account-welcome p {
  margin: 0;
  font-size: 12px;
  color: #fff;
}
.account-welcome__badge > span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid #ffffff55;
  border-radius: 30px;
  font-size: 11px;
  white-space: nowrap;
}
.account-layout {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  align-items: start;
  gap: 26px;
}
.account-sidebar {
  padding: 16px 12px 10px;
  border: 1px solid #eee4e9;
  border-radius: 14px;
}
.account-sidebar__label {
  display: block;
  margin: 0 12px 12px;
  color: #a08c96;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.08em;
}
.account-nav {
  display: grid;
  gap: 5px;
}
.account-nav a {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 43px;
  padding: 10px 12px;
  border-radius: 8px;
  color: #75616c;
  text-decoration: none;
  font-size: 12px;
}
.account-nav a > .q-icon:first-child {
  font-size: 19px;
}
.account-nav__arrow {
  margin-left: auto;
  color: #b89ea9;
}
.account-nav a:hover {
  background: #f6f4f5;
}
.account-nav a.is-active {
  background: #df2f61;
  color: #fff;
  font-weight: 600;
}
.account-nav a.is-active .q-icon {
  color: #fff;
}
.account-sidebar__cart {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 16px 0 4px;
  padding: 16px 12px 12px;
  border-top: 1px solid #f1e8ed;
  color: #a65c77;
  font-size: 12px;
  text-decoration: none;
}
.account-sidebar__cart .q-icon:last-child {
  margin-left: auto;
}
.account-logout {
  justify-content: flex-start;
  width: 100%;
  min-height: 40px;
  padding: 8px 12px;
  color: #9a7b88;
  font-size: 11px;
}
.account-logout--mobile {
  display: none;
}
.account-content {
  min-width: 0;
}
.account-section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}
.account-section-heading h2 {
  margin: 0;
  color: #412b37;
  font-size: 17px;
  line-height: 1.4;
  font-weight: 700;
}
.account-section-heading > span {
  color: #a08c96;
  font-size: 10px;
}
.account-section-heading > .q-icon {
  color: #c63665;
  font-size: 24px;
}
.account-section-heading--spaced {
  margin-top: 26px;
}
.account-overview {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.account-box {
  border: 1px solid #eee4e9;
  border-radius: 14px;
  background: #fff;
}
.account-membership,
.account-cart {
  padding: 20px;
}
.account-icon {
  display: inline-grid;
  place-items: center;
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #f7f4f6;
  color: #c63665;
  font-size: 21px;
}
.account-caption {
  display: block;
  color: #9b8390;
  font-size: 10px;
  font-weight: 600;
}
.account-membership > .account-caption {
  margin-top: 12px;
}
.account-membership h3 {
  margin: 4px 0 7px;
  color: #412b37;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.3;
}
.account-membership p,
.account-cart p {
  margin: 0 0 14px;
  color: #97808c;
  font-size: 11px;
  line-height: 1.6;
}
.account-text-link {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #ce2d5f;
  font-size: 11px;
  font-weight: 600;
  text-decoration: none;
}
.account-cart__heading {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.account-cart__amount {
  margin: 2px 0 5px;
  color: #412b37;
  font-size: 29px;
  font-weight: 750;
  line-height: 1.3;
  overflow-wrap: anywhere;
}
.account-cart__loading {
  margin: 0;
}
.account-cart__loading :deep(.loading-skeleton__row) {
  border: 0;
  padding: 0;
}
.account-cart__loading :deep(.loading-skeleton__thumb) {
  display: none;
}
.account-cart__error p {
  margin-bottom: 5px;
}
.account-cart__error .q-btn {
  padding-left: 0;
  color: #ce2d5f;
  font-size: 11px;
}
.account-shortcuts {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}
.account-shortcut {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 16px 12px;
  border: 1px solid #eee4e9;
  border-radius: 12px;
  background: #fff;
  color: #59404e;
  text-decoration: none;
}
.account-shortcut > span:not(.account-icon) {
  flex: 1;
  min-width: 0;
}
.account-shortcut strong {
  display: block;
  font-size: 12px;
  font-weight: 650;
}
.account-shortcut small {
  display: block;
  margin-top: 4px;
  color: #9b8390;
  font-size: 10px;
  line-height: 1.5;
}
.account-shortcut > .q-icon {
  color: #cb7794;
  font-size: 16px;
}
.account-shortcut:hover {
  border-color: #d992aa;
}
.account-profile-preview {
  padding: 20px;
}
.account-data {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin: 0;
}
.account-data > div {
  min-width: 0;
}
.account-data--summary {
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 130px;
}
.account-orders {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.account-orders > strong {
  color: #df2f61;
  font-size: 24px;
  line-height: 1.2;
}
.account-orders > span,
.account-orders__error {
  color: #97808c;
  font-size: 10px;
}
.account-orders__error button {
  display: block;
  margin-top: 4px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #ce2d5f;
  font: inherit;
  cursor: pointer;
  text-decoration: underline;
}
.account-orders__loading {
  max-width: 100px;
  margin: 0;
}
.account-orders__loading :deep(.loading-skeleton__row) {
  padding: 0;
  border: 0;
}
.account-orders__loading :deep(.loading-skeleton__thumb),
.account-orders__loading :deep(.loading-skeleton__lines > :not(:first-child)) {
  display: none;
}
.account-data dt {
  margin-bottom: 5px;
  color: #9b8390;
  font-size: 10px;
}
.account-data dd {
  margin: 0;
  color: #59404e;
  font-size: 13px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}
.account-help-strip {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 18px;
  padding: 16px 0;
}
.account-help-strip > .q-icon {
  color: #c63665;
  font-size: 26px;
}
.account-help-strip > div {
  flex: 1;
  min-width: 0;
}
.account-help-strip strong {
  display: block;
  font-size: 12px;
}
.account-help-strip span {
  display: block;
  margin-top: 3px;
  color: #9b8390;
  font-size: 10px;
}
.account-help-strip > a {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #ce2d5f;
  font-size: 11px;
  font-weight: 600;
  text-decoration: none;
}
.account-section {
  padding: 24px;
}
.account-section__intro {
  margin: -5px 0 24px;
  color: #97808c;
  font-size: 12px;
  line-height: 1.6;
}
.account-data--profile {
  row-gap: 24px;
}
.account-data--profile dd {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}
.account-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 28px;
  padding-top: 18px;
  border-top: 1px solid #f1e8ed;
  color: #97808c;
  font-size: 11px;
}
.account-note > .q-icon {
  font-size: 17px;
}
.account-note p {
  margin: 0;
  line-height: 1.7;
}
.account-note a {
  color: #ce2d5f;
}
.account-pending {
  display: flex;
  align-items: center;
  flex-direction: column;
  padding: 40px 24px;
  text-align: center;
}
.account-pending__icon {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  margin-bottom: 20px;
  border: 1px solid #eee4e9;
  border-radius: 18px;
  background: #f9f7f8;
  color: #c63665;
  font-size: 32px;
}
.account-pending h2 {
  margin: 10px 0;
  color: #412b37;
  font-size: 21px;
  font-weight: 700;
  line-height: 1.4;
}
.account-pending p {
  max-width: 440px;
  margin: 0 0 24px;
  color: #97808c;
  font-size: 12px;
  line-height: 1.8;
}
.account-primary-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 42px;
  padding: 10px 18px;
  border-radius: 9px;
  background: #df2f61;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
}
.account-benefits {
  display: grid;
  gap: 24px;
  margin: 0 0 28px;
  padding: 0;
  list-style: none;
}
.account-benefits li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.account-benefits li > .q-icon {
  margin-top: 2px;
  color: #c63665;
  font-size: 22px;
}
.account-benefits strong {
  font-size: 13px;
}
.account-benefits p {
  margin: 4px 0 0;
  color: #97808c;
  font-size: 12px;
  line-height: 1.7;
}
.account-contact {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding: 20px 0;
}
.account-contact > .q-icon {
  color: #c63665;
  font-size: 28px;
}
.account-contact span {
  display: block;
  margin-bottom: 5px;
  color: #97808c;
  font-size: 11px;
}
.account-contact a {
  color: #412b37;
  font-size: 21px;
  font-weight: 700;
  text-decoration: none;
}
.account-error {
  margin-bottom: 16px;
  background: #fff1f2;
  color: #94224d;
  font-size: 12px;
}
.account-signin {
  padding: 30px;
  color: #97808c;
  text-align: center;
}
.account-page a:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: 3px;
}
@media (max-width: 1199px) {
  .account-layout {
    grid-template-columns: 200px minmax(0, 1fr);
    gap: 20px;
  }
  .account-shortcut {
    flex-wrap: wrap;
  }
  .account-shortcut > .account-icon {
    width: 30px;
    height: 30px;
    font-size: 18px;
  }
}
@media (max-width: 1023px) {
  .account-page {
    padding: 10px 16px 110px;
  }
  .account-welcome {
    gap: 12px;
    margin-top: 8px;
    padding: 20px;
  }
  .account-welcome__avatar {
    width: 48px;
    height: 48px;
    font-size: 19px;
  }
  .account-welcome h1 {
    font-size: 24px;
  }
  .account-welcome p {
    font-size: 11px;
  }
  .account-welcome__badge {
    display: none;
  }
  .account-layout {
    display: block;
  }
  .account-sidebar {
    margin-bottom: 20px;
    padding: 0;
    border: 0;
    border-radius: 0;
  }
  .account-sidebar__label,
  .account-sidebar__cart,
  .account-sidebar > .account-logout {
    display: none;
  }
  .account-nav {
    display: flex;
    gap: 7px;
    padding: 0 0 7px;
    overflow-x: auto;
  }
  .account-nav a {
    flex-shrink: 0;
    min-height: 38px;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid #eee4e9;
    border-radius: 20px;
    font-size: 11px;
  }
  .account-nav a.is-active {
    border-color: #df2f61;
  }
  .account-nav a > .q-icon:first-child {
    font-size: 17px;
  }
  .account-nav__arrow {
    display: none;
  }
  .account-logout--mobile {
    display: flex;
    justify-content: center;
    margin-top: 22px;
  }
}
@media (max-width: 599px) {
  .account-welcome {
    padding: 18px 14px;
    border-radius: 14px;
  }
  .account-welcome h1 {
    font-size: 21px;
  }
  .account-welcome__copy > span {
    font-size: 8px;
  }
  .account-welcome p {
    font-size: 10px;
    line-height: 1.6;
  }
  .account-section-heading h2 {
    font-size: 15px;
  }
  .account-section-heading > span {
    display: none;
  }
  .account-overview {
    gap: 10px;
  }
  .account-membership,
  .account-cart {
    padding: 14px;
  }
  .account-membership h3 {
    font-size: 17px;
  }
  .account-cart__heading {
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
  }
  .account-cart__amount {
    font-size: 23px;
  }
  .account-shortcuts {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  .account-shortcut {
    flex-wrap: nowrap;
    padding: 13px;
  }
  .account-profile-preview,
  .account-section {
    padding: 16px;
  }
  .account-data {
    grid-template-columns: minmax(0, 1fr);
    gap: 18px;
  }
  .account-help-strip {
    gap: 9px;
    flex-wrap: wrap;
  }
  .account-help-strip > a {
    margin-left: 35px;
  }
  .account-pending {
    padding: 30px 18px;
  }
  .account-pending h2 {
    font-size: 19px;
  }
}
</style>
