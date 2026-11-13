<template>
  <q-page class="branches-page">
    <PageBreadcrumbs title="Sucursales" />
    <LoadingSkeleton v-if="loading" variant="list" :count="3" label="Cargando sucursales..." />
    <div v-else-if="errorMessage" class="branches-status" role="alert"><p>{{ errorMessage }}</p><q-btn no-caps color="primary" label="Reintentar" @click="loadBranches" /></div>
    <div v-else-if="branches.length" class="branches-grid">
      <article v-for="branch in branches" :key="branch.id" class="branch-card">
        <div class="branch-card__visual" aria-hidden="true">
          <svg class="branch-store" viewBox="0 0 160 160" fill="none">
            <ellipse cx="80" cy="137" rx="57" ry="7" fill="#a91f49" opacity=".12" />
            <rect x="28" y="53" width="104" height="80" rx="5" fill="#fff" />
            <rect x="25" y="39" width="110" height="28" rx="5" fill="#a91f49" />
            <text x="80" y="57" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="10" font-weight="700" letter-spacing="1">SUPERNOVA</text>
            <path d="M28 67h104l8 17H20l8-17Z" fill="#fff" />
            <path d="m44 67-4 17H25l7-17h12Zm29 0-1 17H56l3-17h14Zm28 0 3 17H88l-1-17h14Zm27 0 7 17h-15l-5-17h13Z" fill="#f43f6f" />
            <rect x="38" y="91" width="37" height="32" rx="2" fill="#f8dce6" />
            <path d="m41 118 27-24M52 123l20-18" stroke="white" stroke-width="3" opacity=".8" />
            <rect x="85" y="91" width="36" height="42" rx="2" fill="#f8dce6" />
            <path d="M103 92v40" stroke="#fff" stroke-width="2" />
            <path d="M99 111v7m8-7v7" stroke="#bd365d" stroke-width="2" stroke-linecap="round" />
            <path d="M24 134h112" stroke="#fff" stroke-width="5" stroke-linecap="round" />
          </svg>
          <span class="branch-card__pin"><q-icon name="location_on" /></span>
        </div>
        <div class="branch-card__body">
          <header class="branch-card__header"><h2>{{ branch.nombre }}</h2><span class="branch-status"><span aria-hidden="true"></span>Sucursal activa</span></header>
          <div class="branch-detail"><q-icon name="location_on" aria-hidden="true" /><div><address>{{ branch.direccion }}</address><p class="branch-postcode">C.P. {{ branch.codigo_postal }}</p></div></div>
          <div v-if="branch.horario_atencion" class="branch-detail"><q-icon name="schedule" aria-hidden="true" /><p class="branch-hours">{{ branch.horario_atencion }}</p></div>
          <div v-if="branch.telefono" class="branch-detail"><q-icon name="call" aria-hidden="true" /><a v-if="branch.telefono_url" :href="branch.telefono_url" class="branch-phone">{{ branch.telefono }}</a><p v-else>{{ branch.telefono }}</p></div>
        </div>
        <div class="branch-actions">
            <a :href="branch.google_maps_url" target="_blank" rel="noopener noreferrer" class="branch-action branch-action--map" :aria-label="`Cómo llegar a ${branch.nombre} (abre Google Maps)`"><q-icon name="directions" aria-hidden="true" /> Cómo llegar <q-icon name="north_east" size="16px" aria-hidden="true" /></a>
            <a v-if="branch.telefono_url" :href="branch.telefono_url" class="branch-action branch-action--call" :aria-label="`Llamar a ${branch.nombre}`"><q-icon name="call" aria-hidden="true" /> Llamar</a>
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
    if (!current.signal.aborted) errorMessage.value = 'No se pudieron cargar las sucursales. Intenta de nuevo.'
  } finally {
    if (request === current) loading.value = false
  }
}
loadBranches()
onBeforeUnmount(() => request?.abort())
</script>

<style scoped lang="scss">
.branches-page { max-width: 1280px; margin: 0 auto; padding: 28px 48px 60px; }
.branches-status { padding: 40px 12px; text-align: center; color: #88717d; }
.branches-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
.branch-card { display: grid; grid-template-columns: 76px minmax(0, 1fr); align-content: start; gap: 12px; min-width: 0; padding: 12px; border: 1px solid #eadfe4; border-radius: 14px; background: #fff; box-shadow: 0 6px 24px #54314006; }
.branch-card__visual { position: relative; align-self: start; display: grid; place-items: center; min-height: 92px; overflow: hidden; border-radius: 10px; background: linear-gradient(155deg, transparent 34%, #ffffff20 35% 40%, transparent 41%), radial-gradient(140% 90% at 0 0, transparent 63%, #ffffff30 65%, transparent 68%), linear-gradient(135deg, #ff5c83, #df2f61); }
.branch-store { display: block; width: 100%; max-width: 160px; }
.branch-card__pin { position: absolute; top: 5px; right: 5px; display: grid; place-items: center; width: 20px; height: 20px; border: 1px solid #ffffff50; border-radius: 50%; background: #ffffff25; color: #fff; font-size: 13px; }
.branch-card__header { margin-bottom: 3px; }
.branch-card h2 { margin: 0 0 5px; color: #382d34; font-size: 15px; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
.branch-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 6px; background: #eaf7ef; color: #237447; font-size: 10px; font-weight: 600; }
.branch-status > span { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.branch-card__body { display: flex; flex-direction: column; gap: 7px; }
.branch-detail { display: flex; align-items: flex-start; gap: 7px; color: #75656e; font-size: 12px; line-height: 1.5; overflow-wrap: anywhere; }
.branch-detail > .q-icon { flex-shrink: 0; margin-top: 1px; color: #b56b83; font-size: 15px; }
.branch-detail p { margin: 0; }
.branch-detail address { font-style: normal; }
.branch-postcode { color: #98838e; font-size: 11px; }
.branch-hours { white-space: pre-line; }
.branch-phone { color: inherit; text-decoration: underline; text-underline-offset: 3px; }
.branch-actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; padding-top: 9px; border-top: 1px solid #f2e9ee; }
.branch-action { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; padding: 6px 11px; border: 1px solid #df2f61; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; }
.branch-action--map { background: linear-gradient(112deg, #fa4779, #df2f61); color: #fff; }
.branch-action--call { border-color: #eadfe4; background: #fff; color: #c52555; }
.branch-action:hover { box-shadow: 0 3px 10px #df2f6125; }
.branch-action:focus-visible, .branch-phone:focus-visible { outline: 2px solid #df2f61; outline-offset: 3px; }
@media (max-width: 1023px) { .branches-page { padding: 16px 16px 100px; } .branches-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 699px) { .branches-grid { grid-template-columns: minmax(0, 1fr); } .branch-actions { justify-content: stretch; } .branch-action { min-height: 40px; } .branch-action--map { flex: 1; } }
</style>
