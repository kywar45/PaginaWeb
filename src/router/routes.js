const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'ofertas', component: () => import('pages/OffersPage.vue') },
      { path: 'mas-vendidos', component: () => import('pages/BestSellersPage.vue') },
      { path: 'buscar', component: () => import('pages/SearchPage.vue') },
      { path: 'carrito', component: () => import('pages/CartPage.vue') },
      { path: 'checkout', component: () => import('pages/CheckoutPage.vue') },
      { path: 'nuevos', component: () => import('pages/NewProductsPage.vue') },
      { path: 'categorias', component: () => import('pages/CategoriesPage.vue') },
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
