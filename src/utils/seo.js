import { productLocation } from './productRoutes.js'

export const SITE_NAME = 'Importadora Supernova'
export const SITE_ORIGIN = 'https://www.importadorasupernova.com'
export const HOME_TITLE = `${SITE_NAME} - Tienda de Uñas, Pestañas, Geles y Más`
export const HOME_DESCRIPTION =
  'Productos para uñas, pestañas, geles, belleza y hogar. Compra en Importadora Supernova con envío nacional o entrega en nuestras tiendas.'
export const SHARE_IMAGE = `${SITE_ORIGIN}/Logo/logo_meta.jpg`
export const ANALYTICS_ID = 'G-9BG23LE340'
export const SOCIAL_LINKS = [
  'https://www.facebook.com/profile.php?id=61558081776009',
  'https://www.instagram.com/lemussaoficial/',
  'https://www.tiktok.com/@supernovashopmx',
  'https://www.youtube.com/@lemussaoficial3601',
]
const TITLES = {
  '/ofertas': 'Ofertas',
  '/mas-vendidos': 'Más vendidos',
  '/nuevos': 'Nuevos productos',
  '/categorias': 'Categorías',
  '/sucursales': 'Sucursales',
  '/marcas': 'Nuestras marcas',
  '/buscar': 'Buscar productos',
  '/carrito': 'Mi carrito',
  '/checkout': 'Checkout',
  '/login': 'Iniciar sesión',
  '/register': 'Crear cuenta',
  '/panel': 'Mi cuenta',
}

export function plainText(value, max = 160) {
  return String(value || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, max)
}
export function publicImage(value, homeUrl) {
  if (!value) return null
  try {
    const url = new URL(value, homeUrl)
    if (
      !['https:', 'http:'].includes(url.protocol) ||
      url.pathname.endsWith('/product-no-image.svg')
    )
      return null
    return url.href
  } catch {
    return null
  }
}
export function buildPageSeo(
  path,
  { homeUrl, product = null, notFound = false, unavailable = false } = {},
) {
  const home = new URL(homeUrl || '/', SITE_ORIGIN).href.replace(/\/?$/, '/')
  const url = new URL(path.replace(/^\//, ''), home).href
  const privatePage = /^\/(carrito|checkout|login|register|panel|buscar|pedido)(\/|$)/.test(path)
  const meta = {
    title: path === '/' ? HOME_TITLE : `${TITLES[path] || 'Catálogo'} | ${SITE_NAME}`,
    description: HOME_DESCRIPTION,
    url,
    image: SHARE_IMAGE,
    type: 'website',
    robots: privatePage || notFound || unavailable ? 'noindex, follow' : 'index, follow',
    graph: [],
  }
  if (path === '/') {
    meta.graph = [
      { '@type': 'WebSite', '@id': `${home}#website`, name: SITE_NAME, url: home },
      {
        '@type': 'Organization',
        '@id': `${home}#organization`,
        name: SITE_NAME,
        url: home,
        logo: SHARE_IMAGE,
        sameAs: SOCIAL_LINKS,
      },
    ]
  }
  if (product) {
    const location = productLocation(product)
    meta.url = new URL(`producto/${location.params.id}/${location.params.slug}`, home).href
    meta.title = `${plainText(product.nombre, 250)} | ${SITE_NAME}`
    meta.description =
      plainText(product.descripcion) ||
      `${plainText(product.nombre, 100)}. Consulta precio y disponibilidad en ${SITE_NAME}.`
    const images = [
      ...new Set(
        [product.imagen, ...(product.imagenes || [])]
          .map((value) => publicImage(value, home))
          .filter(Boolean),
      ),
    ]
    meta.image = images[0] || SHARE_IMAGE
    meta.type = 'product'
    const schema = {
      '@type': 'Product',
      name: plainText(product.nombre, 500),
      description: meta.description,
      url: meta.url,
    }
    if (product.codigo) schema.sku = String(product.codigo)
    if (images.length) schema.image = images
    // Older VIP APIs lack a public price. Never publish a session-specific price as a public offer.
    const price = product.precio_publico ?? (product.es_vip ? null : product.precio_final)
    if (
      price !== null &&
      price !== undefined &&
      Number.isFinite(Number(price)) &&
      Number(price) > 0
    ) {
      schema.offers = {
        '@type': 'Offer',
        url: meta.url,
        priceCurrency: 'MXN',
        price: Number(price).toFixed(2),
        availability:
          Number(product.estatus) === 1
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
        seller: { '@type': 'Organization', name: SITE_NAME },
      }
    }
    meta.graph = [schema]
  } else if (notFound) {
    meta.title = `Producto no disponible | ${SITE_NAME}`
    meta.description = 'El producto solicitado no existe o no está publicado.'
  } else if (/^\/producto\//.test(path)) {
    meta.title = `Producto | ${SITE_NAME}`
  }
  return meta
}
export const serializeSchema = (graph) =>
  JSON.stringify({ '@context': 'https://schema.org', '@graph': graph }).replace(/</g, '\\u003c')

export function applyPageSeo(meta) {
  document.title = meta.title
  const set = (attribute, key, content) => {
    let element = document.head.querySelector(`meta[${attribute}="${key}"]`)
    if (!element) {
      element = document.createElement('meta')
      element.setAttribute(attribute, key)
      document.head.append(element)
    }
    element.setAttribute('content', content)
  }
  set('name', 'description', meta.description)
  set('name', 'robots', meta.robots)
  for (const [key, value] of Object.entries({
    title: meta.title,
    description: meta.description,
    image: meta.image,
    url: meta.url,
    type: meta.type,
    site_name: SITE_NAME,
    locale: 'es_MX',
  }))
    set('property', `og:${key}`, value)
  for (const [key, value] of Object.entries({
    card: 'summary_large_image',
    title: meta.title,
    description: meta.description,
    image: meta.image,
  }))
    set('name', `twitter:${key}`, value)
  let canonical = document.head.querySelector('link[rel="canonical"]')
  if (!canonical) {
    canonical = document.createElement('link')
    canonical.rel = 'canonical'
    document.head.append(canonical)
  }
  canonical.href = meta.url
  let schema = document.getElementById('store-structured-data')
  if (!meta.graph.length) {
    schema?.remove()
    return
  }
  if (!schema) {
    schema = document.createElement('script')
    schema.id = 'store-structured-data'
    schema.type = 'application/ld+json'
    document.head.append(schema)
  }
  schema.textContent = serializeSchema(meta.graph)
}
