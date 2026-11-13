<template>
  <q-page class="login-page">
    <section class="login-shell">
      <aside class="login-brand gt-xs">
        <div class="login-brand__content">
          <img v-image-skeleton
            :src="publicAsset('img/logob.png')"
            alt="Importadora Supernova"
            class="login-brand__logo"
          />
          <p class="login-brand__tagline">De todo para ti a precios para ti.</p>
          <h2>Tu cuenta Supernova, todo en un mismo lugar.</h2>
          <p class="login-brand__copy">
            Consulta tus pedidos y mantén tus productos favoritos siempre a la mano.
          </p>

          <ul class="login-brand__benefits">
            <li><q-icon name="inventory_2" /> Seguimiento de pedidos</li>
            <li><q-icon name="favorite" /> Favoritos guardados</li>
            <li><q-icon name="local_offer" /> Ofertas para clientes</li>
          </ul>
        </div>

        <div class="login-brand__secure">
          <q-icon name="verified_user" /> Compra segura y datos protegidos
        </div>
      </aside>

      <div class="login-form-wrap">
        <PageBreadcrumbs title="Iniciar sesión" />

        <div class="login-heading">
          <span class="login-heading__icon"><q-icon name="person" /></span>
          <div>
            <p class="login-heading__eyebrow">MI CUENTA</p>
            <h1>Qué gusto verte de nuevo</h1>
            <p>Ingresa tus datos para continuar.</p>
          </div>
        </div>

        <q-form greedy class="login-form" @submit.prevent="submitLogin">
          <q-input
            v-model.trim="email"
            label="Correo electrónico"
            type="email"
            autocomplete="email"
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
            <button type="button" class="login-form__forgot">Olvidé mi contraseña</button>
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
            icon-right="arrow_forward"
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
      </div>
    </section>
  </q-page>
</template>

<script setup>
import { publicAsset } from 'src/utils/publicAsset'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import authApi from 'src/services/authApi'
import { useAuthSession } from 'src/services/authSession'
import { useCart } from 'src/services/cart'

const router = useRouter()
const { loadCurrentUser, setCurrentUser } = useAuthSession()
const { setMergeNotice } = useCart()

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  const user = await loadCurrentUser()

  if (user) {
    router.replace('/panel')
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
    router.push('/')
  } catch (error) {
    errorMessage.value = error?.message || 'No pudimos iniciar sesion. Intenta de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
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
  width: min(980px, 100%);
  min-height: 570px;
  grid-template-columns: minmax(340px, 0.88fr) minmax(460px, 1.12fr);
  overflow: hidden;
  border: 1px solid #f1dce2;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 22px 56px rgba(135, 36, 69, 0.12);
}

.login-brand {
  position: relative;
  display: flex;
  min-width: 0;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  padding: 34px 38px;
  color: #fff;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, 0.08), transparent 42%),
    linear-gradient(150deg, #f65a82 0%, #e23868 58%, #c92658 100%);
}

.login-brand::after {
  content: '';
  position: absolute;
  right: -105px;
  bottom: 52px;
  width: 330px;
  height: 1px;
  background: rgba(255, 255, 255, 0.25);
  box-shadow:
    0 18px 0 rgba(255, 255, 255, 0.12),
    0 36px 0 rgba(255, 255, 255, 0.08);
  transform: rotate(-24deg);
  pointer-events: none;
}

.login-brand__home {
  position: relative;
  z-index: 1;
  display: inline-flex;
  width: fit-content;
  align-items: center;
  gap: 8px;
  padding: 0;
  border: 0;
  background: transparent;
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
}

.login-brand__home:hover {
  color: #fff;
}

.login-brand__content {
  position: relative;
  z-index: 1;
}

.login-brand__logo {
  display: block;
  width: min(230px, 90%);
  max-height: 76px;
  margin-bottom: 8px;
  object-fit: contain;
  object-position: left center;
  filter: brightness(0) invert(1);
}

.login-brand__tagline {
  margin: 0 0 30px;
  color: rgba(255, 255, 255, 0.78);
  font-family: Georgia, serif;
  font-size: 14px;
  font-style: italic;
}

.login-brand h2 {
  max-width: 310px;
  margin: 0 0 14px;
  font-size: clamp(26px, 3vw, 34px);
  font-weight: 850;
  line-height: 1.08;
}

.login-brand__copy {
  max-width: 300px;
  margin: 0;
  color: rgba(255, 255, 255, 0.86);
  font-size: 14px;
  line-height: 1.55;
}

.login-brand__benefits {
  display: grid;
  gap: 13px;
  margin: 28px 0 0;
  padding: 0;
  list-style: none;
}

.login-brand__benefits li,
.login-brand__secure {
  display: flex;
  align-items: center;
  gap: 10px;
}

.login-brand__benefits li {
  font-size: 13px;
  font-weight: 700;
}

.login-brand__benefits .q-icon {
  font-size: 19px;
}

.login-brand__secure {
  position: relative;
  z-index: 1;
  color: rgba(255, 255, 255, 0.82);
  font-size: 11px;
  font-weight: 650;
}

.login-form-wrap {
  display: flex;
  min-width: 0;
  flex-direction: column;
  justify-content: center;
  padding: 46px clamp(38px, 5vw, 66px);
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
    grid-template-columns: minmax(280px, 0.78fr) minmax(420px, 1.22fr);
  }

  .login-brand {
    padding-inline: 28px;
  }
}

@media (min-width: 1024px) {
  .login-page {
    padding: 30px 24px 42px;
    background: #fff;
  }
  .login-shell {
    width: min(940px, 100%);
    min-height: 550px;
    grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
    border-color: #f1dce5;
    border-radius: 24px;
    box-shadow: 0 16px 44px rgba(113, 43, 70, 0.08);
  }
  .login-brand {
    padding: 36px 32px 28px;
    background:
      radial-gradient(
        ellipse 100% 130% at 47% -20%,
        rgba(255, 126, 155, 0.68),
        rgba(255, 90, 131, 0.25) 43%,
        transparent 70%
      ),
      linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  }
  .login-brand::before {
    position: absolute;
    inset: -12% -8% -18%;
    background:
      linear-gradient(
        158deg,
        transparent 0 17%,
        rgba(255, 255, 255, 0.07) 17.4% 20.4%,
        transparent 20.8% 34%,
        rgba(255, 255, 255, 0.05) 34.4% 37%,
        transparent 37.4%
      ),
      linear-gradient(
        20deg,
        transparent 0 70%,
        rgba(255, 255, 255, 0.06) 70.4% 73.5%,
        transparent 74%
      ),
      radial-gradient(
        110% 72% at -2% 10%,
        transparent 63%,
        rgba(255, 255, 255, 0.12) 64%,
        rgba(255, 255, 255, 0.035) 66%,
        transparent 68%
      ),
      radial-gradient(
        115% 76% at 103% 94%,
        transparent 60%,
        rgba(255, 255, 255, 0.1) 61%,
        rgba(255, 255, 255, 0.03) 63%,
        transparent 65%
      );
    content: '';
    pointer-events: none;
  }
  .login-brand::after {
    display: none;
  }
  .login-brand__logo {
    width: 210px;
    margin-bottom: 10px;
  }
  .login-brand__tagline {
    margin-bottom: 32px;
    font-size: 13px;
    color: #fff;
  }
  .login-brand h2 {
    max-width: 280px;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.18;
  }
  .login-brand__copy {
    font-size: 13px;
    line-height: 1.7;
  }
  .login-brand__benefits {
    gap: 14px;
    margin-top: 26px;
  }
  .login-brand__benefits li {
    font-size: 12px;
    font-weight: 500;
  }
  .login-brand__benefits .q-icon {
    padding: 7px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.16);
    font-size: 18px;
  }
  .login-brand__secure {
    margin-top: 36px;
    font-size: 10px;
  }
  .login-form-wrap {
    padding: 28px 38px 30px;
  }
  .login-heading {
    gap: 12px;
    margin: 12px 0 24px;
  }
  .login-heading h1 {
    font-size: 27px;
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
