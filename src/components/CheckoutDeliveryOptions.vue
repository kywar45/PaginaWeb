<template>
  <section class="checkout-delivery" aria-labelledby="checkout-delivery-title">
    <h2 id="checkout-delivery-title">¿Cómo quieres recibir tu pedido?</h2>
    <p v-if="validationError" class="checkout-delivery__validation" role="alert">
      {{ validationError }}
    </p>
    <LoadingSkeleton
      v-if="loading"
      variant="list"
      :count="2"
      label="Cargando métodos de entrega..."
    />
    <div v-else-if="error" class="checkout-delivery__state" role="alert">
      <p>{{ error }}</p>
      <q-btn flat no-caps color="primary" label="Reintentar" @click="load" />
    </div>
    <p v-else-if="!methods.length" class="checkout-delivery__state">
      No hay métodos de entrega disponibles por el momento.
    </p>
    <template v-else>
      <div class="checkout-delivery__choices" role="group" aria-label="Tipo de entrega">
        <div
          v-for="option in options"
          :key="option.envio"
          class="checkout-delivery__choice"
          :class="{
            'is-selected': mode === option.envio,
            'is-disabled': !available(option.envio).length,
          }"
          @click="selectMode(option.envio)"
        >
          <q-radio
            :model-value="mode"
            :val="option.envio"
            :disable="!available(option.envio).length"
            :aria-label="option.title"
            color="primary"
            @update:model-value="selectMode"
          />
          <q-icon :name="option.icon" size="25px" aria-hidden="true" />
          <span
            ><strong>{{ option.title }}</strong
            ><small>{{
              available(option.envio).length ? option.description : 'No disponible por el momento'
            }}</small></span
          >
        </div>
      </div>
      <div v-if="mode !== null" class="checkout-delivery__details">
        <div class="checkout-delivery__notice" :class="{ 'is-shipping': mode === 1 }">
          <q-icon
            :name="mode === 0 ? 'storefront' : 'inventory_2'"
            size="23px"
            aria-hidden="true"
          />
          <div v-if="mode === 0">
            <strong>Instrucciones para entrega personal</strong>
            <p>
              Tu pedido se entregará en el punto de entrega seleccionado. Elige dónde quieres
              recibirlo.
            </p>
          </div>
          <div v-else>
            <strong>Información para tu envío</strong>
            <p>
              Preparamos y embalamos tu pedido para entregarlo a la paquetería que elijas. Revisa
              que tu dirección y teléfono estén completos y correctos.
            </p>
            <p>El traslado y los tiempos de entrega dependen de la paquetería seleccionada.</p>
          </div>
        </div>
        <div v-if="mode === 0" class="checkout-delivery__branches">
          <h3>Selecciona tu sucursal *</h3>
          <LoadingSkeleton
            v-if="branchesLoading"
            variant="list"
            :count="3"
            label="Cargando sucursales..."
          />
          <div v-else-if="branchesError" class="checkout-delivery__state" role="alert">
            <p>{{ branchesError }}</p>
            <q-btn flat no-caps color="primary" label="Reintentar" @click="loadBranches" />
          </div>
          <p v-else-if="!branches.length" class="checkout-delivery__state">
            No hay sucursales disponibles por el momento.
          </p>
          <div
            v-else
            class="checkout-delivery__branch-list"
            role="group"
            aria-label="Sucursal para entrega personal"
          >
            <div
              v-for="branch in branches"
              :key="branch.id"
              class="checkout-delivery__branch"
              :class="{ 'is-selected': selection.sucursal_id === branch.id }"
              @click="selectBranch(branch.id)"
            >
              <q-radio
                :model-value="selection.sucursal_id"
                :val="branch.id"
                :aria-label="branch.nombre"
                color="primary"
                @update:model-value="selectBranch"
              />
              <span>
                <strong>{{ branch.nombre }}</strong>
                <span
                  >{{ branch.direccion
                  }}<template v-if="branch.codigo_postal">
                    · C.P. {{ branch.codigo_postal }}</template
                  ></span
                >
                <small v-if="branch.horario_atencion"
                  ><q-icon name="schedule" aria-hidden="true" />
                  {{ branch.horario_atencion }}</small
                >
                <q-btn
                  v-if="branch.google_maps_url"
                  tag="a"
                  :href="branch.google_maps_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  outline
                  dense
                  no-caps
                  color="primary"
                  icon="location_on"
                  label="Ver en Maps"
                  class="checkout-delivery__map"
                  :aria-label="`Ver ubicación de ${branch.nombre} en Google Maps`"
                  @click.stop
                />
              </span>
            </div>
          </div>
        </div>
        <q-select
          v-if="mode === 1 || available(0).length > 1"
          ref="carrierSelect"
          :model-value="selectedId"
          :options="available(mode)"
          option-label="nombre"
          option-value="id"
          emit-value
          map-options
          outlined
          dense
          :label="mode === 0 ? 'Selecciona tu punto de entrega *' : 'Selecciona tu paquetería *'"
          :rules="[
            (value) =>
              available(mode).some((method) => method.id === value) ||
              'Selecciona una opción de la lista',
          ]"
          lazy-rules
          @update:model-value="selectCarrier"
        />
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getDeliveryMethods } from 'src/services/shippingApi'
import { getBranches } from 'src/services/branchApi'

const selection = defineModel({ type: Object, required: true })
const methods = ref([])
const loading = ref(true)
const error = ref('')
const validationError = ref('')
const carrierSelect = ref(null)
const branches = ref([])
const branchesLoading = ref(false)
const branchesError = ref('')
let branchRequest
let branchesLoaded = false
const mode = computed(() => selection.value.envio)
const selectedId = computed(() => selection.value.shipping_carrier_id)
const options = [
  {
    envio: 0,
    title: 'Entrega personal',
    icon: 'storefront',
    description: 'Recibe en un punto de entrega',
  },
  { envio: 1, title: 'Envío nacional', icon: 'local_shipping', description: 'Elige tu paquetería' },
]
let request
let disposed = false
const available = (envio) =>
  methods.value.filter((method) => method.envio === envio && (envio !== 0 || method.id === 21))
watch(
  selection,
  () => {
    validationError.value = ''
  },
  { deep: true },
)

function validate() {
  let message = ''
  if (loading.value) message = 'Espera a que terminen de cargar los métodos de entrega.'
  else if (error.value || !methods.value.length)
    message = 'Carga los métodos de entrega antes de continuar.'
  else if (![0, 1].includes(mode.value)) message = 'Elige entrega personal o envío nacional.'
  else if (!available(mode.value).some((method) => method.id === selectedId.value))
    message =
      mode.value === 1 ? 'Selecciona una paquetería.' : 'Selecciona el método de entrega personal.'
  else if (mode.value === 0) {
    if (branchesLoading.value) message = 'Espera a que terminen de cargar las sucursales.'
    else if (branchesError.value || !branchesLoaded)
      message = 'Carga las sucursales antes de continuar.'
    else if (!branches.value.some((branch) => branch.id === selection.value.sucursal_id))
      message = 'Selecciona la sucursal donde recibirás tu pedido.'
  }
  validationError.value = message
  carrierSelect.value?.validate()
  return !message
}
defineExpose({ validate })

function selectMode(envio) {
  if (mode.value === envio) return
  const carriers = available(envio)
  if (!carriers.length) return
  selection.value = {
    envio,
    shipping_carrier_id: carriers.length === 1 ? carriers[0].id : null,
    sucursal_id: null,
  }
  if (envio === 0 && !branchesLoaded && !branchesLoading.value) loadBranches()
}
function selectCarrier(id) {
  if (!available(mode.value).some((method) => method.id === id)) return
  selection.value = { ...selection.value, shipping_carrier_id: id }
}
function selectBranch(id) {
  if (mode.value !== 0 || !branches.value.some((branch) => branch.id === id)) return
  selection.value = { ...selection.value, sucursal_id: id }
}
async function loadBranches() {
  branchRequest?.abort()
  const current = new AbortController()
  branchRequest = current
  const timeout = setTimeout(() => current.abort(), 15000)
  branchesLoading.value = true
  branchesError.value = ''
  branches.value = []
  try {
    const result = await getBranches({ signal: current.signal })
    if (!disposed && branchRequest === current) {
      branches.value = result
      branchesLoaded = true
    }
  } catch {
    if (!disposed && branchRequest === current)
      branchesError.value = 'No pudimos cargar las sucursales. Intenta de nuevo.'
  } finally {
    clearTimeout(timeout)
    if (!disposed && branchRequest === current) branchesLoading.value = false
  }
}
async function load() {
  request?.abort()
  request = new AbortController()
  const current = request
  const timeout = setTimeout(() => current.abort(), 15000)
  loading.value = true
  error.value = ''
  methods.value = []
  selection.value = { envio: null, shipping_carrier_id: null, sucursal_id: null }
  try {
    const result = await getDeliveryMethods({ signal: current.signal })
    if (!disposed && request === current) methods.value = result
  } catch {
    if (!disposed && request === current)
      error.value = 'No pudimos cargar los métodos de entrega. Intenta de nuevo.'
  } finally {
    clearTimeout(timeout)
    if (!disposed && request === current) loading.value = false
  }
}
onMounted(load)
onBeforeUnmount(() => {
  disposed = true
  request?.abort()
  branchRequest?.abort()
})
</script>

<style scoped>
.checkout-delivery {
  min-width: 0;
  padding: 22px;
  border: 1px solid #f0dfe7;
  border-radius: 18px;
  background: #fff;
}
.checkout-delivery__validation {
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff0f0;
  color: #b42318;
  font-size: 12px;
  line-height: 1.5;
}
.checkout-delivery h2 {
  margin: 0 0 18px;
  color: #503340;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.4;
}
.checkout-delivery__choices {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.checkout-delivery__choice {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 10px 14px 3px;
  border: 1px solid #e9dfe4;
  border-radius: 12px;
  color: #8a7280;
  cursor: pointer;
}
.checkout-delivery__choice.is-selected {
  border-color: #e62f66;
  background: #fff6f9;
  color: #e62f66;
}
.checkout-delivery__choice.is-disabled {
  opacity: 0.6;
  cursor: default;
}
.checkout-delivery__choice > span {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 4px;
}
.checkout-delivery__choice strong {
  color: #503340;
  font-size: 13px;
}
.checkout-delivery__choice small {
  font-size: 11px;
  line-height: 1.5;
}
.checkout-delivery__details {
  margin-top: 18px;
}
.checkout-delivery__branches {
  margin-bottom: 18px;
}
.checkout-delivery__branches h3 {
  margin: 0 0 12px;
  color: #503340;
  font-size: 14px;
  font-weight: 700;
}
.checkout-delivery__branch-list {
  display: grid;
  gap: 10px;
}
.checkout-delivery__branch {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 12px 12px 12px 4px;
  border: 1px solid #e9dfe4;
  border-radius: 12px;
  cursor: pointer;
}
.checkout-delivery__branch.is-selected {
  border-color: #e62f66;
  background: #fff6f9;
}
.checkout-delivery__branch > span {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 6px;
  padding-top: 6px;
  color: #8a7280;
  font-size: 12px;
  line-height: 1.6;
  overflow-wrap: anywhere;
}
.checkout-delivery__branch strong {
  color: #503340;
  font-size: 13px;
}
.checkout-delivery__branch small {
  font-size: 11px;
}
.checkout-delivery__map {
  align-self: flex-start;
  margin-top: 2px;
  padding: 3px 10px;
  border-radius: 8px;
  font-size: 12px;
}
.checkout-delivery__notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 18px;
  padding: 16px;
  border-left: 3px solid #30976a;
  border-radius: 10px;
  background: #f0faf4;
  color: #286346;
  font-size: 12px;
  line-height: 1.7;
}
.checkout-delivery__notice.is-shipping {
  border-color: #e1a52d;
  background: #fff8e6;
  color: #856122;
}
.checkout-delivery__notice p {
  margin: 7px 0 0;
}
.checkout-delivery__notice a {
  display: inline-block;
  margin-top: 9px;
  color: inherit;
  font-weight: 600;
}
.checkout-delivery__state {
  padding: 20px 0;
  color: #8a7280;
  text-align: center;
  font-size: 13px;
}
@media (max-width: 599px) {
  .checkout-delivery {
    padding: 18px 14px;
  }
  .checkout-delivery__choices {
    grid-template-columns: 1fr;
  }
  .checkout-delivery__branch-list {
    gap: 8px;
  }
  .checkout-delivery__branch {
    gap: 3px;
    padding: 8px 10px 8px 3px;
    border-radius: 10px;
  }
  .checkout-delivery__branch > .q-radio {
    flex-shrink: 0;
  }
  .checkout-delivery__branch :deep(.q-radio__inner) {
    font-size: 32px;
  }
  .checkout-delivery__branch > span {
    flex: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 4px 6px;
    padding-top: 3px;
    font-size: 11px;
    line-height: 1.4;
  }
  .checkout-delivery__branch > span > strong,
  .checkout-delivery__branch > span > span {
    grid-column: 1 / -1;
  }
  .checkout-delivery__branch strong {
    font-size: 12px;
  }
  .checkout-delivery__branch small {
    grid-column: 1;
    grid-row: 3;
    font-size: 10px;
  }
  .checkout-delivery__map {
    grid-column: 2;
    grid-row: 3;
    margin-top: 0;
    padding: 2px 5px;
    min-height: 28px;
    font-size: 10px;
    white-space: nowrap;
  }
  .checkout-delivery__map :deep(.q-icon) {
    font-size: 16px;
    margin-right: 3px;
  }
}
</style>
