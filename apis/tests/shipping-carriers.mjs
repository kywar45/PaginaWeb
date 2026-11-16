import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { DatabaseSync } from 'node:sqlite'
import vm from 'node:vm'

const repository = readFileSync(new URL('../src/Repositories/ShippingCarrierRepository.php', import.meta.url), 'utf8')
const query = repository.match(/query\('([\s\S]*?)'\)/)[1]
const db = new DatabaseSync(':memory:')
db.exec(`CREATE TABLE shipping_carriers (id INTEGER PRIMARY KEY, nombre TEXT, envio INTEGER, estatus INTEGER, vendemos INTEGER);
INSERT INTO shipping_carriers VALUES (4, 'Entrega personal', 0, 1, 0), (8, 'CASTORES', 1, 1, 0), (9, 'ESTAFETA', 1, 1, 1), (10, 'Inactivo', 1, 0, 1), (11, 'Invalido', 2, 1, 1);`)
assert.deepEqual(db.prepare(query).all().map(row => row.id), [4, 8, 9], 'Active methods include both delivery types regardless of guide sales')
db.close()

let source = readFileSync(new URL('../../src/components/CheckoutDeliveryOptions.vue', import.meta.url), 'utf8').match(/<script setup>([\s\S]*?)<\/script>/)[1]
source = source.replace(/^import .*$/gm, '')
const selection = { value: { envio: null, shipping_carrier_id: null } }
const context = vm.createContext({
  defineModel: () => selection,
  ref: value => ({ value }),
  computed: getter => ({ get value() { return getter() } }),
  onMounted: () => {}, onBeforeUnmount: () => {},
  watch: () => {}, defineExpose: () => {},
  AbortController, setTimeout, clearTimeout,
  getBranches: async () => [{ id: 25, nombre: 'Sucursal Centro' }],
})
vm.runInContext(source + '\nglobalThis.api = { methods, branches, loading, error, branchesLoading, branchesError, validationError, validate, selectMode, selectCarrier, selectBranch, loadBranches };', context)
context.api.methods.value = [{ id: 21, envio: 0 }, { id: 8, envio: 1 }, { id: 9, envio: 1 }]
assert.equal(context.api.validate(), false, 'Cannot continue during loading')
context.api.loading.value = false
assert.equal(context.api.validate(), false, 'Delivery type is required')
context.api.selectMode(0)
await context.api.loadBranches()
assert.equal(context.api.validate(), false, 'Personal delivery requires a branch')
assert.equal(selection.value.shipping_carrier_id, 21, 'Single personal delivery uses its actual database ID')
context.api.branches.value = [{ id: 25, nombre: 'Sucursal Centro' }]
context.api.selectBranch(25)
assert.equal(selection.value.sucursal_id, 25, 'Branch ID is separate from the shipping carrier ID')
assert.equal(selection.value.shipping_carrier_id, 21, 'Selecting a branch keeps the delivery method ID')
assert.equal(context.api.validate(), true, 'Personal delivery is complete with both method and branch')
context.api.selectBranch(99)
assert.equal(selection.value.sucursal_id, 25, 'Unknown branches cannot be selected')
context.api.selectMode(1)
assert.equal(selection.value.shipping_carrier_id, null, 'Changing modes clears the previous ID')
assert.equal(context.api.validate(), false, 'National shipping requires a carrier')
assert.equal(selection.value.sucursal_id, null, 'National shipping clears the selected branch')
context.api.selectBranch(25)
assert.equal(selection.value.sucursal_id, null, 'National shipping cannot select a branch')
context.api.selectCarrier(21)
assert.equal(selection.value.shipping_carrier_id, null, 'Cannot select a personal delivery ID for national shipping')
context.api.selectCarrier(9)
assert.equal(selection.value.shipping_carrier_id, 9)
assert.equal(context.api.validate(), true, 'National shipping does not require a branch')
context.api.error.value = 'Connection failed'
assert.equal(context.api.validate(), false, 'Failed catalog cannot pass validation')
context.api.error.value = ''
context.api.selectMode(0)
assert.equal(selection.value.shipping_carrier_id, 21)
// Vue updates a child's model prop on the next render, not inside the emitted setter.
// Reproduce that delay so loading branches cannot overwrite the newly selected mode.
for (const initialMode of [null, 1]) {
  let current = { envio: initialMode, shipping_carrier_id: initialMode === 1 ? 8 : null, sucursal_id: null }
  const emitted = []
  const delayedModel = {
    get value() { return current },
    set value(value) { emitted.push(value) },
  }
  const delayedContext = vm.createContext({
    defineModel: () => delayedModel,
    ref: value => ({ value }),
    computed: getter => ({ get value() { return getter() } }),
    onMounted: () => {}, onBeforeUnmount: () => {}, watch: () => {}, defineExpose: () => {},
    AbortController, setTimeout, clearTimeout,
    getBranches: async () => [{ id: 25, nombre: 'Sucursal Centro' }],
  })
  vm.runInContext(source + '\nglobalThis.api = { methods, selectMode };', delayedContext)
  delayedContext.api.methods.value = [{ id: 21, envio: 0 }, { id: 8, envio: 1 }]
  delayedContext.api.selectMode(0)
  current = emitted.at(-1)
  assert.equal(current.envio, 0, 'First click must retain personal delivery while branches start loading')
  assert.equal(current.shipping_carrier_id, 21, 'Branch loading must retain the personal delivery carrier ID')
  await Promise.resolve()
}
console.log('Delivery method checks passed: active SQL filter, types, selection resets, real IDs and first-click personal delivery.')
