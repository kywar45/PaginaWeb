import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'

const script = path => readFileSync(new URL(path, import.meta.url), 'utf8').match(/<script setup>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '')
const ref = value => ({ value })
const navigation = []
let user = null
let checks = 0
const cart = { items: ref([{ id_producto: 1, quantity: 2 }]), total: ref(100), busy: ref(false) }
const cartContext = vm.createContext({
  ref, onMounted: () => {}, onBeforeUnmount: () => {},
  useRouter: () => ({ push: async target => navigation.push(target) }),
  useAuthSession: () => ({ loadCurrentUser: async () => { checks++; return user } }),
  useCart: () => cart, publicAsset: value => value,
})
vm.runInContext(script('../../src/pages/CartPage.vue') + '\nglobalThis.api = { continueOrder, continueAsGuest, loginForCheckout, guestPrompt };', cartContext)
const api = cartContext.api
await Promise.all([api.continueOrder(), api.continueOrder()])
assert.equal(checks, 1, 'Repeated clicks check the session only once')
assert.equal(api.guestPrompt.value, true)
assert.equal(navigation.length, 0, 'Guest sees choice before leaving cart')
api.loginForCheckout()
assert.equal(navigation[0].path, '/login')
assert.equal(navigation[0].query.next, '/checkout')
api.continueAsGuest()
assert.equal(navigation[1], '/checkout')
assert.equal(cart.items.value[0].quantity, 2, 'Choice does not mutate or copy cart data')
user = { id: 7 }
await api.continueOrder()
assert.equal(navigation[2], '/checkout', 'Signed-in customer skips the prompt')
cart.total.value = 0
await api.continueOrder()
assert.equal(navigation.length, 3, 'Empty amount cannot advance')

let mounted
let response = { usuario: { id: 7 }, carrito_fusion: { fusionado: true } }
let failure = false
let notice
const route = { query: { next: '/checkout' } }
const loginContext = vm.createContext({
  ref, computed: getter => ({ get value() { return getter() } }),
  onMounted: callback => { mounted = callback }, publicAsset: value => value,
  useRoute: () => route,
  useRouter: () => ({ replace: async target => navigation.push(target) }),
  useCart: () => ({ setMergeNotice: value => { notice = value } }),
  useAuthSession: () => ({ loadCurrentUser: async () => user, setCurrentUser: value => { user = value } }),
  authApi: { login: async () => { if (failure) throw new Error('Login failed'); return response } },
})
vm.runInContext(script('../../src/pages/LoginPage.vue') + '\nglobalThis.api = { submitLogin, errorMessage };', loginContext)
await loginContext.api.submitLogin()
assert.equal(navigation.at(-1), '/checkout', 'Successful login returns to checkout after server merge')
assert.equal(notice.fusionado, true)
response.carrito_fusion = { pendiente: true }
await loginContext.api.submitLogin()
assert.equal(navigation.at(-1), '/carrito', 'Failed merge returns to cart to recover pending pieces')
route.query.next = 'https://external.example/'
await loginContext.api.submitLogin()
assert.equal(navigation.at(-1), '/', 'Untrusted redirect is ignored')
await mounted()
assert.equal(navigation.at(-1), '/panel', 'Normal signed-in login visit keeps previous behavior')
failure = true
const before = navigation.length
await loginContext.api.submitLogin()
assert.equal(navigation.length, before)
assert.equal(loginContext.api.errorMessage.value, 'Login failed', 'Failed login remains available for retry')
console.log('Cart checkout choice checks passed: guest options, session bypass, safe login return and merge failure recovery.')
