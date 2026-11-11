<template>
  <q-page class="register-page">
    <div class="register-page__shell">
      <PageBreadcrumbs title="Crear cuenta" back-to="/login" :parents="[{ label: 'Iniciar sesión', to: '/login' }]" />
      <div class="register-page__card">
        <div class="register-page__header">
          <div>
            <div class="text-h5 text-white">Crear cuenta</div>
            <div class="text-subtitle2 text-white text-opacity-80 q-mt-sm">
              Completa tus datos para acceder a la tienda.
            </div>
          </div>
          <q-avatar size="56px" class="bg-white text-primary shadow-2">
            <q-icon name="person_add" size="28px" />
          </q-avatar>
        </div>

        <q-form @submit.prevent="submitRegister" class="register-page__form">
          <q-input
            v-model="name"
            label="Nombre completo"
            outlined
            dense
            clearable
            stack-label
            class="q-mb-md"
          />
          <q-input
            v-model="email"
            label="Correo electrónico"
            type="email"
            outlined
            dense
            clearable
            stack-label
            class="q-mb-md"
          />
          <q-input
            v-model="password"
            label="Contraseña"
            type="password"
            outlined
            dense
            clearable
            stack-label
            class="q-mb-md"
          />
          <q-input
            v-model="confirmPassword"
            label="Confirmar contraseña"
            type="password"
            outlined
            dense
            clearable
            stack-label
            class="q-mb-lg"
          />

          <q-btn
            type="submit"
            unelevated
            color="primary"
            label="Crear cuenta"
            icon="person_add"
            class="full-width"
          />

          <div class="register-page__footer text-center q-mt-md">
            <span class="text-white text-opacity-70">¿Ya tienes cuenta?</span>
            <q-btn
              flat
              color="white"
              label="Ingresar"
              @click="$router.push('/login')"
              class="register-page__login-btn"
            />
          </div>
        </q-form>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

function submitRegister() {
  if (!name.value || !email.value || !password.value || !confirmPassword.value) {
    return
  }
  if (password.value !== confirmPassword.value) {
    return
  }
  router.push('/login')
}
</script>

<style scoped lang="scss">
.register-page {
  min-height: calc(100vh - 64px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  background: linear-gradient(135deg, #3f0f25 0%, #5a113f 45%, #3b0c29 100%);
}

.register-page__shell {
  width: 100%;
  max-width: 520px;
}

.register-page__card {
  border-radius: 24px;
  padding: 32px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 28px 80px rgba(0, 0, 0, 0.28);
}

.register-page__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}

.register-page__form {
  display: flex;
  flex-direction: column;
}

.register-page__footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.register-page__login-btn {
  text-transform: none;
  font-weight: 700;
}
</style>
