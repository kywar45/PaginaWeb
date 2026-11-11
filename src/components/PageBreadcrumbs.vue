<template>
  <header class="page-breadcrumbs">
    <q-btn
      flat
      round
      dense
      icon="arrow_back"
      aria-label="Volver"
      :to="backTo"
      class="page-breadcrumbs__back"
    />
    <nav aria-label="Ruta de navegación">
      <RouterLink to="/">Inicio</RouterLink>
      <template v-for="item in parents" :key="item.to">
        <q-icon name="chevron_right" />
        <RouterLink :to="item.to">{{ item.label }}</RouterLink>
      </template>
      <q-icon name="chevron_right" />
      <span aria-current="page">{{ title }}</span>
    </nav>
  </header>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  backTo: { type: [String, Object], default: '/' },
  parents: { type: Array, default: () => [] },
})
</script>

<style scoped lang="scss">
.page-breadcrumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  margin-bottom: 10px;
}
.page-breadcrumbs .page-breadcrumbs__back {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  min-height: 30px;
  border: 0;
  background: transparent;
  color: #df2f61;
}
.page-breadcrumbs__back :deep(.q-icon) {
  font-size: 21px;
}
.page-breadcrumbs nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  min-width: 0;
  color: #a77e8d;
  font-size: 12px;
  line-height: 1.4;
}
.page-breadcrumbs a {
  color: #bb4b70;
  text-decoration: none;
}
.page-breadcrumbs [aria-current] {
  color: #543140;
  font-size: 14px;
  font-weight: 600;
}
</style>
