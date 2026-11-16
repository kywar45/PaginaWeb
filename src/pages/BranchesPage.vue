<template>
  <q-page class="branches-page">
    <PageBreadcrumbs title="Sucursales" />
    <LoadingSkeleton v-if="loading" variant="list" :count="3" label="Cargando sucursales..." />
    <div v-else-if="errorMessage" class="branches-status" role="alert">
      <p>{{ errorMessage }}</p>
      <q-btn no-caps color="primary" label="Reintentar" @click="loadBranches" />
    </div>
    <div v-else-if="branches.length" class="branches-grid">
      <article v-for="branch in branches" :key="branch.id" class="branch-card">
        <header class="branch-card__header">
          <div class="branch-card__visual" aria-hidden="true">
            <svg class="branch-store" viewBox="0 0 160 160" fill="none">
              <ellipse cx="80" cy="137" rx="57" ry="7" fill="#a91f49" opacity=".12" />
              <rect x="28" y="53" width="104" height="80" rx="5" fill="#fff" />
              <rect x="25" y="39" width="110" height="28" rx="5" fill="#a91f49" />
              <text
                x="80"
                y="57"
                text-anchor="middle"
                fill="white"
                font-family="Arial, sans-serif"
                font-size="10"
                font-weight="700"
                letter-spacing="1"
              >
                SUPERNOVA
              </text>
              <path d="M28 67h104l8 17H20l8-17Z" fill="#fff" />
              <path
                d="m44 67-4 17H25l7-17h12Zm29 0-1 17H56l3-17h14Zm28 0 3 17H88l-1-17h14Zm27 0 7 17h-15l-5-17h13Z"
                fill="#f43f6f"
              />
              <rect x="38" y="91" width="37" height="32" rx="2" fill="#f8dce6" />
              <path d="m41 118 27-24M52 123l20-18" stroke="white" stroke-width="3" opacity=".8" />
              <rect x="85" y="91" width="36" height="42" rx="2" fill="#f8dce6" />
              <path d="M103 92v40" stroke="#fff" stroke-width="2" />
              <path d="M99 111v7m8-7v7" stroke="#bd365d" stroke-width="2" stroke-linecap="round" />
              <path d="M24 134h112" stroke="#fff" stroke-width="5" stroke-linecap="round" />
            </svg>
            <span class="branch-card__pin"><q-icon name="location_on" /></span>
          </div>
          <div class="branch-card__identity">
            <span class="branch-card__eyebrow">SUPERNOVA</span>
            <h2>{{ branch.nombre }}</h2>
          </div>
          <span class="branch-status"><span aria-hidden="true"></span>Sucursal activa</span>
        </header>
        <div class="branch-card__body">
          <div class="branch-detail branch-detail--address">
            <q-icon name="location_on" aria-hidden="true" />
            <div>
              <span class="branch-detail__label">Dirección</span>
              <address>{{ branch.direccion }}</address>
            </div>
          </div>
          <div class="branch-card__contact">
            <div v-if="branch.horario_atencion" class="branch-detail">
              <q-icon name="schedule" aria-hidden="true" />
              <div>
                <span class="branch-detail__label">Horario de atención</span>
                <p class="branch-hours">{{ branch.horario_atencion }}</p>
              </div>
            </div>
            <div v-if="branch.telefono" class="branch-detail">
              <q-icon name="call" aria-hidden="true" />
              <div>
                <span class="branch-detail__label">Teléfono</span
                ><a v-if="branch.telefono_url" :href="branch.telefono_url" class="branch-phone">{{
                  displayPhone(branch.telefono)
                }}</a>
                <p v-else>{{ branch.telefono }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="branch-actions">
          <a
            :href="branch.google_maps_url"
            target="_blank"
            rel="noopener noreferrer"
            class="branch-action branch-action--map"
            :aria-label="`Cómo llegar a ${branch.nombre} (abre Google Maps)`"
            ><q-icon name="directions" aria-hidden="true" /> Cómo llegar
            <q-icon name="north_east" size="16px" aria-hidden="true"
          /></a>
          <a
            v-if="branch.telefono_url"
            :href="branch.telefono_url"
            class="branch-action branch-action--call"
            :aria-label="`Llamar a ${branch.nombre}`"
            ><q-icon name="call" aria-hidden="true" /> Llamar</a
          >
        </div>
      </article>
    </div>
    <p v-else class="branches-status">Pronto encontrarás nuestras sucursales aquí.</p>
  </q-page>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { getBranches } from 'src/services/branchApi'

function displayPhone(value) {
  const digits = String(value).replace(/\D/g, '')
  const local = digits.length === 12 && digits.startsWith('52') ? digits.slice(2) : digits
  if (local.length !== 10) return value
  return `${digits.length === 12 ? '+52 ' : ''}${local.slice(0, 2)} ${local.slice(2, 6)} ${local.slice(6)}`
}

const branches = ref([])
const loading = ref(true)
const errorMessage = ref('')
let request

async function loadBranches() {
  request?.abort()
  const current = new AbortController()
  request = current
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await getBranches({ signal: current.signal })
    if (!current.signal.aborted) branches.value = result
  } catch {
    if (!current.signal.aborted)
      errorMessage.value = 'No se pudieron cargar las sucursales. Intenta de nuevo.'
  } finally {
    if (request === current) loading.value = false
  }
}
loadBranches()
onBeforeUnmount(() => request?.abort())
</script>

<style scoped lang="scss">
.branches-page {
  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 32px 64px;
}
.branches-status {
  padding: 40px 12px;
  text-align: center;
  color: #727780;
}
.branches-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 12px;
}
.branch-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  padding: 14px;
  border: 1px solid #e7e7ec;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 4px 18px #20212b04;
}
.branch-card__header {
  display: flex;
  align-items: center;
  gap: 9px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eef0f3;
}
.branch-card__visual {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 34px;
  height: 38px;
  overflow: hidden;
  border-radius: 10px;
  background: linear-gradient(135deg, #ff5c83, #df2f61);
}
.branch-store {
  display: block;
  width: 32px;
  height: 32px;
}
.branch-card__pin {
  display: none;
}
.branch-card__identity {
  flex: 1;
  min-width: 0;
}
.branch-card__eyebrow {
  display: none;
  color: #858a93;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1.2px;
}
.branch-card h2 {
  margin: 3px 0 0;
  color: #282d35;
  font-size: 15px;
  font-weight: 750;
  line-height: 1.3;
  overflow-wrap: anywhere;
}
.branch-status {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  gap: 5px;
  color: #278051;
  font-size: 10px;
  font-weight: 600;
}
.branch-status > span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}
.branch-card__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 10px;
  padding: 12px 0;
}
.branch-detail {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  min-width: 0;
  color: #4e5560;
  font-size: 12px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}
.branch-detail > .q-icon {
  flex-shrink: 0;
  margin-top: 2px;
  color: #df2f61;
  font-size: 16px;
}
.branch-detail > div {
  min-width: 0;
}
.branch-detail__label {
  display: block;
  margin-bottom: 2px;
  color: #858a93;
  font-size: 11px;
  line-height: 1.4;
}
.branch-detail p {
  margin: 0;
}
.branch-detail address {
  font-style: normal;
}
.branch-card__contact {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.branch-hours {
  white-space: pre-line;
}
.branch-phone {
  color: #303640;
  text-decoration: none;
  font-weight: 600;
}
.branch-phone:hover {
  text-decoration: underline;
}
.branch-actions {
  display: flex;
  gap: 10px;
  margin-top: auto;
  padding-top: 10px;
  border-top: 1px solid #eef0f3;
}
.branch-action {
  display: inline-flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 34px;
  padding: 6px 9px;
  border: 1px solid transparent;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 650;
  text-decoration: none;
}
.branch-action--map {
  background: #ee386e;
  color: #fff;
}
.branch-action--call {
  border-color: #e3e6eb;
  background: #fff;
  color: #404650;
}
.branch-action--map:hover {
  background: #d52c5d;
}
.branch-action--call:hover {
  background: #f7f8fa;
}
.branch-action:focus-visible,
.branch-phone:focus-visible {
  outline: 2px solid #df2f61;
  outline-offset: 3px;
}
@media (min-width: 600px) and (max-width: 1023px) {
  .branches-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 599px) {
  .branches-page {
    padding: 12px 10px 100px;
  }
  .branches-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
  }
  .branch-card {
    padding: 12px;
  }
  .branch-card__header {
    gap: 8px;
    padding-bottom: 8px;
  }
  .branch-card__visual {
    width: 30px;
    height: 32px;
  }
  .branch-store {
    width: 28px;
    height: 28px;
  }
  .branch-card h2 {
    font-size: 14px;
  }
  .branch-status {
    font-size: 9px;
    gap: 4px;
  }
  .branch-card__body {
    gap: 10px;
    padding: 10px 0;
  }
  .branch-detail {
    gap: 6px;
    font-size: 11px;
    line-height: 1.5;
  }
  .branch-detail__label {
    display: none;
  }
  .branch-card__contact {
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
    gap: 8px;
  }
  .branch-detail > .q-icon {
    font-size: 15px;
  }
  .branch-actions {
    gap: 8px;
    padding-top: 8px;
  }
  .branch-action {
    min-height: 36px;
    padding: 6px 8px;
    font-size: 11px;
  }
}
</style>
