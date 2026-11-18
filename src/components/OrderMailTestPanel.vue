<template>
  <section class="mail-test" aria-label="Prueba del correo del pedido">
    <header>
      <q-icon name="mark_email_read" aria-hidden="true" />
      <div>
        <h2>Prueba de correo</h2>
        <p>Comprueba el envío al correo de los datos del cliente antes de registrar el pedido.</p>
      </div>
    </header>
    <p class="mail-test__recipient">
      Destino:
      <strong>{{ customer.correo?.trim() || 'Completa el correo en los datos del pedido' }}</strong>
    </p>
    <q-btn
      outline
      no-caps
      color="primary"
      icon="send"
      label="Enviar correo de prueba"
      :loading="sending"
      :disable="disabled || sending"
      @click="send"
    />
    <div v-if="error" class="mail-test__result is-error" role="alert">{{ error }}</div>
    <div
      v-if="report"
      class="mail-test__result"
      :class="{ 'is-error': !report.accepted }"
      role="status"
    >
      <strong>{{ report.accepted ? 'Aceptado por el servidor' : 'No se pudo enviar' }}</strong>
      <p>{{ report.message }}</p>
      <dl>
        <div>
          <dt>Destinatario</dt>
          <dd>{{ recipient }}</dd>
        </div>
        <div>
          <dt>Transporte</dt>
          <dd>PHP mail()</dd>
        </div>
        <div>
          <dt>Remitente</dt>
          <dd>{{ report.from }}</dd>
        </div>
        <div>
          <dt>Diagnóstico</dt>
          <dd>{{ report.code }}</dd>
        </div>
        <div>
          <dt>Referencia</dt>
          <dd>{{ report.reference }}</dd>
        </div>
        <div>
          <dt>Envío al procesar</dt>
          <dd>{{ report.automatic_enabled ? 'Activado' : 'Desactivado' }}</dd>
        </div>
      </dl>
      <p v-if="report.accepted">
        Si no llega, revisa spam y el registro de entregas del hosting. La aceptación de PHP por sí
        sola no garantiza la llegada al buzón.
      </p>
    </div>
  </section>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue'
import { sendOrderEmailTest } from 'src/services/checkoutApi'

const props = defineProps({ customer: { type: Object, required: true }, disabled: Boolean })
const emit = defineEmits(['busy-change'])
const sending = ref(false)
const error = ref('')
const report = ref(null)
const recipient = ref('')
let request
let disposed = false
async function send() {
  if (sending.value || props.disabled) return
  error.value = ''
  report.value = null
  const correo = props.customer.correo?.trim() || ''
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
    error.value = 'Escribe un correo válido en los datos del pedido.'
    return
  }
  sending.value = true
  emit('busy-change', true)
  request = new AbortController()
  const timeout = setTimeout(() => request.abort(), 25000)
  try {
    const result = await sendOrderEmailTest(
      { correo, nombre: props.customer.nombre?.trim() || '' },
      { signal: request.signal },
    )
    if (!disposed) {
      report.value = result
      recipient.value = correo
    }
  } catch (failure) {
    if (!disposed)
      error.value =
        failure.name === 'AbortError'
          ? 'La prueba tardó demasiado. Revisa tu correo y los registros antes de volver a enviarla.'
          : failure.message
  } finally {
    clearTimeout(timeout)
    if (!disposed) {
      sending.value = false
      emit('busy-change', false)
    }
  }
}
onBeforeUnmount(() => {
  disposed = true
  request?.abort()
  emit('busy-change', false)
})
</script>

<style scoped>
.mail-test {
  padding: 18px;
  border: 1px dashed #e5acbe;
  border-radius: 14px;
  background: #fff;
  min-width: 0;
}
.mail-test header {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
.mail-test header > .q-icon {
  font-size: 24px;
  color: #e72e68;
}
.mail-test h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}
.mail-test p {
  margin: 6px 0 12px;
  font-size: 12px;
  line-height: 1.5;
  color: #796974;
}
.mail-test__recipient {
  overflow-wrap: anywhere;
}
.mail-test .q-btn {
  border-radius: 8px;
  font-size: 12px;
}
.mail-test__result {
  margin-top: 14px;
  padding: 12px;
  border-radius: 9px;
  background: #eff8f2;
  color: #17653c;
  font-size: 12px;
  overflow-wrap: anywhere;
}
.mail-test__result.is-error {
  background: #fff1f4;
  color: #a5284c;
}
.mail-test__result p {
  color: inherit;
}
.mail-test dl {
  margin: 10px 0 0;
}
.mail-test dl > div {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 10px;
  margin: 4px 0;
}
.mail-test dt {
  font-weight: 650;
  min-width: 105px;
}
.mail-test dd {
  margin: 0;
}
</style>
