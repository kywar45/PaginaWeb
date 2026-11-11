<template>
  <section
    v-if="slides.length"
    class="banner-carousel"
    :class="{ 'banner-carousel--strip': strip }"
    :style="mobileRatio ? { '--mobile-banner-ratio': mobileRatio } : undefined"
    :aria-label="label"
    aria-roledescription="carrusel"
  >
    <div
      ref="viewport"
      class="banner-carousel__viewport"
      :class="{ 'is-dragging': dragging }"
      @pointerdown="startDrag"
      @pointermove="drag"
      @pointerup="endDrag"
      @pointercancel="cancelDrag"
      @click.capture="preventDragClick"
      @dragstart.prevent
    >
      <div
        class="banner-carousel__track"
        :class="{ 'is-instant': dragging || !animate }"
        :style="{ transform: `translateX(calc(${-position * 100}% + ${dragOffset}px))` }"
        @transitionend.self="finishTransition"
      >
        <component
          :is="slide.href ? 'a' : 'div'"
          v-for="(slide, slot) in trackSlides"
          :key="`${slot}-${slide.id}`"
          :href="slide.href || undefined"
          :aria-hidden="slot !== position ? 'true' : undefined"
          :tabindex="slot === position && slide.href ? 0 : -1"
          class="banner-carousel__slide"
        >
          <img
            :src="slide.image"
            :alt="`${label} ${slide.id}`"
            :loading="strip ? 'lazy' : 'eager'"
            :fetchpriority="slot === position && !strip ? 'high' : 'auto'"
            draggable="false"
            @error="failedImages.add(slide.image)"
            @load="rememberMobileRatio"
          />
        </component>
      </div>
    </div>
    <template v-if="slides.length > 1">
      <button
        class="banner-carousel__arrow banner-carousel__arrow--previous"
        type="button"
        aria-label="Banner anterior"
        @click.stop.prevent="move(-1)"
      >
        <q-icon name="chevron_left" />
      </button>
      <button
        class="banner-carousel__arrow banner-carousel__arrow--next"
        type="button"
        aria-label="Banner siguiente"
        @click.stop.prevent="move(1)"
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
          @click.stop.prevent="selectSlide(position)"
        >
          <span :class="{ active: index === position }" />
        </button>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useQuasar } from 'quasar'

const props = defineProps({
  banners: { type: Array, required: true },
  strip: Boolean,
  label: { type: String, default: 'Promociones principales' },
})
const quasar = useQuasar()
const index = ref(0)
const failedImages = reactive(new Set())
const mobileRatio = ref(null)
const viewport = ref(null)
const position = ref(1)
const animate = ref(true)
const dragging = ref(false)
const dragOffset = ref(0)
let pointerId = null
let startX = 0
let startY = 0
let blockClick = false
let settling = false
let settleTimer
let frame
function finishTransition() {
  window.clearTimeout(settleTimer)
  if (position.value === 0 || position.value === slides.value.length + 1) {
    animate.value = false
    position.value = index.value + 1
    frame = window.requestAnimationFrame(() => {
      frame = window.requestAnimationFrame(() => {
        animate.value = true
      })
    })
  }
  settling = false
}
function startDrag(event) {
  if (!event.isPrimary || event.button !== 0 || slides.value.length < 2 || settling) return
  pointerId = event.pointerId
  startX = event.clientX
  startY = event.clientY
  blockClick = false
  window.clearInterval(autoplayTimer)
}
function drag(event) {
  if (event.pointerId !== pointerId) return
  const dx = event.clientX - startX
  const dy = event.clientY - startY
  if (!dragging.value && Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 8) {
    pointerId = null
    restartAutoplay()
    return
  }
  if (!dragging.value && Math.abs(dx) > 8) {
    dragging.value = true
    blockClick = true
    viewport.value.setPointerCapture(event.pointerId)
  }
  if (dragging.value) dragOffset.value = dx
}
function releasePointer(event) {
  if (viewport.value?.hasPointerCapture(event.pointerId))
    viewport.value.releasePointerCapture(event.pointerId)
  pointerId = null
}
function endDrag(event) {
  if (event.pointerId !== pointerId) return
  const offset = dragOffset.value
  const wasDragging = dragging.value
  dragging.value = false
  dragOffset.value = 0
  releasePointer(event)
  if (wasDragging && Math.abs(offset) > Math.min(80, viewport.value.clientWidth * 0.18))
    move(offset < 0 ? 1 : -1)
  else restartAutoplay()
}
function cancelDrag(event) {
  if (event.pointerId !== pointerId) return
  dragging.value = false
  dragOffset.value = 0
  releasePointer(event)
  restartAutoplay()
}
function preventDragClick(event) {
  if (!blockClick) return
  event.preventDefault()
  event.stopPropagation()
  blockClick = false
}
let autoplayTimer
function rememberMobileRatio(event) {
  const image = event.target
  if (quasar.screen.width < 600 && !mobileRatio.value && image.naturalHeight) {
    mobileRatio.value = `${image.naturalWidth} / ${image.naturalHeight}`
  }
}
function restartAutoplay() {
  window.clearInterval(autoplayTimer)
  if (quasar.screen.width >= 600 || props.strip || slides.value.length < 2) return
  autoplayTimer = window.setInterval(() => {
    if (!document.hidden && !dragging.value) move(1)
  }, 5000)
}
function selectSlide(position) {
  if (settling || dragging.value) return
  index.value = position
  animate.value = true
  setPosition(position + 1)
  restartAutoplay()
}
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
const trackSlides = computed(() => {
  const list = slides.value
  return list.length > 1 ? [list[list.length - 1], ...list, list[0]] : list
})
watch(
  slides,
  () => {
    if (index.value >= slides.value.length) index.value = 0
    position.value = slides.value.length > 1 ? index.value + 1 : 0
    settling = false
    dragging.value = false
    dragOffset.value = 0
  },
  { immediate: true },
)
function setPosition(value) {
  if (position.value === value) return
  settling = true
  position.value = value
  window.clearTimeout(settleTimer)
  settleTimer = window.setTimeout(finishTransition, 550)
}
function move(direction) {
  if (slides.value.length < 2 || settling || dragging.value) return
  animate.value = true
  index.value = (index.value + direction + slides.value.length) % slides.value.length
  setPosition(position.value + direction)
  restartAutoplay()
}
onMounted(() => {
  watch(
    [slides, () => quasar.screen.width < 600],
    () => {
      restartAutoplay()
      // Cache upcoming images so switching slides does not show an empty frame.
      slides.value.forEach((slide) => {
        const image = new Image()
        image.src = slide.image
      })
    },
    { immediate: true },
  )
})
onBeforeUnmount(() => {
  window.clearInterval(autoplayTimer)
  window.clearTimeout(settleTimer)
  window.cancelAnimationFrame(frame)
})
</script>

<style scoped lang="scss">
.banner-carousel {
  position: relative;
  background: var(--store-paper);
}
.banner-carousel__viewport {
  width: 100%;
  height: 100%;
  overflow: hidden;
  touch-action: pan-y pinch-zoom;
  cursor: grab;
  user-select: none;
}
.banner-carousel__viewport.is-dragging {
  cursor: grabbing;
}
.banner-carousel__track {
  display: flex;
  width: 100%;
  height: 100%;
  transition: transform 480ms cubic-bezier(0.22, 0.61, 0.36, 1);
}
.banner-carousel__track.is-instant {
  transition: none;
}
.banner-carousel__slide {
  flex: 0 0 100%;
  min-width: 0;
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
  background: #f8c6d4;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.banner-carousel__dots .active {
  border-color: #f43f6f;
  background: #f43f6f;
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
    aspect-ratio: var(--mobile-banner-ratio, 16 / 9);
    border-radius: 16px;

    .banner-carousel__slide,
    .banner-carousel__slide img {
      height: 100%;
    }

    .banner-carousel__slide img {
      object-fit: contain;
    }
  }
  .banner-carousel__dots {
    flex-wrap: nowrap;
    max-width: 70%;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .banner-carousel__dots button {
    flex-shrink: 0;
    padding: 6px 3px;
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
