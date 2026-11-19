<template>
  <q-page class="login-page">
    <section class="login-shell">
      <AuthShoppingPanel class="login-shopping-panel gt-xs" />

      <div class="login-form-wrap">
        <PageBreadcrumbs title="Iniciar sesión" />

        <div class="login-heading">
          <span class="login-heading__icon"><q-icon name="person" /></span>
          <div>
            <p class="login-heading__eyebrow">MI CUENTA</p>
            <h1>Qué gusto verte de nuevo</h1>
            <p>Entra para ver tus pedidos y seguir comprando.</p>
          </div>
        </div>

        <q-form greedy class="login-form" @submit.prevent="submitLogin">
          <p v-if="checkoutReturn" class="login-checkout-note">
            Inicia sesión para continuar tu compra. Tus productos se conservarán en tu carrito.
          </p>
          <q-input
            v-model.trim="email"
            label="Correo electrónico"
            type="email"
            autocomplete="email"
            dense
            outlined
            clearable
            stack-label
            color="primary"
            :rules="[validateEmail]"
            hide-bottom-space
          >
            <template #prepend>
              <q-icon name="alternate_email" />
            </template>
          </q-input>

          <q-input
            v-model="password"
            label="Contraseña"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            dense
            outlined
            clearable
            stack-label
            color="primary"
            :rules="[validatePassword]"
            hide-bottom-space
          >
            <template #prepend>
              <q-icon name="lock" />
            </template>
            <template #append>
              <q-btn
                flat
                round
                dense
                :icon="showPassword ? 'visibility_off' : 'visibility'"
                @click="showPassword = !showPassword"
              >
                <q-tooltip>{{ showPassword ? 'Ocultar' : 'Mostrar' }}</q-tooltip>
              </q-btn>
            </template>
          </q-input>

          <div class="login-form__options">
            <q-checkbox v-model="remember" label="Recordarme" dense color="primary" />
            <RouterLink
              :to="{ path: '/olvide-contrasena', query: email ? { correo: email } : {} }"
              class="login-form__forgot"
              >Olvidé mi contraseña</RouterLink
            >
          </div>

          <q-banner v-if="errorMessage" rounded class="login-form__message">
            <template #avatar>
              <q-icon name="error_outline" color="negative" />
            </template>
            {{ errorMessage }}
          </q-banner>

          <q-btn
            type="submit"
            unelevated
            color="primary"
            label="Iniciar sesión"
            class="login-form__submit"
            :loading="loading"
            :disable="loading"
          />

          <div class="login-form__divider"><span>¿Primera vez aquí?</span></div>

          <q-btn
            outline
            no-caps
            color="primary"
            label="Crear una cuenta"
            class="login-form__register"
            @click="$router.push('/register')"
          />

          <div class="login-form__support">
            <q-icon name="support_agent" />
            <span>¿Necesitas ayuda? <a href="tel:+525524954670">55 2495 4670</a></span>
          </div>
        </q-form>
        <ResendConfirmation v-if="awaitingConfirmation" :email="email" />
      </div>
    </section>
  </q-page>
</template>

<script setup>
import AuthShoppingPanel from 'src/components/AuthShoppingPanel.vue'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { computed, onMounted, ref, watch } from 'vue'
import ResendConfirmation from 'src/components/ResendConfirmation.vue'
import { useRoute, useRouter } from 'vue-router'
import authApi from 'src/services/authApi'
import { useAuthSession } from 'src/services/authSession'
import { useCart } from 'src/services/cart'

const router = useRouter()
const route = useRoute()
// Only accept this known internal destination; never follow arbitrary redirect URLs.
const checkoutReturn = computed(() => route.query.next === '/checkout')
const { loadCurrentUser, setCurrentUser } = useAuthSession()
const { setMergeNotice } = useCart()

const email = ref(typeof route.query.correo === 'string' ? route.query.correo.slice(0, 250) : '')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const awaitingConfirmation = ref(false)
watch([email, password], () => {
  awaitingConfirmation.value = false
})

onMounted(async () => {
  const user = await loadCurrentUser()

  if (user) {
    router.replace(checkoutReturn.value ? '/checkout' : '/panel')
  }
})

function validateEmail(value) {
  if (!value) {
    return 'Escribe tu correo.'
  }

  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Correo invalido.'
}

function validatePassword(value) {
  return !!value || 'Escribe tu contrasena.'
}

async function submitLogin() {
  errorMessage.value = ''
  loading.value = true

  try {
    const data = await authApi.login({
      correo: email.value.toLowerCase(),
      password: password.value,
      remember: remember.value,
    })

    setCurrentUser(data?.usuario)
    setMergeNotice(data?.carrito_fusion)
    await router.replace(
      checkoutReturn.value ? (data?.carrito_fusion?.pendiente ? '/carrito' : '/checkout') : '/',
    )
  } catch (error) {
    errorMessage.value = error?.message || 'No pudimos iniciar sesion. Intenta de nuevo.'
    awaitingConfirmation.value = error?.code === 'EMAIL_UNVERIFIED'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.login-checkout-note {
  margin: 0;
  padding: 12px 14px;
  border-radius: 9px;
  background: #f7f7f9;
  color: #736875;
  font-size: 13px;
  line-height: 1.5;
}
.login-page {
  display: flex;
  min-height: calc(100dvh - 120px);
  align-items: center;
  justify-content: center;
  padding: clamp(28px, 4vw, 54px) 20px;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 250, 0.96)), #fff;
}

.login-shell {
  display: grid;
  width: min(820px, 100%);
  min-height: 0;
  grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
  overflow: hidden;
  border: 1px solid #f1dce2;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 22px 56px rgba(135, 36, 69, 0.12);
}

.login-shopping-panel {
  border-radius: 0;
}

.login-form-wrap {
  display: flex;
  min-width: 0;
  flex-direction: column;
  justify-content: center;
  padding: 24px 30px;
}

.login-mobile-head {
  align-items: center;
  gap: 14px;
  margin-bottom: 36px;
}

.login-mobile-head img {
  width: 175px;
  filter: brightness(0) saturate(100%) invert(35%) sepia(73%) saturate(2060%) hue-rotate(313deg)
    brightness(95%) contrast(93%);
}

.login-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 30px;
}

.login-heading__icon {
  display: grid;
  width: 46px;
  height: 46px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 50%;
  background: #fff0f4;
  color: #dc3965;
}

.login-heading__icon .q-icon {
  font-size: 24px;
}

.login-heading__eyebrow {
  margin: 0 0 5px !important;
  color: #dc3965 !important;
  font-size: 10px !important;
  font-weight: 850;
}

.login-heading h1 {
  margin: 0;
  color: #211b1d;
  font-size: clamp(25px, 3vw, 34px);
  font-weight: 850;
  line-height: 1.1;
}

.login-heading > div > p:last-child {
  margin: 8px 0 0;
  color: #756a6d;
  font-size: 13px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.login-form :deep(.q-field__control) {
  min-height: 52px;
  border-radius: 7px;
  background: #fff;
}

.login-form :deep(.q-field--outlined .q-field__control::before) {
  border-color: #e6dadd;
}

.login-form :deep(.q-field--focused .q-field__control::before),
.login-form :deep(.q-field--focused .q-field__control::after) {
  border-color: #de7c97;
}

.login-form :deep(.q-field__prepend),
.login-form :deep(.q-field__append) {
  color: #de7c97;
}

.login-form__options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: -2px 0 2px;
  color: #655b5e;
  font-size: 12px;
}

.login-form__forgot {
  text-decoration: none;
  padding: 4px 0;
  border: 0;
  background: transparent;
  color: #d73764;
  cursor: pointer;
  font: inherit;
  font-weight: 750;
}

.login-form__forgot:hover {
  text-decoration: underline;
}

.login-form__message {
  color: #7a1f2f;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.login-form__submit,
.login-form__register {
  min-height: 48px;
  border-radius: 6px;
  font-weight: 750;
}

.login-form__submit {
  background: linear-gradient(180deg, #ee7091, #df4d77) !important;
}

.login-form__divider {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #9a8f92;
  font-size: 11px;
  white-space: nowrap;
}

.login-form__divider::before,
.login-form__divider::after {
  content: '';
  width: 100%;
  height: 1px;
  background: #eee4e6;
}

.login-form__register {
  background: #fff !important;
}

.login-form__support {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 4px;
  color: #8a7e81;
  font-size: 11px;
}

.login-form__support .q-icon {
  color: #de7c97;
  font-size: 17px;
}

.login-form__support a {
  color: #d73764;
  font-weight: 750;
  text-decoration: none;
}

@media (max-width: 820px) {
  .login-shell {
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
  }
}

@media (min-width: 1024px) {
  .login-page {
    padding: 30px 24px 42px;
    background: #fff;
  }
  .login-shell {
    width: min(820px, 100%);
    min-height: 0;
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
    border-color: #f1dce5;
    border-radius: 24px;
    box-shadow: 0 16px 44px rgba(113, 43, 70, 0.08);
  }
  .login-form-wrap {
    padding: 24px 30px;
  }
  .login-heading {
    gap: 12px;
    margin: 12px 0 24px;
  }
  .login-heading h1 {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    color: #49303e;
  }
  .login-heading__icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
  }
  .login-heading__eyebrow {
    letter-spacing: 0.08em;
    font-weight: 600;
  }
  .login-heading > div > p:last-child {
    font-size: 12px;
    color: #9b7d8b;
  }
  .login-form {
    gap: 16px;
  }
  .login-form :deep(.q-field__control) {
    border-radius: 13px;
    background: #fffcfd;
  }
  .login-form :deep(.q-field__prepend),
  .login-form :deep(.q-field__append) {
    color: #e74776;
  }
  .login-form__options {
    gap: 10px;
    font-size: 11px;
  }
  .login-form__forgot {
    font-weight: 600;
  }
  .login-form__submit,
  .login-form__register {
    min-height: 46px;
    border-radius: 13px;
    font-size: 13px;
    font-weight: 600;
    text-transform: none;
  }
  .login-form__submit {
    background:
      linear-gradient(
        158deg,
        transparent 0 30%,
        rgba(255, 255, 255, 0.1) 30.4% 36%,
        transparent 36.4%
      ),
      linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61) !important;
  }
  .login-form__register {
    background: #fff6f9 !important;
    color: #d72c60 !important;
  }
  .login-form__register::before {
    border-color: #f2cad8;
  }
  .login-form__divider {
    font-size: 10px;
  }
  .login-form__support {
    font-size: 10px;
  }
}

@media (min-width: 600px) {
  .login-form-wrap {
    width: 100%;
    max-width: 420px;
    justify-self: center;
    padding: 22px 26px;
  }

  .login-heading {
    margin: 8px 0 18px;
  }

  .login-heading__icon,
  .login-heading__eyebrow {
    display: none;
  }

  .login-heading h1 {
    font-size: 20px;
    font-weight: 600;
    line-height: 1.3;
  }

  .login-heading > div > p:last-child {
    margin-top: 5px;
    font-size: 14px;
    line-height: 1.5;
  }

  .login-form {
    gap: 12px;
  }

  .login-form :deep(.q-field__control) {
    min-height: 44px;
    border-radius: 8px;
    background: #fff;
  }

  .login-form :deep(.q-field__marginal) {
    height: 44px;
    font-size: 20px;
  }

  .login-form__submit,
  .login-form__register {
    min-height: 40px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-transform: none;
  }

  .login-form__submit {
    background: #e93168 !important;
  }

  .login-form__options {
    font-size: 12px;
  }

  .login-form__divider,
  .login-form__support {
    font-size: 12px;
  }
}

@media (max-width: 599px) {
  .login-page {
    align-items: stretch;
    padding: 0;
    background: #fff;
  }

  .login-shell {
    min-height: 0;
    grid-template-columns: 1fr;
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .login-form-wrap {
    justify-content: flex-start;
    padding: 10px 16px 110px;
  }

  .login-mobile-head {
    display: flex;
  }

  .login-heading {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    align-items: center;
    gap: 12px;
    margin: 4px 0 22px;
    padding: 20px 16px;
    border-radius: 20px;
    background:
      linear-gradient(
        158deg,
        transparent 0 17%,
        rgba(255, 255, 255, 0.08) 17.4% 21%,
        transparent 21.4% 44%,
        rgba(255, 255, 255, 0.06) 44.4% 48%,
        transparent 48.4%
      ),
      radial-gradient(
        115% 76% at 103% 94%,
        transparent 60%,
        rgba(255, 255, 255, 0.13) 61%,
        transparent 65%
      ),
      linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  }

  .login-heading__icon {
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
  }

  .login-heading h1 {
    color: #fff;
    font-size: 21px;
    font-weight: 700;
    line-height: 1.25;
  }
  .login-heading .login-heading__eyebrow {
    color: #fff !important;
    font-size: 9px !important;
    letter-spacing: 0.08em;
  }
  .login-heading > div > p:last-child {
    margin-top: 6px;
    color: rgba(255, 255, 255, 0.92);
    font-size: 11px;
    line-height: 1.4;
  }
  .login-form {
    gap: 16px;
  }
  .login-form :deep(.q-field__control) {
    border-radius: 14px;
    background: #fffcfd;
  }
  .login-form :deep(.q-field__prepend),
  .login-form :deep(.q-field__append) {
    color: #e74776;
  }
  .login-form :deep(.q-field__label) {
    color: #8d7781;
  }
  .login-form__submit,
  .login-form__register {
    min-height: 46px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 700;
    text-transform: none;
  }
  .login-form__submit {
    background:
      linear-gradient(
        158deg,
        transparent 0 30%,
        rgba(255, 255, 255, 0.1) 30.4% 36%,
        transparent 36.4%
      ),
      linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61) !important;
    box-shadow: 0 5px 14px rgba(222, 47, 97, 0.15);
  }
  .login-form__register {
    color: #d72c60 !important;
    background: #fff5f8 !important;
  }
  .login-form__register::before {
    border-color: #f3cbd8;
  }
  .login-form__divider {
    font-size: 10px;
  }
  .login-form__forgot {
    color: #cc2b5a;
    font-size: 11px;
    font-weight: 600;
  }

  .login-form__options {
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 11px;
  }
}

@media (max-width: 370px) {
  .login-form__options {
    flex-direction: column;
    gap: 4px;
  }
}
</style>
