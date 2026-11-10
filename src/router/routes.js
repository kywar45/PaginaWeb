const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'ofertas', component: () => import('pages/OffersPage.vue') },
      { path: 'nuevos', component: () => import('pages/NewProductsPage.vue') },
      { path: 'login', component: () => import('pages/LoginPage.vue') },
      { path: 'panel', component: () => import('pages/AccountPage.vue') },
      { path: 'register', component: () => import('pages/RegisterPage.vue') },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
