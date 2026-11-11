<template>
  <span v-if="visits !== null" class="product-views">
    <q-icon name="visibility" aria-hidden="true" />
    <span>{{ visits.toLocaleString('es-MX') }} {{ visits === 1 ? 'vista' : 'vistas' }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ product: { type: Object, required: true } })
const visits = computed(() => {
  const value = props.product.visitas
  if (value === null || value === undefined || value === '') return null
  const count = Number(value)
  return Number.isFinite(count) && count >= 0 ? Math.floor(count) : null
})
</script>

<style scoped>
.product-views {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 3px;
  color: #82727b;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.4;
}
.product-views .q-icon {
  color: #b98b9b;
  font-size: 14px;
}
</style>
