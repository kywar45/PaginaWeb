<template>
  <q-menu ref="menu" :model-value="active" :target="anchorTarget" no-parent-event no-focus no-refocus fit anchor="bottom left" self="top left" :offset="[0, 6]" :transition-duration="100" class="store-search-menu" @before-show="trackAnchor" @hide="stopTrackingAnchor" @update:model-value="!$event && $emit('close')">
    <div class="search-suggestions" @mousedown.prevent>
      <div v-if="!term && history.length" class="search-suggestions__heading"><span>Búsquedas recientes</span><button type="button" @click="clear">Borrar historial</button></div>
      <p v-else-if="!term" class="search-suggestions__message">Tus búsquedas recientes aparecerán aquí.</p>
      <p v-else-if="term.length < 2" class="search-suggestions__message">Escribe al menos 2 caracteres para ver sugerencias.</p>
      <LoadingSkeleton v-else-if="loading" variant="list" :count="3" label="Buscando sugerencias..." class="search-suggestions__loading" />
      <div v-else-if="error" class="search-suggestions__message" role="alert"><span>No pudimos cargar las sugerencias.</span><button type="button" @click="fetchSuggestions">Reintentar</button></div>
      <p v-else-if="!suggestions.length" class="search-suggestions__message">Sin sugerencias para esta búsqueda. Puedes buscar con Enter.</p>
      <div :id="listId" role="listbox" :aria-label="term ? 'Sugerencias de búsqueda' : 'Búsquedas recientes'">
        <div v-for="(option, index) in options" :key="option" class="search-suggestions__row">
          <button :id="`${listId}-${index}`" type="button" role="option" :aria-selected="index === selectedIndex" class="search-suggestions__option" :class="{ 'is-selected': index === selectedIndex }" @mouseenter="selectedIndex = index" @click="select(option)">
            <q-icon :name="term ? 'search' : 'history'" aria-hidden="true" /><span>{{ option }}</span><q-icon v-if="term" name="north_west" class="search-suggestions__hint" aria-hidden="true" />
          </button>
          <button v-if="!term" type="button" class="search-suggestions__remove" :aria-label="`Quitar ${option} del historial`" @click="remove(option)"><q-icon name="close" aria-hidden="true" /></button>
        </div>
      </div>
      <button v-if="term.length >= 2" type="button" class="search-suggestions__all" @click="select(term)">Buscar «{{ term }}»<q-icon name="arrow_forward" aria-hidden="true" /></button>
    </div>
  </q-menu>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { getProducts } from 'src/services/productApi'
import { useSearchHistory } from 'src/services/searchHistory'
import { cleanSearchHistory, normalizeSearch } from 'src/utils/searchHistory'

const props = defineProps({ query: { type: String, default: '' }, active: Boolean, listId: { type: String, required: true }, anchorTarget: { type: String, required: true } })
const emit = defineEmits(['search', 'close', 'open'])
const { history, refresh, remove, clear } = useSearchHistory()
const menu = ref(null)
const term = computed(() => normalizeSearch(props.query))
const suggestions = ref([])
const loading = ref(false)
const error = ref(false)
const selectedIndex = ref(-1)
const options = computed(() => !term.value ? history.value : loading.value || error.value ? [] : suggestions.value)
const activeDescendant = computed(() => props.active && selectedIndex.value >= 0 ? `${props.listId}-${selectedIndex.value}` : undefined)
let request
let timer
let anchorFrame

function stopTrackingAnchor() {
  cancelAnimationFrame(anchorFrame)
  anchorFrame = undefined
}
function trackAnchor() {
  stopTrackingAnchor()
  const anchor = document.querySelector(props.anchorTarget)
  let previousBounds = ''
  function follow() {
    if (!props.active) return
    const bounds = anchor?.getBoundingClientRect()
    const viewport = window.visualViewport
    const top = viewport?.offsetTop || 0
    const left = viewport?.offsetLeft || 0
    const bottom = top + (viewport?.height || window.innerHeight)
    const right = left + (viewport?.width || window.innerWidth)
    // QMenu clamps itself to the viewport even when its header has scrolled away.
    // Close before that happens, and follow the header's animated position.
    if (!anchor?.isConnected || !bounds?.width || !bounds?.height || bounds.top < top || bounds.bottom > bottom || bounds.right <= left || bounds.left >= right) {
      emit('close')
      return
    }
    const currentBounds = `${bounds.top},${bounds.left},${bounds.width},${bounds.height}`
    if (currentBounds !== previousBounds) {
      previousBounds = currentBounds
      menu.value?.updatePosition()
    }
    anchorFrame = requestAnimationFrame(follow)
  }
  anchorFrame = requestAnimationFrame(follow)
}

async function fetchSuggestions() {
  clearTimeout(timer)
  request?.abort()
  if (!props.active || term.value.length < 2) return
  const query = term.value
  const current = new AbortController()
  request = current
  suggestions.value = []
  selectedIndex.value = -1
  loading.value = true
  error.value = false
  try {
    const result = await getProducts({ q: query, limit: 6, signal: current.signal })
    if (!current.signal.aborted && term.value === query) {
      suggestions.value = cleanSearchHistory(result.productos.map((product) => product.nombre))
    }
  } catch {
    if (!current.signal.aborted) error.value = true
  } finally {
    if (request === current) loading.value = false
  }
}
watch([() => props.active, term], ([active]) => {
  clearTimeout(timer)
  request?.abort()
  request = null
  suggestions.value = []
  selectedIndex.value = -1
  error.value = false
  loading.value = active && term.value.length >= 2
  if (!active) { stopTrackingAnchor(); return }
  if (!term.value) refresh()
  else if (term.value.length >= 2) timer = setTimeout(fetchSuggestions, 300)
}, { immediate: true, flush: 'sync' })
watch(options, () => { selectedIndex.value = -1 })

function select(value) {
  emit('search', value)
  emit('close')
}
function handleKeydown(event) {
  if (event.isComposing) return true
  if (event.key === 'Escape') { emit('close'); event.preventDefault(); return true }
  if (event.key === 'Tab') { emit('close'); return true }
  if (event.key === 'Delete' && !term.value && selectedIndex.value >= 0) {
    remove(options.value[selectedIndex.value])
    event.preventDefault()
    return true
  }
  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault()
    if (!props.active) emit('open')
    if (options.value.length) {
      const count = options.value.length
      selectedIndex.value = event.key === 'ArrowDown' ? (selectedIndex.value + 1) % count : (selectedIndex.value < 0 ? count - 1 : (selectedIndex.value - 1 + count) % count)
      nextTick(() => document.getElementById(activeDescendant.value)?.scrollIntoView({ block: 'nearest' }))
    }
    return true
  }
  if (event.key === 'Enter' && props.active && selectedIndex.value >= 0) {
    event.preventDefault()
    select(options.value[selectedIndex.value])
    return true
  }
  return false
}
defineExpose({ handleKeydown, activeDescendant })
onBeforeUnmount(() => { clearTimeout(timer); request?.abort(); stopTrackingAnchor() })
</script>

<style scoped>
.store-search-menu { border: 1px solid #eadfe4; border-radius: 12px; box-shadow: 0 12px 30px #54314020; max-height: min(65vh, 460px); }
.search-suggestions { min-width: 0; padding: 6px 0; background: #fff; color: #44343d; }
.search-suggestions button { font: inherit; cursor: pointer; }
.search-suggestions__heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 16px; color: #97838e; font-size: 11px; }
.search-suggestions__heading button, .search-suggestions__message button { padding: 4px 0; border: 0; background: transparent; color: #c52555; font-size: 11px; }
.search-suggestions__row { display: flex; align-items: stretch; }
.search-suggestions__option { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; min-height: 44px; padding: 10px 16px; border: 0; background: #fff; color: #44343d; text-align: left; font-size: 13px; }
.search-suggestions__option > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.search-suggestions__option > .q-icon { flex-shrink: 0; color: #ad9da5; font-size: 18px; }
.search-suggestions__option .search-suggestions__hint { margin-left: auto; font-size: 15px; }
.search-suggestions__option:hover, .search-suggestions__option.is-selected { background: #f7f5f6; }
.search-suggestions__remove { width: 40px; border: 0; background: #fff; color: #ad9da5; }
.search-suggestions__remove:hover { color: #c52555; }
.search-suggestions__all { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; padding: 13px 16px; border: 0; border-top: 1px solid #f2e9ee; background: #fff; color: #c52555; font-size: 12px; text-align: left; overflow-wrap: anywhere; }
.search-suggestions__message { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; margin: 0; padding: 16px; color: #88717d; font-size: 12px; }
.search-suggestions__loading { margin: 0; padding: 4px 12px; }
.search-suggestions__loading :deep(.loading-skeleton__row) { padding: 6px 0; border: 0; }
.search-suggestions__loading :deep(.loading-skeleton__thumb), .search-suggestions__loading :deep(.loading-skeleton__lines > :not(:first-child)) { display: none; }
.search-suggestions button:focus-visible { outline: 2px solid #df2f61; outline-offset: -3px; }
@media (max-width: 599px) { .search-suggestions__option { gap: 8px; padding: 10px 12px; font-size: 12px; } .search-suggestions__heading { padding-inline: 12px; } }
</style>
