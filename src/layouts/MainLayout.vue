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
            icon="menu"
            color="dark"
            @click="leftDrawerOpen = !leftDrawerOpen"
          />

          <div class="sn-mobile-logo" @click="$router.push('/')">
            <img src="img/logob.png" alt="Importadora Supernova" />
          </div>

          <div class="sn-mobile-head__actions">
            <button
              class="sn-mobile-icon-btn"
              type="button"
              aria-label="Buscar"
              @click="focusMobileSearch"
            >
              <q-icon name="search" />
            </button>
            <button class="sn-mobile-icon-btn" type="button" aria-label="Carrito">
              <q-icon name="shopping_cart" />
              <q-badge color="primary" floating rounded style="font-size: 10px">{{
                cartCount
              }}</q-badge>
              <q-tooltip>Ver carrito</q-tooltip>
            </button>
          </div>
        </div>

        <q-input
          ref="mobileSearchInput"
          v-model="searchQuery"
          filled
          rounded
          dense
          placeholder="Buscar productos, marcas y más..."
          class="sn-mobile-head__search"
          bg-color="white"
        >
          <template #append>
            <q-icon name="search" color="grey-7" />
          </template>
        </q-input>
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
              class="lt-md sn-mobile-search"
              bg-color="white"
              style="flex: 1"
              autofocus
            >
              <template #append v-if="searchQuery">
                <q-btn
                  flat
                  round
                  dense
                  icon="close"
                  size="xs"
                  color="grey"
                  @click="searchQuery = ''"
                />
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
              <img src="img/logob.png" alt="Importadora Supernova" width="180px" />
            </div>

            <!-- Separador flexible (desktop) -->
            <q-space class="gt-sm" />

            <!-- Barra de búsqueda (solo desktop) -->
            <q-input
              v-model="searchQuery"
              filled
              rounded
              placeholder="Buscar productos, marcas y más..."
              class="sn-search gt-sm"
              bg-color="white"
              style="width: 100%; max-width: 680px"
            >
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
              color="dark"
              size="md"
              class="q-ml-xs sn-cart-btn sn-header-icon-btn"
            >
              <q-badge color="accent" floating rounded style="font-size: 10px">{{
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
              <StoreCategoryMenu
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
            <q-badge color="primary" label="HOT" floating />
          </q-btn>
          <q-btn flat no-caps icon="shield" label="Nuevos" to="/nuevos" class="sn-desktop-nav__link" />
          <q-btn
            flat
            no-caps
            icon="trending_up"
            label="Más vendidos"
            class="sn-desktop-nav__link"
          />
          <q-btn
            flat
            no-caps
            icon="workspace_premium"
            label="Marcas"
            class="sn-desktop-nav__link"
          />
          <q-btn flat no-caps icon="storefront" label="Sucursales" class="sn-desktop-nav__link" />
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

    <q-dialog :model-value="!!menuProduct" @update:model-value="menuProduct = null">
      <q-card v-if="menuProduct" style="width: 420px; max-width: 94vw">
        <q-card-section class="row justify-end">
          <q-btn flat round icon="close" v-close-popup aria-label="Cerrar" />
        </q-card-section>
        <q-card-section>
          <StoreMenuProductImage
            :index="menuProduct.spriteIndex"
            :label="menuProduct.nombre"
            style="width: 220px; margin: 0 auto"
          />
          <h2 class="text-h6">{{ menuProduct.nombre }}</h2>
          <p class="text-grey-7">
            Consulta la disponibilidad y el precio vigente con nuestro equipo.
          </p>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            no-caps
            color="primary"
            label="Consultar disponibilidad"
            href="tel:+525524954670"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

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
              <img v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 24px; height: 24px" />
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
                <img v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 100%; height: 24px" />
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
      <router-view />
    </q-page-container>

    <!-- ══════════════════════════════════ FOOTER ════════════════════════════════════ -->
    <q-footer bordered :elevated="false" class="sn-footer sn-footer--premium">
      <!-- DESKTOP FOOTER -->
      <section class="sn-brand-strip gt-sm" aria-label="Nuestras marcas">
        <div class="sn-brand-strip__inner">
          <h2>NUESTRAS MARCAS</h2>
          <div class="sn-brand-strip__logos">
            <img
              v-for="brand in footerBrandLogos"
              :key="brand.name"
              :src="brand.image"
              :alt="brand.name"
              loading="lazy"
            />
          </div>
        </div>
      </section>
      <div class="sn-footer-desktop gt-sm">
        <div class="sn-footer-grid">
          <div class="sn-footer-identity">
            <img src="img/logob.png" alt="Importadora Supernova" class="sn-footer-logo" />
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
                <img :src="social.image" :alt="social.name" /><q-tooltip>{{
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
          <q-tab name="home" icon="home" label="Inicio" />
          <q-tab name="search" icon="format_list_bulleted" label="Categorías" />
          <q-tab name="fav" icon="favorite_border" label="Favoritos" />
          <q-tab name="cart" icon="shopping_cart" label="Carrito">
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { mockCategories } from 'src/data/mockStore'
import { menuFallbackCategories } from 'src/data/categoryMenu'
import StoreCategoryMenu from 'src/components/StoreCategoryMenu.vue'
import StoreMenuProductImage from 'src/components/StoreMenuProductImage.vue'
import { getCategories } from 'src/services/categoryApi'
import { useAuthSession } from 'src/services/authSession'

const router = useRouter()
const { currentUser, loadCurrentUser } = useAuthSession()

const leftDrawerOpen = ref(false)
const searchQuery = ref('')
const searchOpen = ref(false)
const mobileSearchInput = ref(null)
const activeCategory = ref('all')
const activeSubcategory = ref('')
const mobileTab = ref('home')
const cartCount = ref(0)
const categoriesMenu = ref(false)
const categoryRequest = new AbortController()

// Entrada fija "Todos" que siempre encabeza la lista
const categories = ref(menuFallbackCategories)
const menuProduct = ref(null)

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

onMounted(async () => {
  loadCurrentUser()
  const timeout = setTimeout(() => categoryRequest.abort(), 15000)

  try {
    const apiCategories = await getCategories({ signal: categoryRequest.signal })
    const allCategory = mockCategories.find((cat) => cat.slug === 'all')
    categories.value = allCategory ? [allCategory, ...apiCategories] : apiCategories
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar las categorias.')
  } finally {
    clearTimeout(timeout)
  }
})

onBeforeUnmount(() => {
  categoryRequest.abort()
})

watch(mobileTab, (tab) => {
  if (tab === 'home') {
    router.push('/')
    return
  }

  if (tab === 'search') {
    leftDrawerOpen.value = true
  }
})

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
  { name: 'Facebook', image: 'img/facebook.png', href: '#' },
  { name: 'Instagram', image: 'img/instagram.png', href: '#' },
  { name: 'TikTok', image: 'img/tiktok.png', href: '#' },
  { name: 'YouTube', image: 'img/youtube.png', href: '#' },
]

const footerBrandLogos = [
  { name: 'Le Reveur', image: 'img/le-reveur.png' },
  { name: 'Le Mussa', image: 'img/le-mussa.png' },
  { name: 'Wing Star', image: 'img/wing-star.png' },
]

function closeSearch() {
  searchOpen.value = false
  searchQuery.value = ''
}

async function focusMobileSearch() {
  await nextTick()
  mobileSearchInput.value?.focus?.()
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
  selectCategory(slug)
  categoriesMenu.value = false
}

function selectSubcategory(categorySlug, subSlug) {
  activeCategory.value = categorySlug
  activeSubcategory.value = subSlug
  leftDrawerOpen.value = false
  router.push({ path: '/', query: { category: categorySlug, subcategory: subSlug } })
}

function selectSubcategoryFromMenu(categorySlug, subSlug) {
  selectSubcategory(categorySlug, subSlug)
  categoriesMenu.value = false
}

function showMenuProduct(product) {
  categoriesMenu.value = false
  menuProduct.value = product
}

async function showOffers() {
  categoriesMenu.value = false
  await router.push('/ofertas')
}
</script>
<style lang="scss">
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
</style>
