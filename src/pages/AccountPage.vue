<template>
  <q-page class="account-page">
    <section class="account-page__shell">
      <div class="account-page__header">
        <q-avatar size="64px" class="account-page__avatar">
          <q-icon name="person" size="34px" />
        </q-avatar>

        <div>
          <div class="account-page__eyebrow">Mi panel</div>
          <h1>{{ pageTitle }}</h1>
          <p>Desde aqui puedes revisar tu cuenta.</p>
        </div>
      </div>

      <q-banner v-if="sessionLoading" rounded class="account-page__notice">
        <template #avatar>
          <q-spinner color="primary" size="22px" />
        </template>
        Cargando tu sesion...
      </q-banner>

      <template v-else-if="currentUser">
        <div class="account-page__grid">
          <q-card flat bordered class="account-page__card">
            <div class="account-page__card-title">
              <q-icon name="account_circle" />
              Datos de cuenta
            </div>

            <q-list separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nombre</q-item-label>
                  <q-item-label>{{ fullName }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Correo</q-item-label>
                  <q-item-label>{{ currentUser.correo }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>

          <q-card flat bordered class="account-page__card">
            <div class="account-page__card-title">
              <q-icon name="verified_user" />
              Estado
            </div>

            <q-list separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Tipo</q-item-label>
                  <q-item-label>{{ currentUser.tipo || 'Cliente' }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>

        <div class="account-page__actions">
          <q-btn unelevated color="primary" icon="storefront" label="Seguir comprando" to="/" />
          <q-btn
            outline
            color="negative"
            icon="logout"
            label="Cerrar sesion"
            :loading="logoutLoading"
            :disable="logoutLoading"
            @click="handleLogout"
          />
        </div>

        <q-banner v-if="logoutError" rounded class="account-page__error">
          <template #avatar>
            <q-icon name="error_outline" color="negative" />
          </template>
          {{ logoutError }}
        </q-banner>
      </template>

      <q-banner v-else rounded class="account-page__notice">
        <template #avatar>
          <q-icon name="lock" color="primary" />
        </template>
        Redirigiendo al login...
      </q-banner>
    </section>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthSession } from 'src/services/authSession'

const router = useRouter()
const { currentUser, sessionLoading, loadCurrentUser, logout } = useAuthSession()
const logoutLoading = ref(false)
const logoutError = ref('')

const fullName = computed(() => {
  const user = currentUser.value
  const name = [user?.nombre, user?.apellido].filter(Boolean).join(' ').trim()

  return name || 'cliente'
})

const pageTitle = computed(() => {
  return currentUser.value ? `Hola, ${fullName.value}!` : 'Mi panel'
})

onMounted(async () => {
  const user = await loadCurrentUser()

  if (!user) {
    router.replace('/login')
  }
})

async function handleLogout() {
  logoutError.value = ''
  logoutLoading.value = true

  try {
    await logout()
    router.replace('/login')
  } catch (error) {
    logoutError.value = error?.message || 'No pudimos cerrar sesion. Intenta de nuevo.'
  } finally {
    logoutLoading.value = false
  }
}
</script>

<style scoped lang="scss">
.account-page {
  min-height: calc(100vh - 64px);
  padding: clamp(24px, 5vw, 56px) 16px;
  background: linear-gradient(180deg, #fff 0%, #fff8fb 100%);
}

.account-page__shell {
  width: min(1040px, 100%);
  margin: 0 auto;
}

.account-page__header {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 28px;

  h1 {
    margin: 0;
    color: #2d1b2e;
    font-size: clamp(1.65rem, 3vw, 2.35rem);
    font-weight: 850;
    line-height: 1.1;
  }

  p {
    margin: 8px 0 0;
    color: #76576a;
    font-size: 1rem;
  }
}

.account-page__avatar {
  flex: 0 0 auto;
  color: #c2185b;
  background: #fff0f5;
  box-shadow: inset 0 0 0 1px rgba(194, 24, 91, 0.15);
}

.account-page__eyebrow {
  margin-bottom: 6px;
  color: #c2185b;
  font-size: 0.76rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.account-page__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.account-page__card {
  border-radius: 8px;
  background: #fff;
}

.account-page__card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 18px 18px 8px;
  color: #2d1b2e;
  font-size: 1rem;
  font-weight: 850;

  .q-icon {
    color: #c2185b;
    font-size: 22px;
  }
}

.account-page__actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 22px;
  flex-wrap: wrap;
}

.account-page__notice {
  color: #59354a;
  background: #fff;
  border: 1px solid rgba(194, 24, 91, 0.13);
}

.account-page__error {
  margin-top: 16px;
  color: #7a1f2f;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

@media (max-width: 700px) {
  .account-page__header {
    align-items: flex-start;
  }

  .account-page__grid {
    grid-template-columns: 1fr;
  }

  .account-page__actions {
    justify-content: stretch;

    .q-btn {
      flex: 1 1 100%;
    }
  }
}
</style>
