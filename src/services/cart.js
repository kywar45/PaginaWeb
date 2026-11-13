import cartApi from './cartApi'
import { createCartStore } from './cartStore'

export { MAX_CART_QUANTITY } from './cartStore'
const cart = createCartStore(cartApi)
export function useCart() {
  return cart
}
