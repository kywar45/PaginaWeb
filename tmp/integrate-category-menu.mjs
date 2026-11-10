import { readFile, writeFile, mkdir, copyFile } from 'node:fs/promises'

await mkdir('tmp/category-menu-before', { recursive: true })
for (const [file, name] of [['src/layouts/MainLayout.vue', 'MainLayout.vue'], ['src/pages/IndexPage.vue', 'IndexPage.vue'], ['src/css/app.scss', 'app.scss']]) {
  await copyFile(file, `tmp/category-menu-before/${name}`)
}
function replace(source, before, after) {
  if (!source.includes(before)) throw new Error(`Missing anchor: ${before.slice(0, 100)}`)
  return source.replace(before, after)
}
function removeBetween(source, before, after) {
  const start = source.indexOf(before)
  const end = source.indexOf(after, start)
  if (start < 0 || end < 0) throw new Error(`Missing range: ${before}`)
  return source.slice(0, start) + source.slice(end)
}

let layout = (await readFile('src/layouts/MainLayout.vue', 'utf8')).replaceAll('\r\n', '\n')
layout = removeBetween(layout, '            <div\n              v-if="false"', '            <!-- Separador flexible (desktop) -->')
const menuStart = layout.indexOf('            <q-menu', layout.indexOf('<div class="sn-desktop-nav gt-sm">'))
const menuEnd = layout.indexOf('            </q-menu>', menuStart) + '            </q-menu>'.length
layout = layout.slice(0, menuStart) + `            <q-menu
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
                @select-subcategory="(category, sub) => selectSubcategoryFromMenu(category.slug, sub.slug)"
                @select-product="showMenuProduct"
                @offers="showOffers"
              />
            </q-menu>` + layout.slice(menuEnd)
layout = replace(layout, 'label="Todas las categorias"', 'label="Todas las categorías"\n            :aria-expanded="categoriesMenu"\n            aria-label="Todas las categorías"')
layout = replace(layout, 'label="Mas vendidos"', 'label="Más vendidos"')
layout = replace(layout, '<q-btn flat no-caps icon="local_offer" label="Ofertas" class="sn-desktop-nav__link">', '<q-btn flat no-caps icon="local_offer" label="Ofertas" class="sn-desktop-nav__link" @click="showOffers">')
layout = replace(layout, '    <!-- ════════════════════════════════ DRAWER MOBILE', `    <Teleport to="body">
      <div v-if="categoriesMenu" class="sn-catalog-backdrop" aria-hidden="true" @click="categoriesMenu = false" />
    </Teleport>

    <q-dialog :model-value="!!menuProduct" @update:model-value="menuProduct = null">
      <q-card v-if="menuProduct" style="width: 420px; max-width: 94vw">
        <q-card-section class="row justify-end">
          <q-btn flat round icon="close" v-close-popup aria-label="Cerrar" />
        </q-card-section>
        <q-card-section>
          <StoreMenuProductImage :index="menuProduct.spriteIndex" :label="menuProduct.nombre" style="width: 220px; margin: 0 auto" />
          <h2 class="text-h6">{{ menuProduct.nombre }}</h2>
          <p class="text-grey-7">Consulta la disponibilidad y el precio vigente con nuestro equipo.</p>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn no-caps color="primary" label="Consultar disponibilidad" href="tel:+525524954670" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ════════════════════════════════ DRAWER MOBILE`)
layout = replace(layout, "import { mockCategories } from 'src/data/mockStore'", "import { mockCategories } from 'src/data/mockStore'\nimport { menuFallbackCategories } from 'src/data/categoryMenu'\nimport StoreCategoryMenu from 'src/components/StoreCategoryMenu.vue'\nimport StoreMenuProductImage from 'src/components/StoreMenuProductImage.vue'")
layout = layout.replace("import { getBanners } from 'src/services/bannerApi'\n", '')
layout = layout.replace('const categoriesLoading = ref(true)\n', '')
layout = layout.replace('const bannerRequest = new AbortController()\n', '')
layout = replace(layout, "const categories = ref(mockCategories)\nconst megaCategory = ref(mockCategories.find((category) => category.subcategories?.length) || null)\nconst categoryPromo = ref(null)", "const categories = ref(menuFallbackCategories)\nconst menuProduct = ref(null)")
layout = layout.replace('    megaCategory.value = apiCategories.find((category) => category.subcategories?.length) || null\n', '')
layout = layout.replace('    categoriesLoading.value = false\n', '')
layout = removeBetween(layout, '\n  try {\n    const banners = await getBanners', '\n})\n\nonBeforeUnmount')
layout = layout.replace('  bannerRequest.abort()\n', '')
layout = replace(layout, "  router.push('/')\n}\n\nfunction selectCategoryFromMenu", "  router.push({ path: '/', query: slug === 'all' ? {} : { category: slug } })\n}\n\nfunction selectCategoryFromMenu")
layout = replace(layout, "  router.push('/')\n}\n\nfunction selectSubcategoryFromMenu", "  router.push({ path: '/', query: { category: categorySlug, subcategory: subSlug } })\n}\n\nfunction selectSubcategoryFromMenu")
layout = replace(layout, 'function previewCategory(category) {\n  megaCategory.value = category\n}', `function showMenuProduct(product) {
  categoriesMenu.value = false
  menuProduct.value = product
}

async function showOffers() {
  categoriesMenu.value = false
  await router.push({ path: '/', hash: '#ofertas' })
  await nextTick()
  document.getElementById('ofertas')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}`)
layout = removeBetween(layout, '.sn-category-trigger {', '.sn-footer-stores {')
layout = layout.replace(/\n\.sn-subcategory-menu \{[\s\S]*?\n\}/, '')
await writeFile('src/layouts/MainLayout.vue', layout)

let page = (await readFile('src/pages/IndexPage.vue', 'utf8')).replaceAll('\r\n', '\n')
const showcaseStart = page.indexOf('      <section class="category-showcase"')
const showcaseEnd = page.indexOf('      <section class="store-benefits"', showcaseStart)
page = page.slice(0, showcaseStart) + `      <StoreCategoryMenu
        class="store-category-showcase"
        :categories="categories"
        @select-category="openCategory"
        @select-subcategory="openSubcategory"
        @select-product="selectedProduct = $event"
        @offers="openOffers"
      />
` + page.slice(showcaseEnd)
page = replace(page, '<h2 class="text-h6 q-ma-none">{{ selectedCategory.name }}</h2>', '<h2 class="text-h6 q-ma-none">{{ selectedSubcategory?.name || selectedCategory.name }}</h2>')
page = replace(page, '<q-list v-if="selectedCategory.subcategories?.length" dense separator>', '<q-list v-if="!selectedSubcategory && selectedCategory.subcategories?.length" dense separator>')
page = replace(page, '<img class="detail-image" :src="selectedProduct.imagen" :alt="selectedProduct.nombre" />', `<StoreMenuProductImage v-if="selectedProduct.spriteIndex !== undefined" :index="selectedProduct.spriteIndex" :label="selectedProduct.nombre" style="width: 220px; margin: auto" />
          <img v-else class="detail-image" :src="selectedProduct.imagen" :alt="selectedProduct.nombre" />`)
page = replace(page, '<p>{{ selectedProduct.codigo }}</p>\n          <strong class="text-h5">{{ money(selectedProduct.precio_final) }}</strong>', `<template v-if="selectedProduct.spriteIndex === undefined"><p>{{ selectedProduct.codigo }}</p><strong class="text-h5">{{ money(selectedProduct.precio_final) }}</strong></template>
          <p v-else>Consulta la disponibilidad y el precio vigente con nuestro equipo.</p>`)
page = replace(page, "import { computed, onMounted, onBeforeUnmount, ref } from 'vue'", "import { computed, nextTick, onMounted, onBeforeUnmount, ref, watch } from 'vue'\nimport { useRoute } from 'vue-router'\nimport StoreCategoryMenu from 'src/components/StoreCategoryMenu.vue'\nimport StoreMenuProductImage from 'src/components/StoreMenuProductImage.vue'\nimport { menuFallbackCategories } from 'src/data/categoryMenu'")
page = replace(page, 'import { mockCategories, mockOfferProducts, mockBestSellers, mockNewestProducts }', 'import { mockOfferProducts, mockBestSellers, mockNewestProducts }')
page = replace(page, 'const selectedCategory = ref(null)', 'const route = useRoute()\nconst selectedCategory = ref(null)\nconst selectedSubcategory = ref(null)')
page = replace(page, "const categories = ref(mockCategories.filter((cat) => cat.slug !== 'all'))\nconst activeCategory = ref(categories.value[0] || null)", 'const categories = ref(menuFallbackCategories)')
page = page.replace('    activeCategory.value = apiCategories[0] || null\n', '')
page = removeBetween(page, 'function openCategory(cat) {', 'const benefits = [')
page = replace(page, 'const benefits = [', `function openCategory(category) {
  selectedSubcategory.value = null
  selectedCategory.value = category
  categoriesOpen.value = false
}
function openSubcategory(category, subcategory) {
  selectedSubcategory.value = subcategory
  selectedCategory.value = category
}
async function openOffers() {
  allOffers.value = true
  await nextTick()
  document.getElementById('ofertas')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
watch([() => route.query, categories], ([query]) => {
  const category = categories.value.find((item) => item.slug === query.category)
  if (!category) return
  const subcategory = category.subcategories?.find((item) => item.slug === query.subcategory)
  if (subcategory) openSubcategory(category, subcategory)
  else openCategory(category)
}, { immediate: true })
const benefits = [`)
page = removeBetween(page, '.category-showcase {', '.store-benefits {')
page = removeBetween(page, '  .category-showcase {', '  .store-benefit {')
page = removeBetween(page, '  .category-showcase {', '  .store-benefits {')
page = replace(page, '.store-container {\n  max-width: 1280px;', '.store-container {\n  max-width: 1440px;')
page = replace(page, '.store-benefits {', `.store-category-showcase { margin: 0 -16px 24px; }
.daily-offers { scroll-margin-top: 200px; }
@media (max-width: 599px) { .store-category-showcase { margin: 0 -8px 18px; } }
.store-benefits {`)
await writeFile('src/pages/IndexPage.vue', page)

let styles = (await readFile('src/css/app.scss', 'utf8')).replaceAll('\r\n', '\n')
styles = removeBetween(styles, '.sn-category-menu {', '.sn-desktop-nav__link {')
styles += `
// Full-width category menu, aligned with the reference storefront.
.sn-catalog-popup {
  left: max(16px, calc((100vw - 1440px) / 2)) !important;
  width: min(1440px, calc(100vw - 32px));
  max-width: calc(100vw - 32px) !important;
  border-radius: 12px;
  box-shadow: 0 14px 40px #4a1c3833;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
}
.sn-catalog-popup .catalog-menu { box-shadow: none; }
.sn-catalog-backdrop {
  position: fixed;
  inset: 181px 0 0;
  z-index: 5998;
  background: #723b5814;
  backdrop-filter: blur(4px);
}
.sn-all-category-trigger { min-width: 216px; font-size: 14px; font-weight: 600; }
@media (max-width: 1023px) { .sn-catalog-popup, .sn-catalog-backdrop { display: none; } }
`
await writeFile('src/css/app.scss', styles)
console.log('Category menu integrated in layout and home page.')
