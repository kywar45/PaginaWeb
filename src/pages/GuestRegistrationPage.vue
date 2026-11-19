<template>
  <q-page class="guest-registration-page">
    <PageBreadcrumbs title="Crear mi cuenta" />
    <section class="guest-registration-card">
      <LoadingSkeleton v-if="checking" variant="list" :count="2" label="Comprobando tu enlace..." />
      <template v-else-if="created">
        <h1>Tu cuenta está lista</h1>
        <p role="status">
          Guardamos los datos de tu compra y vinculamos ese pedido a tu cuenta. Ya puedes iniciar
          sesión.
        </p>
        <q-btn unelevated no-caps color="primary" label="Iniciar sesión" to="/login" />
      </template>
      <template v-else-if="!approved">
        <h1>{{ existing ? 'Ya tienes una cuenta' : 'Enlace no disponible' }}</h1>
        <p role="alert">{{ error }}</p>
        <q-btn
          v-if="retryable"
          outline
          no-caps
          color="primary"
          label="Reintentar"
          @click="checkLink"
        />
        <template v-else>
          <q-btn
            unelevated
            no-caps
            color="primary"
            :label="existing ? 'Iniciar sesión' : 'Crear una cuenta'"
            :to="existing ? '/login' : '/register'"
          />
          <RouterLink v-if="existing" to="/olvide-contrasena">Olvidé mi contraseña</RouterLink>
        </template>
      </template>
      <template v-else>
        <h1>Solo falta tu contraseña</h1>
        <p>
          Usaremos los datos de contacto y envío de tu pedido para crear tu cuenta. Tu correo
          quedará confirmado con este enlace.
        </p>
        <q-form greedy @submit.prevent="submit">
          <q-input
            v-model="password"
            label="Contraseña"
            :type="visible ? 'text' : 'password'"
            autocomplete="new-password"
            outlined
            dense
            hide-bottom-space
            :rules="[passwordRule]"
            :disable="busy"
          >
            <template #append>
              <q-btn
                flat
                round
                dense
                :icon="visible ? 'visibility_off' : 'visibility'"
                :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :disable="busy"
                @click="visible = !visible"
              />
            </template>
          </q-input>
          <q-input
            v-model="confirmation"
            label="Confirma tu contraseña"
            type="password"
            autocomplete="new-password"
            outlined
            dense
            hide-bottom-space
            :rules="[(value) => (!!value && value === password) || 'Las contraseñas no coinciden']"
            :disable="busy"
          />
          <p class="guest-registration-hint">Usa al menos 8 caracteres.</p>
          <q-banner v-if="error" rounded role="alert">{{ error }}</q-banner>
          <q-btn
            type="submit"
            unelevated
            no-caps
            color="primary"
            label="Crear mi cuenta"
            :loading="busy"
            :disable="busy"
          />
        </q-form>
      </template>
    </section>
  </q-page>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { validateGuestRegistration, registerFromOrder } from 'src/services/authApi'

const route = useRoute()
const checking = ref(true)
const approved = ref(false)
const created = ref(false)
const existing = ref(false)
const retryable = ref(false)
const busy = ref(false)
const error = ref('')
const password = ref('')
const confirmation = ref('')
const visible = ref(false)
let requestVersion = 0
const passwordRule = (value) =>
  ([...value].length >= 8 && new TextEncoder().encode(value).length <= 72) ||
  'Usa al menos 8 caracteres y evita una contraseña demasiado larga.'

function linkError(failure) {
  existing.value = ['EMAIL_EXISTS', 'GUEST_REGISTER_USED'].includes(failure?.code)
  retryable.value = !failure?.status || failure.status >= 500 || failure.status === 429
  error.value = failure?.message || 'No pudimos comprobar tu enlace. Intenta de nuevo.'
}
async function checkLink() {
  const version = ++requestVersion
  checking.value = true
  approved.value = false
  created.value = false
  existing.value = false
  retryable.value = false
  error.value = ''
  password.value = confirmation.value = ''
  try {
    if (typeof route.query.token !== 'string' || !/^[a-f0-9]{64}$/.test(route.query.token)) {
      throw Object.assign(new Error('Abre el enlace que enviamos en el correo de tu pedido.'), {
        status: 422,
      })
    }
    const result = await validateGuestRegistration(route.query.token)
    if (version === requestVersion) approved.value = result?.valid === true
  } catch (failure) {
    if (version === requestVersion) linkError(failure)
  } finally {
    if (version === requestVersion) checking.value = false
  }
}
async function submit() {
  if (busy.value || !approved.value) return
  busy.value = true
  error.value = ''
  const version = requestVersion
  try {
    const result = await registerFromOrder(route.query.token, password.value, confirmation.value)
    if (version === requestVersion) {
      created.value = result?.created === true
      password.value = confirmation.value = ''
    }
  } catch (failure) {
    if (version === requestVersion) {
      linkError(failure)
      if (existing.value || /^GUEST_REGISTER_/.test(failure?.code || '')) approved.value = false
    }
  } finally {
    busy.value = false
  }
}
watch(() => route.query.token, checkLink, { immediate: true })
</script>

<style scoped lang="scss">
.guest-registration-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px 20px 80px;
}
.guest-registration-card {
  max-width: 430px;
  margin: 24px auto;
  padding: 24px;
  border: 1px solid #f1dce5;
  border-radius: 16px;
  h1 {
    margin: 0 0 12px;
    font-size: 24px;
    font-weight: 650;
    line-height: 1.3;
  }
  p {
    font-size: 16px;
    line-height: 1.6;
    color: #755462;
  }
  form {
    display: grid;
    gap: 16px;
    margin-top: 22px;
  }
  a {
    display: block;
    margin-top: 16px;
    color: #d62d61;
  }
  .guest-registration-hint {
    margin: 0;
    font-size: 14px;
  }
}
</style>
