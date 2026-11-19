import { getCompletedOrder } from '../services/checkoutCompletion.js'

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'ofertas', component: () => import('pages/OffersPage.vue') },
      {
        path: 'inspirate/:videoId(\\d+)/productos',
        name: 'video-productos',
        component: () => import('pages/OffersPage.vue'),
      },
      { path: 'mas-vendidos', component: () => import('pages/BestSellersPage.vue') },
      { path: 'buscar', component: () => import('pages/SearchPage.vue') },
      { path: 'carrito', component: () => import('pages/CartPage.vue') },
      { path: 'checkout', component: () => import('pages/CheckoutPage.vue') },
      {
        path: 'pedido/completado',
        name: 'pedido-completado',
        component: () => import('pages/OrderCompletePage.vue'),
        beforeEnter: () => (getCompletedOrder() ? true : '/carrito'),
      },
      { path: 'nuevos', component: () => import('pages/NewProductsPage.vue') },
      { path: 'sucursales', component: () => import('pages/BranchesPage.vue') },
      { path: 'marcas/:id(\\d+)?', component: () => import('pages/BrandsPage.vue') },
      {
        path: 'producto/:id(\\d+)/:slug?',
        name: 'producto',
        component: () => import('pages/ProductPage.vue'),
      },
      { path: 'subcategoria', component: () => import('pages/SubcategoryProductsPage.vue') },
      {
        path: 'catalogo/:categoriaSlug/:subcategoriaSlug?',
        name: 'catalogo',
        component: () => import('pages/SubcategoryProductsPage.vue'),
      },
      { path: 'login', component: () => import('pages/LoginPage.vue') },
      { path: 'panel', component: () => import('pages/AccountPage.vue') },
      { path: 'register', component: () => import('pages/RegisterPage.vue') },
      { path: 'crear-cuenta-pedido', component: () => import('pages/GuestRegistrationPage.vue') },
      { path: 'confirmar-correo', component: () => import('pages/ConfirmEmailPage.vue') },
      { path: 'olvide-contrasena', component: () => import('pages/PasswordRecoveryPage.vue') },
      { path: 'restablecer-contrasena', component: () => import('pages/PasswordRecoveryPage.vue') },
    ],
  },

  {
    path: '/pedido/correo/pdf',
    name: 'pedido-correo-pdf',
    component: () => import('pages/OrderPdfPage.vue'),
  },
  {
    path: '/pedido/invitado/:cliente(\\d+)/:token(p1\\.[A-Za-z0-9_-]{54})/pdf',
    name: 'pedido-invitado-pdf',
    component: () => import('pages/OrderPdfPage.vue'),
  },
  {
    path: '/pedido/:token(p1\\.[A-Za-z0-9_-]{54})/pdf',
    name: 'pedido-pdf',
    component: () => import('pages/OrderPdfPage.vue'),
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
