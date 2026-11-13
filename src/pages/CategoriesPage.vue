<template>
  <q-page class="categories-page">
    <PageBreadcrumbs title="Categorías" />
    <LoadingSkeleton v-if="loading" variant="categories" label="Cargando categorías..." />
    <div v-else-if="errorMessage" class="q-pa-lg text-center" role="alert">
      <p>{{ errorMessage }}</p>
      <q-btn no-caps color="primary" label="Reintentar" @click="loadCategories" />
    </div>
    <div
      v-else-if="selectedCategory"
      ref="browser"
      class="category-browser"
      :style="height ? { height: `${height}px` } : undefined"
    >
      <nav class="category-browser__rail" aria-label="Categorías">
        <button
          v-for="category in categories"
          :key="category.id"
          type="button"
          class="category-selector"
          :class="{ 'category-selector--active': selectedCategory.id === category.id }"
          :aria-pressed="selectedCategory.id === category.id"
          aria-controls="category-content"
          @click="selectCategory(category)"
        >
          <span class="category-selector__image"
            ><img v-image-skeleton :src="category.svgIcon" alt="" loading="lazy" @error="fallbackImage"
          /></span>
          <span>{{ category.name }}</span>
        </button>
      </nav>
      <section
        id="category-content"
        ref="content"
        class="category-browser__content"
        :aria-label="selectedCategory.name"
      >
        <div v-if="selectedCategory.subcategories.length" class="subcategory-grid">
          <RouterLink
            v-for="sub in selectedCategory.subcategories"
            :key="sub.id"
            class="subcategory-link"
            :to="catalogLocation(selectedCategory, sub, 1, categories)"
          >
            <span>{{ sub.name }}</span
            ><q-icon name="arrow_forward" aria-hidden="true" />
          </RouterLink>
        </div>
        <p v-else class="category-empty">Pronto encontrarás subcategorías aquí.</p>
      </section>
    </div>
    <p v-else class="q-pa-lg text-center">No hay categorías disponibles.</p>
  </q-page>
</template>

<script setup>
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getCategories } from 'src/services/categoryApi'
import { catalogLocation } from 'src/utils/catalogRoutes'
import { publicAsset } from 'src/utils/publicAsset'

const route = useRoute()
const router = useRouter()
const browser = ref(null)
const content = ref(null)
const height = ref(null)
let resizeObserver
function resizeBrowser() {
  if (!browser.value) return
  const nav = document.querySelector('.sn-mobile-nav')
  const bottom =
    nav && getComputedStyle(nav).display !== 'none'
      ? nav.getBoundingClientRect().top - 12
      : window.innerHeight - 24
  height.value = Math.max(180, bottom - browser.value.getBoundingClientRect().top)
}
function selectCategory(category) {
  if (category.id === selectedCategory.value?.id) return
  router.replace({ path: '/categorias', query: { categoria: String(category.id) } })
}
const categories = ref([])
const loading = ref(true)
const errorMessage = ref('')
let request
const selectedCategory = computed(
  () =>
    categories.value.find((category) => String(category.id) === route.query.categoria) ||
    categories.value[0],
)
async function loadCategories() {
  request?.abort()
  const currentRequest = new AbortController()
  request = currentRequest
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await getCategories({ signal: currentRequest.signal })
    if (!currentRequest.signal.aborted) categories.value = result
  } catch {
    if (!currentRequest.signal.aborted) errorMessage.value = 'No se pudieron cargar las categorías.'
  } finally {
    if (request === currentRequest) loading.value = false
  }
}
function fallbackImage(event) {
  if (event.target.dataset.fallback) return
  event.target.dataset.fallback = 'true'
  event.target.src = publicAsset('iconos/otros.webp')
}
watch([selectedCategory, loading], async () => {
  await nextTick()
  if (content.value) content.value.scrollTop = 0
  resizeBrowser()
})
onMounted(() => {
  loadCategories()
  window.addEventListener('resize', resizeBrowser)
  if ('ResizeObserver' in window) {
    resizeObserver = new ResizeObserver(resizeBrowser)
    const header = document.querySelector('.sn-header')
    const nav = document.querySelector('.sn-mobile-nav')
    if (header) resizeObserver.observe(header)
    if (nav) resizeObserver.observe(nav)
  }
})
onBeforeUnmount(() => {
  request?.abort()
  window.removeEventListener('resize', resizeBrowser)
  resizeObserver?.disconnect()
})
</script>

<style scoped lang="scss">
.category-tile {
  position: relative;
  isolation: isolate;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: auto auto;
  column-gap: 14px;
  row-gap: 4px;
  align-items: center;
  overflow: hidden;
  padding: 18px 14px;
  border: 1px solid #ed4773;
  border-radius: 18px;
  color: #fff;
  text-decoration: none;
  background:
    radial-gradient(
      ellipse 100% 130% at 47% -20%,
      rgba(255, 126, 155, 0.68),
      rgba(255, 90, 131, 0.25) 43%,
      transparent 70%
    ),
    linear-gradient(112deg, #ff4f79 0%, #f43f6f 48%, #df2f61 100%);
  box-shadow: 0 8px 22px rgba(180, 42, 80, 0.12);
}
.category-tile::before {
  position: absolute;
  z-index: -1;
  inset: -12% -8% -18%;
  background:
    linear-gradient(
      158deg,
      transparent 0 17%,
      rgba(255, 255, 255, 0.06) 17.4% 20.4%,
      transparent 20.8% 34%,
      rgba(255, 255, 255, 0.05) 34.4% 37%,
      transparent 37.4%
    ),
    linear-gradient(
      20deg,
      transparent 0 70%,
      rgba(255, 255, 255, 0.05) 70.4% 73.5%,
      transparent 74%
    ),
    radial-gradient(
      110% 72% at -2% 10%,
      transparent 63%,
      rgba(255, 255, 255, 0.12) 64%,
      rgba(255, 255, 255, 0.035) 66%,
      transparent 68%
    ),
    radial-gradient(
      115% 76% at 103% 94%,
      transparent 60%,
      rgba(255, 255, 255, 0.1) 61%,
      rgba(255, 255, 255, 0.03) 63%,
      transparent 65%
    );
  pointer-events: none;
  content: '';
}

.categories-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 8px 12px 100px;
  background: #fff;
}
.category-browser {
  display: grid;
  grid-template-columns: 94px minmax(0, 1fr);
  height: calc(100dvh - 240px);
  min-height: 180px;
  overflow: hidden;
  border: 1px solid #f3e3e9;
  border-radius: 20px;
  background: #fff;
}
.category-browser__rail,
.category-browser__content {
  min-width: 0;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior-y: contain;
  scrollbar-width: thin;
  scrollbar-color: #efbacb transparent;
}
.category-browser__rail {
  border-right: 1px solid #f4e2e9;
  background: #fff;
}
.category-selector {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  width: 100%;
  min-height: 96px;
  padding: 12px 7px;
  border: 0;
  border-bottom: 1px solid #f3e5eb;
  background: transparent;
  color: #79636e;
  font: inherit;
  font-size: 11px;
  line-height: 1.3;
  text-align: center;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    color 160ms ease;
}
.category-selector--active {
  isolation: isolate;
  overflow: hidden;
  background:
    radial-gradient(
      ellipse 100% 130% at 47% -20%,
      rgba(255, 126, 155, 0.68),
      rgba(255, 90, 131, 0.25) 43%,
      transparent 70%
    ),
    linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
  font-weight: 700;
}
.category-selector--active::before {
  position: absolute;
  z-index: -1;
  inset: -12% -8% -18%;
  pointer-events: none;
  background:
    linear-gradient(
      158deg,
      transparent 0 17%,
      rgba(255, 255, 255, 0.08) 17.4% 20.4%,
      transparent 20.8% 34%,
      rgba(255, 255, 255, 0.07) 34.4% 37%,
      transparent 37.4%
    ),
    linear-gradient(
      20deg,
      transparent 0 70%,
      rgba(255, 255, 255, 0.07) 70.4% 73.5%,
      transparent 74%
    ),
    radial-gradient(
      110% 72% at -2% 10%,
      transparent 63%,
      rgba(255, 255, 255, 0.14) 64%,
      rgba(255, 255, 255, 0.035) 66%,
      transparent 68%
    ),
    radial-gradient(
      115% 76% at 103% 94%,
      transparent 60%,
      rgba(255, 255, 255, 0.12) 61%,
      rgba(255, 255, 255, 0.03) 63%,
      transparent 65%
    );
  content: '';
}
.category-selector__image {
  display: grid;
  place-items: center;
  width: 70px;
  height: 70px;
  padding: 5px;
  overflow: hidden;
  border: 1px solid #f5e7ed;
  border-radius: 13px;
  background: #fff;
}
.category-selector__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.category-browser__content {
  padding: 10px;
}
.category-tile h1 {
  margin: 7px 0;
  color: #fff;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.2;
}
.category-tile > small {
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 0.09em;
}
.category-tile > span {
  font-size: 10px;
}
.category-section-label {
  margin: 20px 2px 12px;
}
.category-section-label h2 {
  margin: 0 0 3px;
  color: #44313b;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.4;
}
.category-section-label > span {
  color: #99828e;
  font-size: 10px;
}
.subcategory-grid {
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
  align-items: flex-start;
  gap: 8px;
  padding-bottom: 4px;
}
.subcategory-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 34px;
  max-width: 100%;
  padding: 7px 10px;
  border: 1px solid #f2dfe7;
  border-radius: 18px;
  background: #fff;
  color: #57404c;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.4;
  text-decoration: none;
}
.subcategory-link .q-icon {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #ffe6ef;
  color: #e52e65;
  font-size: 13px;
}
.category-empty {
  padding: 20px 4px;
  color: #99828e;
  font-size: 12px;
}
.category-selector:focus-visible,
.subcategory-link:focus-visible {
  outline: 2px solid #e52e65;
  outline-offset: -3px;
}
@media (min-width: 600px) {
  .category-browser {
    grid-template-columns: 160px minmax(0, 1fr);
  }
  .category-browser__content {
    padding: 20px;
  }
  .category-selector {
    font-size: 13px;
  }
  .subcategory-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .category-tile h1 {
    font-size: 26px;
  }
}
</style>
