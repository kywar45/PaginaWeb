import { defineBoot } from '#q-app/wrappers'
import { applyPageSeo, buildPageSeo, SITE_ORIGIN, ANALYTICS_ID } from 'src/utils/seo'

export default defineBoot(({ router }) => {
  const homeUrl = new URL(import.meta.env.BASE_URL || '/', SITE_ORIGIN).href
  // Keep initial server-rendered product metadata until its API request completes.
  let initial = true
  router.afterEach((to, from) => {
    const sameProduct =
      to.name === 'producto' && from.name === 'producto' && to.params.id === from.params.id
    if (!(to.name === 'producto' && (initial || sameProduct))) {
      applyPageSeo(
        buildPageSeo(to.path, {
          homeUrl,
          notFound: to.matched.some((record) => record.path.includes(':catchAll')),
        }),
      )
    }
    initial = false
    if (sameProduct || /^\/pedido\//.test(to.path)) return
    window.dataLayer = window.dataLayer || []
    window.gtag =
      window.gtag ||
      function () {
        window.dataLayer.push(arguments)
      }
    window.gtag('event', 'page_view', {
      send_to: ANALYTICS_ID,
      page_location: new URL(to.path.replace(/^\//, ''), homeUrl).href,
    })
  })
})
