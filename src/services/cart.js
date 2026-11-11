import { computed, readonly, ref } from 'vue'

const STORAGE_KEY = 'supernova-cart-v1'
export const MAX_CART_QUANTITY = 9999
function validQuantity(value) {
  return Number.isSafeInteger(value) && value >= 1 && value <= MAX_CART_QUANTITY
}
function loadCart() {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(saved)
      ? saved.filter(
          (item) =>
            Number.isSafeInteger(item.id_producto) &&
            item.id_producto > 0 &&
            validQuantity(item.quantity) &&
            Number.isFinite(item.precio_final) &&
            item.precio_final >= 0,
        )
      : []
  } catch {
    return []
  }
}
const items = ref(typeof window === 'undefined' ? [] : loadCart())
const cartCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
const total = computed(
  () =>
    items.value.reduce(
      (sum, item) => sum + Math.round(item.precio_final * 100) * item.quantity,
      0,
    ) / 100,
)
function persist() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value))
  } catch {
    /* Keep cart usable in memory. */
  }
}
export function useCart() {
  function addProduct(product, quantity) {
    if (Number(product.estatus) !== 1) throw new Error('Este producto no está disponible.')
    if (!validQuantity(quantity)) throw new Error('Ingresa una cantidad válida de piezas.')
    const id = Number(product.id_producto)
    const price = Number(product.precio_final)
    if (!Number.isSafeInteger(id) || id <= 0 || !Number.isFinite(price) || price < 0)
      throw new Error('No se pudo agregar este producto.')
    const existing = items.value.find((item) => item.id_producto === id)
    const next = quantity + (existing?.quantity || 0)
    if (!validQuantity(next)) throw new Error('La cantidad excede el límite del carrito.')
    const item = {
      id_producto: id,
      nombre: product.nombre,
      codigo: product.codigo,
      imagen: product.imagen,
      precio_final: price,
      quantity: next,
    }
    items.value = existing
      ? items.value.map((row) => (row.id_producto === id ? item : row))
      : [...items.value, item]
    persist()
  }
  function setQuantity(id, quantity) {
    if (!validQuantity(quantity)) return
    items.value = items.value.map((item) =>
      item.id_producto === id ? { ...item, quantity } : item,
    )
    persist()
  }
  function removeProduct(id) {
    items.value = items.value.filter((item) => item.id_producto !== id)
    persist()
  }
  return { items: readonly(items), cartCount, total, addProduct, setQuantity, removeProduct }
}
