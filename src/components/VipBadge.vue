<template>
  <span class="sale-badge vip-badge">
    <img v-image-skeleton :src="vipImage" alt="Precio especial VIP" class="vip-badge__image" />
  </span>
</template>

<script setup>
import vipImage from '../../img/vip.png'
</script>

<style scoped>
.vip-badge {
  display: inline-flex;
  flex-shrink: 0;
  width: var(--vip-badge-width, 88px);
  padding: 0;
  border: 0;
  background: transparent;
  vertical-align: middle;
  line-height: 0;
}
.vip-badge > .vip-badge__image {
  display: block;
  width: 100% !important;
  height: auto !important;
  padding: 0 !important;
  border: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  transform: none !important;
  filter: none;
  object-fit: contain;
}
</style>
