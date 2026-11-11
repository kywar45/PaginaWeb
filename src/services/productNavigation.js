import { useRouter } from 'vue-router'
import { productLocation } from 'src/utils/productRoutes'

export function useProductNavigation() {
  const router = useRouter()
  return (product) => router.push(productLocation(product))
}
