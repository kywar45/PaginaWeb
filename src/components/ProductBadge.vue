<template>
  <VipBadge v-if="product.es_vip" />
  <SaleBadge v-else-if="product.tiene_descuento" :percentage="product.porcentaje_descuento" />
</template>
<script setup>
import VipBadge from './VipBadge.vue'
import SaleBadge from './SaleBadge.vue'
defineProps({ product: { type: Object, required: true } })
</script>
