<template>
  <span v-if="product.estatus === 0 || product.estatus === '0'" class="product-availability">
    <q-icon name="block" aria-hidden="true" /> Agotado
  </span>
</template>

<script setup>
defineProps({ product: { type: Object, required: true } })
</script>

<style scoped>
.product-availability {
  display: inline-flex;
  align-items: center;
  align-self: flex-start;
  gap: 4px;
  padding: 3px 7px;
  border: 1px solid #eadce2;
  border-radius: 6px;
  background: #f7f1f4;
  color: #825365;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.3;
}
</style>
