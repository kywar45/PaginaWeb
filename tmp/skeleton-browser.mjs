import assert from 'node:assert/strict'
import { writeFile } from 'node:fs/promises'
await fetch('http://127.0.0.1:4173/reset-images')
const tab = await (await fetch('http://127.0.0.1:9223/json/new?about:blank', { method: 'PUT' })).json()
const socket = new WebSocket(tab.webSocketDebuggerUrl)
await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }))
let nextId = 0
const pending = new Map()
const exceptions = []
socket.addEventListener('message', ({ data }) => {
  const message = JSON.parse(data)
  if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails.text + ': ' + message.params.exceptionDetails.exception?.description)
  if (message.id) {
    const callbacks = pending.get(message.id)
    pending.delete(message.id)
    if (message.error) callbacks.reject(new Error(JSON.stringify(message.error)))
    else callbacks.resolve(message.result)
  }
})
function cdp(method, params = {}) {
  const id = ++nextId
  return new Promise((resolve, reject) => { pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params })) })
}
async function evaluate(expression) {
  const result = await cdp('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description)
  return result.result.value
}
const pause = ms => new Promise(resolve => setTimeout(resolve, ms))
async function until(expression) {
  for (let i = 0; i < 60; i++) { if (await evaluate(expression)) return; await pause(100) }
  throw new Error('Timed out: ' + expression)
}
await cdp('Page.enable')
await cdp('Runtime.enable')
await cdp('Network.enable')
await cdp('Network.setCacheDisabled', { cacheDisabled: true })
await cdp('Page.addScriptToEvaluateOnNewDocument', { source: `
  window.__apiReady = new Promise(resolve => { window.__releaseApi = resolve });
  const originalFetch = window.fetch;
  window.fetch = async (url, options) => {
    if (!String(url).includes('StoreApi/v1')) return originalFetch(url, options);
    await window.__apiReady;
    const parsed = new URL(url);
    if(window.__failCollection && parsed.searchParams.get('coleccion')===window.__failCollection)
      return new Response(JSON.stringify({success:false,error:{message:'Error de prueba'}}),{status:500});
    let data = {};
    const products = Array.from({length:8}, (_, i) => ({ id_producto:i+1, nombre:'Producto real '+(i+1), codigo:'REAL-01',
      imagen:'http://127.0.0.1:4173/slow.svg?id='+i, precio_normal:100, precio_final:80, precio_mayoreo:70, precio_caja:60,
      minimo_mayoreo:6,minimo_caja:20, tiene_descuento:true,porcentaje_descuento:20,estatus:1,puede_comprar:true,
      visitas:123, descripcion:'Descripcion de producto',es_vip:false }));
    const categories = [{id_categoria:1,nombre_categoria:'Categoria real',icon:'otros.webp',subcategorias:[{id_subcategoria:1,nombre_subcategoria:'Subcategoria real'}]}];
    if(parsed.pathname.endsWith('/auth/me')) return new Response(JSON.stringify({success:false}),{status:401});
    if(parsed.pathname.endsWith('/productos')) data = parsed.searchParams.has('id_producto')
      ? {producto:{...products[0],imagenes:[products[0].imagen],variantes:[],relacionados:[],categoria:{id:1,name:'Categoria real'},subcategoria:{id:1,name:'Subcategoria real'}}}
      : {productos:products,pagination:{total:8,total_pages:1,page:1}};
    else if(parsed.pathname.endsWith('/categorias')) data = {categorias:categories};
    else if(parsed.pathname.endsWith('/subcategorias-aleatorias')) data = {subcategorias:[]};
    else if(parsed.pathname.endsWith('/carrito')) data = {items:[{...products[0],quantity:2,subtotal:160}],total:160,cantidad_total:2,ahorro_total:40,total_sin_descuentos:200};
    else if(parsed.pathname.endsWith('/banners')) data = {banners:parsed.searchParams.get('tipo')==='1' ? [1,2].map(id=>({id,tipo:1,imagen_web:'http://127.0.0.1:4173/slow.svg?banner='+id,imagen_movil:'http://127.0.0.1:4173/slow.svg?mobile='+id})) : []};
    else if(parsed.pathname.endsWith('/tiktok-videos')) data = {videos:[]};
    else if(parsed.pathname.endsWith('/sucursales')) data = {sucursales:[]};
    else if(parsed.pathname.endsWith('/marcas')) data = {marcas:[]};
    return new Response(JSON.stringify({success:true,data}),{headers:{'Content-Type':'application/json'}});
  };
` })
try {
  await cdp('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false })
  await cdp('Page.navigate', { url: 'http://127.0.0.1:4173/ImportadoraSupernova/' })
  await until(`document.querySelectorAll('.loading-skeleton--rail').length===2`)
  assert.equal(await evaluate(`document.querySelectorAll('.offer-card:not(.offer-card--see-all)').length`), 0, 'No mock products while API pending')
  let screenshot = await cdp('Page.captureScreenshot', { format: 'png' })
  await writeFile('tmp/skeleton-desktop.png', Buffer.from(screenshot.data, 'base64'))
  await evaluate('window.__releaseApi()')
  await until(`document.querySelector('.offer-image img') && document.querySelector('.offer-image .sn-image-skeleton')`)
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.offer-image img')).visibility`), 'hidden')
  assert.ok(await evaluate(`document.querySelector('.offer-image .sn-image-skeleton').offsetHeight > 100`), 'Image skeleton preserves actual card bounds')
  await fetch('http://127.0.0.1:4173/release-images')
  await until(`document.querySelector('.offer-image img').complete && getComputedStyle(document.querySelector('.offer-image img')).visibility==='visible'`)
  assert.equal(await evaluate(`document.querySelectorAll('.offer-image .sn-image-skeleton').length`), 0, 'Image skeletons clean up after decode')
  await until(`!!document.querySelector('.banner-carousel__slide img') && getComputedStyle(document.querySelector('.banner-carousel__slide img')).visibility==='visible'`)
  const originalSlide = await evaluate(`document.querySelector('.banner-carousel__track').style.transform`)
  await evaluate(`document.querySelector('.banner-carousel__arrow--next').click()`)
  await until(`document.querySelector('.banner-carousel__track').style.transform!==${JSON.stringify(originalSlide)}`)
  // Cached image, changed source and broken image/fallback.
  await evaluate(`document.querySelector('.offer-image img').src='http://127.0.0.1:4173/not-an-image.png'`)
  await until(`document.querySelector('.offer-image img').src.includes('product-no-image.svg') && document.querySelector('.offer-image img').complete && getComputedStyle(document.querySelector('.offer-image img')).visibility==='visible'`)
  for (const [route, selector] of [['nuevos','.loading-skeleton'], ['ofertas','.loading-skeleton'], ['mas-vendidos','.loading-skeleton'], ['producto/1/prueba','.loading-skeleton--product'], ['categorias','.loading-skeleton--categories'], ['carrito','.loading-skeleton--cart'], ['marcas','.loading-skeleton'], ['sucursales','.loading-skeleton']]) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true })
    await cdp('Page.navigate', { url: 'http://127.0.0.1:4173/ImportadoraSupernova/'+route })
    await until(`!!document.querySelector(${JSON.stringify(selector)})`)
    assert.ok(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'No horizontal overflow while loading '+route)
    if (route==='nuevos') {
      screenshot = await cdp('Page.captureScreenshot', { format: 'png' })
      await writeFile('tmp/skeleton-mobile.png', Buffer.from(screenshot.data, 'base64'))
    }
    await evaluate('window.__releaseApi()')
    await until(`!document.querySelector(${JSON.stringify(selector)})`)
    if (route === 'carrito') {
      assert.match(await evaluate(`document.querySelector('.cart-summary-mobile__savings').textContent`), /40/)
      assert.ok(await evaluate(`document.querySelector('.cart-total').getBoundingClientRect().bottom < document.querySelector('.sn-mobile-nav').getBoundingClientRect().top`), 'Summary stays above bottom navigation')
      assert.ok(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Summary fits narrow mobile viewport')
      screenshot = await cdp('Page.captureScreenshot', { format: 'png' })
      await writeFile('tmp/cart-summary-mobile.png', Buffer.from(screenshot.data, 'base64'))
      await evaluate(`document.querySelector('.cart-summary-mobile__continue').click()`)
      await until(`!!document.querySelector('.checkout-page') && !document.querySelector('.loading-skeleton')`)
      assert.match(await evaluate(`document.querySelector('.checkout-summary__savings').textContent`), /40/)
      assert.ok(await evaluate(`location.pathname.endsWith('/checkout')`), 'Continue opens a real checkout route')
      assert.ok(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Checkout fits mobile viewport')
      await cdp('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false })
      await cdp('Page.navigate', { url: 'http://127.0.0.1:4173/ImportadoraSupernova/carrito' })
      await until(`!!document.querySelector('.loading-skeleton--cart')`)
      await evaluate('window.__releaseApi()')
      await until(`!!document.querySelector('.cart-total')`)
      assert.equal(await evaluate(`getComputedStyle(document.querySelector('.cart-summary-mobile')).display`), 'none', 'Desktop cart retains its original total layout')
    }
  }
  await cdp('Page.navigate', { url: 'http://127.0.0.1:4173/ImportadoraSupernova/' })
  await until(`document.querySelectorAll('.loading-skeleton--rail').length===2`)
  await evaluate(`window.__failCollection='ofertas'; window.__releaseApi()`)
  await until(`!!document.querySelector('.home-load-error') && !!document.querySelector('.featured-product-main')`)
  assert.equal(await evaluate(`document.querySelector('#ofertas .offer-card')`), null, 'API error shows no made-up or stale product cards')
  await evaluate(`window.__failCollection=null; document.querySelector('.home-load-error .q-btn').click()`)
  await until(`!!document.querySelector('#ofertas .offer-card') && !document.querySelector('.home-load-error')`)
  assert.deepEqual(exceptions, [], 'No browser runtime exceptions')
  console.log('Browser checks passed: no mocks, desktop/mobile skeletons, image decode, fallback, cached images and no overflow.')
} finally { socket.close(); await fetch('http://127.0.0.1:9223/json/close/'+tab.id) }
