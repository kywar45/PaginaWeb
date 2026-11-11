<template>
  <aside class="catalog-sidebar" aria-label="Filtrar productos por categoría">
    <h2>Categorías</h2>
    <nav aria-label="Categorías y subcategorías">
      <div v-for="category in categories" :key="category.id" class="catalog-sidebar__group">
        <div class="catalog-sidebar__category" :class="{ 'is-active': category.id === categoryId }">
          <RouterLink
            :to="categoryUrl(category.id)"
            :aria-current="category.id === categoryId && !subcategoryId ? 'page' : undefined"
          >
            {{ category.name }}
          </RouterLink>
          <button
            v-if="category.subcategories.length"
            type="button"
            :aria-label="`${expanded.has(category.id) ? 'Contraer' : 'Expandir'} ${category.name}`"
            :aria-expanded="expanded.has(category.id)"
            :aria-controls="`catalog-subs-${category.id}`"
            @click="toggle(category.id)"
          >
            <q-icon :name="expanded.has(category.id) ? 'remove' : 'add'" />
          </button>
        </div>
        <ul
          v-if="category.subcategories.length"
          v-show="expanded.has(category.id)"
          :id="`catalog-subs-${category.id}`"
        >
          <li>
            <RouterLink
              :to="categoryUrl(category.id)"
              class="catalog-sidebar__all"
              :class="{ 'is-selected': category.id === categoryId && !subcategoryId }"
            >
              Ver toda la categoría
            </RouterLink>
          </li>
          <li v-for="sub in category.subcategories" :key="sub.id">
            <RouterLink
              :to="categoryUrl(category.id, sub.id)"
              :class="{ 'is-selected': category.id === categoryId && sub.id === subcategoryId }"
              :aria-current="
                category.id === categoryId && sub.id === subcategoryId ? 'page' : undefined
              "
            >
              {{ sub.name }}
            </RouterLink>
          </li>
        </ul>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { ref, watch } from 'vue'
import { catalogLocation } from 'src/utils/catalogRoutes'
const props = defineProps({
  categories: { type: Array, required: true },
  categoryId: { type: Number, default: null },
  subcategoryId: { type: Number, default: null },
})
const expanded = ref(new Set())
watch(
  () => props.categoryId,
  (id) => {
    if (id) expanded.value = new Set([id])
  },
  { immediate: true },
)
function toggle(id) {
  expanded.value = expanded.value.has(id) ? new Set() : new Set([id])
}
function categoryUrl(categoryId, subcategoryId) {
  const category = props.categories.find((item) => item.id === categoryId)
  const subcategory = category.subcategories.find((item) => item.id === subcategoryId)
  return catalogLocation(category, subcategory, 1, props.categories)
}
</script>

<style scoped lang="scss">
.catalog-sidebar {
  display: flex;
  flex-direction: column;
  position: static;
  align-self: start;
  max-height: calc(100dvh - 200px);
  min-height: 0;
  overflow: hidden;
  padding: 18px 12px;
  border: 1px solid #f0e0e7;
  border-radius: 16px;
  background: #fff;
}
.catalog-sidebar > nav {
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior-y: contain;
  scrollbar-width: thin;
  scrollbar-color: #efc1d0 transparent;
  scrollbar-gutter: stable;
}
.catalog-sidebar h2 {
  flex-shrink: 0;
  margin: 0 8px 16px;
  color: #4b3541;
  font-size: 17px;
  font-weight: 700;
  line-height: 1.4;
}
.catalog-sidebar__category {
  display: flex;
  align-items: center;
  gap: 4px;
  border-radius: 9px;
}
.catalog-sidebar__category > a {
  flex: 1;
  min-width: 0;
  padding: 10px 8px;
  color: #705b66;
  font-size: 13px;
  line-height: 1.4;
  text-decoration: none;
}
.catalog-sidebar__category.is-active {
  background: #fff0f5;
}
.catalog-sidebar__category.is-active > a {
  color: #d72c60;
  font-weight: 700;
}
.catalog-sidebar__category button {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: #fff7fa;
  color: #be6c89;
  cursor: pointer;
  font-size: 17px;
}
.catalog-sidebar ul {
  display: grid;
  gap: 3px;
  margin: 3px 0 8px 8px;
  padding: 4px 0 4px 10px;
  border-left: 1px solid #f3d7e2;
  list-style: none;
}
.catalog-sidebar li a {
  display: block;
  padding: 7px 8px;
  border-radius: 7px;
  color: #8e7983;
  font-size: 12px;
  line-height: 1.4;
  text-decoration: none;
}
.catalog-sidebar li a.is-selected {
  background: #fff0f5;
  color: #ce285b;
  font-weight: 700;
}
.catalog-sidebar li a.catalog-sidebar__all {
  font-weight: 600;
}
.catalog-sidebar a:hover {
  color: #d72c60;
  background: #fff8fb;
}
.catalog-sidebar a:focus-visible,
.catalog-sidebar button:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: -2px;
}
</style>
