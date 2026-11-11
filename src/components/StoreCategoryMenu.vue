<template>
  <section class="catalog-menu" aria-label="Explorar categorías">
    <nav class="catalog-menu__sidebar" aria-label="Categorías de productos">
      <div class="catalog-menu__category-list">
        <button
          v-for="category in visibleCategories"
          :key="category.slug"
          type="button"
          class="catalog-menu__category"
          :class="{ 'catalog-menu__category--active': activeCategory?.slug === category.slug }"
          :aria-pressed="activeCategory?.slug === category.slug"
          :aria-controls="panelId"
          @mouseenter="previewCategory(category)"
          @focus="previewCategory(category)"
          @click="previewCategory(category)"
        >
          <CategoryLineIcon :category-id="category.id" />
          <span>{{ categoryLabel(category) }}</span>
          <q-icon name="chevron_right" />
        </button>
      </div>
      <button type="button" class="catalog-menu__offers" @click="$emit('offers')">
        <span class="catalog-menu__offer-seal">%</span>
        <span
          ><strong>Ver todas las ofertas</strong
          ><small>Descuentos especiales<br />cada semana</small></span
        >
        <q-icon name="chevron_right" />
      </button>
    </nav>

    <div :id="panelId" class="catalog-menu__main">
      <template v-if="activeCategory">
        <header class="catalog-menu__heading">
          <div class="catalog-menu__brand" :class="{ 'catalog-menu__brand--mussa': isLeMussa }">
            <h2>{{ categoryLabel(activeCategory) }}</h2>
            <span :class="{ 'catalog-menu__slogan': !isLeMussa }">{{ isLeMussa ? 'UÑAS QUE INSPIRAN' : 'DESCUBRE MÁS, PAGA MENOS' }}</span>
          </div>
          <p>
            Calidad profesional, resultados extraordinarios.<br />Descubre toda la línea de
            productos {{ categoryLabel(activeCategory) }}<br class="catalog-menu__desktop-break" />
            y lleva tu talento al siguiente nivel.
          </p>
        </header>

        <h3 class="catalog-menu__subheading">
          {{
            activeCategory.subcategories?.length ? 'Explorar subcategorías' : 'Explorar categoría'
          }}
        </h3>
        <div class="catalog-menu__tiles">
          <button
            v-for="tile in tiles"
            :key="tile.key"
            type="button"
            class="catalog-menu__tile"
            @click="selectTile(tile)"
          >
            <span class="catalog-menu__tile-title"
              >{{ tile.label }}<q-icon name="chevron_right"
            /></span>
          </button>
        </div>
      </template>
      <p v-else class="catalog-menu__empty">No hay categorías disponibles.</p>
    </div>

    <aside
      v-if="activeCategory"
      class="catalog-menu__promotion"
      :aria-label="`Descubre ${categoryLabel(activeCategory)}`"
    >
      <div class="catalog-menu__campaign" :class="{ 'catalog-menu__campaign--mussa': isLeMussa }">
        <img
          v-if="isLeMussa"
          class="catalog-menu__campaign-photo"
          :src="publicAsset('img/menu/manicure-promo.png')"
          alt="Manicura profesional en tonos rosa nude"
        />
        <img
          v-else
          class="catalog-menu__campaign-category"
          :src="activeCategory.svgIcon || activeCategory.iconUrl"
          :alt="categoryLabel(activeCategory)"
        />
        <div class="catalog-menu__campaign-brand">
          <p>{{ categoryLabel(activeCategory) }}</p>
          <span :class="{ 'catalog-menu__slogan': !isLeMussa }">{{ isLeMussa ? 'UÑAS QUE INSPIRAN' : 'DESCUBRE MÁS, PAGA MENOS' }}</span>
        </div>
        <div v-if="isLeMussa" class="catalog-menu__bestseller">
          <strong>#1</strong><span>EN VENTAS</span>
        </div>
        <div class="catalog-menu__campaign-bottom">
          <ul>
            <li><q-icon name="check" /> Calidad profesional</li>
            <li><q-icon name="check" /> Resultados duraderos</li>
            <li><q-icon name="check" /> Amada por expertas</li>
          </ul>
          <button
            type="button"
            class="catalog-menu__line-button"
            @click="$emit('select-category', activeCategory)"
          >
            Ver toda la línea <q-icon name="east" />
          </button>
        </div>
      </div>
      <div class="catalog-menu__benefits">
        <span><q-icon name="local_shipping" /> Envíos a todo México</span>
        <span><q-icon name="verified_user" /> Compra 100% segura</span>
        <span><q-icon name="percent" /> Precios de mayoreo</span>
      </div>
    </aside>
  </section>
</template>

<script setup>
import { computed, ref, useId, watch } from 'vue'
import CategoryLineIcon from './CategoryLineIcon.vue'
import { publicAsset } from 'src/utils/publicAsset'

const props = defineProps({ categories: { type: Array, default: () => [] } })
const emit = defineEmits(['select-category', 'select-subcategory', 'select-product', 'offers'])
const panelId = useId()
const activeSlug = ref('')
const visibleCategories = computed(() =>
  props.categories.filter((category) => category.slug !== 'all'),
)
const activeCategory = computed(
  () =>
    visibleCategories.value.find((category) => category.slug === activeSlug.value) ||
    visibleCategories.value[0],
)
const isLeMussa = computed(() => /^le\s*['’]?\s*mussa$/i.test(activeCategory.value?.name || ''))

watch(
  visibleCategories,
  (categories) => {
    if (!categories.some((category) => category.slug === activeSlug.value)) {
      activeSlug.value =
        (categories.find((category) => Number(category.id) === 1) || categories[0])?.slug || ''
    }
  },
  { immediate: true },
)

function categoryLabel(category) {
  return (
    {
      Cosmetiquertas: 'Cosmetiqueras',
      Electricos: 'Eléctricos',
      'Corporales/ Faciales': 'Corporales / Faciales',
    }[category.name] ||
    category.label ||
    category.name
  )
}

function previewCategory(category) {
  activeSlug.value = category.slug
}

const tiles = computed(() => {
  const category = activeCategory.value
  if (!category) return []
  if (!category.subcategories?.length)
    return [
      {
        key: 'catalog',
        label: 'Ver colección',
        categoryId: category.id,
      },
    ]
  return category.subcategories.map((sub) => ({
    key: sub.slug,
    label: sub.name,
    categoryId: category.id,
    subcategoryId: sub.id,
  }))
})

function selectTile(tile) {
  const category =
    visibleCategories.value.find((category) => Number(category.id) === Number(tile.categoryId)) ||
    activeCategory.value
  const subcategory = category?.subcategories?.find(
    (sub) => Number(sub.id) === Number(tile.subcategoryId),
  )
  if (subcategory) emit('select-subcategory', category, subcategory)
  else emit('select-category', category)
}
</script>

<style scoped lang="scss">
.catalog-sale-badge {
  position: absolute;
  z-index: 2;
  top: 8px;
  left: 8px;
}
.catalog-menu {
  --menu-pink: var(--q-primary);
  --menu-wine: var(--fuchsia-dark);
  display: grid;
  grid-template-columns: 20.5% minmax(0, 1fr) 20%;
  gap: 22px;
  padding: 16px;
  border: 1px solid var(--store-border);
  border-radius: 12px;
  background: #fff;
  color: #292331;
  box-shadow: 0 14px 34px color-mix(in srgb, var(--q-primary) 14%, transparent);
  font-family: 'Roboto', Arial, sans-serif;
  text-align: left;
}
.catalog-menu button {
  font: inherit;
  cursor: pointer;
}
.catalog-menu button:focus-visible {
  outline: 2px solid var(--menu-pink);
  outline-offset: 3px;
}
.catalog-menu__sidebar {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding-right: 10px;
  border-right: 1px solid var(--store-border);
  background: linear-gradient(90deg, #fff, var(--store-paper));
}
.catalog-menu__category {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr) 14px;
  align-items: center;
  gap: 14px;
  width: 100%;
  min-height: 44px;
  padding: 9px 12px;
  border: 0;
  border-bottom: 1px solid var(--store-border);
  background: transparent;
  color: #292331;
  text-align: left;
  font-size: 14px !important;
  line-height: 1.3;
  transition:
    background 0.15s,
    color 0.15s;
}
.catalog-menu__category > svg {
  width: 25px;
  height: 25px;
}
.catalog-menu__category > .q-icon {
  font-size: 19px;
  color: #847987;
}
.catalog-menu__category--active {
  border-radius: 8px;
  border-color: transparent;
  background: linear-gradient(90deg, var(--store-paper), var(--nude-light));
  color: var(--fuchsia-dark);
  font-weight: 700 !important;
}
.catalog-menu__category--active > .q-icon {
  color: inherit;
}
.catalog-menu__offers {
  display: grid;
  grid-template-columns: 46px minmax(0, 1fr) 16px;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 86px;
  margin-top: auto;
  padding: 14px;
  border: 1px solid var(--store-border);
  border-radius: 12px;
  background: var(--store-paper);
  color: var(--fuchsia-dark);
  text-align: left;
}
.catalog-menu__offers strong {
  display: block;
  margin-bottom: 5px;
  font-size: 13px;
  font-weight: 600;
}
.catalog-menu__offers small {
  display: block;
  color: #817883;
  font-size: 11px;
  line-height: 1.25;
}
.catalog-menu__offers > .q-icon {
  font-size: 22px;
}
.catalog-menu__offer-seal {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  background: var(--store-button-gradient);
  color: #fff;
  font-size: 29px;
  clip-path: polygon(
    50% 0%,
    63% 8%,
    79% 6%,
    88% 20%,
    100% 30%,
    97% 47%,
    100% 63%,
    87% 75%,
    80% 91%,
    63% 92%,
    50% 100%,
    35% 93%,
    19% 92%,
    11% 77%,
    0% 65%,
    4% 48%,
    0% 31%,
    13% 20%,
    20% 5%,
    37% 8%
  );
}
.catalog-menu__main {
  min-width: 0;
  padding: 3px 0 5px;
}
.catalog-menu__heading {
  display: flex;
  align-items: center;
  gap: 25px;
  min-height: 76px;
  margin-bottom: 25px;
}
.catalog-menu__brand {
  flex: 0 0 auto;
  max-width: 45%;
  color: var(--fuchsia-dark);
}
.catalog-menu__brand h2 {
  margin: 0;
  font:
    32px/1.05 Georgia,
    'Times New Roman',
    serif;
  letter-spacing: -1px;
}
.catalog-menu__brand--mussa h2 {
  font-size: clamp(38px, 3.5vw, 52px);
  white-space: nowrap;
}
.catalog-menu__brand > span {
  display: block;
  margin-top: 5px;
  text-align: center;
  font-size: 9px;
  letter-spacing: 3.3px;
}
.catalog-menu__heading > p {
  flex: 1;
  margin: 0;
  color: #7d6c76;
  font-size: 12px;
  line-height: 1.5;
}
.catalog-menu__view-all {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 4px;
  padding: 8px 0;
  border: 0;
  background: transparent;
  color: var(--menu-pink);
  font-size: 12px !important;
  font-weight: 600 !important;
}
.catalog-menu__view-all .q-icon {
  font-size: 19px;
}
.catalog-menu__tiles {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 26px 24px;
}
.catalog-menu__tile {
  min-width: 0;
  padding: 0;
  border: 0;
  background: transparent;
  text-align: left;
  color: inherit;
}
.catalog-menu__circle {
  display: grid;
  place-items: center;
  width: min(100%, 118px);
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 50%;
  margin-bottom: 7px;
  background: var(--store-paper);
  transition:
    transform 0.2s,
    background 0.2s;
}
.catalog-menu__circle :deep(.menu-product-photo) {
  width: 100%;
}
.catalog-menu__tile:hover .catalog-menu__circle {
  transform: translateY(-3px);
  background: var(--nude-light);
}
.catalog-menu__tile-title {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 3px;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.2;
}
.catalog-menu__tile-title .q-icon {
  flex-shrink: 0;
  font-size: 20px;
  color: var(--menu-pink);
}
.catalog-menu__tile > small {
  display: block;
  min-height: 30px;
  padding-right: 8px;
  color: #7f717b;
  font-size: 11px;
  line-height: 1.25;
}
.catalog-menu__featured {
  margin-top: 26px;
}
.catalog-menu__featured h3 {
  margin: 0 0 9px;
  color: #251d29;
  font-size: 18px;
  line-height: 1.2;
  font-weight: 600;
}
.catalog-menu__products {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}
.catalog-menu__product {
  position: relative;
  cursor: pointer;
  min-width: 0;
  padding: 6px 10px 10px;
  border: 1px solid var(--store-border);
  border-radius: 9px;
  background: #fff;
}
.catalog-menu__product:focus-visible {
  outline: 2px solid #de7c97;
  outline-offset: 2px;
}
.catalog-menu__discount {
  position: absolute;
  top: 6px;
  left: 5px;
  z-index: 1;
  padding: 3px 6px;
  border-radius: 4px;
  background: var(--store-button-gradient);
  color: #fff;
  font-size: 11px;
  line-height: 1.2;
  font-weight: 600;
}
.catalog-menu__product-link {
  display: block;
  width: 100%;
  padding: 0;
  border: 0;
  background: none;
  text-align: left;
  color: inherit;
}
.catalog-menu__product-link :deep(.menu-product-photo) {
  max-width: 132px;
  margin: 0 auto;
}
.catalog-menu__product-link h4 {
  min-height: 32px;
  margin: 1px 0 8px;
  font-size: 12px;
  line-height: 1.35;
  font-weight: 400;
}
.catalog-menu__product-bottom {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 4px;
}
.catalog-menu__product-bottom del {
  display: block;
  margin-bottom: 2px;
  color: #9a929e;
  font-size: 11px;
  line-height: 1;
}
.catalog-menu__product-bottom strong {
  color: var(--q-primary);
  font-size: 17px;
  line-height: 1.1;
}
.catalog-menu__cart {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 1px solid var(--hot-pink);
  border-radius: 7px;
  background: var(--store-button-gradient);
  color: #fff;
  box-shadow: inset 2px 0 4px #ffffff66;
}
.catalog-menu__cart .q-icon {
  font-size: 18px;
}
.catalog-menu__promotion {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 724px;
  overflow: hidden;
  border-radius: 11px;
  background: var(--nude-light);
}
.catalog-menu__campaign {
  position: relative;
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 560px;
  overflow: hidden;
  background: linear-gradient(135deg, var(--store-paper), var(--nude-light));
  color: var(--menu-wine);
}
.catalog-menu__campaign-photo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.catalog-menu__campaign::after {
  position: absolute;
  inset: 0;
  content: '';
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--nude-light) 25%, transparent),
    transparent 40%,
    color-mix(in srgb, var(--nude-light) 56%, transparent) 68%,
    var(--nude-light) 100%
  );
}
.catalog-menu__campaign-category {
  position: absolute;
  top: 130px;
  left: 5%;
  width: 90%;
  height: 240px;
  object-fit: contain;
  mix-blend-mode: multiply;
}
.catalog-menu__campaign-brand {
  position: relative;
  z-index: 1;
  margin: 32px 12px 0;
  text-align: center;
  color: var(--fuchsia-dark);
}
.catalog-menu__campaign-brand p {
  margin: 0 0 6px;
  font:
    35px/1.05 Georgia,
    'Times New Roman',
    serif;
  letter-spacing: -1.5px;
}
.catalog-menu__campaign--mussa .catalog-menu__campaign-brand p {
  font-size: clamp(36px, 3.6vw, 53px);
  white-space: nowrap;
}
.catalog-menu__campaign-brand > span {
  font-size: 10px;
  letter-spacing: 3px;
}
.catalog-menu__bestseller {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  width: 96px;
  height: 96px;
  margin: 44px 0 0 10px;
  border: 9px solid var(--fuchsia-light);
  border-radius: 50%;
  background: linear-gradient(135deg, var(--q-primary), var(--fuchsia-dark));
  box-shadow: 0 0 0 2px var(--nude-medium);
  color: #fff;
}
.catalog-menu__bestseller strong {
  font-size: 28px;
  font-weight: 500;
  line-height: 1.1;
}
.catalog-menu__bestseller > span {
  font-size: 11px;
  font-weight: 500;
}
.catalog-menu__campaign-bottom {
  position: relative;
  z-index: 1;
  margin-top: auto;
  padding: 20px 28px 29px;
}
.catalog-menu__campaign-bottom ul {
  display: grid;
  gap: 17px;
  margin: 0 0 25px;
  padding: 0;
  list-style: none;
}
.catalog-menu__campaign-bottom li {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  line-height: 1.25;
}
.catalog-menu__campaign-bottom li .q-icon {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--menu-wine);
  box-shadow: 0 1px 3px #ffffff80;
  color: #fff;
  font-size: 19px;
}
.catalog-menu__line-button {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  width: 100%;
  min-height: 44px;
  padding: 8px 12px;
  border: 0;
  border-radius: 28px;
  background: var(--store-button-gradient);
  color: #fff;
  font-size: 14px !important;
  font-weight: 500 !important;
}
.catalog-menu__line-button .q-icon {
  font-size: 23px;
}
.catalog-menu__benefits {
  position: relative;
  display: grid;
  gap: 15px;
  padding: 23px 28px;
  border-radius: 24px 24px 0 0;
  background: var(--store-paper);
  color: var(--fuchsia-dark);
}
.catalog-menu__benefits > span {
  display: flex;
  align-items: center;
  gap: 22px;
  font-size: 12px;
}
.catalog-menu__benefits .q-icon {
  flex-shrink: 0;
  font-size: 29px;
  font-weight: 400;
}
.catalog-menu__empty {
  margin: 40px 0;
  text-align: center;
}
@media (min-width: 1440px) {
  .catalog-menu__circle {
    width: 124px;
  }
  .catalog-menu__tiles {
    row-gap: 32px;
  }
}
@media (min-width: 1024px) and (max-height: 950px) {
  .catalog-menu {
    gap: 18px;
    padding: 12px;
  }
  .catalog-menu__category {
    min-height: 34px;
    gap: 8px;
    padding: 5px 8px;
    font-size: 13px !important;
  }
  .catalog-menu__offers {
    min-height: 76px;
    gap: 8px;
    margin-top: 12px;
    padding: 12px;
  }
  .catalog-menu__offer-seal {
    width: 40px;
    height: 40px;
    font-size: 25px;
  }
  .catalog-menu__heading {
    min-height: 66px;
    margin-bottom: 16px;
  }
  .catalog-menu__tiles {
    gap: 18px 18px;
  }
  .catalog-menu__circle {
    width: min(100%, 108px);
  }
  .catalog-menu__featured {
    margin-top: 18px;
  }
  .catalog-menu__product-link :deep(.menu-product-photo) {
    max-width: 110px;
  }
  .catalog-menu__product-link h4 {
    min-height: 30px;
    margin-bottom: 5px;
  }
  .catalog-menu__promotion {
    min-height: 0;
  }
  .catalog-menu__campaign {
    min-height: 400px;
  }
  .catalog-menu__campaign-category {
    top: 100px;
    height: 170px;
  }
  .catalog-menu__campaign-brand {
    margin-top: 18px;
  }
  .catalog-menu__campaign-brand p {
    font-size: 29px;
  }
  .catalog-menu__bestseller {
    width: 84px;
    height: 84px;
    margin-top: 34px;
  }
  .catalog-menu__campaign-bottom {
    padding: 12px 18px 14px;
  }
  .catalog-menu__campaign-bottom ul {
    gap: 8px;
    margin-bottom: 12px;
  }
  .catalog-menu__benefits {
    gap: 8px;
    padding: 12px 18px;
  }
  .catalog-menu__benefits .q-icon {
    font-size: 23px;
  }
}
@media (min-width: 1024px) and (max-width: 1240px) {
  .catalog-menu {
    grid-template-columns: 22% minmax(0, 1fr) 20%;
    gap: 16px;
    padding: 12px;
  }
  .catalog-menu__category {
    gap: 9px;
    padding-inline: 8px;
    font-size: 12px !important;
  }
  .catalog-menu__heading {
    flex-wrap: wrap;
    gap: 8px 16px;
    margin-bottom: 18px;
  }
  .catalog-menu__heading > p {
    font-size: 11px;
  }
  .catalog-menu__view-all {
    margin-left: auto;
  }
  .catalog-menu__tiles {
    gap: 22px 12px;
  }
  .catalog-menu__tile-title {
    font-size: 12px;
  }
  .catalog-menu__products {
    gap: 7px;
  }
  .catalog-menu__product {
    padding-inline: 6px;
  }
  .catalog-menu__product-bottom strong {
    font-size: 15px;
  }
  .catalog-menu__cart {
    width: 29px;
    height: 31px;
  }
  .catalog-menu__campaign-bottom {
    padding-inline: 16px;
  }
  .catalog-menu__campaign-bottom li {
    font-size: 12px;
    gap: 8px;
  }
  .catalog-menu__benefits {
    padding-inline: 17px;
  }
  .catalog-menu__benefits > span {
    gap: 12px;
    font-size: 11px;
  }
  .catalog-menu__offers {
    gap: 8px;
    padding-inline: 9px;
  }
  .catalog-menu__offers strong {
    font-size: 12px;
  }
}
@media (max-width: 1023px) {
  .catalog-menu {
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 18px;
  }
  .catalog-menu__promotion {
    display: none;
  }
  .catalog-menu__heading {
    flex-wrap: wrap;
    gap: 12px;
  }
  .catalog-menu__heading > p {
    display: none;
  }
  .catalog-menu__view-all {
    margin-left: auto;
  }
  .catalog-menu__tiles {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
  }
  .catalog-menu__category {
    font-size: 12px !important;
    gap: 8px;
  }
  .catalog-menu__products {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .catalog-menu__offers {
    margin-top: 24px;
    gap: 8px;
    padding-inline: 8px;
  }
}
@media (max-width: 599px) {
  .catalog-menu {
    display: block;
    padding: 14px 12px 18px;
    border-radius: 14px;
  }
  .catalog-menu__sidebar {
    display: block;
    padding: 0 0 14px;
    border: 0;
    background: none;
  }
  .catalog-menu__category-list {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 3px 2px 8px;
    scrollbar-width: thin;
    scrollbar-color: var(--nude-medium) transparent;
  }
  .catalog-menu__category {
    flex: 0 0 auto;
    width: auto;
    max-width: 205px;
    grid-template-columns: 22px minmax(0, 1fr);
    gap: 8px;
    min-height: 42px;
    padding: 8px 10px;
    border: 1px solid var(--store-border);
    border-radius: 7px;
  }
  .catalog-menu__category > svg {
    width: 22px;
    height: 22px;
  }
  .catalog-menu__category > .q-icon,
  .catalog-menu__offers {
    display: none;
  }
  .catalog-menu__main {
    padding: 0;
  }
  .catalog-menu__heading {
    gap: 8px;
    min-height: 58px;
    margin: 0 0 18px;
  }
  .catalog-menu__brand h2 {
    font-size: 26px;
  }
  .catalog-menu__brand--mussa h2 {
    font-size: 34px;
  }
  .catalog-menu__brand > span {
    font-size: 7px;
    letter-spacing: 2px;
  }
  .catalog-menu__view-all {
    max-width: 128px;
    font-size: 11px !important;
    text-align: right;
    line-height: 1.4;
  }
  .catalog-menu__tiles {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px 12px;
  }
  .catalog-menu__circle {
    width: 100%;
    max-width: 106px;
    margin-inline: auto;
  }
  .catalog-menu__tile-title {
    font-size: 11px;
  }
  .catalog-menu__tile-title .q-icon {
    font-size: 17px;
  }
  .catalog-menu__tile > small {
    font-size: 10px;
    min-height: 25px;
  }
  .catalog-menu__featured {
    margin-top: 23px;
  }
  .catalog-menu__featured h3 {
    font-size: 16px;
  }
  .catalog-menu__products {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .catalog-menu__product-link h4 {
    font-size: 12px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .catalog-menu__category,
  .catalog-menu__circle {
    transition: none;
  }
}

.catalog-menu__subheading {
  margin: 4px 0 12px;
  color: #92828b;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.catalog-menu__brand > .catalog-menu__slogan,
.catalog-menu__campaign-brand > .catalog-menu__slogan { letter-spacing: 1.3px; }
.catalog-menu__tiles {
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  align-content: start;
}
.catalog-menu__tile {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  min-height: 68px;
  padding: 13px 12px 13px 15px;
  border: 1px solid #f0dce3;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 4px 12px rgba(97, 45, 62, 0.05);
  color: #292331;
  cursor: pointer;
  transition:
    color 0.18s ease,
    background 0.18s ease,
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s ease;
}
.catalog-menu__tile::before {
  position: absolute;
  z-index: 0;
  inset: 0 0 0 auto;
  width: 52px;
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
    linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  pointer-events: none;
  content: '';
}
.catalog-menu__tile-title {
  position: relative;
  z-index: 1;
  align-items: center;
  gap: 18px;
  color: #292331;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.35;
}
.catalog-menu__tile-title .q-icon {
  display: grid;
  width: 27px;
  height: 27px;
  place-items: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
  font-size: 18px;
  transition:
    transform 0.18s ease,
    background 0.18s ease;
}
.catalog-menu__tile:hover {
  transform: translateY(-2px);
  border-color: #d9cbd0;
  background: #fff;
  color: #292331;
  box-shadow: 0 7px 17px rgba(58, 39, 46, 0.1);
}
.catalog-menu__tile:hover .q-icon {
  transform: translateX(3px);
}
.catalog-menu__tile:focus-visible {
  outline: 2px solid #de7c97;
  outline-offset: 2px;
}
@media (max-width: 599px) {
  .catalog-menu__tiles {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  .catalog-menu__tile {
    min-height: 58px;
    padding: 10px;
  }
  .catalog-menu__tile-title {
    font-size: 12px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .catalog-menu__tile,
  .catalog-menu__tile-title .q-icon {
    transition: none;
  }
}
</style>
