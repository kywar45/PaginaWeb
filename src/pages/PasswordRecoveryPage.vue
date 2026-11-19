<template>
  <q-page class="recovery-page">
    <PageBreadcrumbs
      :title="resetMode ? 'Nueva contraseña' : 'Recuperar cuenta'"
      back-to="/login"
      :parents="[{ label: 'Iniciar sesión', to: '/login' }]"
    />
    <section class="recovery-card">
      <LoadingSkeleton
        v-if="checkingLink"
        variant="list"
        :count="2"
        label="Comprobando tu enlace..."
      />
      <template v-else>
        <span class="recovery-icon"
          ><q-icon
            :name="completed ? 'verified_user' : requested ? 'mark_email_unread' : 'lock_reset'"
        /></span>
        <p class="recovery-eyebrow">TU CUENTA SUPERNOVA</p>
        <h1 ref="heading" tabindex="-1">
          {{
            completed
              ? 'Contraseña actualizada'
              : requested
                ? 'Revisa tu correo'
                : resetMode && !linkApproved
                  ? 'Enlace no disponible'
                  : resetMode
                    ? 'Elige tu nueva contraseña'
                    : 'Recupera el acceso'
          }}
        </h1>
        <template v-if="completed">
          <p role="status">
            Tu contraseña se guardó correctamente y cerramos las sesiones anteriores. Vuelve a
            iniciar sesión con tu contraseña nueva.
          </p>
          <p class="recovery-small">
            Si tu correo aún no estaba confirmado, deberás confirmarlo antes de entrar.
          </p>
          <q-btn unelevated no-caps color="primary" label="Iniciar sesión" to="/login" />
        </template>
        <template v-else-if="requested">
          <p role="status">
            Si existe una cuenta con ese correo, recibirás un enlace para cambiar tu contraseña.
            Revisa también spam.
          </p>
          <p class="recovery-small">
            El enlace dura 30 minutos. Si solicitas varios, utiliza el más reciente.
          </p>
          <q-btn
            outline
            no-caps
            color="primary"
            label="Solicitar otro enlace"
            @click="requested = false"
          />
          <RouterLink class="recovery-back" to="/login">Ir a iniciar sesión</RouterLink>
        </template>
        <template v-else-if="resetMode && (!validToken || invalidLink || !linkApproved)">
          <p role="alert">
            {{ error || 'El enlace no es válido. Solicita uno nuevo para cambiar tu contraseña.' }}
          </p>
          <q-btn
            v-if="linkCheckError"
            outline
            no-caps
            color="primary"
            label="Reintentar comprobación"
            @click="checkLink"
          />
          <q-btn
            v-else
            unelevated
            no-caps
            color="primary"
            label="Solicitar otro enlace"
            to="/olvide-contrasena"
          />
        </template>
        <q-form v-else greedy class="recovery-form" @submit.prevent="submit">
          <template v-if="resetMode">
            <p>Crea una contraseña de al menos 8 caracteres para proteger tu cuenta.</p>
            <q-input
              v-model="password"
              name="password"
              :type="showPassword ? 'text' : 'password'"
              label="Contraseña nueva"
              autocomplete="new-password"
              outlined
              hide-bottom-space
              :rules="[passwordRule]"
              :disable="busy"
            >
              <template #append
                ><q-btn
                  flat
                  round
                  dense
                  :icon="showPassword ? 'visibility_off' : 'visibility'"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  :disable="busy"
                  @click="showPassword = !showPassword"
              /></template>
            </q-input>
            <q-input
              v-model="confirmation"
              name="password_confirmation"
              type="password"
              label="Confirmar contraseña nueva"
              autocomplete="new-password"
              outlined
              hide-bottom-space
              :rules="[
                (value) => (!!value && value === password) || 'Las contraseñas no coinciden',
              ]"
              :disable="busy"
            />
          </template>
          <template v-else>
            <p>
              Escribe el correo con el que creaste tu cuenta y te enviaremos un enlace para elegir
              una contraseña nueva.
            </p>
            <q-input
              v-model="email"
              name="correo"
              type="email"
              label="Correo electrónico"
              autocomplete="email"
              maxlength="250"
              outlined
              hide-bottom-space
              :rules="[
                (value) =>
                  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value?.trim() || '') ||
                  'Escribe un correo válido',
              ]"
              :disable="busy"
            />
          </template>
          <q-banner v-if="error" rounded class="recovery-error" role="alert">{{ error }}</q-banner>
          <q-btn
            type="submit"
            unelevated
            no-caps
            color="primary"
            :label="resetMode ? 'Guardar contraseña nueva' : 'Enviar enlace de recuperación'"
            :loading="busy"
            :disable="busy"
          />
          <RouterLink v-if="!resetMode" class="recovery-back" to="/login"
            >Volver a iniciar sesión</RouterLink
          >
        </q-form>
      </template>
    </section>
  </q-page>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { requestPasswordReset, resetPassword, validateResetLink } from 'src/services/authApi'
import { passwordRule } from 'src/utils/registration'
import { useAuthSession } from 'src/services/authSession'

const route = useRoute()
const router = useRouter()
const { setCurrentUser } = useAuthSession()
const resetMode = computed(() => route.path === '/restablecer-contrasena')
const validToken = computed(
  () => typeof route.query.token === 'string' && /^[a-f0-9]{64}$/.test(route.query.token),
)
const email = ref(typeof route.query.correo === 'string' ? route.query.correo.slice(0, 250) : '')
const password = ref('')
const confirmation = ref('')
const showPassword = ref(false)
const busy = ref(false)
const requested = ref(false)
const completed = ref(false)
const invalidLink = ref(false)
const error = ref('')
const heading = ref(null)
const checkingLink = ref(resetMode.value)
const linkApproved = ref(false)
const linkCheckError = ref(false)
let linkRequest
let alive = true
watch(
  () => route.fullPath,
  () => {
    if (completed.value && resetMode.value && !route.query.token) return
    error.value = ''
    requested.value = false
    completed.value = false
    invalidLink.value = false
    password.value = ''
    confirmation.value = ''
    checkLink()
  },
  { immediate: true },
)
async function checkLink() {
  linkRequest?.abort()
  const current = new AbortController()
  linkRequest = current
  linkApproved.value = false
  linkCheckError.value = false
  invalidLink.value = false
  if (!resetMode.value) {
    checkingLink.value = false
    return
  }
  if (!validToken.value) {
    checkingLink.value = false
    invalidLink.value = true
    error.value = 'El enlace no es válido. Solicita uno nuevo para cambiar tu contraseña.'
    return
  }
  checkingLink.value = true
  error.value = ''
  const location = route.fullPath
  try {
    const result = await validateResetLink(route.query.token, { signal: current.signal })
    if (!alive || linkRequest !== current || route.fullPath !== location) return
    if (result?.valid !== true) throw new Error('No pudimos comprobar el enlace. Intenta de nuevo.')
    linkApproved.value = true
  } catch (failure) {
    if (!alive || linkRequest !== current || failure.name === 'AbortError') return
    error.value = failure.message
    invalidLink.value = failure.code === 'RESET_LINK_INVALID'
    linkCheckError.value = !invalidLink.value
  } finally {
    if (alive && linkRequest === current) checkingLink.value = false
  }
}
async function submit() {
  if (busy.value) return
  const resetting = resetMode.value
  const location = route.fullPath
  const token = route.query.token
  if (resetting && (!validToken.value || !linkApproved.value || checkingLink.value)) return
  busy.value = true
  error.value = ''
  try {
    const result = resetting
      ? await resetPassword(token, password.value, confirmation.value)
      : await requestPasswordReset(email.value.trim().toLowerCase())
    if (!alive || route.fullPath !== location) return
    if (resetting) {
      if (result?.reset !== true)
        throw new Error(
          'No pudimos confirmar el cambio de contraseña. Intenta iniciar sesión o solicita otro enlace.',
        )
      password.value = ''
      confirmation.value = ''
      completed.value = true
      setCurrentUser(null)
      await router.replace({ path: route.path })
    } else {
      if (result?.requested !== true)
        throw new Error('No pudimos confirmar la solicitud. Intenta de nuevo.')
      requested.value = true
    }
    await nextTick()
    heading.value?.focus()
  } catch (failure) {
    if (alive && route.fullPath === location) {
      error.value = failure.message
      invalidLink.value = resetting && failure.code === 'RESET_LINK_INVALID'
      if (invalidLink.value) {
        linkApproved.value = false
        password.value = ''
        confirmation.value = ''
      }
    }
  } finally {
    if (alive) busy.value = false
  }
}
onBeforeUnmount(() => {
  alive = false
  linkRequest?.abort()
  password.value = ''
  confirmation.value = ''
})
</script>
<style scoped>
.recovery-page {
  max-width: 1000px;
  padding: 20px 20px 100px;
  margin: 0 auto;
}
.recovery-card {
  max-width: 470px;
  margin: 30px auto;
  padding: 30px 28px;
  border: 1px solid #efdee6;
  border-radius: 18px;
  text-align: center;
}
.recovery-icon {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  margin: 0 auto 18px;
  border-radius: 20px;
  background: #fff0f5;
  color: #e9316a;
  font-size: 34px;
}
.recovery-eyebrow {
  color: #ce3263 !important;
  font-size: 10px !important;
  font-weight: 750;
  letter-spacing: 1px;
  margin-bottom: 8px !important;
}
.recovery-card h1 {
  font-size: 26px;
  line-height: 1.3;
  font-weight: 750;
  margin: 0 0 12px;
}
.recovery-card p {
  color: #81717d;
  font-size: 13px;
  line-height: 1.7;
  margin: 0 0 20px;
}
.recovery-card .recovery-small {
  font-size: 12px;
}
.recovery-form {
  display: grid;
  gap: 16px;
}
.recovery-form > p {
  margin: 0;
}
.recovery-form :deep(.q-field__control) {
  border-radius: 9px;
}
.recovery-card .q-btn {
  border-radius: 9px;
}
.recovery-back {
  display: block;
  margin-top: 12px;
  color: #cc2b60;
  font-size: 12px;
  text-decoration: none;
}
.recovery-error {
  background: #fff0f4;
  color: #a0284a;
  font-size: 12px;
  text-align: left;
}
@media (max-width: 480px) {
  .recovery-card {
    margin: 22px auto;
    padding: 26px 18px;
  }
  .recovery-card h1 {
    font-size: 23px;
  }
}
</style>
