<template>
  <q-expansion-item
    class="resend-confirmation"
    icon="forward_to_inbox"
    label="No recibí mi correo de confirmación"
    dense
  >
    <div class="resend-confirmation__body">
      <p>
        Revisa también spam. Para solicitar un enlace nuevo, confirma el correo y la contraseña de
        tu cuenta.
      </p>
      <q-input
        ref="emailInput"
        v-model="address"
        outlined
        dense
        type="email"
        label="Correo electrónico"
        autocomplete="email"
        maxlength="250"
        hide-bottom-space
        :rules="[
          (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value || '') || 'Escribe un correo válido',
        ]"
        :disable="busy"
      />
      <q-input
        ref="passwordInput"
        v-model="password"
        outlined
        dense
        type="password"
        label="Contraseña de tu cuenta"
        autocomplete="current-password"
        hide-bottom-space
        :rules="[(value) => !!value || 'Escribe tu contraseña']"
        :disable="busy"
        @keydown.enter.prevent="resend"
      />
      <p v-if="message" :class="{ 'is-error': error }" :role="error ? 'alert' : 'status'">
        {{ message }}
      </p>
      <q-btn
        outline
        no-caps
        color="primary"
        label="Reenviar confirmación"
        :loading="busy"
        :disable="busy"
        @click="resend"
      />
    </div>
  </q-expansion-item>
</template>
<script setup>
import { ref, watch } from 'vue'
import { resendAccountConfirmation } from 'src/services/authApi'
const props = defineProps({ email: { type: String, default: '' } })
const address = ref(props.email)
const password = ref('')
const emailInput = ref(null)
const passwordInput = ref(null)
const busy = ref(false)
const message = ref('')
const error = ref(false)
watch(
  () => props.email,
  (value) => {
    address.value = value || ''
    message.value = ''
  },
)
async function resend() {
  if (busy.value || !(await emailInput.value.validate()) || !(await passwordInput.value.validate()))
    return
  busy.value = true
  message.value = ''
  error.value = false
  try {
    const result = await resendAccountConfirmation(
      address.value.trim().toLowerCase(),
      password.value,
    )
    error.value = result.email_status === 'pending'
    message.value = result.already_verified
      ? 'Tu correo ya está confirmado. Puedes iniciar sesión.'
      : error.value
        ? 'Tu cuenta sigue pendiente. No pudimos enviar el correo; intenta más tarde o contacta a soporte.'
        : 'Solicitamos un nuevo correo de confirmación. Usa el enlace más reciente y revisa spam.'
  } catch (failure) {
    error.value = true
    message.value = failure.message
  } finally {
    password.value = ''
    busy.value = false
  }
}
</script>
<style scoped>
.resend-confirmation {
  margin-top: 20px;
  border: 1px solid #eedfe6;
  border-radius: 10px;
  font-size: 13px;
  overflow: hidden;
  color: #ad315a;
}
.resend-confirmation__body {
  display: grid;
  gap: 12px;
  padding: 8px 16px 16px;
}
.resend-confirmation p {
  margin: 0;
  font-size: 12px;
  line-height: 1.6;
  color: #776974;
}
.resend-confirmation .is-error {
  color: #af284b;
}
</style>
