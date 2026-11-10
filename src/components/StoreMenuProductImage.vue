<template>
  <span
    v-if="index !== null"
    class="menu-product-photo"
    role="img"
    :aria-label="label"
    :style="spriteStyle"
  />
  <img
    v-else
    class="menu-product-photo menu-product-photo--image"
    :src="src"
    :alt="label"
    loading="lazy"
  />
</template>

<script setup>
import { computed } from 'vue'
import { publicAsset } from 'src/utils/publicAsset'
const props = defineProps({
  index: { type: Number, default: null },
  src: { type: String, default: '' },
  label: { type: String, default: '' },
})
const spriteStyle = computed(() => ({
  backgroundImage: `url("${publicAsset('img/menu/nail-products.png')}")`,
  backgroundPosition: `${(props.index % 5) * 25}% ${Math.floor(props.index / 5) * 100}%`,
}))
</script>

<style scoped>
.menu-product-photo {
  display: block;
  width: 100%;
  aspect-ratio: 1;
  background-repeat: no-repeat;
  background-size: 500% 200%;
  mix-blend-mode: multiply;
}
.menu-product-photo--image {
  object-fit: contain;
}
</style>
