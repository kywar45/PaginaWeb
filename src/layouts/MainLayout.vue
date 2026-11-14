<template>
  <q-layout view="hHh lpR fff">
    <!-- SUPERNOVA NAILS - HEADER -->
    <q-header class="sn-header sn-header--premium">
      <!-- Barra de contacto (solo desktop) -->
      <div class="sn-topbar gt-sm">
        <div class="sn-trustbar">
          <div class="sn-trustbar__group">
            <span><q-icon name="local_shipping" /> Envíos a todo México</span>
            <span><q-icon name="verified_user" /> Compra segura</span>
            <span><q-icon name="handshake" /> Mayoreo y menudeo</span>
            <span><q-icon name="category" /> +10,000 productos</span>
            <a href="tel:+525524954670"><q-icon name="call" /> Atención: 55 2495 4670</a>
          </div>
        </div>
      </div>
      <div class="sn-mobile-head lt-md">
        <div class="sn-mobile-head__row">
          <q-btn
            flat
            round
            dense
            :icon="accountInitials ? undefined : 'person_outline'"
            :label="accountInitials || undefined"
            color="dark"
            class="sn-mobile-profile"
            aria-label="Mi cuenta"
            @click="goToAccount"
          />

          <q-input
            v-model="searchQuery"
            outlined
            rounded
            dense
            placeholder="Buscar en Supernova"
            maxlength="80"
            @focus="focusedSearch = 'mobile'"
            @click="openSearchSuggestions($event, 'mobile')"
            @update:model-value="focusedSearch = 'mobile'"
            @keydown="searchKeydown($event, mobileSuggestions)"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            aria-controls="mobile-search-options"
            :aria-expanded="focusedSearch === 'mobile'"
            :aria-activedescendant="mobileSuggestions?.activeDescendant"
            aria-label="Buscar productos"
            class="sn-mobile-head__search"
            bg-color="white"
          >
            <StoreSearchSuggestions ref="mobileSuggestions" :query="searchQuery" :active="focusedSearch === 'mobile'" list-id="mobile-search-options" anchor-target=".sn-mobile-head__search" @search="submitSearch" @close="focusedSearch === 'mobile' && (focusedSearch = '')" @open="focusedSearch = 'mobile'" />
            <template #prepend>
              <q-btn flat round dense icon="search" aria-label="Buscar productos" @click="submitSearch" />
            </template>
          </q-input>

        </div>
        <nav class="sn-mobile-shortcuts" aria-label="Secciones de la tienda">
          <RouterLink to="/ofertas" class="sn-mobile-shortcuts__offers">
            <q-icon name="local_offer" /> Ofertas
          </RouterLink>
          <RouterLink to="/nuevos"><q-icon name="shield" /> Nuevos</RouterLink>
          <RouterLink to="/mas-vendidos"><q-icon name="trending_up" /> Más vendidos</RouterLink>
          <RouterLink to="/marcas"><q-icon name="workspace_premium" /> Nuestras marcas</RouterLink>
          <RouterLink to="/sucursales"><q-icon name="storefront" /> Sucursales</RouterLink>
        </nav>
      </div>

      <!-- Toolbar principal -->
      <q-toolbar class="q-py-sm gt-sm sn-desktop-toolbar" style="min-height: 60px; padding: 0">
        <div
          style="
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 16px;
          "
        >
          <!-- ── MOBILE: barra de búsqueda expandida ── -->
          <template v-if="searchOpen">
            <q-btn
              flat
              round
              dense
              icon="arrow_back"
              color="dark"
              class="lt-md"
              @click="closeSearch"
            />
            <q-input
              v-model="searchQuery"
              filled
              rounded
              dense
              placeholder="Buscar productos..."
              maxlength="80"
              @focus="focusedSearch = 'expanded'"
              @click="openSearchSuggestions($event, 'expanded')"
              @update:model-value="focusedSearch = 'expanded'"
              @keydown="searchKeydown($event, expandedSuggestions)"
              autocomplete="off"
              role="combobox"
              aria-label="Buscar productos"
              aria-autocomplete="list"
              aria-controls="expanded-search-options"
              :aria-expanded="focusedSearch === 'expanded'"
              :aria-activedescendant="expandedSuggestions?.activeDescendant"
              class="lt-md sn-mobile-search"
              bg-color="white"
              style="flex: 1"
              autofocus
            >
              <StoreSearchSuggestions ref="expandedSuggestions" :query="searchQuery" :active="focusedSearch === 'expanded'" list-id="expanded-search-options" anchor-target=".sn-mobile-search" @search="submitSearch" @close="focusedSearch === 'expanded' && (focusedSearch = '')" @open="focusedSearch = 'expanded'" />
              <template #append>
                <q-btn
                  v-if="searchQuery"
                  flat
                  round
                  dense
                  icon="close"
                  size="xs"
                  color="grey"
                  @click="searchQuery = ''"
                />
                <q-btn flat round dense icon="search" aria-label="Buscar productos" @click="submitSearch" />
              </template>
            </q-input>
          </template>

          <!-- ── MOBILE/DESKTOP: barra normal ── -->
          <template v-else>
            <!-- Hamburger (solo mobile) -->
            <q-btn
              flat
              round
              dense
              icon="menu"
              color="dark"
              class="lt-md"
              @click="leftDrawerOpen = !leftDrawerOpen"
            />

            <!-- Logo -->
            <div
              class="sn-logo-main no-wrap"
              style="min-width: fit-content; cursor: pointer"
              @click="$router.push('/')"
            >
              <img v-image-skeleton :src="publicAsset('img/logob.png')" alt="Importadora Supernova" width="180px" />
            </div>

            <!-- Separador flexible (desktop) -->
            <q-space class="gt-sm" />

            <!-- Barra de búsqueda (solo desktop) -->
            <q-input
              v-model="searchQuery"
              filled
              rounded
              placeholder="Buscar productos, marcas y más..."
              maxlength="80"
              aria-label="Buscar productos"
              @focus="focusedSearch = 'desktop'"
              @click="openSearchSuggestions($event, 'desktop')"
              @update:model-value="focusedSearch = 'desktop'"
              @keydown="searchKeydown($event, desktopSuggestions)"
              autocomplete="off"
              role="combobox"
              aria-autocomplete="list"
              aria-controls="desktop-search-options"
              :aria-expanded="focusedSearch === 'desktop'"
              :aria-activedescendant="desktopSuggestions?.activeDescendant"
              class="sn-search gt-sm"
              bg-color="white"
              style="width: 100%; max-width: 680px"
            >
              <StoreSearchSuggestions ref="desktopSuggestions" :query="searchQuery" :active="focusedSearch === 'desktop'" list-id="desktop-search-options" anchor-target=".sn-search" @search="submitSearch" @close="focusedSearch === 'desktop' && (focusedSearch = '')" @open="focusedSearch = 'desktop'" />
              <template #append>
                <q-btn
                  v-if="searchQuery"
                  flat
                  round
                  dense
                  icon="close"
                  size="xs"
                  color="grey"
                  @click="searchQuery = ''"
                />
                <q-btn
                  unelevated
                  round
                  dense
                  icon="search"
                  color="primary"
                  class="sn-search-submit"
                  aria-label="Buscar productos"
                  @click="submitSearch"
                />
              </template>
            </q-input>

            <q-space />

            <!-- Ícono buscar (solo mobile) -->
            <q-btn
              flat
              round
              icon="search"
              color="dark"
              size="md"
              class="lt-md"
              @click="searchOpen = true"
            />

            <!-- Acciones desktop -->
            <div class="row items-center no-wrap q-gutter-xs gt-sm">
              <q-btn
                flat
                round
                icon="favorite_border"
                color="dark"
                size="md"
                class="sn-header-icon-btn"
              >
                <q-tooltip>Lista de deseos</q-tooltip>
              </q-btn>
            </div>

            <!-- Carrito (siempre visible) -->
            <q-btn
              flat
              round
              icon="shopping_cart"
              to="/carrito"
              color="dark"
              size="md"
              class="q-ml-xs sn-cart-btn sn-header-icon-btn"
            >
              <q-badge class="sn-cart-count" floating rounded>{{
                cartCount
              }}</q-badge>
              <q-tooltip>Ver carrito</q-tooltip>
            </q-btn>
            <q-btn
              flat
              no-caps
              :round="false"
              color="dark"
              size="md"
              class="sn-account-btn"
              @click="goToAccount"
            >
              <q-icon name="person" size="22px" />
              <span class="sn-account-copy gt-sm">
                <span class="sn-account-hello">{{ currentUser ? 'Hola!' : 'Iniciar sesión' }}</span>
                <span class="sn-account-name">{{ accountName || 'Mi cuenta' }}</span>
              </span>
              <q-tooltip>{{ currentUser ? 'Ir a mi panel' : 'Mi cuenta' }}</q-tooltip>
            </q-btn>
            <span class="sn-header-motto"
              >De todo para ti<br />a precios para ti. <span aria-hidden="true">♡</span></span
            >
          </template>
        </div>
      </q-toolbar>

      <div class="sn-desktop-nav gt-sm">
        <div class="sn-desktop-nav__inner">
          <q-btn
            unelevated
            no-caps
            color="primary"
            icon="menu"
            label="Todas las categorías"
            :aria-expanded="categoriesMenu"
            aria-label="Todas las categorías"
            class="sn-all-category-trigger"
            :class="{ 'sn-category-trigger--active': categoriesMenu }"
          >
            <q-menu
              v-model="categoriesMenu"
              anchor="bottom left"
              self="top left"
              :offset="[0, 2]"
              class="sn-catalog-popup"
              max-height="calc(100dvh - 195px)"
            >
              <LoadingSkeleton v-if="categoriesLoading" variant="categories" class="q-pa-md" label="Cargando categorías..." />
              <StoreCategoryMenu
                v-else
                :categories="categories"
                @select-category="(category) => selectCategoryFromMenu(category.slug)"
                @select-subcategory="
                  (category, sub) => selectSubcategoryFromMenu(category.slug, sub.slug)
                "
                @select-product="showMenuProduct"
                @offers="showOffers"
              />
            </q-menu>
          </q-btn>

          <q-btn
            flat
            no-caps
            icon="local_offer"
            label="Ofertas"
            class="sn-desktop-nav__link"
            @click="showOffers"
          >
          </q-btn>
          <q-btn flat no-caps icon="shield" label="Nuevos" to="/nuevos" class="sn-desktop-nav__link" />
          <q-btn
            flat
            no-caps
            icon="trending_up"
            label="Más vendidos"
            to="/mas-vendidos"
            class="sn-desktop-nav__link"
          />
          <q-btn
            flat
            no-caps
            icon="workspace_premium"
            label="Nuestras marcas"
            to="/marcas"
            class="sn-desktop-nav__link"
          />
          <q-btn flat no-caps icon="storefront" label="Sucursales" to="/sucursales" class="sn-desktop-nav__link" />
        </div>
      </div>
    </q-header>

    <Teleport to="body">
      <div
        v-if="categoriesMenu"
        class="sn-catalog-backdrop"
        aria-hidden="true"
        @click="categoriesMenu = false"
      />
    </Teleport>



    <!-- ════════════════════════════════ DRAWER MOBILE ══════════════════════════════ -->
    <q-drawer v-model="leftDrawerOpen" bordered :width="290" class="lt-md">
      <!-- Cabecera del drawer -->
      <div class="sn-drawer-header">
        <div class="row items-center justify-between q-mb-lg">
          <div>
            <div class="text-white font-playfair text-h6 text-weight-bold">SUPERNOVA</div>
            <div class="text-caption" style="color: #f8bbd9; letter-spacing: 3px">
              NAILS & BEAUTY
            </div>
          </div>
          <q-btn flat round dense icon="close" color="white" @click="leftDrawerOpen = false" />
        </div>

        <!-- Avatar / usuario -->
        <div class="row items-center cursor-pointer" @click="goToAccount">
          <q-avatar color="white" size="42px">
            <q-icon name="person" color="primary" size="sm" />
          </q-avatar>
          <div class="q-ml-sm">
            <div class="text-white text-weight-bold text-body2">{{ accountGreeting }}</div>
            <div class="text-caption" style="color: #f8bbd9">
              {{ currentUser ? 'Ir a mi panel' : 'Ingresa o registrate' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Categorías -->
      <q-list padding>
        <q-item-label
          header
          class="text-weight-bold text-uppercase text-caption"
          style="color: var(--q-primary)"
        >
          Categorías
        </q-item-label>

        <LoadingSkeleton v-if="categoriesLoading" variant="list" :count="4" class="q-pa-sm" label="Cargando categorías..." />
        <template v-for="cat in categories" :key="cat.slug">
          <!-- Sin subcategorías: item simple -->
          <q-item
            v-if="!cat.subcategories || cat.subcategories.length === 0"
            clickable
            v-ripple
            :active="activeCategory === cat.slug"
            active-class="bg-pink-1 text-primary"
            @click="selectCategory(cat.slug)"
          >
            <q-item-section avatar>
              <img v-image-skeleton v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 24px; height: 24px" />
              <q-icon v-else name="category" color="grey-6" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ cat.name }}</q-item-label>
            </q-item-section>
            <q-item-section side v-if="cat.badge">
              <q-badge :color="cat.badgeColor || 'primary'" :label="cat.badge" />
            </q-item-section>
          </q-item>

          <!-- Con subcategorías: expansion item -->
          <q-expansion-item
            v-else
            :label="cat.name"
            :default-opened="activeCategory === cat.slug"
            :header-class="activeCategory === cat.slug ? 'text-primary' : ''"
            expand-icon-class="text-grey-6"
            @before-show="activeCategory = cat.slug"
          >
            <template #header>
              <q-item-section avatar>
                <img v-image-skeleton v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 100%; height: 24px" />
                <q-icon v-else name="category" color="grey-6" />
              </q-item-section>
              <q-item-section>
                <q-item-label
                  :class="activeCategory === cat.slug ? 'text-primary text-weight-medium' : ''"
                >
                  {{ cat.name }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="cat.badge">
                <q-badge :color="cat.badgeColor || 'primary'" :label="cat.badge" />
              </q-item-section>
            </template>

            <q-item
              v-for="sub in cat.subcategories"
              :key="sub.slug"
              clickable
              v-ripple
              class="q-pl-xl"
              :active="activeSubcategory === sub.slug"
              active-class="bg-pink-1 text-primary"
              @click="selectSubcategory(cat.slug, sub.slug)"
            >
              <q-item-section avatar>
                <q-icon name="chevron_right" size="xs" color="grey-5" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ sub.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-expansion-item>
        </template>

        <q-separator class="q-my-sm" />

        <q-item-label
          header
          class="text-weight-bold text-uppercase text-caption"
          style="color: #c4516f"
        >
          Mi cuenta
        </q-item-label>
        <q-item clickable v-ripple @click="goToAccount">
          <q-item-section avatar><q-icon name="person" color="pink-4" /></q-item-section>
          <q-item-section>{{ currentUser ? 'Mi panel' : 'Ingresar' }}</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="favorite" color="pink-4" /></q-item-section>
          <q-item-section>Lista de deseos</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="receipt_long" color="pink-4" /></q-item-section>
          <q-item-section>Mis pedidos</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="headset_mic" color="pink-4" /></q-item-section>
          <q-item-section>Contacto / Soporte</q-item-section>
        </q-item>
      </q-list>

      <!-- Social -->
      <div class="q-pa-md q-mt-auto">
        <q-separator class="q-mb-md" />
        <div class="text-caption text-grey q-mb-xs">Seguinos en</div>
        <div class="row q-gutter-sm">
          <q-btn round flat icon="camera_alt" size="sm" />
          <q-btn round flat icon="music_note" size="sm" />
          <q-btn round flat icon="phone_iphone" size="sm" />
        </div>
      </div>
    </q-drawer>

    <!-- ══════════════════════════════════ CONTENIDO ══════════════════════════════════ -->
    <q-page-container class=sn-page-container>
      <aside v-if="cartNotice" class="cart-merge-notice" :role="cartNotice.retry ? 'alert' : 'status'">
        <span class="cart-merge-notice__icon" aria-hidden="true">
          <q-icon :name="cartNotice.retry ? 'sync_problem' : 'check_circle'" />
        </span>
        <div class="cart-merge-notice__copy">
          <strong>{{ cartNotice.retry ? 'Tu carrito sigue guardado' : 'Carrito actualizado' }}</strong>
          <p>{{ cartNotice.message }}</p>
        </div>
        <div class="cart-merge-notice__actions">
          <q-btn v-if="cartNotice.retry" flat no-caps label="Reintentar" :loading="cartBusy" @click="retryCartMerge" />
          <q-btn unelevated no-caps label="Ver carrito" icon-right="arrow_forward" to="/carrito" class="cart-merge-notice__link" />
        </div>
        <q-btn flat round dense icon="close" class="cart-merge-notice__close" aria-label="Cerrar aviso del carrito" @click="clearCartNotice" />
      </aside>
      <router-view />
    </q-page-container>

    <!-- ══════════════════════════════════ FOOTER ════════════════════════════════════ -->
    <q-footer bordered :elevated="false" class="sn-footer sn-footer--premium" :class="{ 'sn-footer--keyboard': keyboardVisible }">
      <!-- DESKTOP FOOTER -->
      <div class="sn-footer-desktop gt-sm">
        <div class="sn-footer-grid">
          <div class="sn-footer-identity">
            <img v-image-skeleton :src="publicAsset('img/logob.png')" alt="Importadora Supernova" class="sn-footer-logo" />
            <p class="sn-footer-motto">Estamos,<br />aquí para tí ♡</p>
          </div>
          <div>
            <div class="sn-footer-title">Contacto</div>
            <div v-for="contact in footerContacts" :key="contact.title" class="sn-footer-block">
              <div class="sn-footer-line">
                <q-icon :name="contact.icon" /><span>{{ contact.title }}</span>
              </div>
              <a :href="contact.href" class="sn-footer-line sn-footer-phone"
                ><q-icon name="call" /><span>{{ contact.phone }}</span></a
              >
              <div class="sn-footer-line">
                <q-icon name="schedule" /><span>{{ contact.hours }}</span>
              </div>
            </div>
          </div>
          <div class="sn-footer-stores-column">
            <div class="sn-footer-title">Tiendas oficiales</div>
            <div class="sn-footer-stores-grid">
              <article v-for="store in footerStores" :key="store.phone" class="sn-store-card">
                <q-icon name="location_on" />
                <div class="sn-store-card__body">
                  <p>{{ store.address }}</p>
                  <a :href="store.href">{{ store.phone }}</a>
                  <span>{{ store.hours }}</span>
                </div>
                <q-btn
                  flat
                  dense
                  round
                  icon="map"
                  :href="store.mapsHref"
                  target="_blank"
                  rel="noopener"
                  aria-label="Abrir ubicacion en Maps"
                />
              </article>
            </div>
          </div>
          <div>
            <div class="sn-footer-title">Mi cuenta</div>
            <button type="button" class="sn-footer-account" @click="goToAccount">
              {{ currentUser ? 'Mi panel' : 'Iniciar sesión' }}
            </button>
            <p class="sn-footer-line">Consulta tus pedidos y los datos de tu cuenta.</p>
          </div>
          <div>
            <div class="sn-footer-title">Síguenos</div>
            <div class="sn-footer-socials">
              <a
                v-for="social in footerSocialLinks"
                :key="social.name"
                :href="social.href"
                target="_blank"
                rel="noopener"
                class="sn-footer-social"
                :aria-label="social.name"
              >
                <img v-image-skeleton :src="social.image" :alt="social.name" /><q-tooltip>{{
                  social.name
                }}</q-tooltip>
              </a>
            </div>
            <!--<p class="sn-footer-motto sn-footer-motto--small">
              Más que belleza,<br />es tu estilo ♡
            </p>-->
          </div>
        </div>
        <div class="sn-footer-bottom">
          <span
            >© {{ new Date().getFullYear() }} Importadora Supernova. Todos los derechos
            reservados.</span
          >
          <span>Belleza · Calidad · Confianza · Siempre contigo</span>
        </div>
      </div>

      <!-- MOBILE BOTTOM NAV -->
      <div class="sn-mobile-nav lt-md">
        <q-tabs
          v-model="mobileTab"
          dense
          align="justify"
          active-color="primary"
          indicator-color="transparent"
        >
          <q-tab name="home" icon="home" label="Inicio" @click="goToHome" />
          <q-tab name="search" icon="format_list_bulleted" label="Categorías" @click="goToCategories" />
          <q-tab name="fav" icon="favorite_border" label="Favoritos" />
          <q-tab name="cart" icon="shopping_cart" label="Carrito" @click="router.push('/carrito')">
            <q-badge color="primary" floating rounded style="font-size: 10px">{{
              cartCount
            }}</q-badge>
          </q-tab>
          <q-tab name="user" icon="person" label="Cuenta" @click="goToAccount" />
        </q-tabs>
      </div>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { publicAsset } from 'src/utils/publicAsset'
import { useProductNavigation } from 'src/services/productNavigation'
import { useCart } from 'src/services/cart'
const openProduct = useProductNavigation()
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { catalogLocation } from 'src/utils/catalogRoutes'
import StoreCategoryMenu from 'src/components/StoreCategoryMenu.vue'
import StoreSearchSuggestions from 'src/components/StoreSearchSuggestions.vue'
import { useSearchHistory } from 'src/services/searchHistory'
import { normalizeSearch } from 'src/utils/searchHistory'
import { useMobileKeyboard } from 'src/composables/useMobileKeyboard'
import { getCategories } from 'src/services/categoryApi'
import { useAuthSession } from 'src/services/authSession'

const router = useRouter()
const keyboardVisible = useMobileKeyboard()
const { currentUser, sessionChecked, loadCurrentUser } = useAuthSession()

const leftDrawerOpen = ref(false)
const searchQuery = ref('')
const searchOpen = ref(false)
const focusedSearch = ref('')
const mobileSuggestions = ref(null)
const desktopSuggestions = ref(null)
const expandedSuggestions = ref(null)
const { remember: rememberSearch } = useSearchHistory()
const activeCategory = ref('all')
const activeSubcategory = ref('')
const mobileTab = ref('home')
const {
  cartCount, resetCart, loadCart, notice: cartNotice, busy: cartBusy,
  mergeCart, clearNotice: clearCartNotice,
} = useCart()
async function retryCartMerge() {
  try { await mergeCart() }
  catch { /* El aviso conserva el error y permite reintentar. */ }
}
watch(
  [() => sessionChecked.value, () => currentUser.value?.id, () => currentUser.value?.user_vip],
  ([checked]) => {
    if (!checked) return
    resetCart()
    loadCart().catch(() => { /* El carrito muestra el error y permite reintentar. */ })
  },
  { immediate: true, flush: 'sync' },
)
const categoriesMenu = ref(false)
const categoryRequest = new AbortController()

// Entrada fija "Todos" que siempre encabeza la lista
const categories = ref([])
const categoriesLoading = ref(true)

const accountName = computed(() => {
  const user = currentUser.value
  const name = String(user?.nombre || '')
    .trim()
    .split(/\s+/)[0]

  return name || ''
})

const accountGreeting = computed(() => {
  return accountName.value ? `Hola, ${accountName.value}` : 'Mi cuenta'
})

const accountInitials = computed(() => {
  const user = currentUser.value
  if (!user) return ''
  return [user.nombre, user.apellido]
    .map((value) => Array.from(String(value || '').trim())[0] || '')
    .join('')
    .toLocaleUpperCase('es-MX')
})

onMounted(async () => {
  loadCurrentUser()
  const timeout = setTimeout(() => categoryRequest.abort(), 15000)

  try {
    const apiCategories = await getCategories({ signal: categoryRequest.signal })
    const allCategory = { id: 'all', slug: 'all', name: 'Todos', label: 'Todos', svgIcon: publicAsset('iconos/home.webp'), subcategories: [] }
    categories.value = [allCategory, ...apiCategories]
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar las categorias.')
  } finally {
    clearTimeout(timeout)
    categoriesLoading.value = false
  }
})

onBeforeUnmount(() => {
  categoryRequest.abort()
})

const route = useRoute()
watch(() => route.query.q, (value) => {
  if (route.path === '/buscar') searchQuery.value = typeof value === 'string' ? value : ''
}, { immediate: true })
function openSearchSuggestions(event, name) {
  if (event.target.tagName === 'INPUT') focusedSearch.value = name
}
function searchKeydown(event, suggestions) {
  if (suggestions?.handleKeydown(event)) return
  if (event.key === 'Enter') {
    event.preventDefault()
    submitSearch()
  }
}
function submitSearch(value) {
  const q = normalizeSearch(typeof value === 'string' ? value : searchQuery.value)
  if (!q) return
  rememberSearch(q)
  focusedSearch.value = ''
  searchQuery.value = q
  searchOpen.value = false
  categoriesMenu.value = false
  leftDrawerOpen.value = false
  router.push({ path: '/buscar', query: { q } })
}
watch(() => route.fullPath, () => { focusedSearch.value = '' })
watch(() => route.path, (path) => {
  if (path === '/categorias' || path === '/subcategoria' || path.startsWith('/catalogo/')) mobileTab.value = 'search'
  else if (path === '/') mobileTab.value = 'home'
  else if (path === '/carrito') mobileTab.value = 'cart'
  else if (['/login', '/register', '/panel'].includes(path)) mobileTab.value = 'user'
  else mobileTab.value = ''
}, { immediate: true })

function goToCategories() {
  leftDrawerOpen.value = false
  router.push('/categorias')
}

function goToHome() {
  leftDrawerOpen.value = false
  router.push('/')
}

const footerContacts = [
  {
    title: 'Envios a Nivel nacional',
    icon: 'local_shipping',
    phone: '+52 55 4556-4443',
    href: 'tel:+525545564443',
    hours: 'Lun-Sab 09:00am - 6:00pm',
  },
  {
    title: 'Entregas en CDMX',
    icon: 'handshake',
    phone: '+52 55 7997-8519',
    href: 'tel:+525579978519',
    hours: 'Lun-Sab 09:00am - 6:00pm',
  },
]

const footerStores = [
  {
    address: 'Calle articulo 123 #41, piso 4. colonia centro, CDMX.',
    phone: '55 3882-6981',
    href: 'tel:+525538826981',
    mapsHref:
      'https://www.google.com/maps/search/?api=1&query=Calle%20articulo%20123%2041%20colonia%20centro%20CDMX',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle manuel de la peña y peña plaza #18 planta baja, locales A5 y A6.',
    phone: '56 3353-3333',
    href: 'tel:+525633533333',
    mapsHref:
      'https://www.google.com/maps/search/?api=1&query=Calle%20manuel%20de%20la%20pena%20y%20pena%20plaza%2018%20CDMX',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle manuel de la peña y peña plaza #13 planta baja, locales 22 y 23',
    phone: '56 1333-0202',
    href: 'tel:+525613330202',
    mapsHref:
      'https://www.google.com/maps/search/?api=1&query=Calle%20manuel%20de%20la%20pena%20y%20pena%20plaza%2013%20CDMX',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle Republica de colombia 79, Plaza Carmen #51, planta baja, local 23 y 24.',
    phone: '55 7417-7482',
    href: 'tel:+525574177482',
    mapsHref:
      'https://www.google.com/maps/search/?api=1&query=Calle%20Republica%20de%20colombia%2079%20Plaza%20Carmen%2051%20CDMX',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
]

const footerSocialLinks = [
  { name: 'Facebook', image: publicAsset('img/facebook.png'), href: '#' },
  { name: 'Instagram', image: publicAsset('img/instagram.png'), href: '#' },
  { name: 'TikTok', image: publicAsset('img/tiktok.png'), href: '#' },
  { name: 'YouTube', image: publicAsset('img/youtube.png'), href: '#' },
]



function closeSearch() {
  focusedSearch.value = ''
  searchOpen.value = false
  searchQuery.value = ''
}


function goToAccount() {
  leftDrawerOpen.value = false
  router.push(currentUser.value ? '/panel' : '/login')
}

function selectCategory(slug) {
  activeCategory.value = slug
  activeSubcategory.value = ''
  leftDrawerOpen.value = false
  router.push({ path: '/', query: slug === 'all' ? {} : { category: slug } })
}

function selectCategoryFromMenu(slug) {
  const category = categories.value.find(item => item.slug === slug)
  const id = category?.id_categoria ?? category?.id
  categoriesMenu.value = false
  if (id) router.push(catalogLocation(category, null, 1, categories.value))
  else router.push('/categorias')
}

function selectSubcategory(categorySlug, subSlug) {
  const category = categories.value.find((item) => item.slug === categorySlug)
  const subcategory = category?.subcategories?.find((item) => item.slug === subSlug)
  const categoryId = category?.id_categoria ?? category?.id
  const subcategoryId = subcategory?.id_subcategoria ?? subcategory?.id
  if (!categoryId || !subcategoryId) return
  activeCategory.value = categorySlug
  activeSubcategory.value = subSlug
  leftDrawerOpen.value = false
  categoriesMenu.value = false
  router.push(catalogLocation(category, subcategory, 1, categories.value))
}

function selectSubcategoryFromMenu(categorySlug, subSlug) {
  selectSubcategory(categorySlug, subSlug)
  categoriesMenu.value = false
}

function showMenuProduct(product) {
  categoriesMenu.value = false
  openProduct(product)
}

async function showOffers() {
  categoriesMenu.value = false
  await router.push('/ofertas')
}
</script>
<style lang="scss">
@media (max-width: 1023px) {
  .sn-footer.sn-footer--keyboard { display: none !important; }
}
.cart-merge-notice {
  display: grid;
  grid-template-columns: 40px minmax(0, 1fr) auto 28px;
  align-items: center;
  gap: 14px;
  width: calc(100% - 48px);
  max-width: 1184px;
  margin: 12px auto;
  padding: 14px 16px;
  border: 1px solid #f6dce6;
  border-radius: 16px;
  background: linear-gradient(110deg, #fff7fa, #fff 70%);
  box-shadow: 0 4px 16px rgba(133, 37, 72, 0.04);
}
.cart-merge-notice__icon {
  display: grid;
  width: 40px;
  height: 40px;
  place-items: center;
  border-radius: 13px;
  background: #ffe5ee;
  color: #ee346c;
  font-size: 23px;
}
.cart-merge-notice__copy {
  min-width: 0;
  strong { color: #50313f; font-size: 14px; font-weight: 700; }
  p { margin: 3px 0 0; color: #8c6c7b; font-size: 12px; line-height: 1.5; }
}
.cart-merge-notice__actions {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #d72c60;
}
.cart-merge-notice__link {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 11px;
  background: linear-gradient(110deg, #ff4f79, #e72e65);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  .q-icon { font-size: 17px; }
}
.cart-merge-notice__close {
  color: #ad8a9a;
  font-size: 10px;
}
@media (max-width: 599px) {
  .cart-merge-notice {
    grid-template-columns: 32px minmax(0, 1fr) 24px;
    align-items: start;
    gap: 7px 10px;
    width: calc(100% - 24px);
    margin: 10px 12px;
    padding: 12px;
    border-radius: 14px;
  }
  .cart-merge-notice__icon { width: 32px; height: 32px; border-radius: 10px; font-size: 20px; }
  .cart-merge-notice__copy {
    strong { font-size: 13px; }
    p { margin-top: 2px; font-size: 11px; }
  }
  .cart-merge-notice__actions { grid-column: 2 / -1; flex-wrap: wrap; }
  .cart-merge-notice__link { min-height: 30px; padding: 0 11px; border-radius: 9px; font-size: 11px; }
  .cart-merge-notice__close { grid-column: 3; grid-row: 1; margin-top: -3px; }
}
.sn-topbar-account {
  max-width: 240px;

  .q-btn__content {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.sn-account-btn {
  min-width: 42px;
  min-height: 42px;
  padding: 4px 10px;
  border-radius: 6px;

  .q-btn__content {
    flex-wrap: nowrap;
    gap: 6px;
  }
}

.sn-account-copy {
  min-width: 0;
  max-width: 112px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.05;
}

.sn-account-hello {
  font-size: 10px;
  font-weight: 750;
  opacity: 0.9;
}

.sn-account-name {
  display: block;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 850;
  line-height: 1.15;
}

.sn-header .sn-header-icon-btn:hover {
  background: #fff !important;
  color: #d92f60 !important;
}

.sn-header .sn-header-icon-btn:hover .q-focus-helper {
  opacity: 0 !important;
}

.sn-header .sn-header-icon-btn .q-btn__content,
.sn-header .sn-header-icon-btn .q-icon {
  position: relative;
  z-index: 2;
  visibility: visible !important;
  opacity: 1 !important;
  filter: none !important;
}

.sn-header .sn-header-icon-btn:hover .q-btn__content,
.sn-header .sn-header-icon-btn:hover .q-icon {
  color: #d92f60 !important;
}

.sn-header .sn-header-icon-btn:hover .q-badge {
  background: #d92f60 !important;
  color: #fff !important;
  opacity: 1 !important;
}

.sn-footer-stores {
  display: flex;
  flex-direction: column;
}

.sn-footer-store {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

@media (max-width: 959px) {
  .sn-footer-stores {
    grid-template-columns: 1fr;
  }
}
.sn-header .sn-cart-btn.sn-header-icon-btn .q-badge.sn-cart-count,
.sn-header .sn-cart-btn.sn-header-icon-btn:hover .q-badge.sn-cart-count {
  top: -3px;
  right: -3px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border: 1px solid #f7ccda;
  border-radius: 12px;
  background: #fff !important;
  color: #b9184d !important;
  box-shadow: 0 2px 6px rgba(124, 15, 50, .24);
  font-size: 11px;
  font-weight: 800;
  line-height: 1;
}
</style>
