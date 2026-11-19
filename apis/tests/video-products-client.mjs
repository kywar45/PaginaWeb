import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'

// Supply Vite's asset helper and empty environment so the real client can run in Node.
const source = (await readFile(new URL('../../src/services/productApi.js', import.meta.url), 'utf8'))
  .replace("import { publicAsset } from 'src/utils/publicAsset'", 'const publicAsset = (path) => path')
  .replaceAll('import.meta.env', '({})')
const { getProducts } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`)
const originalFetch = globalThis.fetch
try {
  let requested
  globalThis.fetch = async (url, options) => {
    requested = { url: new URL(url), options }
    return { ok: true, json: async () => ({ success: true, data: {
      video: { id: 12, titulo: 'Tutorial' }, productos: [{ id: 7, nombre: 'Gel', estatus: 0, precio_normal: 100, precio_final: 80 }],
      pagination: { page: 2, total: 31, total_pages: 2 },
    } }) }
  }
  const result = await getProducts({ id_video: 12, page: 2, limit: 30 })
  assert.equal(requested.url.searchParams.get('id_video'), '12')
  assert.equal(requested.url.searchParams.get('page'), '2')
  assert.equal(requested.url.searchParams.has('solo_disponibles'), false, 'Sold-out products remain visible')
  assert.equal(requested.options.credentials, 'include', 'Server session determines VIP prices')
  assert.equal(requested.options.cache, 'no-store')
  assert.equal(result.video.titulo, 'Tutorial')
  assert.equal(result.productos[0].id_producto, 7)
  assert.equal(result.productos[0].estatus, 0)
  globalThis.fetch = async () => ({ ok: true, json: async () => ({ success: true, data: { productos: [] } }) })
  await assert.rejects(getProducts({ id_video: 12 }), /Actualiza la API/, 'Old API cannot silently show unrelated products')
  globalThis.fetch = async () => ({ ok: false, status: 404, json: async () => ({ success: false, error: { code: 'VIDEO_NOT_FOUND', message: 'Video no disponible' } }) })
  await assert.rejects(getProducts({ id_video: 12 }), (error) => error.status === 404 && error.code === 'VIDEO_NOT_FOUND')
} finally { globalThis.fetch = originalFetch }
console.log('Video product client tests passed: filter, pagination, session, availability, metadata and old API rejection.')
