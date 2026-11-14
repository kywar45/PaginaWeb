export const SEARCH_HISTORY_KEY = 'supernova.search-history.v1'
export const SEARCH_HISTORY_LIMIT = 6

export function normalizeSearch(value) {
  return typeof value === 'string' ? value.trim().replace(/\s+/g, ' ').slice(0, 80) : ''
}

export function cleanSearchHistory(values) {
  if (!Array.isArray(values)) return []
  const seen = new Set()
  return values.map(normalizeSearch).filter((term) => {
    const key = term.toLocaleLowerCase('es-MX')
    if (!term || seen.has(key)) return false
    seen.add(key)
    return true
  }).slice(0, SEARCH_HISTORY_LIMIT)
}

export function readSearchHistory(storage) {
  try { return cleanSearchHistory(JSON.parse(storage.getItem(SEARCH_HISTORY_KEY))) }
  catch { return [] }
}

export function addSearchHistory(values, term) {
  const query = normalizeSearch(term)
  return query ? cleanSearchHistory([query, ...values]) : cleanSearchHistory(values)
}
