<template>
  <q-page class="account-page">
    <section class="account-page__shell">
      <PageBreadcrumbs
        :title="$q.screen.lt.md && section ? selectedSection.label : 'Mi cuenta'"
        :back-to="$q.screen.lt.md && section ? '/panel' : '/'"
        :parents="$q.screen.lt.md && section ? [{ label: 'Mi cuenta', to: '/panel' }] : []"
      />
      <div v-if="!section || !$q.screen.lt.md" class="account-page__header">
        <q-avatar size="64px" class="account-page__avatar">
          <span v-if="initials">{{ initials }}</span>
          <q-icon v-else name="person" size="34px" />
        </q-avatar>

        <div>
          <div class="account-page__eyebrow">Mi panel</div>
          <h1>
            <span class="gt-sm">{{ pageTitle }}</span
            ><span class="lt-md">{{ mobileGreeting }}</span>
          </h1>
        </div>
      </div>

      <q-banner v-if="sessionLoading" rounded class="account-page__notice">
        <template #avatar>
          <q-spinner color="primary" size="22px" />
        </template>
        Cargando tu sesion...
      </q-banner>

      <template v-else-if="currentUser">
        <nav v-if="!section" class="account-menu lt-md" aria-label="Opciones de mi cuenta">
          <RouterLink
            v-for="item in accountSections"
            :key="item.id"
            :to="{ path: '/panel', query: { seccion: item.id } }"
            class="account-menu__item"
            :class="{ 'account-menu__item--active': section === item.id }"
            :aria-current="section === item.id ? 'page' : undefined"
          >
            <span class="account-menu__icon"><q-icon :name="item.icon" /></span>
            <span class="account-menu__copy"
              ><strong>{{ item.label }}</strong
              ><small>{{ item.description }}</small></span
            >
            <q-icon name="chevron_right" class="account-menu__arrow" />
          </RouterLink>
        </nav>
        <div v-if="!$q.screen.lt.md" class="account-page__grid">
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

        <section v-if="$q.screen.lt.md && section" class="account-detail-section">
          <q-card class="account-detail">
            <q-card-section class="account-detail__header">
              <q-icon :name="selectedSection?.icon" />
              <h2>{{ selectedSection?.label }}</h2>
            </q-card-section>
            <q-list v-if="section === 'perfil'" separator class="account-detail__details">
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
              <q-item>
                <q-item-section>
                  <q-item-label caption>Tipo de cuenta</q-item-label>
                  <q-item-label>{{ currentUser.tipo || 'Cliente' }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-section v-else class="account-detail__pending">
              <q-icon :name="selectedSection?.icon" />
              <p>Esta sección estará disponible próximamente.</p>
            </q-card-section>
          </q-card>
        </section>

        <div v-if="!section || !$q.screen.lt.md" class="account-page__actions">
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
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthSession } from 'src/services/authSession'

const router = useRouter()
const route = useRoute()
const accountSections = [
  { id: 'perfil', label: 'Mi perfil', description: 'Tus datos personales', icon: 'person_outline' },
  {
    id: 'direcciones',
    label: 'Mis direcciones',
    description: 'Tus lugares de entrega',
    icon: 'location_on',
  },
  { id: 'pedidos', label: 'Mis pedidos', description: 'Consulta tus compras', icon: 'inventory_2' },
]
const section = computed(() =>
  accountSections.some((item) => item.id === route.query.seccion) ? route.query.seccion : '',
)
const selectedSection = computed(() => accountSections.find((item) => item.id === section.value))
const { currentUser, sessionLoading, loadCurrentUser, logout } = useAuthSession()
const logoutLoading = ref(false)
const logoutError = ref('')
const initials = computed(() =>
  [currentUser.value?.nombre, currentUser.value?.apellido]
    .map((value) => Array.from(String(value || '').trim())[0] || '')
    .join('')
    .toLocaleUpperCase('es-MX'),
)
const mobileGreeting = computed(() => {
  const name = String(currentUser.value?.nombre || '')
    .trim()
    .split(/\s+/)[0]
  return name ? `Hola, ${name}` : 'Mi cuenta'
})

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
.account-detail {
  width: 100%;
  overflow: hidden;
  border: 1px solid #f3dce5;
  border-radius: 22px;
}
.account-detail__header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  background: linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
}
.account-detail__header > .q-icon {
  font-size: 24px;
}
.account-detail__header h2 {
  flex: 1;
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  line-height: 1.4;
}
.account-detail__details {
  padding: 8px;
}
.account-detail__details .q-item {
  padding: 14px 12px;
}
.account-detail__details .q-item__label {
  overflow-wrap: anywhere;
}
.account-detail__pending {
  padding: 30px 20px;
  text-align: center;
  color: #907480;
}
.account-detail__pending > .q-icon {
  margin-bottom: 12px;
  color: #e33269;
  font-size: 34px;
}
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
@media (max-width: 1023px) {
  .account-page {
    padding: 10px 16px 110px;
    background: #fff;
  }
  .account-page__header {
    align-items: center;
    gap: 14px;
    margin: 4px 0 20px;
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
  .account-page__header h1 {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
  }
  .account-page__header p {
    margin-top: 5px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 11px;
  }
  .account-page__eyebrow {
    color: #fff;
    font-size: 9px;
    letter-spacing: 0.08em;
  }
  .account-page__avatar {
    width: 48px !important;
    height: 48px !important;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    box-shadow: none;
    font-size: 18px !important;
    font-weight: 700;
  }
  .account-menu {
    display: grid;
    gap: 10px;
    margin-bottom: 20px;
  }
  .account-menu__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 1px solid #f2e0e7;
    border-radius: 16px;
    background: #fff;
    color: #4b3340;
    text-decoration: none;
  }
  .account-menu__item--active {
    border-color: #f2b5c8;
    background: #fff7fa;
  }
  .account-menu__icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #fff0f5;
    color: #e33269;
    font-size: 23px;
  }
  .account-menu__copy {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: 3px;
  }
  .account-menu__copy strong {
    font-size: 14px;
    font-weight: 600;
  }
  .account-menu__copy small {
    color: #9b8390;
    font-size: 11px;
  }
  .account-menu__arrow {
    color: #dc3965;
    font-size: 20px;
  }
  .account-page__card {
    border-color: #f2e0e7;
    border-radius: 16px;
  }
  .account-page__card-title {
    padding: 16px 16px 8px;
    font-size: 14px;
    font-weight: 600;
  }
  .account-page__grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .account-section {
    padding: 24px 16px;
    border: 1px solid #f2e0e7;
    border-radius: 16px;
    text-align: center;
  }
  .account-section > .q-icon {
    color: #e33269;
    font-size: 30px;
  }
  .account-section h2 {
    margin: 10px 0 5px;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.4;
  }
  .account-section p {
    margin: 0;
    color: #9b8390;
    font-size: 12px;
  }
  .account-page__actions {
    flex-direction: column;
    gap: 10px;
    margin-top: 20px;
  }
  .account-page__actions .q-btn {
    min-height: 44px;
    border-radius: 13px;
    font-size: 12px;
    text-transform: none;
  }
  .account-page__actions .q-btn:last-child {
    border: 0;
    color: #9a687b !important;
  }
  .account-page__actions .q-btn:last-child::before {
    border: 0;
  }
  .account-menu__item:focus-visible {
    outline: 2px solid #df2f61;
    outline-offset: 2px;
  }
}
</style>
