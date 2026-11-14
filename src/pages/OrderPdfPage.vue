<template>
  <main class="order-pdf-page">
    <div v-if="loading" class="order-pdf-page__loading">
      <LoadingSkeleton variant="list" :count="4" label="Preparando el PDF de tu pedido..." />
    </div>
    <div v-else-if="errorMessage" class="order-pdf-page__error" role="alert">
      <q-icon name="description" aria-hidden="true" />
      <p>{{ errorMessage }}</p>
      <q-btn
        v-if="sessionExpired"
        unelevated
        no-caps
        color="primary"
        label="Iniciar sesión"
        to="/login"
      /><q-btn
        v-else-if="invalidLink"
        unelevated
        no-caps
        color="primary"
        label="Ir a Mis pedidos"
        to="/panel?seccion=pedidos"
      /><q-btn v-else unelevated no-caps color="primary" label="Reintentar" @click="loadPdf" />
    </div>
    <iframe v-else :src="pdfSource" title="PDF de tu pedido" class="order-pdf-page__document" />
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getOrderPdf } from 'src/services/authApi'

const route = useRoute()
const orderToken = computed(() => String(route.params.token || ''))
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
  try {
    const pdf = await getOrderPdf(orderToken.value, { signal: current.signal })
    if (!current.signal.aborted) pdfSource.value = URL.createObjectURL(pdf)
  } catch (error) {
    if (!current.signal.aborted) {
      sessionExpired.value = error.status === 401
      invalidLink.value = error.status === 404 || error.status === 410
      errorMessage.value = sessionExpired.value
        ? 'Tu sesión terminó. Inicia sesión para abrir tu pedido.'
        : error.message || 'No pudimos abrir este pedido.'
    }
  } finally {
    if (request === current) loading.value = false
  }
}
watch(orderToken, loadPdf, { immediate: true })
onBeforeUnmount(() => {
  request?.abort()
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
