<template>
  <main class="order-pdf-page">
    <div v-if="loading" class="order-pdf-page__loading">
      <LoadingSkeleton variant="list" :count="4" label="Preparando el PDF de tu pedido..." />
    </div>
    <div v-else-if="errorMessage" class="order-pdf-page__error" role="alert">
      <q-icon name="description" aria-hidden="true" />
      <p>{{ errorMessage }}</p>
      <q-btn
        v-if="sessionExpired && !isGuest"
        unelevated
        no-caps
        color="primary"
        label="Iniciar sesión"
        to="/login"
      /><q-btn
        v-else-if="isGuest && (invalidLink || sessionExpired)"
        unelevated
        no-caps
        color="primary"
        label="Volver al checkout"
        to="/checkout"
      /><q-btn
        v-else-if="invalidLink"
        unelevated
        no-caps
        color="primary"
        label="Ir a Mis pedidos"
        to="/panel?seccion=pedidos"
      /><q-btn v-else unelevated no-caps color="primary" label="Reintentar" @click="loadPdf" />
    </div>
    <section v-else-if="isMobile" class="order-pdf-page__download" aria-label="Descargar pedido">
      <div class="order-pdf-page__file-icon"><q-icon name="picture_as_pdf" /></div>
      <h1>Tu pedido está listo</h1>
      <p>Descarga el PDF para verlo, guardarlo o compartirlo desde tu celular.</p>
      <div class="order-pdf-page__file">
        <q-icon name="description" color="primary" size="28px" />
        <div><strong>pedido.pdf</strong><span>Comprobante de tu pedido</span></div>
      </div>
      <q-btn
        tag="a"
        :href="pdfSource"
        download="pedido.pdf"
        unelevated
        no-caps
        color="primary"
        icon="download"
        label="Descargar mi pedido"
      />
    </section>
    <template v-else>
      <p v-if="printHint" class="order-pdf-page__hint" role="status">{{ printHint }}</p>
      <iframe
        ref="pdfFrame"
        :src="pdfSource"
        title="PDF de tu pedido"
        class="order-pdf-page__document"
        @load="onPdfLoaded"
      />
    </template>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { getOrderPdf } from 'src/services/authApi'
import { getGuestOrderPdf, getEmailOrderPdf } from 'src/services/checkoutApi'

const route = useRoute()
const $q = useQuasar()
const isMobile = computed(() => $q.screen.lt.sm)
const isEmail = computed(() => route.name === 'pedido-correo-pdf')
const orderToken = computed(() =>
  String((isEmail.value ? route.query.token : route.params.token) || ''),
)
const isGuest = computed(() => isEmail.value || route.name === 'pedido-invitado-pdf')
const pdfFrame = ref(null)
const printHint = ref('')
let printTimer
let autoPrinted = false
const pdfSource = ref('')
const loading = ref(true)
const errorMessage = ref('')
const sessionExpired = ref(false)
const invalidLink = ref(false)
let request
function releasePdf() {
  if (pdfSource.value) URL.revokeObjectURL(pdfSource.value)
  pdfSource.value = ''
}
async function loadPdf() {
  request?.abort()
  const current = new AbortController()
  request = current
  releasePdf()
  loading.value = true
  errorMessage.value = ''
  sessionExpired.value = false
  invalidLink.value = false
  printHint.value = ''
  autoPrinted = false
  clearTimeout(printTimer)
  try {
    let pdf
    if (isEmail.value) pdf = await getEmailOrderPdf(orderToken.value, { signal: current.signal })
    else if (isGuest.value)
      pdf = await getGuestOrderPdf(orderToken.value, route.params.cliente, {
        signal: current.signal,
      })
    else pdf = await getOrderPdf(orderToken.value, { signal: current.signal })
    if (!current.signal.aborted) pdfSource.value = URL.createObjectURL(pdf)
  } catch (error) {
    if (!current.signal.aborted) {
      sessionExpired.value = error.status === 401
      invalidLink.value = error.status === 404 || error.status === 410
      errorMessage.value =
        sessionExpired.value && !isGuest.value
          ? 'Tu sesión terminó. Inicia sesión para abrir tu pedido.'
          : error.message || 'No pudimos abrir este pedido.'
    }
  } finally {
    if (request === current) loading.value = false
  }
}
function printPdf() {
  try {
    const frame = pdfFrame.value?.contentWindow
    if (!frame) return
    frame.focus()
    frame.print()
  } catch {
    printHint.value = 'Usa la opción de imprimir o descargar del visor de PDF.'
  }
}
function onPdfLoaded() {
  if (route.query.imprimir !== '1' || autoPrinted) return
  autoPrinted = true
  printTimer = setTimeout(printPdf, 600)
}
watch(() => [orderToken.value, route.params.cliente, isGuest.value], loadPdf, { immediate: true })
onBeforeUnmount(() => {
  request?.abort()
  clearTimeout(printTimer)
  releasePdf()
})
</script>

<style scoped>
.order-pdf-page {
  display: flex;
  flex-direction: column;
  height: 100dvh;
  min-height: 320px;
  background: #f4f2f3;
}
.order-pdf-page__hint {
  margin: 0;
  padding: 8px 18px;
  color: #666;
  background: #fff;
  font-size: 12px;
}
.order-pdf-page__download {
  width: calc(100% - 32px);
  max-width: 380px;
  margin: auto;
  padding: 28px 22px;
  border: 1px solid #e7e7ec;
  border-radius: 20px;
  background: #fff;
  text-align: center;
}
.order-pdf-page__file-icon {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  margin: 0 auto 18px;
  border-radius: 18px;
  background: #fff0f5;
  color: #ed326b;
  font-size: 36px;
}
.order-pdf-page__download h1 {
  margin: 0 0 10px;
  color: #30343b;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;
}
.order-pdf-page__download p {
  margin: 0 0 22px;
  color: #73717b;
  font-size: 14px;
  line-height: 1.5;
}
.order-pdf-page__file {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding: 14px;
  border-radius: 12px;
  background: #f7f7f9;
  text-align: left;
}
.order-pdf-page__file strong,
.order-pdf-page__file span {
  display: block;
}
.order-pdf-page__file span {
  margin-top: 3px;
  color: #777681;
  font-size: 12px;
}
.order-pdf-page__download .q-btn {
  width: 100%;
  min-height: 46px;
  border-radius: 10px;
}
.order-pdf-page__document {
  display: block;
  flex: 1;
  width: 100%;
  min-height: 0;
  border: 0;
  background: #525659;
}
.order-pdf-page__loading {
  width: min(720px, 100%);
  margin: 20px auto;
  padding: 16px;
}
.order-pdf-page__error {
  align-self: center;
  max-width: 440px;
  margin: 48px 16px;
  padding: 28px;
  border: 1px solid #eadfe4;
  border-radius: 14px;
  background: #fff;
  color: #88717d;
  text-align: center;
  font-size: 13px;
}
.order-pdf-page__error > .q-icon {
  color: #df2f61;
  font-size: 34px;
}
.order-pdf-page__error p {
  margin: 16px 0;
  line-height: 1.6;
}
</style>
