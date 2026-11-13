<template>
  <section v-if="videosLoading || videos.length" class="tiktok-section" aria-labelledby="tiktok-title">
    <div class="tiktok-heading">
      <h2 id="tiktok-title">INSPÍRATE</h2>
    </div>
    <div v-if="videosLoading" class="tiktok-rail" role="status" aria-label="Cargando videos..." aria-busy="true">
      <div v-for="item in 3" :key="item" class="tiktok-card" aria-hidden="true">
        <q-skeleton class="tiktok-card__player" /><q-skeleton height="44px" />
      </div>
    </div>
    <div v-else class="tiktok-carousel-shell">
      <q-btn
        unelevated
        round
        icon="chevron_left"
        class="tiktok-carousel-arrow tiktok-carousel-arrow--previous"
        aria-label="Videos anteriores"
        @click="scroll(-1)"
      />
      <div ref="rail" class="tiktok-rail" tabindex="0" aria-label="Videos de TikTok">
        <article
          v-for="video in videos"
          :key="video.id"
          class="tiktok-card"
          :data-video-id="video.id"
        >
          <div class="tiktok-card__player">
            <iframe
              v-if="playerLoads[video.id] !== undefined"
              :key="`${video.id}-${playerLoads[video.id]}`"
              :data-video-id="video.id"
              :src="playerUrl(video)"
              :title="video.titulo || 'Video de TikTok'"
              loading="lazy"
              allow="fullscreen; encrypted-media; picture-in-picture"
              allowfullscreen
              @load="readyPlayers[video.id] = true"
            />
            <q-skeleton v-if="!readyPlayers[video.id]" class="tiktok-card__skeleton" aria-label="Cargando video..." />
          </div>
          <div class="tiktok-card__title">
            {{ video.titulo || 'Video de TikTok' }}
          </div>
        </article>
      </div>
      <q-btn
        unelevated
        round
        icon="chevron_right"
        class="tiktok-carousel-arrow tiktok-carousel-arrow--next"
        aria-label="Videos siguientes"
        @click="scroll(1)"
      />
    </div>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const base = (
  import.meta.env.VITE_STORE_API_BASE_URL || 'https://www.importadorasupernova.com/StoreApi/v1'
).replace(/\/+$/, '')
const request = new AbortController()
const videos = ref([])
const videosLoading = ref(true)
const readyPlayers = ref({})
const rail = ref(null)
const playerLoads = ref({})
const loadQueue = []
const queuedIds = new Set()
let observer = null
let queueTimer = null
let disposed = false

function queuePlayer(id) {
  if (disposed || queuedIds.has(id)) return
  queuedIds.add(id)
  loadQueue.push(id)
  if (queueTimer === null) processQueue()
}

function processQueue() {
  if (disposed || !loadQueue.length) {
    queueTimer = null
    return
  }
  const id = loadQueue.shift()
  queuedIds.delete(id)
  playerLoads.value = { ...playerLoads.value, [id]: (playerLoads.value[id] ?? -1) + 1 }
  queueTimer = window.setTimeout(processQueue, 900)
}

onMounted(async () => {
  window.addEventListener('message', handlePlayerMessage)
  const timeout = setTimeout(() => request.abort(), 15000)
  try {
    const response = await fetch(`${base}/tiktok-videos`, {
      headers: { Accept: 'application/json' },
      signal: request.signal,
    })
    const payload = await response.json()
    if (!response.ok || payload?.success !== true || !Array.isArray(payload.data?.videos))
      throw new Error('Respuesta no valida de TikTok')
    if (disposed) return
    videos.value = payload.data.videos.filter(
      (video) =>
        /^\d{10,25}$/.test(String(video.video_id)) &&
        /^https:\/\/www\.tiktok\.com\/@[A-Za-z0-9._-]+\/video\/\d{10,25}\/?$/.test(video.url),
    )
    videosLoading.value = false
    await nextTick()
    if (disposed || !rail.value) return
    const cards = rail.value.querySelectorAll('.tiktok-card')
    if ('IntersectionObserver' in window) {
      observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return
            queuePlayer(entry.target.dataset.videoId)
            observer.unobserve(entry.target)
          })
        },
        { rootMargin: '100px' },
      )
      cards.forEach((card) => observer.observe(card))
    } else {
      cards.forEach((card) => queuePlayer(card.dataset.videoId))
    }
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar los videos.')
  } finally {
    clearTimeout(timeout)
    videosLoading.value = false
  }
})
onBeforeUnmount(() => {
  disposed = true
  request.abort()
  observer?.disconnect()
  if (queueTimer !== null) window.clearTimeout(queueTimer)
  window.removeEventListener('message', handlePlayerMessage)
})

function scroll(direction) {
  rail.value?.scrollBy({ left: direction * rail.value.clientWidth * 0.8, behavior: 'smooth' })
}
function playerUrl(video) {
  return `https://www.tiktok.com/player/v1/${video.video_id}?controls=1&autoplay=0`
}
function handlePlayerMessage(event) {
  if (event.origin !== 'https://www.tiktok.com' || !event.data?.['x-tiktok-player']) return
  const players = Array.from(rail.value?.querySelectorAll('iframe') || []).filter((player) =>
    player.src.startsWith('https://www.tiktok.com/'),
  )
  const sender = players.find((player) => player.contentWindow === event.source)
  if (!sender) return
  if (event.data.type !== 'onStateChange' || event.data.value !== 1) return
  players.forEach((player) => {
    if (player.contentWindow !== event.source) {
      player.contentWindow?.postMessage(
        { 'x-tiktok-player': true, type: 'pause' },
        'https://www.tiktok.com',
      )
    }
  })
}
</script>

<style scoped lang="scss">
.tiktok-section {
  margin: 22px 0 34px;
  padding: 18px 0 22px;
  border-top: 1px solid #f1e9ec;
}
.tiktok-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.tiktok-heading h2 {
  margin: 0;
  color: #21191b;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.2;
}
.tiktok-carousel-shell {
  position: relative;
}
.tiktok-carousel-arrow {
  position: absolute;
  z-index: 5;
  top: 42%;
  width: 58px;
  height: 58px;
  min-height: 58px;
  transform: translateY(-50%);
  background: #fff !important;
  color: #f06b8e !important;
  box-shadow: 0 8px 24px rgba(93, 48, 62, 0.13) !important;
}
.tiktok-carousel-arrow :deep(.q-icon) {
  font-size: 32px;
}
.tiktok-carousel-arrow--previous {
  left: -29px;
}
.tiktok-carousel-arrow--next {
  right: -29px;
}
.tiktok-carousel-arrow:hover {
  color: #f43f6f !important;
}
.tiktok-rail {
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
.tiktok-rail::-webkit-scrollbar {
  height: 4px;
}
.tiktok-rail::-webkit-scrollbar-thumb {
  border-radius: 8px;
  background: #e8c7d2;
}
.tiktok-card {
  position: relative;
  min-width: 0;
  overflow: hidden;
  border: 1px solid #f0e7ea;
  border-radius: 9px;
  background: #fff;
  scroll-snap-align: start;
}
.tiktok-card__player {
  position: relative;
  aspect-ratio: 9 / 16;
}
.tiktok-card__skeleton {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border-radius: 0;
}
.tiktok-card__player iframe {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
}
.tiktok-card__loading {
  display: flex;
  width: 100%;
  height: 100%;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: #fff8fa;
  color: #8c6c77;
  font-size: 12px;
}
.tiktok-card__title {
  min-height: 48px;
  padding: 10px 12px;
  background: #fff;
  color: #302428;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.3;
}
.tiktok-card__link {
  position: absolute;
  z-index: 1;
  top: 8px;
  right: 8px;
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #ed2470;
  color: #fff;
  font-size: 15px;
  box-shadow: 0 2px 7px #0003;
}
.tiktok-card__link:focus-visible {
  outline: 2px solid #ed2470;
  outline-offset: 2px;
}
@media (max-width: 599px) {
  .tiktok-carousel-arrow {
    width: 34px;
    height: 34px;
    min-height: 34px;
  }
  .tiktok-carousel-arrow :deep(.q-icon) {
    font-size: 22px;
  }
  .tiktok-carousel-arrow--previous {
    left: -10px;
  }
  .tiktok-carousel-arrow--next {
    right: -10px;
  }
  .tiktok-section {
    margin: 16px 0 24px;
    padding: 16px 0;
  }
  .tiktok-heading h2 {
    font-size: 14px;
  }
  .tiktok-rail {
    grid-auto-columns: min(44vw, 170px);
    gap: 8px;
  }
  .tiktok-card__title {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    min-height: 44px;
    max-height: 44px;
    padding: 7px 8px;
    font-size: 11px;
    line-height: 15px;
  }
}
</style>
