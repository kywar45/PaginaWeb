<template>
  <section class="guest-checkout" aria-labelledby="guest-checkout-title">
    <header class="guest-checkout__heading">
      <div>
        <span class="guest-checkout__eyebrow">COMPRA COMO INVITADO</span>
        <h2 id="guest-checkout-title">Datos del cliente</h2>
      </div>
      <q-btn
        flat
        dense
        no-caps
        color="primary"
        icon="cleaning_services"
        label="Limpiar"
        @click="clear"
      />
    </header>
    <p class="guest-checkout__intro">
      Completa tus datos de contacto y la dirección de entrega. Los campos con * son obligatorios.
    </p>
    <q-form
      ref="form"
      class="guest-checkout__form"
      @submit="confirm"
      @validation-error="confirmed = false"
    >
      <q-input
        v-model="details.nombre"
        outlined
        dense
        label="Nombre *"
        autocomplete="given-name"
        maxlength="100"
        :rules="[requiredGuestField]"
        lazy-rules
      />
      <q-input
        v-model="details.apellido"
        outlined
        dense
        label="Apellido *"
        autocomplete="family-name"
        maxlength="100"
        :rules="[requiredGuestField]"
        lazy-rules
      />
      <q-input
        v-model="details.correo"
        class="guest-checkout__full"
        outlined
        dense
        label="Correo electrónico *"
        type="email"
        autocomplete="email"
        maxlength="150"
        :rules="[requiredGuestField, validGuestEmail]"
        lazy-rules
      />
      <q-select
        v-model="details.estado"
        outlined
        dense
        label="Estado *"
        :options="mexicanStates"
        autocomplete="address-level1"
        :rules="[validGuestState]"
        lazy-rules
      />
      <q-input
        v-model="details.ciudad"
        outlined
        dense
        label="Ciudad / municipio *"
        autocomplete="address-level2"
        maxlength="150"
        :rules="[requiredGuestField]"
        lazy-rules
      />
      <q-input
        v-model="details.colonia"
        class="guest-checkout__full"
        outlined
        dense
        label="Colonia *"
        autocomplete="address-level3"
        maxlength="150"
        :rules="[requiredGuestField]"
        lazy-rules
      />
      <q-input
        v-model="details.direccion"
        class="guest-checkout__full"
        outlined
        dense
        autogrow
        label="Dirección: calle y número *"
        autocomplete="street-address"
        maxlength="255"
        hint="Incluye número interior si aplica."
        :rules="[requiredGuestField]"
        lazy-rules
      />
      <q-input
        v-model="details.cp"
        outlined
        dense
        label="Código postal *"
        autocomplete="postal-code"
        inputmode="numeric"
        maxlength="5"
        :rules="[validGuestPostalCode]"
        lazy-rules
      />
      <q-input
        v-model="details.telefono"
        outlined
        dense
        label="Teléfono *"
        type="tel"
        autocomplete="tel"
        maxlength="20"
        :rules="[requiredGuestField, validGuestPhone]"
        lazy-rules
      />
      <div class="guest-checkout__actions guest-checkout__full">
        <p v-if="confirmed" class="guest-checkout__confirmed" role="status">
          <q-icon name="check_circle" /> Datos de envío revisados.
        </p>
      </div>
    </q-form>
  </section>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue'
import { mexicanStates } from 'src/utils/mexicanStates'
import {
  emptyGuestDetails,
  normalizeGuestDetails,
  requiredGuestField,
  validGuestEmail,
  validGuestState,
  validGuestPostalCode,
  validGuestPhone,
} from 'src/utils/guestCheckout'

const details = defineModel({ type: Object, required: true })
const emit = defineEmits(['validated'])
const form = ref(null)
const confirmed = ref(false)
watch(
  details,
  () => {
    confirmed.value = false
  },
  { deep: true, flush: 'sync' },
)

async function clear() {
  details.value = emptyGuestDetails()
  await nextTick()
  form.value?.resetValidation()
}
function confirm() {
  details.value = normalizeGuestDetails(details.value)
  confirmed.value = true
  emit('validated', { ...details.value })
}
async function validate() {
  const valid = await form.value?.validate(false)
  if (valid) confirm()
  else confirmed.value = false
  return !!valid
}
defineExpose({ validate })
</script>

<style scoped>
.guest-checkout {
  padding: 22px;
  border: 1px solid #f0dfe7;
  border-radius: 18px;
  background: #fff;
}
.guest-checkout__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.guest-checkout__eyebrow {
  color: #e62f66;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
}
.guest-checkout h2 {
  margin: 5px 0 0;
  color: #503340;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;
}
.guest-checkout__intro {
  margin: 12px 0 22px;
  color: #8a7280;
  font-size: 12px;
  line-height: 1.6;
}
.guest-checkout__form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 16px;
}
.guest-checkout__full {
  grid-column: 1 / -1;
}
.guest-checkout__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 4px;
}
.guest-checkout__confirmed {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0 auto 0 0;
  color: #177345;
  font-size: 12px;
}
@media (max-width: 599px) {
  .guest-checkout {
    padding: 18px 14px;
  }
  .guest-checkout h2 {
    font-size: 20px;
  }
  .guest-checkout__form {
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
  }
  .guest-checkout__actions > .q-btn {
    width: 100%;
  }
}
</style>
