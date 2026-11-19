<template>
  <q-page class="registration-page">
    <PageBreadcrumbs
      title="Crear cuenta"
      back-to="/login"
      :parents="[{ label: 'Iniciar sesión', to: '/login' }]"
    />
    <section v-if="created" class="registration-success">
      <span class="registration-success__icon"><q-icon name="mark_email_unread" /></span>
      <p class="registration-eyebrow">UN PASO MÁS</p>
      <h1 ref="successHeading" tabindex="-1">Confirma tu correo</h1>
      <p>
        Tu cuenta está creada. Revisa <strong>{{ registeredEmail }}</strong> y abre el enlace de
        confirmación para habilitar el inicio de sesión.
      </p>
      <q-banner v-if="mailPending" rounded class="registration-error" role="alert"
        >Tu cuenta quedó guardada, pero no pudimos enviar la confirmación. Puedes solicitar otro
        envío abajo.</q-banner
      >
      <p class="registration-muted">
        El enlace vence en 24 horas. Revisa tu bandeja de entrada y spam.
      </p>
      <q-btn
        unelevated
        no-caps
        color="primary"
        label="Ir a iniciar sesión"
        :to="{ path: '/login', query: { correo: registeredEmail } }"
      />
      <ResendConfirmation :email="registeredEmail" />
    </section>
    <div v-else class="registration-layout">
      <aside class="registration-brand sn-header--premium">
        <img
          v-image-skeleton
          :src="publicAsset('img/logob.png')"
          alt="Importadora Supernova"
          width="220"
        />
        <p class="registration-brand__motto">De todo para ti,<br />a precios para ti.</p>
        <h2>Tu próximo favorito<br />empieza aquí.</h2>
        <p>Crea tu cuenta y ten tus compras siempre a la mano.</p>
        <ul>
          <li><q-icon name="inventory_2" /> Consulta tus pedidos</li>
          <li><q-icon name="location_on" /> Administra tus direcciones</li>
          <li><q-icon name="shopping_bag" /> Conserva tu carrito</li>
        </ul>
      </aside>
      <section class="registration-form-wrap">
        <p class="registration-eyebrow">BIENVENIDO A SUPERNOVA</p>
        <h1>Crea tu cuenta</h1>
        <p class="registration-muted">
          Completa tus datos. Después te enviaremos un enlace para confirmar tu correo.
        </p>
        <q-form greedy @submit.prevent="submit">
          <fieldset v-for="group in groups" :key="group.title" class="registration-group">
            <legend>{{ group.title }}</legend>
            <div class="registration-fields">
              <template v-for="field in group.fields" :key="field.key">
                <q-select
                  v-if="field.key === 'estado'"
                  v-model="form.estado"
                  name="estado"
                  :options="registrationStates"
                  label="Estado *"
                  outlined
                  dense
                  options-dense
                  hide-bottom-space
                  :disable="busy"
                  :rules="[(value) => registrationStates.includes(value) || 'Selecciona un estado']"
                />
                <q-input
                  v-else
                  v-model="form[field.key]"
                  :name="field.key"
                  :label="field.label"
                  :type="field.type || 'text'"
                  :autocomplete="field.autocomplete"
                  :maxlength="field.max || 250"
                  :inputmode="field.key === 'codigop' ? 'numeric' : undefined"
                  :class="{ 'registration-full': field.full }"
                  outlined
                  dense
                  hide-bottom-space
                  :rules="fieldRules(field.key)"
                  :disable="busy"
                />
              </template>
            </div>
          </fieldset>
          <fieldset class="registration-group">
            <legend>Protege tu cuenta</legend>
            <div class="registration-fields">
              <q-input
                v-model="form.password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                label="Contraseña *"
                autocomplete="new-password"
                outlined
                dense
                hide-bottom-space
                :disable="busy"
                :rules="[passwordRule]"
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
                v-model="form.password_confirmation"
                name="password_confirmation"
                type="password"
                label="Confirmar contraseña *"
                autocomplete="new-password"
                outlined
                dense
                hide-bottom-space
                :disable="busy"
                :rules="[
                  (value) => (!!value && value === form.password) || 'Las contraseñas no coinciden',
                ]"
              />
            </div>
            <p class="registration-hint">
              Usa al menos 8 caracteres. Tu contraseña se guardará protegida.
            </p>
          </fieldset>
          <q-banner v-if="error" rounded class="registration-error" role="alert">{{
            error
          }}</q-banner>
          <q-btn
            type="submit"
            unelevated
            no-caps
            color="primary"
            label="Crear cuenta"
            icon-right="arrow_forward"
            class="registration-submit"
            :loading="busy"
            :disable="busy"
          />
          <p class="registration-login">
            ¿Ya tienes una cuenta? <RouterLink to="/login">Inicia sesión</RouterLink>
          </p>
        </q-form>
        <ResendConfirmation v-if="emailExists" :email="form.correo" />
      </section>
    </div>
  </q-page>
</template>
<script setup>
import { nextTick, reactive, ref } from 'vue'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import ResendConfirmation from 'src/components/ResendConfirmation.vue'
import { registerAccount } from 'src/services/authApi'
import { registrationStates, emptyRegistration, registrationPayload } from 'src/utils/registration'
import { publicAsset } from 'src/utils/publicAsset'
const form = reactive(emptyRegistration())
const busy = ref(false)
const showPassword = ref(false)
const created = ref(false)
const registeredEmail = ref('')
const mailPending = ref(false)
const error = ref('')
const emailExists = ref(false)
const successHeading = ref(null)
const groups = [
  {
    title: 'Tus datos',
    fields: [
      { key: 'nombre', label: 'Nombre *', autocomplete: 'given-name' },
      { key: 'apellido', label: 'Apellidos *', autocomplete: 'family-name' },
      { key: 'correo', label: 'Correo electrónico *', type: 'email', autocomplete: 'email' },
      { key: 'telefono', label: 'Teléfono con lada *', type: 'tel', autocomplete: 'tel', max: 25 },
    ],
  },
  {
    title: 'Tu dirección',
    fields: [
      {
        key: 'direccion',
        label: 'Calle, número exterior e interior *',
        autocomplete: 'address-line1',
        full: true,
        max: 500,
      },
      { key: 'colonia', label: 'Colonia *', autocomplete: 'address-line2' },
      { key: 'codigop', label: 'Código postal *', autocomplete: 'postal-code', max: 5 },
      { key: 'estado', label: 'Estado *' },
      { key: 'ciudad', label: 'Ciudad o municipio *', autocomplete: 'address-level2' },
      { key: 'rfc', label: 'RFC (opcional)', max: 13, full: true },
    ],
  },
]
const required = (value) => !!String(value || '').trim() || 'Completa este campo'
const passwordRule = (value) =>
  (typeof value === 'string' &&
    [...value].length >= 8 &&
    new TextEncoder().encode(value).length <= 72) ||
  'Usa al menos 8 caracteres y evita una contraseña demasiado larga'
function fieldRules(key) {
  if (key === 'rfc')
    return [
      (value) =>
        !value ||
        /^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/.test(value.trim().toUpperCase()) ||
        'Revisa el RFC',
    ]
  if (key === 'correo')
    return [
      (value) =>
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value?.trim() || '') || 'Escribe un correo válido',
    ]
  if (key === 'codigop') return [(value) => /^[0-9]{5}$/.test(value || '') || 'Ingresa 5 dígitos']
  if (key === 'telefono')
    return [
      (value) =>
        (/^\+?[0-9() .-]{7,25}$/.test(value || '') &&
          value.replace(/\D/g, '').length >= 7 &&
          value.replace(/\D/g, '').length <= 15) ||
        'Escribe un teléfono válido con lada',
    ]
  return [required]
}
async function submit() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  emailExists.value = false
  const payload = registrationPayload(form)
  try {
    const result = await registerAccount(payload)
    if (result?.verification_required !== true)
      throw new Error(
        'No pudimos confirmar el registro. Intenta iniciar sesión o solicita otra confirmación.',
      )
    registeredEmail.value = payload.correo
    mailPending.value = result.email_status !== 'accepted'
    created.value = true
    form.password = ''
    form.password_confirmation = ''
    await nextTick()
    successHeading.value?.focus()
  } catch (failure) {
    error.value = failure.message
    emailExists.value = failure.code === 'EMAIL_EXISTS'
  } finally {
    busy.value = false
  }
}
</script>
<style scoped lang="scss">
.registration-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px 24px 60px;
}
.registration-layout {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 36px;
  margin-top: 20px;
  align-items: start;
}
.registration-brand {
  border-radius: 18px;
  padding: 32px 26px;
  color: #fff;
  position: sticky;
  top: 160px;
  img {
    display: block;
    width: 220px;
    max-width: 100%;
    height: auto;
  }
  h2 {
    font-size: 28px;
    line-height: 1.25;
    font-weight: 700;
    margin: 30px 0 14px;
  }
  p {
    font-size: 13px;
    line-height: 1.7;
  }
  .registration-brand__motto {
    font-size: 16px;
    line-height: 1.5;
  }
  ul {
    list-style: none;
    padding: 0;
    margin: 28px 0 0;
    display: grid;
    gap: 15px;
    font-size: 13px;
  }
  li {
    display: flex;
    gap: 9px;
    align-items: center;
  }
  .q-icon {
    font-size: 21px;
  }
}
.registration-form-wrap {
  padding: 8px 0;
  min-width: 0;
}
.registration-eyebrow {
  font-size: 10px;
  letter-spacing: 1.2px;
  color: #d82f62;
  font-weight: 750;
  margin: 0 0 7px;
}
h1 {
  font-size: 29px;
  font-weight: 750;
  line-height: 1.25;
  margin: 0 0 10px;
}
.registration-muted {
  color: #83737e;
  font-size: 13px;
  line-height: 1.7;
  margin: 0 0 24px;
}
.registration-group {
  border: 0;
  border-bottom: 1px solid #f1e7ed;
  padding: 0 0 22px;
  margin: 22px 0;
  min-width: 0;
}
.registration-group legend {
  padding: 0 0 14px;
  font-size: 14px;
  font-weight: 700;
  color: #45323d;
}
.registration-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  :deep(.q-field) {
    min-width: 0;
  }
  :deep(.q-field__control) {
    border-radius: 9px;
  }
}
.registration-full {
  grid-column: 1/-1;
}
.registration-hint {
  font-size: 11px;
  color: #8c7d87;
  line-height: 1.6;
  margin: 10px 0 0;
}
.registration-submit {
  width: 100%;
  min-height: 44px;
  border-radius: 10px;
}
.registration-error {
  background: #fff0f4;
  color: #a0284a;
  font-size: 13px;
  margin-bottom: 16px;
}
.registration-login {
  text-align: center;
  font-size: 13px;
  color: #83737e;
  margin: 20px 0 0;
  a {
    color: #d62d61;
    font-weight: 650;
    text-decoration: none;
  }
}
.registration-success {
  max-width: 540px;
  margin: 30px auto;
  padding: 30px;
  border: 1px solid #efdee6;
  border-radius: 18px;
  text-align: center;
  > p {
    font-size: 13px;
    line-height: 1.7;
    overflow-wrap: anywhere;
  }
  .registration-eyebrow {
    font-size: 10px;
  }
  .registration-success__icon {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    border-radius: 20px;
    background: #fff0f5;
    color: #e9316a;
    font-size: 32px;
  }
  .resend-confirmation {
    text-align: left;
  }
}
@media (max-width: 800px) {
  .registration-layout {
    grid-template-columns: 1fr;
    margin-top: 12px;
  }
  .registration-brand {
    display: none;
  }
}
@media (max-width: 480px) {
  .registration-page {
    padding: 16px 16px 90px;
  }
  h1 {
    font-size: 25px;
  }
  .registration-fields {
    gap: 12px;
    :deep(.q-field__label) {
      font-size: 12px;
    }
  }
  .registration-success {
    padding: 24px 18px;
  }
}
</style>
