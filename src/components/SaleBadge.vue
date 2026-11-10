<template>
  <span class="sale-badge">
    <q-icon name="local_offer" aria-hidden="true" />
    ¡Oferta! <strong v-if="percentage > 0">-{{ percentage }}%</strong>
  </span>
</template>

<script setup>
defineProps({ percentage: { type: Number, default: 0 } })
</script>

<style scoped>
.sale-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  width: fit-content;
  padding: 5px 8px;
  border-radius: 6px;
  background: #e11d2e;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}
.sale-badge strong {
  padding-left: 4px;
  border-left: 1px solid rgba(255, 255, 255, 0.55);
  color: inherit !important;
  font-size: inherit !important;
}
</style>
