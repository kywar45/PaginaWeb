import assert from 'node:assert/strict'
import {
  SEARCH_HISTORY_KEY, addSearchHistory, cleanSearchHistory, normalizeSearch, readSearchHistory,
} from '../../src/utils/searchHistory.js'
import { useSearchHistory } from '../../src/services/searchHistory.js'

assert.equal(normalizeSearch('  gel   para uñas  '), 'gel para uñas')
assert.equal(normalizeSearch({}), '')
assert.equal(normalizeSearch('a'.repeat(100)).length, 80)
assert.deepEqual(cleanSearchHistory(null), [])
assert.deepEqual(cleanSearchHistory(['  Gel ', null, 5, '', 'gel', 'Lámpara']), ['Gel', 'Lámpara'])
assert.deepEqual(addSearchHistory(['gel', 'Lámpara'], ' GEL '), ['GEL', 'Lámpara'])
assert.equal(cleanSearchHistory(Array.from({ length: 12 }, (_, i) => `Producto ${i}`)).length, 6)
assert.deepEqual(readSearchHistory({ getItem: () => '{broken' }), [])
assert.deepEqual(readSearchHistory({ getItem: () => '{"injected":"value"}' }), [])
assert.deepEqual(readSearchHistory({ getItem: () => { throw new Error('blocked') } }), [])

const stored = new Map()
globalThis.window = { localStorage: {
  getItem: (key) => stored.get(key) ?? null,
  setItem: (key, value) => stored.set(key, value),
} }
const desktop = useSearchHistory()
const mobile = useSearchHistory()
desktop.remember('Lámpara')
desktop.remember('Gel')
assert.deepEqual(mobile.history.value, ['Gel', 'Lámpara'], 'Shared between search inputs')
assert.deepEqual(JSON.parse(stored.get(SEARCH_HISTORY_KEY)), ['Gel', 'Lámpara'], 'Persists submitted searches')
mobile.remove('Gel')
assert.deepEqual(desktop.history.value, ['Lámpara'], 'Remove one recent search')
stored.set(SEARCH_HISTORY_KEY, '["Otra pestaña", "Lámpara"]')
desktop.refresh()
assert.deepEqual(desktop.history.value, ['Otra pestaña', 'Lámpara'], 'Refresh history on focus')
desktop.clear()
assert.deepEqual(JSON.parse(stored.get(SEARCH_HISTORY_KEY)), [], 'Clear persisted history')
Object.defineProperty(window, 'localStorage', { get() { throw new Error('blocked') } })
assert.doesNotThrow(() => mobile.remember('Gel'), 'Search still works without storage')
assert.deepEqual(mobile.history.value, ['Gel'])
delete globalThis.window
console.log('Search history tests passed: normalization, limit, persistence, removal and blocked storage.')
