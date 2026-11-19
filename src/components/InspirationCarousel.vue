<template>
  <section id="inspirate" class="inspiration-section" aria-labelledby="inspiration-title">
    <div class="inspiration-heading"><h2 id="inspiration-title">INSPÍRATE</h2></div>
    <LoadingSkeleton v-if="loading" variant="videos" :count="5" label="Cargando videos..." />
    <div v-else-if="loadError" class="inspiration-status" role="alert">
      <p>No pudimos cargar los videos.</p>
      <q-btn flat no-caps color="primary" label="Reintentar" @click="loadVideos" />
    </div>
    <p v-else-if="!videos.length" class="inspiration-status">
      Pronto tendremos más ideas para inspirarte.
    </p>
    <div v-else class="inspiration-carousel-shell">
      <q-btn
        unelevated
        round
        icon="chevron_left"
        class="inspiration-carousel-arrow inspiration-carousel-arrow--previous"
        aria-label="Videos anteriores"
        @click="scroll(-1)"
      />
      <div ref="rail" class="inspiration-rail" tabindex="0" aria-label="Videos para inspirarte">
        <article v-for="video in videos" :key="video.id" class="inspiration-card">
          <div class="inspiration-card__player">
            <template v-if="activeId === video.id">
              <video
                :ref="setPlayer"
                :src="video.url"
                :poster="video.portada_url"
                :aria-label="video.titulo || 'Video para inspirarte'"
                controls
                playsinline
                preload="none"
                @loadeddata="buffering = false"
                @playing="buffering = false"
                @waiting="buffering = true"
                @error="handlePlaybackError"
              />
              <LoadingSkeleton
                v-if="buffering && !playbackError"
                variant="videos"
                :count="1"
                label="Cargando video..."
                class="inspiration-card__buffering"
              />
              <div v-if="playbackError" class="inspiration-card__error" role="alert">
                <q-icon name="videocam_off" size="28px" />
                <p>No pudimos reproducir este video.</p>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  label="Reintentar"
                  @click="activate(video)"
                />
              </div>
            </template>
            <button
              v-else
              type="button"
              class="inspiration-card__cover"
              :aria-label="`Reproducir ${video.titulo || 'video'}`"
              @click="activate(video)"
            >
              <img
                v-if="!failedCovers.has(video.id)"
                v-image-skeleton
                :src="video.portada_url"
                :alt="video.titulo || 'Miniatura del video'"
                loading="lazy"
                @error="failedCovers.add(video.id)"
              />
              <span v-else class="inspiration-card__cover-fallback">Video disponible</span>
              <span class="inspiration-card__play" aria-hidden="true"
                ><q-icon name="play_arrow"
              /></span>
            </button>
          </div>
          <div class="inspiration-card__title">
            {{ video.titulo || 'Descubre más ideas' }}
            <RouterLink
              class="inspiration-card__products"
              :to="{ name: 'video-productos', params: { videoId: video.id } }"
              :aria-label="`Ver productos de ${video.titulo || 'este video'}`"
            >
              Ver productos del video
            </RouterLink>
          </div>
        </article>
      </div>
      <q-btn
        unelevated
        round
        icon="chevron_right"
        class="inspiration-carousel-arrow inspiration-carousel-arrow--next"
        aria-label="Videos siguientes"
        @click="scroll(1)"
      />
    </div>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { isInspirationMedia } from 'src/utils/inspirationMedia'

// Retain the existing API/table names so installations need no route or SQL migration.
const base = (
  import.meta.env.VITE_STORE_API_BASE_URL || 'https://www.importadorasupernova.com/StoreApi/v1'
).replace(/\/+$/, '')
const videos = ref([])
const loading = ref(true)
const loadError = ref(false)
const rail = ref(null)
const activeId = ref(null)
const buffering = ref(false)
const playbackError = ref(false)
const failedCovers = reactive(new Set())
let player = null
let request = null
let disposed = false
let playVersion = 0
function handlePlaybackError() {
  playbackError.value = true
  buffering.value = false
}
function setPlayer(element) {
  player = element
}
async function loadVideos() {
  request?.abort()
  const current = new AbortController()
  request = current
  loading.value = true
  loadError.value = false
  const timeout = setTimeout(() => current.abort(), 15000)
  try {
    const response = await fetch(`${base}/tiktok-videos`, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
      signal: current.signal,
    })
    const payload = await response.json()
    if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.videos))
      throw new Error('Invalid video response')
    if (disposed || request !== current) return
    videos.value = payload.data.videos.filter(
      (video) =>
        Number.isSafeInteger(video.id) &&
        video.id > 0 &&
        isInspirationMedia(video.url, 'video') &&
        isInspirationMedia(video.portada_url, 'image'),
    )
  } catch {
    if (!disposed && request === current) loadError.value = true
  } finally {
    clearTimeout(timeout)
    if (!disposed && request === current) loading.value = false
  }
}
async function activate(video) {
  const version = ++playVersion
  player?.pause()
  activeId.value = null
  await nextTick()
  if (disposed || version !== playVersion) return
  activeId.value = video.id
  playbackError.value = false
  buffering.value = true
  await nextTick()
  if (disposed || version !== playVersion || !player) return
  try {
    await player.play()
  } catch (error) {
    if (disposed || version !== playVersion) return
    buffering.value = false
    // Some mobile browsers require another tap on the native controls.
    if (error.name !== 'NotAllowedError' && error.name !== 'AbortError') playbackError.value = true
  }
}
function scroll(direction) {
  rail.value?.scrollBy({
    left: direction * rail.value.clientWidth * 0.8,
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
  })
}
onMounted(loadVideos)
onBeforeUnmount(() => {
  disposed = true
  playVersion++
  request?.abort()
  player?.pause()
})
</script>

<style scoped lang="scss">
.inspiration-status {
  padding: 20px 0;
  color: #806675;
  font-size: 16px;
}
.inspiration-card__cover {
  appearance: none;
  border-radius: 0;
  box-shadow: none;
  position: relative;
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  overflow: hidden;
  background: #fff0f5;
  cursor: pointer;
  color: #d62d61;
}
.inspiration-card__cover img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.inspiration-card__cover:focus-visible {
  outline: 3px solid #d62d61;
  outline-offset: -3px;
}
.inspiration-card__play {
  position: absolute;
  inset: 50% auto auto 50%;
  display: grid;
  place-items: center;
  width: 54px;
  height: 54px;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  background: #fff;
  color: #e63066;
  box-shadow: 0 4px 18px #0003;
  font-size: 36px;
}
.inspiration-card__cover-fallback {
  display: block;
  padding: 24px 10px;
  font-size: 14px;
}
.inspiration-card__buffering {
  position: absolute;
  inset: 0;
  margin: 0;
  pointer-events: none;
  :deep(.loading-skeleton__videos) {
    grid-auto-columns: 100%;
    height: 100%;
  }
  :deep(.loading-skeleton__video) {
    height: 100%;
  }
}
.inspiration-card__error {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #fff5f8;
  color: #806675;
  text-align: center;
  font-size: 14px;
  p {
    margin: 0;
  }
}

.inspiration-section {
  margin: 22px 0 34px;
  padding: 18px 0 22px;
  border-top: 1px solid #f1e9ec;
}
.inspiration-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.inspiration-heading h2 {
  margin: 0;
  color: #21191b;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.2;
}
.inspiration-carousel-shell {
  position: relative;
}
.inspiration-carousel-arrow {
  --arrow-size: 58px;
  position: absolute;
  z-index: 5;
  top: 42%;
  width: var(--arrow-size);
  min-width: var(--arrow-size);
  max-width: var(--arrow-size);
  height: var(--arrow-size);
  min-height: var(--arrow-size);
  max-height: var(--arrow-size);
  padding: 0;
  border-radius: 50%;
  flex-shrink: 0;
  transform: translateY(-50%);
  background: #fff !important;
  color: #f06b8e !important;
  box-shadow: 0 8px 24px rgba(93, 48, 62, 0.13) !important;
}
.inspiration-carousel-arrow :deep(.q-icon) {
  font-size: 32px;
}
.inspiration-carousel-arrow--previous {
  left: -29px;
}
.inspiration-carousel-arrow--next {
  right: -29px;
}
.inspiration-carousel-arrow:hover {
  color: #f43f6f !important;
}
.inspiration-rail {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(210px, 240px);
  gap: 10px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  padding: 2px 1px 8px;
  scrollbar-width: thin;
  scrollbar-color: #e8c7d2 transparent;
}
.inspiration-rail::-webkit-scrollbar {
  height: 4px;
}
.inspiration-rail::-webkit-scrollbar-thumb {
  border-radius: 8px;
  background: #e8c7d2;
}
.inspiration-card {
  position: relative;
  min-width: 0;
  overflow: hidden;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  background: transparent;
  scroll-snap-align: start;
}
.inspiration-card__player {
  position: relative;
  aspect-ratio: 9 / 16;
  overflow: hidden;
  border: 0;
  border-radius: 0;
  background: transparent;
}
.inspiration-card__player video {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  object-fit: contain;
  background: transparent;
}
.inspiration-card__title {
  min-height: 48px;
  padding: 10px 12px;
  border: 1px solid #f0e7ea;
  border-radius: 0 0 8px 8px;
  background: transparent;
  color: #302428;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.3;
}
.inspiration-card__products {
  display: block;
  margin-top: 10px;
  padding: 4px 0;
  color: #d62d61;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.5;
  text-decoration: underline;
  text-underline-offset: 3px;
}
@media (max-width: 599px) {
  .inspiration-carousel-arrow {
    --arrow-size: 34px;
  }
  .inspiration-carousel-arrow :deep(.q-icon) {
    font-size: 22px;
  }
  .inspiration-carousel-arrow--previous {
    left: -10px;
  }
  .inspiration-carousel-arrow--next {
    right: -10px;
  }
  .inspiration-section {
    margin: 16px 0 24px;
    padding: 16px 0;
  }
  .inspiration-heading h2 {
    font-size: 14px;
  }
  .inspiration-rail {
    grid-auto-columns: min(44vw, 170px);
    gap: 8px;
  }
  .inspiration-card__title {
    min-height: 0;
    padding: 10px 8px;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    overflow-wrap: anywhere;
  }
  .inspiration-card {
    border: 0;
    border-radius: 0;
    background: transparent;
  }
  .inspiration-card__player {
    overflow: hidden;
    border-radius: 0;
    background: transparent;
  }
  .inspiration-card__player video {
    background: transparent;
    border-radius: 0;
    box-shadow: none;
  }
  .inspiration-card__cover {
    appearance: none;
    border-radius: 0;
    box-shadow: none;
  }
}
</style>
