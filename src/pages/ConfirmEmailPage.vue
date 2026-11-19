<template>
  <q-page class="confirm-page">
    <PageBreadcrumbs
      title="Confirmar correo"
      back-to="/login"
      :parents="[{ label: 'Iniciar sesión', to: '/login' }]"
    />
    <section class="confirm-card">
      <LoadingSkeleton
        v-if="checking || busy"
        variant="list"
        :count="2"
        :label="checking ? 'Comprobando tu enlace...' : 'Confirmando tu correo...'"
      />
      <template v-else>
        <span class="confirm-card__icon"
          ><q-icon :name="verified ? 'verified' : error ? 'link_off' : 'mark_email_read'"
        /></span>
        <h1>
          {{
            verified
              ? 'Tu correo está confirmado'
              : used
                ? 'Tu correo ya fue confirmado'
                : error
                  ? 'Revisemos tu enlace'
                  : 'Activa tu cuenta Supernova'
          }}
        </h1>
        <p v-if="verified" role="status">Ya puedes iniciar sesión y disfrutar de tu cuenta.</p>
        <p v-else-if="error" role="alert">{{ error }}</p>
        <p v-else>Confirma que este correo es tuyo para habilitar el acceso a tu cuenta.</p>
        <q-btn
          v-if="!verified && approved"
          unelevated
          no-caps
          color="primary"
          label="Confirmar mi correo"
          @click="confirm"
        />
        <q-btn
          v-if="verified || used"
          unelevated
          no-caps
          color="primary"
          label="Iniciar sesión"
          to="/login"
        />
        <q-btn
          v-if="checkError"
          outline
          no-caps
          color="primary"
          label="Reintentar comprobación"
          @click="checkLink"
        />
        <ResendConfirmation v-if="!verified && !used && !checkError" />
      </template>
    </section>
  </q-page>
</template>
<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ResendConfirmation from 'src/components/ResendConfirmation.vue'
import { confirmAccount, validateConfirmationLink } from 'src/services/authApi'
const route = useRoute()
const router = useRouter()
const busy = ref(false)
const verified = ref(false)
const error = ref('')
const checking = ref(true)
const approved = ref(false)
const used = ref(false)
const checkError = ref(false)
let linkRequest
let alive = true
const validToken = computed(
  () => typeof route.query.token === 'string' && /^[a-f0-9]{64}$/.test(route.query.token),
)
watch(
  () => route.query.token,
  (value) => {
    if (!value && verified.value) return
    verified.value = false
    checkLink()
  },
  { immediate: true },
)
async function checkLink() {
  linkRequest?.abort()
  const current = new AbortController()
  linkRequest = current
  approved.value = false
  used.value = false
  checkError.value = false
  if (!validToken.value) {
    checking.value = false
    error.value = 'El enlace no es válido. Solicita otro correo de confirmación.'
    return
  }
  checking.value = true
  error.value = ''
  const token = route.query.token
  try {
    const result = await validateConfirmationLink(token, { signal: current.signal })
    if (!alive || linkRequest !== current || route.query.token !== token) return
    if (result?.valid !== true) throw new Error('No pudimos comprobar el enlace. Intenta de nuevo.')
    approved.value = true
  } catch (failure) {
    if (!alive || linkRequest !== current || failure.name === 'AbortError') return
    error.value = failure.message
    used.value = failure.code === 'CONFIRMATION_USED'
    checkError.value = ![
      'CONFIRMATION_USED',
      'CONFIRMATION_INVALID',
      'CONFIRMATION_EXPIRED',
    ].includes(failure.code)
  } finally {
    if (alive && linkRequest === current) checking.value = false
  }
}
async function confirm() {
  if (busy.value || !approved.value || checking.value) return
  busy.value = true
  error.value = ''
  const token = route.query.token
  try {
    const result = await confirmAccount(token)
    if (!alive || route.query.token !== token) return
    if (result?.verified !== true)
      throw new Error('No pudimos confirmar tu correo. Intenta de nuevo.')
    verified.value = true
    await router.replace({ path: route.path })
  } catch (failure) {
    if (alive && route.query.token === token) {
      error.value = failure.message
      if (
        ['CONFIRMATION_USED', 'CONFIRMATION_INVALID', 'CONFIRMATION_EXPIRED'].includes(failure.code)
      ) {
        approved.value = false
        used.value = failure.code === 'CONFIRMATION_USED'
      }
    }
  } finally {
    busy.value = false
  }
}
onBeforeUnmount(() => {
  alive = false
  linkRequest?.abort()
})
</script>
<style scoped>
.confirm-page {
  max-width: 1000px;
  padding: 20px 20px 90px;
  margin: 0 auto;
}
.confirm-card {
  max-width: 480px;
  margin: 36px auto;
  padding: 32px 26px;
  border: 1px solid #efdee6;
  border-radius: 18px;
  text-align: center;
}
.confirm-card__icon {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  margin: 0 auto 20px;
  border-radius: 20px;
  background: #fff0f5;
  color: #e9316a;
  font-size: 34px;
}
.confirm-card h1 {
  font-size: 25px;
  line-height: 1.3;
  font-weight: 750;
  margin: 0 0 12px;
}
.confirm-card p {
  color: #81717d;
  font-size: 13px;
  line-height: 1.7;
  margin: 0 0 22px;
}
.confirm-card .resend-confirmation {
  text-align: left;
}
.confirm-card > .q-btn {
  border-radius: 9px;
}
</style>
