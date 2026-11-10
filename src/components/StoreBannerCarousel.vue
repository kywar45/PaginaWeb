<template>
  <section
    v-if="slides.length"
    class="banner-carousel"
    :class="{ 'banner-carousel--strip': strip }"
    :aria-label="label"
    aria-roledescription="carrusel"
  >
    <component
      :is="active.href ? 'a' : 'div'"
      :href="active.href || undefined"
      class="banner-carousel__slide"
    >
      <img
        :key="active.image"
        :src="active.image"
        :alt="`${label} ${index + 1}`"
        :loading="strip ? 'lazy' : 'eager'"
        :fetchpriority="strip ? 'auto' : 'high'"
        @error="failedImages.add(active.image)"
      />
    </component>
    <div v-if="!strip" class="banner-finish" aria-hidden="true">
      <span class="banner-gel banner-gel--left" />
      <span class="banner-gel banner-gel--right" />
      <span class="banner-glitter banner-glitter--left" />
      <span class="banner-glitter banner-glitter--right" />
      <span
        v-for="spark in 6"
        :key="spark"
        class="banner-spark"
        :class="`banner-spark--${spark}`"
      />
    </div>
    <template v-if="slides.length > 1">
      <button
        class="banner-carousel__arrow banner-carousel__arrow--previous"
        type="button"
        aria-label="Banner anterior"
        @click="move(-1)"
      >
        <q-icon name="chevron_left" />
      </button>
      <button
        class="banner-carousel__arrow banner-carousel__arrow--next"
        type="button"
        aria-label="Banner siguiente"
        @click="move(1)"
      >
        <q-icon name="chevron_right" />
      </button>
      <div class="banner-carousel__dots" aria-label="Seleccionar banner">
        <button
          v-for="(slide, position) in slides"
          :key="slide.id"
          type="button"
          :aria-label="`Ver banner ${position + 1}`"
          :aria-current="index === position ? 'true' : undefined"
          @click="index = position"
        >
          <span :class="{ active: index === position }" />
        </button>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useQuasar } from 'quasar'

const props = defineProps({
  banners: { type: Array, required: true },
  strip: Boolean,
  label: { type: String, default: 'Promociones principales' },
})
const quasar = useQuasar()
const index = ref(0)
const failedImages = reactive(new Set())
function safeUrl(value) {
  if (typeof value !== 'string' || !value.trim()) return null
  try {
    const url = new URL(value, 'https://www.importadorasupernova.com/')
    return ['https:', 'http:'].includes(url.protocol) ? url.href : null
  } catch {
    return null
  }
}
const slides = computed(() =>
  props.banners
    .map((banner) => {
      const mobile = quasar.screen.width < 600
      const images = mobile
        ? [banner.imagen_movil, banner.imagen_web]
        : [banner.imagen_web, banner.imagen_movil]
      const image = images.map(safeUrl).find((url) => url && !failedImages.has(url))
      const href = mobile
        ? safeUrl(banner.redireccion_movil) || safeUrl(banner.redireccion_web)
        : safeUrl(banner.redireccion_web)
      return { id: banner.id, image, href }
    })
    .filter((banner) => banner.image),
)
watch(slides, () => {
  if (index.value >= slides.value.length) index.value = 0
})
const active = computed(() => slides.value[index.value] || slides.value[0])
function move(direction) {
  index.value = (index.value + direction + slides.value.length) % slides.value.length
}
</script>

<style scoped lang="scss">
.banner-finish {
  position: absolute;
  inset: 0;
  overflow: hidden;
  border-radius: inherit;
  pointer-events: none;
}
.banner-gel {
  position: absolute;
  width: 21%;
  height: 56%;
  border-radius: 44% 56% 64% 36% / 52% 35% 65% 48%;
  background:
    radial-gradient(ellipse at 35% 22%, #fffdfbd9, transparent 13%),
    linear-gradient(
      145deg,
      color-mix(in srgb, var(--nude-lightest) 60%, transparent) 8%,
      color-mix(in srgb, var(--nude-medium) 60%, transparent) 28%,
      color-mix(in srgb, var(--store-paper) 73%, transparent) 34%,
      color-mix(in srgb, var(--fuchsia-light) 50%, transparent) 52%,
      color-mix(in srgb, var(--nude-light) 60%, transparent) 80%
    );
  box-shadow:
    inset 3px 4px 5px #fff9,
    inset -5px -7px 12px color-mix(in srgb, var(--q-primary) 20%, transparent);
  border: 1px solid #fff6;
}
.banner-gel--left {
  left: -15%;
  bottom: -20%;
  transform: rotate(-28deg);
}
.banner-gel--right {
  right: -16%;
  top: -22%;
  transform: rotate(35deg);
}
.banner-glitter {
  position: absolute;
  width: 13%;
  height: 85%;
  background-image:
    radial-gradient(circle, #ffe9cddd 0 1px, transparent 1.7px),
    radial-gradient(circle, #d7a58be6 0 0.7px, transparent 1.5px),
    radial-gradient(circle, #fff9ed 0 0.8px, transparent 1.6px);
  background-size:
    11px 17px,
    7px 13px,
    19px 23px;
  background-position:
    0 0,
    3px 5px,
    8px 11px;
  mask-image: linear-gradient(90deg, #000, transparent 85%);
  opacity: 0.65;
}
.banner-glitter--left {
  left: -5%;
  bottom: -30%;
  transform: rotate(-28deg);
}
.banner-glitter--right {
  right: -5%;
  top: -25%;
  transform: rotate(152deg);
}
.banner-spark {
  position: absolute;
  width: 15px;
  height: 15px;
  background: #fff5e6;
  clip-path: polygon(50% 0, 60% 40%, 100% 50%, 60% 60%, 50% 100%, 40% 60%, 0 50%, 40% 40%);
  animation: banner-twinkle 4s ease-in-out infinite;
}
.banner-spark--1 {
  left: 3%;
  bottom: 15%;
}
.banner-spark--2 {
  left: 7%;
  bottom: 6%;
  width: 9px;
  height: 9px;
  animation-delay: -1.2s;
}
.banner-spark--3 {
  left: 1%;
  bottom: 32%;
  width: 8px;
  height: 8px;
  animation-delay: -2.5s;
}
.banner-spark--4 {
  right: 3%;
  top: 12%;
  animation-delay: -2s;
}
.banner-spark--5 {
  right: 7%;
  top: 5%;
  width: 9px;
  height: 9px;
  animation-delay: -0.7s;
}
.banner-spark--6 {
  right: 1%;
  top: 28%;
  width: 8px;
  height: 8px;
  animation-delay: -3s;
}
@keyframes banner-twinkle {
  0%,
  100% {
    opacity: 0.25;
    transform: scale(0.7);
  }
  50% {
    opacity: 0.9;
    transform: scale(1);
  }
}
@media (prefers-reduced-motion: reduce) {
  .banner-spark {
    animation: none;
    opacity: 0.65;
  }
}
.banner-carousel {
  position: relative;
  background: var(--store-paper);
}
.banner-carousel__slide {
  display: block;
  width: 100%;
}
.banner-carousel__slide img {
  display: block;
  width: 100%;
  height: auto;
}
.banner-carousel:not(.banner-carousel--strip) {
  width: 100%;
  height: auto;
  aspect-ratio: 3 / 1;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 10px 24px rgba(181, 78, 112, 0.14);

  .banner-carousel__slide,
  .banner-carousel__slide img {
    height: 100%;
  }

  .banner-carousel__slide img {
    object-fit: fill;
  }
}
.banner-carousel__arrow {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  line-height: 1;
  top: 50%;
  transform: translateY(-50%);
  border: 0;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ffffffe0;
  color: var(--q-primary);
  font-size: 26px;
  cursor: pointer;
  box-shadow: 0 2px 10px #0002;
}
.banner-carousel__arrow--previous {
  left: 16px;
}
.banner-carousel__arrow--next {
  right: 16px;
}
.banner-carousel__dots {
  position: absolute;
  bottom: 5px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  max-width: 80%;
  flex-wrap: wrap;
  justify-content: center;
}
.banner-carousel__dots button {
  border: 0;
  padding: 8px 4px;
  background: transparent;
  cursor: pointer;
}
.banner-carousel__dots span {
  display: block;
  border: 1px solid white;
  box-shadow: 0 0 3px #0009;
  background: #0006;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.banner-carousel__dots .active {
  border-color: var(--hot-pink);
  background: var(--hot-pink);
}
.banner-carousel--strip {
  margin: 10px 0 16px;
  border-radius: 8px;
  overflow: hidden;
}
button:focus-visible,
a:focus-visible {
  outline: 3px solid var(--q-primary);
  outline-offset: -3px;
}
@media (max-width: 599px) {
  .banner-carousel:not(.banner-carousel--strip) {
    border-radius: 16px;
  }
  .banner-carousel__arrow {
    width: 28px;
    height: 28px;
    font-size: 22px;
  }
  .banner-carousel__arrow--previous {
    left: 6px;
  }
  .banner-carousel__arrow--next {
    right: 6px;
  }
  .banner-carousel--strip {
    margin: 20px 0 26px;
  }
}
</style>
