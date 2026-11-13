import { computed, readonly, ref } from 'vue'

export const MAX_CART_QUANTITY = 9999

export function createCartStore(api) {
  const items = ref([])
  const total = ref(0)
  const savings = ref(0)
  const regularTotal = ref(null)
  const cartCount = ref(0)
  const loaded = ref(false)
  const pending = ref(0)
  const error = ref('')
  const notice = ref(null)
  const busy = computed(() => pending.value > 0)
  let queue = Promise.resolve()
  let version = 0
  let pendingLoad = null

  function resetCart() {
    version++
    items.value = []
    total.value = 0
    savings.value = 0
    regularTotal.value = null
    cartCount.value = 0
    loaded.value = false
    error.value = ''
    notice.value = null
    pendingLoad = null
  }

  function apply(snapshot, expectedVersion) {
    if (version !== expectedVersion) throw new Error('La sesión cambió. Revisa tu carrito.')
    items.value = snapshot.items
    total.value = snapshot.total
    savings.value = Number.isFinite(snapshot.ahorro_total) ? Math.max(0, snapshot.ahorro_total) : 0
    regularTotal.value = Number.isFinite(snapshot.total_sin_descuentos) ? snapshot.total_sin_descuentos : null
    cartCount.value = snapshot.cantidad_total
    loaded.value = true
    error.value = ''
    return snapshot
  }

  function enqueue(operation) {
    const expectedVersion = version
    pending.value++
    const result = queue.then(async () => {
      if (version !== expectedVersion) throw new Error('La sesión cambió. Revisa tu carrito.')
      try {
        return await operation(expectedVersion)
      } catch (cause) {
        if (version === expectedVersion) error.value = cause.message || 'No pudimos actualizar tu carrito.'
        throw cause
      }
    }).finally(() => pending.value--)
    queue = result.catch(() => {})
    return result
  }

  function loadCart() {
    if (pendingLoad) return pendingLoad
    const result = enqueue(async (expectedVersion) => apply(await api.load(), expectedVersion))
    pendingLoad = result
    const clear = () => { if (pendingLoad === result) pendingLoad = null }
    result.then(clear, clear)
    return result
  }

  function mutate(action, id, quantity) {
    if (!Number.isSafeInteger(id) || id < 1) return Promise.reject(new Error('Producto inválido.'))
    if (action !== 'eliminar' && (!Number.isSafeInteger(quantity) || quantity < 1 || quantity > MAX_CART_QUANTITY))
      return Promise.reject(new Error('Ingresa entre 1 y 9999 piezas.'))
    return enqueue(async (expectedVersion) => {
      // El primer GET establece la cookie del visitante antes de intentar guardar.
      if (!loaded.value) apply(await api.load(), expectedVersion)
      return apply(await api.update(action, id, quantity), expectedVersion)
    })
  }

  function setMergeNotice(info) {
    if (info?.pendiente) {
      notice.value = { retry: true, message: info.mensaje || 'No pudimos combinar los carritos. Tus productos de visitante siguen guardados.' }
    } else if (info?.fusionado) {
      notice.value = {
        retry: false,
        message: 'Tus productos ya están en tu cuenta.' +
          (info.no_disponibles > 0 ? ' Hay productos no disponibles; revísalos en el carrito.' : ''),
      }
    } else notice.value = null
  }

  function mergeCart() {
    return enqueue(async (expectedVersion) => {
      try {
        const snapshot = apply(await api.update('fusionar'), expectedVersion)
        setMergeNotice(snapshot.fusion)
        return snapshot
      } catch (cause) {
        if (version === expectedVersion) setMergeNotice({ pendiente: true, mensaje: cause.message })
        throw cause
      }
    })
  }

  return {
    items: readonly(items), total: readonly(total), savings: readonly(savings), regularTotal: readonly(regularTotal), cartCount: readonly(cartCount),
    loaded: readonly(loaded), busy, error: readonly(error), resetCart, loadCart,
    notice: readonly(notice), setMergeNotice, mergeCart, clearNotice: () => { notice.value = null },
    async addProduct(product, quantity) {
      const snapshot = await mutate('agregar', Number(product.id_producto), quantity)
      return snapshot.items.find((item) => item.id_producto === Number(product.id_producto))
    },
    setQuantity: (id, quantity) => mutate('cantidad', id, quantity),
    removeProduct: (id) => mutate('eliminar', id),
  }
}
