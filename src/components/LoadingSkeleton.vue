<template>
  <div class="loading-skeleton" :class="`loading-skeleton--${variant}`" :style="{ '--skeleton-columns': columns }" role="status" aria-live="polite" aria-busy="true">
    <span class="loading-skeleton__sr">{{ label }}</span>
    <div v-if="variant === 'product'" class="loading-skeleton__detail" aria-hidden="true">
      <q-skeleton class="loading-skeleton__gallery" />
      <div class="loading-skeleton__description">
        <q-skeleton type="text" width="35%" />
        <q-skeleton height="32px" width="85%" />
        <q-skeleton type="text" width="25%" />
        <q-skeleton v-for="line in 3" :key="line" type="text" />
        <q-skeleton height="80px" />
        <q-skeleton height="48px" />
      </div>
    </div>
    <div v-else-if="variant === 'categories'" class="loading-skeleton__categories" aria-hidden="true">
      <div class="loading-skeleton__selectors">
        <q-skeleton v-for="item in 6" :key="item" height="90px" />
      </div>
      <div class="loading-skeleton__chips">
        <q-skeleton v-for="item in 8" :key="item" height="34px" :width="item % 2 ? '76%' : '92%'" />
      </div>
    </div>
    <div v-else-if="variant === 'cart' || variant === 'list' || variant === 'account'" class="loading-skeleton__rows" aria-hidden="true">
      <div v-for="item in count" :key="item" class="loading-skeleton__row">
        <q-skeleton class="loading-skeleton__thumb" />
        <div class="loading-skeleton__lines">
          <q-skeleton type="text" width="85%" />
          <q-skeleton type="text" width="45%" />
          <q-skeleton height="22px" width="70px" />
        </div>
      </div>
    </div>
    <template v-else>
      <q-skeleton v-if="variant === 'featured'" class="loading-skeleton__feature" aria-hidden="true" />
      <div class="loading-skeleton__cards" aria-hidden="true">
        <div v-for="item in count" :key="item" class="loading-skeleton__card">
          <q-skeleton class="loading-skeleton__photo" />
          <div class="loading-skeleton__info">
            <q-skeleton type="text" />
            <q-skeleton type="text" width="70%" />
            <q-skeleton type="text" width="40%" />
            <q-skeleton height="22px" width="70px" class="q-mt-sm" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'products' },
  count: { type: Number, default: 6 },
  columns: { type: Number, default: 4 },
  label: { type: String, default: 'Cargando contenido...' },
})
</script>

<style scoped>
.loading-skeleton { width: 100%; margin: 14px 0; }
.loading-skeleton__sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
.loading-skeleton :deep(.q-skeleton) { background: #f4eff1; border-radius: 9px; }
.loading-skeleton__cards { display: grid; grid-template-columns: repeat(var(--skeleton-columns), minmax(0, 1fr)); gap: 14px; }
.loading-skeleton__card { min-width: 0; overflow: hidden; border: 1px solid #f3e5eb; border-radius: 10px; }
.loading-skeleton__photo { width: 100%; aspect-ratio: 1; border-radius: 0 !important; }
.loading-skeleton__info { padding: 12px; }
.loading-skeleton--rail .loading-skeleton__cards { grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
.loading-skeleton--featured { display: grid; grid-template-columns: 1fr 1.2fr; gap: 12px; }
.loading-skeleton__feature { min-height: 360px; height: 100%; border-radius: 16px !important; }
.loading-skeleton--featured .loading-skeleton__info { padding: 8px; }
.loading-skeleton--featured .loading-skeleton__photo { height: 100px; aspect-ratio: auto; }
.loading-skeleton__detail { display: grid; grid-template-columns: 1fr 1.1fr; gap: 32px; }
.loading-skeleton__gallery { aspect-ratio: 1; border-radius: 20px !important; }
.loading-skeleton__description { display: flex; flex-direction: column; gap: 18px; padding: 12px 0; }
.loading-skeleton__categories { display: grid; grid-template-columns: 100px 1fr; gap: 20px; }
.loading-skeleton__selectors, .loading-skeleton__chips, .loading-skeleton__rows { display: grid; gap: 10px; align-content: start; }
.loading-skeleton__row { display: flex; gap: 16px; padding: 16px; border: 1px solid #f3e5eb; border-radius: 16px; }
.loading-skeleton__thumb { flex: 0 0 80px; height: 90px; }
.loading-skeleton__lines { flex: 1; min-width: 0; }
@media (max-width: 1023px) {
  .loading-skeleton__cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .loading-skeleton--rail .loading-skeleton__cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .loading-skeleton--rail .loading-skeleton__card:nth-child(n+4) { display: none; }
  .loading-skeleton--featured { grid-template-columns: 1fr; }
  .loading-skeleton--featured .loading-skeleton__cards { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 599px) {
  .loading-skeleton__cards, .loading-skeleton--rail .loading-skeleton__cards, .loading-skeleton--featured .loading-skeleton__cards { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
  .loading-skeleton--rail .loading-skeleton__card:nth-child(n+3) { display: none; }
  .loading-skeleton__info { padding: 10px; }
  .loading-skeleton__feature { min-height: 230px; }
  .loading-skeleton__detail { grid-template-columns: 1fr; gap: 20px; }
  .loading-skeleton__description { gap: 12px; }
  .loading-skeleton__thumb { flex-basis: 55px; height: 70px; }
  .loading-skeleton__row { gap: 10px; padding: 12px; }
}
@media (prefers-reduced-motion: reduce) {
  .loading-skeleton :deep(.q-skeleton::before), .loading-skeleton :deep(.q-skeleton::after) { animation: none !important; }
}
</style>
