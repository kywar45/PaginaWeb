<template>
  <section class="addresses" aria-label="Mis direcciones">
    <header class="addresses-heading">
      <div>
        <h2>Mis direcciones</h2>
        <p>Tus lugares de entrega, siempre a la mano.</p>
      </div>
      <q-btn
        v-if="!loading && !loadError"
        unelevated
        no-caps
        color="primary"
        icon="add"
        label="Agregar dirección"
        :disable="busy"
        @click="openForm()"
      />
    </header>
    <LoadingSkeleton v-if="loading" variant="list" :count="3" label="Cargando tus direcciones..." />
    <div v-else-if="loadError" class="addresses-state" role="alert">
      <q-icon name="cloud_off" />
      <p>{{ loadError }}</p>
      <q-btn flat no-caps color="primary" label="Reintentar" @click="load" />
    </div>
    <template v-else>
      <p v-if="notice" class="addresses-notice" role="status">{{ notice }}</p>
      <q-banner v-if="actionError" rounded class="addresses-error" role="alert">{{
        actionError
      }}</q-banner>
      <div v-if="!addresses.length" class="addresses-state">
        <q-icon name="add_location_alt" />
        <h3>¿A dónde enviamos tu próxima compra?</h3>
        <p>Agrega tu primera dirección para tenerla lista cuando la necesites.</p>
      </div>
      <div v-else class="addresses-grid">
        <article
          v-for="address in addresses"
          :key="address.id"
          class="address-card"
          :class="{ 'is-default': address.defecto }"
        >
          <div class="address-card__top">
            <span class="address-card__icon"><q-icon name="location_on" aria-hidden="true" /></span>
            <h3>{{ [address.nombre, address.apellido].filter(Boolean).join(' ') }}</h3>
          </div>
          <div class="address-card__details">
            <p class="address-card__street">{{ address.direccion }}</p>
            <p>{{ address.colonia }} &middot; C.P. {{ address.cp }}</p>
            <p>{{ [address.ciudad, address.estado].filter(Boolean).join(', ') }}</p>
            <p class="address-card__phone">
              <q-icon name="call" aria-hidden="true" />{{ address.telefono || 'Sin teléfono' }}
            </p>
          </div>
          <div class="address-card__actions">
            <span v-if="address.defecto" class="address-card__badge">
              <q-icon name="check_circle" aria-hidden="true" /> Predeterminada
            </span>
            <button v-else type="button" :disabled="busy" @click="setDefault(address)">
              <q-icon name="star_outline" aria-hidden="true" /> Predeterminar
            </button>
            <div class="address-card__tools">
              <q-btn
                flat
                round
                dense
                color="primary"
                icon="edit"
                aria-label="Editar dirección"
                :disable="busy"
                @click="openForm(address)"
                ><q-tooltip>Editar</q-tooltip></q-btn
              >
              <q-btn
                flat
                round
                dense
                color="grey-7"
                icon="delete_outline"
                aria-label="Eliminar dirección"
                :disable="busy"
                @click="removing = address"
                ><q-tooltip>Eliminar</q-tooltip></q-btn
              >
            </div>
          </div>
        </article>
      </div>
    </template>

    <q-dialog v-model="formOpen" :persistent="busy">
      <q-card class="address-form">
        <q-card-section class="address-form__heading">
          <div>
            <span>LUGAR DE ENTREGA</span>
            <h3>{{ form.id ? 'Editar dirección' : 'Nueva dirección' }}</h3>
          </div>
          <q-btn
            v-close-popup
            flat
            round
            dense
            icon="close"
            aria-label="Cerrar formulario"
            :disable="busy"
          />
        </q-card-section>
        <q-form @submit="save">
          <q-card-section class="address-form__fields">
            <q-banner
              v-if="formError"
              class="addresses-error address-form__full"
              rounded
              role="alert"
              >{{ formError }}</q-banner
            >
            <q-input
              v-model="form.nombre"
              hide-bottom-space
              outlined
              dense
              label="Nombre"
              autocomplete="given-name"
              maxlength="255"
              :rules="[required]"
              :disable="busy"
            />
            <q-input
              v-model="form.apellido"
              hide-bottom-space
              outlined
              dense
              label="Apellidos"
              autocomplete="family-name"
              maxlength="255"
              :disable="busy"
            />
            <q-input
              v-model="form.direccion"
              hide-bottom-space
              class="address-form__full"
              outlined
              dense
              label="Calle, número exterior e interior"
              autocomplete="address-line1"
              maxlength="255"
              :rules="[required]"
              :disable="busy"
            />
            <q-input
              v-model="form.colonia"
              class="address-form__mobile-full"
              hide-bottom-space
              outlined
              dense
              label="Colonia"
              autocomplete="address-line2"
              maxlength="255"
              :rules="[required]"
              :disable="busy"
            />
            <q-input
              v-model="form.cp"
              hide-bottom-space
              outlined
              dense
              label="Código postal"
              autocomplete="postal-code"
              inputmode="numeric"
              maxlength="5"
              :rules="[(v) => /^[0-9]{5}$/.test(v) || 'Ingresa 5 dígitos']"
              :disable="busy"
            />
            <q-input
              v-model="form.ciudad"
              hide-bottom-space
              outlined
              dense
              label="Ciudad o municipio"
              autocomplete="address-level2"
              maxlength="255"
              :rules="[required]"
              :disable="busy"
            />
            <q-select
              v-model="form.estado"
              class="address-form__mobile-full"
              hide-bottom-space
              outlined
              dense
              label="Estado"
              :options="states"
              options-dense
              :display-value="form.estado || 'Selecciona uno...'"
              :rules="[(value) => states.includes(value) || 'Selecciona un estado']"
              :disable="busy"
            />
            <q-input
              v-model="form.telefono"
              hide-bottom-space
              class="address-form__full"
              outlined
              dense
              type="tel"
              label="Teléfono de contacto"
              autocomplete="tel"
              maxlength="25"
              :rules="[required]"
              :disable="busy"
            />
            <q-checkbox
              v-model="form.defecto"
              class="address-form__full"
              color="primary"
              label="Usar como dirección predeterminada"
              :disable="busy || editingDefault"
            />
          </q-card-section>
          <q-card-actions align="right" class="address-form__footer">
            <q-btn v-close-popup flat no-caps label="Cancelar" :disable="busy" />
            <q-btn
              unelevated
              no-caps
              color="primary"
              type="submit"
              label="Guardar dirección"
              :loading="busy"
              :disable="busy"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
    <q-dialog
      :model-value="!!removing"
      :persistent="busy"
      @update:model-value="
        (value) => {
          if (!value) removing = null
        }
      "
    >
      <q-card class="address-remove">
        <q-card-section
          ><h3>¿Eliminar esta dirección?</h3>
          <p>{{ removing?.direccion }}</p>
          <p>Podrás agregar otra cuando lo necesites.</p>
          <p v-if="removeError" class="addresses-error" role="alert">{{ removeError }}</p>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Conservar" :disable="busy" @click="removing = null" />
          <q-btn
            unelevated
            no-caps
            color="primary"
            label="Eliminar"
            :loading="busy"
            :disable="busy"
            @click="remove"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { accountAddresses, changeAddress } from 'src/services/authApi'

const addresses = ref([])
const loading = ref(true)
const busy = ref(false)
const loadError = ref('')
const actionError = ref('')
const formError = ref('')
const removeError = ref('')
const notice = ref('')
const formOpen = ref(false)
const editingDefault = ref(false)
const removing = ref(null)
const fields = ['nombre', 'apellido', 'colonia', 'ciudad', 'estado', 'cp', 'direccion', 'telefono']
const states = [
  'Aguascalientes',
  'Baja California',
  'Baja California Sur',
  'Campeche',
  'Chiapas',
  'Chihuahua',
  'Ciudad de México',
  'Coahuila',
  'Colima',
  'Durango',
  'Estado de México',
  'Guanajuato',
  'Guerrero',
  'Hidalgo',
  'Jalisco',
  'Michoacán',
  'Morelos',
  'Nayarit',
  'Nuevo León',
  'Oaxaca',
  'Puebla',
  'Querétaro',
  'Quintana Roo',
  'San Luis Potosí',
  'Sinaloa',
  'Sonora',
  'Tabasco',
  'Tamaulipas',
  'Tlaxcala',
  'Veracruz',
  'Yucatán',
  'Zacatecas',
]
const normalizeState = (value) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
const form = reactive({})
let request
let alive = true
const required = (value) => !!String(value || '').trim() || 'Completa este campo'

async function load() {
  request?.abort()
  request = new AbortController()
  loading.value = true
  loadError.value = ''
  try {
    const data = await accountAddresses({ signal: request.signal })
    if (alive) addresses.value = data.direcciones
  } catch (error) {
    if (alive && error.name !== 'AbortError') loadError.value = error.message
  } finally {
    if (alive) loading.value = false
  }
}

function openForm(address) {
  form.id = address?.id ?? null
  for (const field of fields) form[field] = address?.[field] || ''
  form.estado = states.find((state) => normalizeState(state) === normalizeState(form.estado)) || ''
  form.defecto = !!address?.defecto || addresses.value.length === 0
  editingDefault.value = !!address?.defecto
  formError.value = ''
  formOpen.value = true
}

async function update(payload, message, errorTarget) {
  if (busy.value) return false
  busy.value = true
  errorTarget.value = ''
  actionError.value = ''
  notice.value = ''
  try {
    const data = await changeAddress(payload)
    if (!alive) return false
    addresses.value = data.direcciones
    notice.value = message
    return true
  } catch (error) {
    if (alive) errorTarget.value = error.message
    return false
  } finally {
    if (alive) busy.value = false
  }
}
async function save() {
  if (await update({ ...form, accion: 'guardar' }, 'Dirección guardada.', formError))
    formOpen.value = false
}
async function setDefault(address) {
  await update(
    { id: address.id, accion: 'predeterminada' },
    'Dirección predeterminada actualizada.',
    actionError,
  )
}
async function remove() {
  if (!removing.value) return
  if (
    await update({ id: removing.value.id, accion: 'eliminar' }, 'Dirección eliminada.', removeError)
  )
    removing.value = null
}
watch(removing, () => {
  removeError.value = ''
})
onMounted(load)
onBeforeUnmount(() => {
  alive = false
  request?.abort()
})
</script>

<style scoped lang="scss">
.addresses-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 22px;
  h2 {
    margin: 0;
    font-size: 23px;
    font-weight: 750;
  }
  p {
    color: #8c7b85;
    margin: 5px 0 0;
    font-size: 13px;
  }
  .q-btn {
    border-radius: 10px;
    font-size: 13px;
  }
}
.addresses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 275px), 1fr));
  gap: 12px;
  align-items: start;
}
.address-card {
  border: 1px solid #efdfe7;
  border-radius: 12px;
  padding: 14px 15px 8px;
  background: #fff;
  min-width: 0;
  overflow-wrap: anywhere;
  &.is-default {
    border-color: #ef95b2;
  }
  h3 {
    flex: 1;
    min-width: 0;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
    margin: 0;
  }
  p {
    font-size: 12px;
    line-height: 1.5;
    color: #7b6c76;
    margin: 1px 0;
  }
  .address-card__street {
    color: #372831;
    font-weight: 600;
  }
}
.address-card__top {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 6px;
}
.address-card__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 9px;
  background: #fff1f6;
  color: #ed326b;
  font-size: 19px;
}
.address-card__details {
  padding-left: 37px;
}
.address-card__badge {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #b92b57;
  font-size: 11px;
  font-weight: 650;
}
.address-card .address-card__phone {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
}
.address-card__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  border-top: 1px solid #f4e9ee;
  margin-top: 10px;
  padding-top: 5px;
  > button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: 0;
    background: transparent;
    color: #d83065;
    font: inherit;
    font-size: 11px;
    min-height: 32px;
    padding: 0;
    cursor: pointer;
    text-align: left;
    .q-icon {
      font-size: 16px;
    }
    &:disabled {
      opacity: 0.5;
      cursor: default;
    }
    &:focus-visible {
      outline: 2px solid #ed326b;
      outline-offset: 3px;
      border-radius: 3px;
    }
  }
}
.address-card__tools {
  display: flex;
  gap: 2px;
  margin-left: auto;
  .q-btn {
    font-size: 12px;
  }
}
.addresses-state {
  padding: 36px 20px;
  text-align: center;
  border: 1px dashed #ecd9e2;
  border-radius: 16px;
  > .q-icon {
    font-size: 38px;
    color: #ec3871;
  }
  h3 {
    font-size: 18px;
    line-height: 1.4;
  }
  p {
    color: #8b7b85;
    font-size: 13px;
  }
}
.addresses-notice {
  color: #16804d;
  font-size: 13px;
}
.addresses-error {
  background: #fff1f4;
  color: #a5254a;
  font-size: 13px;
  margin-bottom: 14px;
}
.address-form {
  width: 600px;
  max-width: calc(100vw - 32px);
  max-height: calc(100dvh - 48px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 18px;
  > .q-form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
  }
  h3 {
    margin: 3px 0 0;
    font-size: 22px;
    line-height: 1.3;
    font-weight: 700;
  }
}
.address-form__heading {
  display: flex;
  flex-shrink: 0;
  justify-content: space-between;
  gap: 12px;
  padding: 24px;
  span {
    color: #de3165;
    font-size: 10px;
    font-weight: 750;
    letter-spacing: 1px;
  }
}
.address-form__fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  padding: 8px 24px;
  min-height: 0;
  overflow-y: auto;
  :deep(.q-field) {
    min-width: 0;
    align-self: start;
  }
  :deep(.q-field__control) {
    border-radius: 8px;
  }
}
.address-form__full {
  grid-column: 1/-1;
}
.address-form__footer {
  flex-shrink: 0;
  padding: 16px 24px 24px;
  .q-btn {
    border-radius: 9px;
  }
}
.address-remove {
  width: 420px;
  max-width: calc(100vw - 32px);
  border-radius: 16px;
  padding: 8px;
  h3 {
    margin: 0 0 12px;
    font-size: 20px;
    line-height: 1.4;
  }
  p {
    font-size: 13px;
    color: #806e7a;
  }
}
@media (max-width: 700px) {
  .addresses-grid {
    grid-template-columns: 1fr;
  }
  .addresses-heading {
    align-items: flex-start;
    flex-direction: column;
    h2 {
      font-size: 21px;
    }
  }
  .address-card {
    padding: 12px 14px 7px;
  }
  .address-form__fields {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 8px 18px;
    :deep(.q-field__label) {
      font-size: 12px;
    }
    :deep(.q-checkbox__label) {
      font-size: 12px;
      line-height: 1.4;
    }
  }
  .address-form__mobile-full {
    grid-column: 1 / -1;
  }
  .address-form__heading {
    padding: 20px 18px;
  }
  .address-form__footer {
    padding: 16px 18px;
  }
}
</style>
