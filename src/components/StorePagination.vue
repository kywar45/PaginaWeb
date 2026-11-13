<template>
  <nav class="store-pagination" :aria-label="ariaLabel">
    <span class="store-pagination__status">Página {{ modelValue }} de {{ max }}</span>
    <div class="store-pagination__controls">
      <q-btn flat dense no-caps icon="chevron_left" label="Anterior" aria-label="Página anterior" :disable="modelValue <= 1" @click="$emit('update:modelValue', modelValue - 1)" />
      <q-pagination
        :model-value="modelValue"
        :max="max"
        :max-pages="7"
        ellipses
        boundary-numbers
        color="primary"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <q-btn flat dense no-caps icon-right="chevron_right" label="Siguiente" aria-label="Página siguiente" :disable="modelValue >= max" @click="$emit('update:modelValue', modelValue + 1)" />
    </div>
  </nav>
</template>

<script setup>
defineProps({
  modelValue: { type: Number, required: true },
  max: { type: Number, required: true },
  ariaLabel: { type: String, default: 'Paginación' },
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.store-pagination { display: flex; flex-direction: column; align-items: center; gap: 8px; max-width: 100%; }
.store-pagination__status { color: #8c737e; font-size: 11px; }
.store-pagination__controls { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 8px; color: #d52c60; }
.store-pagination__controls > .q-btn { padding: 5px 9px; border: 1px solid #f0dce4; border-radius: 7px; font-size: 12px; }
@media (max-width: 599px) {
  .store-pagination__controls { gap: 4px; }
  .store-pagination__controls > .q-btn { padding: 5px; font-size: 11px; }
}
</style>
