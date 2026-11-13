const states = new WeakMap()
const parents = new WeakMap()

function attachParent(parent) {
  const existing = parents.get(parent)
  if (existing) { existing.count++; return }
  const original = parent.style.position
  const changed = getComputedStyle(parent).position === 'static'
  if (changed) parent.style.position = 'relative'
  parents.set(parent, { count: 1, changed, original })
}

function detachParent(parent) {
  const state = parents.get(parent)
  if (!state || --state.count > 0) return
  if (state.changed && parent.style.position === 'relative') parent.style.position = state.original
  parents.delete(parent)
}

function mount(image) {
  const parent = image.parentElement
  if (!parent) return
  const overlay = document.createElement('span')
  overlay.className = 'sn-image-skeleton'
  overlay.setAttribute('aria-hidden', 'true')
  const originalVisibility = image.style.visibility
  let source = ''
  let generation = 0
  let disposed = false
  let loading = false
  attachParent(parent)

  const signature = () => `${image.getAttribute('src') || ''}|${image.getAttribute('srcset') || ''}`
  function measure() {
    if (!loading) return
    Object.assign(overlay.style, {
      left: `${image.offsetLeft}px`, top: `${image.offsetTop}px`,
      width: `${image.offsetWidth}px`, height: `${image.offsetHeight}px`,
      borderRadius: getComputedStyle(image).borderRadius,
    })
  }
  function reveal() {
    loading = false
    image.style.visibility = originalVisibility
    overlay.remove()
  }
  async function loaded() {
    const version = generation
    if (image.decode) {
      try { await image.decode() } catch { /* El error nativo conserva el fallback de la pagina. */ }
    }
    if (!disposed && version === generation && signature() === source && image.complete) reveal()
  }
  function update() {
    if (disposed) return
    const next = signature()
    if (next === source) { measure(); return }
    source = next
    generation++
    if (!image.getAttribute('src') && !image.getAttribute('srcset')) { reveal(); return }
    loading = true
    image.style.visibility = 'hidden'
    parent.appendChild(overlay)
    measure()
    if (image.complete) {
      if (image.naturalWidth > 0) loaded()
      else reveal()
    }
  }
  function failed() {
    // Esperar a @error: si cambia src por el fallback, mantener su skeleton hasta cargarlo.
    queueMicrotask(() => {
      if (disposed) return
      if (signature() !== source) update()
      else reveal()
    })
  }
  const observer = new ResizeObserver(measure)
  observer.observe(image)
  const attributes = new MutationObserver(update)
  attributes.observe(image, { attributes: true, attributeFilter: ['src', 'srcset'] })
  image.addEventListener('load', loaded)
  image.addEventListener('error', failed)
  states.set(image, {
    update,
    cleanup() {
      disposed = true
      observer.disconnect()
      attributes.disconnect()
      image.removeEventListener('load', loaded)
      image.removeEventListener('error', failed)
      reveal()
      detachParent(parent)
    },
  })
  update()
}

export default {
  mounted: mount,
  updated(image) { states.get(image)?.update() },
  beforeUnmount(image) { states.get(image)?.cleanup(); states.delete(image) },
}
