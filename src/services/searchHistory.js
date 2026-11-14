import { ref } from 'vue'
import { SEARCH_HISTORY_KEY, addSearchHistory, readSearchHistory } from '../utils/searchHistory.js'

const history = ref([])

function refresh() {
  try { history.value = readSearchHistory(window.localStorage) }
  catch { /* El buscador funciona también si el navegador bloquea el almacenamiento. */ }
}
function save() {
  try { window.localStorage.setItem(SEARCH_HISTORY_KEY, JSON.stringify(history.value)) }
  catch { /* Conservar el historial en memoria durante esta visita. */ }
}
function remember(term) {
  refresh()
  history.value = addSearchHistory(history.value, term)
  save()
}
function remove(term) {
  history.value = history.value.filter((item) => item !== term)
  save()
}
function clear() {
  history.value = []
  save()
}

export function useSearchHistory() { return { history, refresh, remember, remove, clear } }
