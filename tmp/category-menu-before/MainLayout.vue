<template>
  <q-layout view="hHh lpR fff">
    <!-- SUPERNOVA NAILS - HEADER -->
    <q-header class="sn-header">
      <!-- Barra de contacto (solo desktop) -->
      <div class="sn-topbar gt-sm">
        <div class="sn-trustbar">
          <span><q-icon name="local_shipping" /> Envíos a todo México</span>
          <span><q-icon name="verified_user" /> Compra segura</span>
          <span><q-icon name="handshake" /> Mayoreo y menudeo</span>
          <span><q-icon name="category" /> +10,000 productos</span>
          <a href="tel:+525524954670"><q-icon name="call" /> Atención: 55 2495 4670</a>
        </div>
      </div>
      <div class="sn-mobile-head lt-md">
        <div class="sn-mobile-head__row">
          <q-btn
            flat
            round
            dense
            icon="menu"
            color="white"
            @click="leftDrawerOpen = !leftDrawerOpen"
          />

          <div class="sn-mobile-logo" @click="$router.push('/')">
            <img src="img/logob.png" alt="Importadora Supernova" />
          </div>

          <div class="sn-mobile-head__actions">
            <button
              class="sn-mobile-icon-btn"
              type="button"
              aria-label="Buscar"
              @click="focusMobileSearch"
            >
              <q-icon name="search" />
            </button>
            <button class="sn-mobile-icon-btn" type="button" aria-label="Carrito">
              <q-icon name="shopping_cart" />
              <q-badge color="primary" floating rounded style="font-size: 10px">{{
                cartCount
              }}</q-badge>
              <q-tooltip>Ver carrito</q-tooltip>
            </button>
          </div>
        </div>

        <q-input
          ref="mobileSearchInput"
          v-model="searchQuery"
          filled
          rounded
          dense
          placeholder="Buscar productos, marcas y más..."
          class="sn-mobile-head__search"
          bg-color="white"
        >
          <template #append>
            <q-icon name="search" color="grey-7" />
          </template>
        </q-input>
      </div>

      <!-- Toolbar principal -->
      <q-toolbar class="q-py-sm gt-sm sn-desktop-toolbar" style="min-height: 60px; padding: 0">
        <div
          style="
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 16px;
          "
        >
          <!-- ── MOBILE: barra de búsqueda expandida ── -->
          <template v-if="searchOpen">
            <q-btn
              flat
              round
              dense
              icon="arrow_back"
              color="white"
              class="lt-md"
              @click="closeSearch"
            />
            <q-input
              v-model="searchQuery"
              filled
              rounded
              dense
              placeholder="Buscar productos..."
              class="lt-md sn-mobile-search"
              bg-color="white"
              style="flex: 1"
              autofocus
            >
              <template #append v-if="searchQuery">
                <q-btn
                  flat
                  round
                  dense
                  icon="close"
                  size="xs"
                  color="grey"
                  @click="searchQuery = ''"
                />
              </template>
            </q-input>
          </template>

          <!-- ── MOBILE/DESKTOP: barra normal ── -->
          <template v-else>
            <!-- Hamburger (solo mobile) -->
            <q-btn
              flat
              round
              dense
              icon="menu"
              color="white"
              class="lt-md"
              @click="leftDrawerOpen = !leftDrawerOpen"
            />

            <!-- Logo -->
            <div
              class="sn-logo-main no-wrap"
              style="min-width: fit-content; cursor: pointer"
              @click="$router.push('/')"
            >
              <img src="img/logob.png" alt="Importadora Supernova" width="180px" />
            </div>

            <div
              v-if="false"
              class="row items-center no-wrap gt-sm sn-toolbar-category-hidden"
              style="gap: 8px"
            >
              <q-btn
                flat
                dense
                color="white"
                label="Categorías"
                icon="keyboard_arrow_down"
                class="sn-category-trigger"
                :class="{ 'sn-category-trigger--active': categoriesMenu }"
              >
                <q-menu
                  v-model="categoriesMenu"
                  anchor="bottom left"
                  self="top left"
                  :offset="[0, 8]"
                  class="sn-category-menu"
                >
                  <q-list padding style="">
                    <q-item v-if="categoriesLoading">
                      <q-item-section avatar>
                        <q-spinner color="primary" size="20px" />
                      </q-item-section>
                      <q-item-section>Cargando categorias...</q-item-section>
                    </q-item>

                    <q-item v-else-if="categories.length === 0">
                      <q-item-section avatar>
                        <q-icon name="category" color="grey-5" />
                      </q-item-section>
                      <q-item-section>No hay categorias disponibles</q-item-section>
                    </q-item>

                    <template v-else v-for="cat in categories" :key="cat.slug">
                      <q-item
                        v-if="cat.subcategories?.length"
                        clickable
                        v-ripple
                        class="sn-category-menu-item"
                        :active="activeCategory === cat.slug"
                        active-class="bg-pink-1 text-primary"
                      >
                        <q-item-section>{{ cat.label || cat.name }}</q-item-section>
                        <q-item-section side>
                          <q-icon name="chevron_right" color="grey-5" />
                        </q-item-section>

                        <q-menu
                          anchor="top end"
                          self="top start"
                          :offset="[6, 0]"
                          class="sn-subcategory-menu"
                        >
                          <q-list dense padding>
                            <q-item
                              v-for="sub in cat.subcategories"
                              :key="sub.slug"
                              clickable
                              v-ripple
                              :active="activeSubcategory === sub.slug"
                              active-class="bg-pink-1 text-primary"
                              @click="selectSubcategoryFromMenu(cat.slug, sub.slug)"
                            >
                              <q-item-section avatar>
                                <q-icon name="chevron_right" size="14px" color="grey-5" />
                              </q-item-section>
                              <q-item-section>{{ sub.name }}</q-item-section>
                            </q-item>
                          </q-list>
                        </q-menu>
                      </q-item>

                      <q-item
                        v-else
                        clickable
                        v-ripple
                        class="sn-category-menu-item"
                        :active="activeCategory === cat.slug"
                        active-class="bg-pink-1 text-primary"
                        @click="selectCategoryFromMenu(cat.slug)"
                      >
                        <q-item-section avatar>
                          <img
                            v-if="cat.svgIcon"
                            :src="cat.svgIcon"
                            style="width: 24px; height: 24px; object-fit: contain"
                          />
                          <q-icon v-else name="category" color="grey-6" />
                        </q-item-section>
                        <q-item-section>
                          {{ cat.label || cat.name }}
                        </q-item-section>
                      </q-item>
                      <q-separator v-if="cat.subcategories?.length" spaced />
                    </template>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>

            <!-- Separador flexible (desktop) -->
            <q-space class="gt-sm" />

            <!-- Barra de búsqueda (solo desktop) -->
            <q-input
              v-model="searchQuery"
              filled
              rounded
              placeholder="Buscar productos, marcas y más..."
              class="sn-search gt-sm"
              bg-color="white"
              style="width: 100%; max-width: 680px"
            >
              <template #append>
                <q-btn
                  v-if="searchQuery"
                  flat
                  round
                  dense
                  icon="close"
                  size="xs"
                  color="grey"
                  @click="searchQuery = ''"
                />
                <q-btn
                  unelevated
                  round
                  dense
                  icon="search"
                  color="primary"
                  class="sn-search-submit"
                />
              </template>
            </q-input>

            <q-space />

            <!-- Ícono buscar (solo mobile) -->
            <q-btn
              flat
              round
              icon="search"
              color="white"
              size="md"
              class="lt-md"
              @click="searchOpen = true"
            />

            <!-- Acciones desktop -->
            <div class="row items-center no-wrap q-gutter-xs gt-sm">
              <q-btn flat round icon="favorite_border" color="white" size="md">
                <q-tooltip>Lista de deseos</q-tooltip>
              </q-btn>
            </div>

            <!-- Carrito (siempre visible) -->
            <q-btn flat round icon="shopping_cart" color="white" size="md" class="q-ml-xs">
              <q-badge color="accent" floating rounded style="font-size: 10px">{{
                cartCount
              }}</q-badge>
              <q-tooltip>Ver carrito</q-tooltip>
            </q-btn>
            <q-btn
              flat
              no-caps
              :round="false"
              color="white"
              size="md"
              class="sn-account-btn"
              @click="goToAccount"
            >
              <q-icon name="person" size="22px" />
              <span class="sn-account-copy gt-sm">
                <span class="sn-account-hello">{{ currentUser ? 'Hola!' : 'Iniciar sesión' }}</span>
                <span class="sn-account-name">{{ accountName || 'Mi cuenta' }}</span>
              </span>
              <q-tooltip>{{ currentUser ? 'Ir a mi panel' : 'Mi cuenta' }}</q-tooltip>
            </q-btn>
          </template>
        </div>
      </q-toolbar>

      <div class="sn-desktop-nav gt-sm">
        <div class="sn-desktop-nav__inner">
          <q-btn
            unelevated
            no-caps
            color="primary"
            icon="menu"
            label="Todas las categorias"
            class="sn-all-category-trigger"
            :class="{ 'sn-category-trigger--active': categoriesMenu }"
          >
            <q-menu
              v-model="categoriesMenu"
              anchor="bottom left"
              self="top left"
              :offset="[0, 8]"
              class="sn-category-menu"
            >
              <div class="sn-category-mega">
                <div class="sn-category-mega__categories">
                  <div class="sn-category-menu__header">
                    <span>Explora por categoría</span>
                    <q-icon name="auto_awesome" size="16px" />
                  </div>
                  <q-item v-if="categoriesLoading">
                    <q-item-section avatar>
                      <q-spinner color="primary" size="20px" />
                    </q-item-section>
                    <q-item-section>Cargando categorias...</q-item-section>
                  </q-item>

                  <q-item v-else-if="categories.length === 0">
                    <q-item-section avatar>
                      <q-icon name="category" color="grey-5" />
                    </q-item-section>
                    <q-item-section>No hay categorias disponibles</q-item-section>
                  </q-item>

                  <template v-else v-for="cat in categories" :key="cat.slug">
                    <q-item
                      v-if="cat.subcategories?.length"
                      clickable
                      v-ripple
                      class="sn-category-menu-item"
                      :active="megaCategory?.slug === cat.slug"
                      active-class="bg-pink-1 text-primary"
                      @mouseenter="previewCategory(cat)"
                      @click="previewCategory(cat)"
                    >
                      <q-item-section avatar>
                        <span class="sn-category-menu-item__icon">
                          <img v-if="cat.svgIcon" :src="cat.svgIcon" :alt="cat.name" />
                          <q-icon v-else name="category" color="grey-6" />
                        </span>
                      </q-item-section>
                      <q-item-section>{{ cat.label || cat.name }}</q-item-section>
                      <q-item-section side class="sn-category-menu-item__side">
                        <span class="sn-category-menu-item__count">
                          {{ cat.subcategories.length }}
                        </span>
                        <q-icon name="arrow_forward" />
                      </q-item-section>
                    </q-item>

                    <q-item
                      v-else
                      clickable
                      v-ripple
                      class="sn-category-menu-item"
                      :active="activeCategory === cat.slug"
                      active-class="bg-pink-1 text-primary"
                      @click="selectCategoryFromMenu(cat.slug)"
                      @mouseenter="previewCategory(cat)"
                    >
                      <q-item-section avatar>
                        <span class="sn-category-menu-item__icon">
                          <img v-if="cat.svgIcon" :src="cat.svgIcon" :alt="cat.name" />
                          <q-icon v-else name="category" color="grey-6" />
                        </span>
                      </q-item-section>
                      <q-item-section>
                        {{ cat.label || cat.name }}
                      </q-item-section>
                    </q-item>
                    <q-separator v-if="cat.subcategories?.length" spaced />
                  </template>
                </div>

                <div class="sn-category-mega__details">
                  <template v-if="megaCategory?.subcategories?.length">
                    <div class="sn-category-mega__title">
                      <div>
                        <span class="sn-category-mega__eyebrow">Colección</span>
                        <strong>{{ megaCategory.name }}</strong>
                      </div>
                      <q-btn
                        flat
                        dense
                        no-caps
                        label="Ver todo"
                        icon-right="arrow_forward"
                        class="sn-category-mega__all"
                        @click="selectCategoryFromMenu(megaCategory.slug)"
                      />
                    </div>
                    <div class="sn-category-mega__body">
                      <div class="sn-category-mega__subcategories">
                        <q-item
                          v-for="sub in megaCategory.subcategories"
                          :key="sub.slug"
                          clickable
                          v-ripple
                          class="sn-category-subcategory"
                          :active="activeSubcategory === sub.slug"
                          active-class="sn-category-subcategory--active"
                          @click="selectSubcategoryFromMenu(megaCategory.slug, sub.slug)"
                        >
                          <q-item-section avatar>
                            <span class="sn-category-subcategory__mark">
                              <q-icon name="arrow_forward" size="14px" />
                            </span>
                          </q-item-section>
                          <q-item-section>{{ sub.name }}</q-item-section>
                        </q-item>
                      </div>
                      <a
                        v-if="categoryPromo"
                        class="sn-category-promo"
                        :href="categoryPromo.redireccion_web || undefined"
                        @click="categoriesMenu = false"
                      >
                        <img :src="categoryPromo.imagen_web" alt="Promoción" />
                        <span class="sn-category-promo__veil" />
                        <span class="sn-category-promo__copy">
                          <small>Descubre más</small>
                          <strong>Productos que inspiran</strong>
                          <span>Ver colección <q-icon name="arrow_forward" size="15px" /></span>
                        </span>
                      </a>
                    </div>
                  </template>
                  <div v-else class="sn-category-mega__empty">
                    <q-icon name="auto_awesome" size="28px" />
                    <strong>Elige una categoría</strong>
                    <span>Sus productos y subcategorías aparecerán aquí.</span>
                  </div>
                </div>
              </div>
            </q-menu>
          </q-btn>

          <q-btn flat no-caps icon="local_offer" label="Ofertas" class="sn-desktop-nav__link">
            <q-badge color="primary" label="HOT" floating />
          </q-btn>
          <q-btn flat no-caps icon="shield" label="Nuevos" class="sn-desktop-nav__link" />
          <q-btn
            flat
            no-caps
            icon="trending_up"
            label="Mas vendidos"
            class="sn-desktop-nav__link"
          />
          <q-btn
            flat
            no-caps
            icon="workspace_premium"
            label="Marcas"
            class="sn-desktop-nav__link"
          />
          <q-btn flat no-caps icon="storefront" label="Sucursales" class="sn-desktop-nav__link" />
        </div>
      </div>
    </q-header>

    <!-- ════════════════════════════════ DRAWER MOBILE ══════════════════════════════ -->
    <q-drawer v-model="leftDrawerOpen" bordered :width="290" class="lt-md">
      <!-- Cabecera del drawer -->
      <div class="sn-drawer-header">
        <div class="row items-center justify-between q-mb-lg">
          <div>
            <div class="text-white font-playfair text-h6 text-weight-bold">SUPERNOVA</div>
            <div class="text-caption" style="color: #f8bbd9; letter-spacing: 3px">
              NAILS & BEAUTY
            </div>
          </div>
          <q-btn flat round dense icon="close" color="white" @click="leftDrawerOpen = false" />
        </div>

        <!-- Avatar / usuario -->
        <div class="row items-center cursor-pointer" @click="goToAccount">
          <q-avatar color="white" size="42px">
            <q-icon name="person" color="primary" size="sm" />
          </q-avatar>
          <div class="q-ml-sm">
            <div class="text-white text-weight-bold text-body2">{{ accountGreeting }}</div>
            <div class="text-caption" style="color: #f8bbd9">
              {{ currentUser ? 'Ir a mi panel' : 'Ingresa o registrate' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Categorías -->
      <q-list padding>
        <q-item-label
          header
          class="text-weight-bold text-uppercase text-caption"
          style="color: #c2185b"
        >
          Categorías
        </q-item-label>

        <template v-for="cat in categories" :key="cat.slug">
          <!-- Sin subcategorías: item simple -->
          <q-item
            v-if="!cat.subcategories || cat.subcategories.length === 0"
            clickable
            v-ripple
            :active="activeCategory === cat.slug"
            active-class="bg-pink-1 text-primary"
            @click="selectCategory(cat.slug)"
          >
            <q-item-section avatar>
              <img v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 24px; height: 24px" />
              <q-icon v-else name="category" color="grey-6" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ cat.name }}</q-item-label>
            </q-item-section>
            <q-item-section side v-if="cat.badge">
              <q-badge :color="cat.badgeColor || 'primary'" :label="cat.badge" />
            </q-item-section>
          </q-item>

          <!-- Con subcategorías: expansion item -->
          <q-expansion-item
            v-else
            :label="cat.name"
            :default-opened="activeCategory === cat.slug"
            :header-class="activeCategory === cat.slug ? 'text-primary' : ''"
            expand-icon-class="text-grey-6"
            @before-show="activeCategory = cat.slug"
          >
            <template #header>
              <q-item-section avatar>
                <img v-if="cat.svgIcon" :src="cat.svgIcon" style="width: 100%; height: 24px" />
                <q-icon v-else name="category" color="grey-6" />
              </q-item-section>
              <q-item-section>
                <q-item-label
                  :class="activeCategory === cat.slug ? 'text-primary text-weight-medium' : ''"
                >
                  {{ cat.name }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="cat.badge">
                <q-badge :color="cat.badgeColor || 'primary'" :label="cat.badge" />
              </q-item-section>
            </template>

            <q-item
              v-for="sub in cat.subcategories"
              :key="sub.slug"
              clickable
              v-ripple
              class="q-pl-xl"
              :active="activeSubcategory === sub.slug"
              active-class="bg-pink-1 text-primary"
              @click="selectSubcategory(cat.slug, sub.slug)"
            >
              <q-item-section avatar>
                <q-icon name="chevron_right" size="xs" color="grey-5" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ sub.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-expansion-item>
        </template>

        <q-separator class="q-my-sm" />

        <q-item-label
          header
          class="text-weight-bold text-uppercase text-caption"
          style="color: #c2185b"
        >
          Mi cuenta
        </q-item-label>
        <q-item clickable v-ripple @click="goToAccount">
          <q-item-section avatar><q-icon name="person" color="pink-4" /></q-item-section>
          <q-item-section>{{ currentUser ? 'Mi panel' : 'Ingresar' }}</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="favorite" color="pink-4" /></q-item-section>
          <q-item-section>Lista de deseos</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="receipt_long" color="pink-4" /></q-item-section>
          <q-item-section>Mis pedidos</q-item-section>
        </q-item>
        <q-item clickable v-ripple>
          <q-item-section avatar><q-icon name="headset_mic" color="pink-4" /></q-item-section>
          <q-item-section>Contacto / Soporte</q-item-section>
        </q-item>
      </q-list>

      <!-- Social -->
      <div class="q-pa-md q-mt-auto">
        <q-separator class="q-mb-md" />
        <div class="text-caption text-grey q-mb-xs">Seguinos en</div>
        <div class="row q-gutter-sm">
          <q-btn round flat icon="camera_alt" size="sm" />
          <q-btn round flat icon="music_note" size="sm" />
          <q-btn round flat icon="phone_iphone" size="sm" />
        </div>
      </div>
    </q-drawer>

    <!-- ══════════════════════════════════ CONTENIDO ══════════════════════════════════ -->
    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- ══════════════════════════════════ FOOTER ════════════════════════════════════ -->
    <q-footer bordered :elevated="false">
      <!-- DESKTOP FOOTER -->
      <div class="sn-footer-desktop gt-sm">
        <div class="sn-footer-head">
          <img src="img/logob.png" alt="Importadora Supernova" class="sn-footer-logo" />
          <div>
            <div class="sn-footer-kicker">Belleza profesional al alcance de tu negocio</div>
          </div>
        </div>

        <div class="row q-col-gutter-xl sn-footer-content">
          <div class="col-12 col-md-3">
            <div class="sn-footer-title">Contacto</div>
            <div class="sn-footer-subtitle">
              Atención telefónica, entregas en CDMX y envíos a nivel nacional.
            </div>

            <div v-for="contact in footerContacts" :key="contact.title" class="sn-footer-block">
              <div class="sn-footer-line sn-footer-line--strong">
                <q-icon :name="contact.icon" size="18px" />
                <span>{{ contact.title }}</span>
              </div>
              <a :href="contact.href" class="sn-footer-line sn-footer-phone">
                <q-icon name="phone_iphone" size="17px" />
                <span>{{ contact.phone }}</span>
              </a>
              <div class="sn-footer-line">
                <q-icon name="schedule" size="17px" />
                <span>{{ contact.hours }}</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-5">
            <div class="sn-footer-title">Tiendas oficiales</div>
            <div class="sn-footer-subtitle">
              Tiendas oficiales en CDMX, visita nuestras sucursales para atención personalizada y
              entrega de pedidos.
            </div>
            <div class="sn-footer-stores">
              <div v-for="store in footerStores" :key="store.phone" class="sn-footer-block">
                <div class="sn-footer-line sn-footer-line--strong">
                  <q-icon name="public" size="18px" />
                  <span>{{ store.address }}</span>
                </div>
                <a :href="store.href" class="sn-footer-line sn-footer-phone">
                  <q-icon name="phone_iphone" size="17px" />
                  <span>{{ store.phone }}</span>
                </a>
                <div class="sn-footer-line">
                  <q-icon name="schedule" size="17px" />
                  <span>{{ store.hours }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="sn-footer-title">Redes Sociales</div>
            <div class="sn-footer-subtitle">Síguenos en nuestras redes sociales</div>

            <div class="sn-footer-socials">
              <a
                v-for="social in footerSocialLinks"
                :key="social.name"
                :href="social.href"
                target="_blank"
                rel="noopener"
                class="sn-footer-social"
                :aria-label="social.name"
              >
                <img :src="social.image" :alt="social.name" />
                <q-tooltip>{{ social.name }}</q-tooltip>
              </a>
            </div>

            <div class="sn-footer-title sn-footer-title--brands">Nuestras marcas</div>
            <div class="sn-footer-brands">
              <img
                v-for="brand in footerBrandLogos"
                :key="brand.name"
                :src="brand.image"
                :alt="brand.name"
                class="sn-footer-brand"
              />
            </div>
          </div>
        </div>

        <q-separator dark class="sn-footer-separator" />

        <div class="row items-center justify-between sn-footer-bottom">
          <div class="text-caption">
            © {{ new Date().getFullYear() }} Importadora Supernova. Todos los derechos reservados.
          </div>
        </div>
      </div>

      <!-- MOBILE BOTTOM NAV -->
      <div class="sn-mobile-nav lt-md">
        <q-tabs
          v-model="mobileTab"
          dense
          align="justify"
          active-color="primary"
          indicator-color="transparent"
        >
          <q-tab name="home" icon="home" label="Inicio" />
          <q-tab name="search" icon="format_list_bulleted" label="Categorías" />
          <q-tab name="fav" icon="favorite_border" label="Favoritos" />
          <q-tab name="cart" icon="shopping_cart" label="Carrito">
            <q-badge color="primary" floating rounded style="font-size: 10px">{{
              cartCount
            }}</q-badge>
          </q-tab>
          <q-tab name="user" icon="person" label="Cuenta" @click="goToAccount" />
        </q-tabs>
      </div>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { mockCategories } from 'src/data/mockStore'
import { getCategories } from 'src/services/categoryApi'
import { getBanners } from 'src/services/bannerApi'
import { useAuthSession } from 'src/services/authSession'

const router = useRouter()
const { currentUser, loadCurrentUser } = useAuthSession()

const leftDrawerOpen = ref(false)
const searchQuery = ref('')
const searchOpen = ref(false)
const mobileSearchInput = ref(null)
const activeCategory = ref('all')
const activeSubcategory = ref('')
const mobileTab = ref('home')
const cartCount = ref(0)
const categoriesMenu = ref(false)
const categoriesLoading = ref(true)
const categoryRequest = new AbortController()
const bannerRequest = new AbortController()

// Entrada fija "Todos" que siempre encabeza la lista
const categories = ref(mockCategories)
const megaCategory = ref(mockCategories.find((category) => category.subcategories?.length) || null)
const categoryPromo = ref(null)

const accountName = computed(() => {
  const user = currentUser.value
  const name = String(user?.nombre || '')
    .trim()
    .split(/\s+/)[0]

  return name || ''
})

const accountGreeting = computed(() => {
  return accountName.value ? `Hola, ${accountName.value}` : 'Mi cuenta'
})

onMounted(async () => {
  loadCurrentUser()
  const timeout = setTimeout(() => categoryRequest.abort(), 15000)

  try {
    const apiCategories = await getCategories({ signal: categoryRequest.signal })
    const allCategory = mockCategories.find((cat) => cat.slug === 'all')
    categories.value = allCategory ? [allCategory, ...apiCategories] : apiCategories
    megaCategory.value = apiCategories.find((category) => category.subcategories?.length) || null
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudieron cargar las categorias.')
  } finally {
    clearTimeout(timeout)
    categoriesLoading.value = false
  }

  try {
    const banners = await getBanners({ tipo: 1, signal: bannerRequest.signal })
    categoryPromo.value = banners.find((banner) => banner.imagen_web) || null
  } catch (error) {
    if (error.name !== 'AbortError') console.warn('No se pudo cargar la publicidad del menu.')
  }
})

onBeforeUnmount(() => {
  categoryRequest.abort()
  bannerRequest.abort()
})

watch(mobileTab, (tab) => {
  if (tab === 'home') {
    router.push('/')
    return
  }

  if (tab === 'search') {
    leftDrawerOpen.value = true
  }
})

const footerContacts = [
  {
    title: 'Envios a Nivel nacional',
    icon: 'local_shipping',
    phone: '+52 55 4556-4443',
    href: 'tel:+525545564443',
    hours: 'Lun-Sab 09:00am - 6:00pm',
  },
  {
    title: 'Entregas en CDMX',
    icon: 'handshake',
    phone: '+52 55 7997-8519',
    href: 'tel:+525579978519',
    hours: 'Lun-Sab 09:00am - 6:00pm',
  },
]

const footerStores = [
  {
    address: 'Calle articulo 123 #41, piso 4. colonia centro, CDMX.',
    phone: '55 3882-6981',
    href: 'tel:+525538826981',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle manuel de la peña y peña plaza #18 planta baja, locales A5 y A6.',
    phone: '56 3353-3333',
    href: 'tel:+525633533333',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle manuel de la peña y peña plaza #13 planta baja, locales 22 y 23',
    phone: '56 1333-0202',
    href: 'tel:+525613330202',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
  {
    address: 'Calle Republica de colombia 79, Plaza Carmen #51, planta baja, local 23 y 24.',
    phone: '55 7417-7482',
    href: 'tel:+525574177482',
    hours: 'Lun-Sab 09:00am - 4:00pm',
  },
]

const footerSocialLinks = [
  { name: 'Facebook', image: 'img/facebook.png', href: '#' },
  { name: 'Instagram', image: 'img/instagram.png', href: '#' },
  { name: 'TikTok', image: 'img/tiktok.png', href: '#' },
  { name: 'YouTube', image: 'img/youtube.png', href: '#' },
]

const footerBrandLogos = [
  { name: 'Le Reveur', image: 'img/le-reveur.png' },
  { name: 'Le Mussa', image: 'img/le-mussa.png' },
  { name: 'Wing Star', image: 'img/wing-star.png' },
]

function closeSearch() {
  searchOpen.value = false
  searchQuery.value = ''
}

async function focusMobileSearch() {
  await nextTick()
  mobileSearchInput.value?.focus?.()
}

function goToAccount() {
  leftDrawerOpen.value = false
  router.push(currentUser.value ? '/panel' : '/login')
}

function selectCategory(slug) {
  activeCategory.value = slug
  activeSubcategory.value = ''
  leftDrawerOpen.value = false
  router.push('/')
}

function selectCategoryFromMenu(slug) {
  selectCategory(slug)
  categoriesMenu.value = false
}

function selectSubcategory(categorySlug, subSlug) {
  activeCategory.value = categorySlug
  activeSubcategory.value = subSlug
  leftDrawerOpen.value = false
  router.push('/')
}

function selectSubcategoryFromMenu(categorySlug, subSlug) {
  selectSubcategory(categorySlug, subSlug)
  categoriesMenu.value = false
}

function previewCategory(category) {
  megaCategory.value = category
}
</script>
<style lang="scss">
.sn-topbar-account {
  max-width: 240px;

  .q-btn__content {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.sn-account-btn {
  min-width: 42px;
  min-height: 42px;
  padding: 4px 10px;
  border-radius: 6px;

  .q-btn__content {
    flex-wrap: nowrap;
    gap: 6px;
  }
}

.sn-account-copy {
  min-width: 0;
  max-width: 112px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.05;
}

.sn-account-hello {
  font-size: 10px;
  font-weight: 750;
  opacity: 0.9;
}

.sn-account-name {
  display: block;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 850;
  line-height: 1.15;
}

.sn-category-trigger {
  min-height: 34px;
  padding: 0 12px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0;

  .q-focus-helper {
    border-radius: 4px;
  }

  &:hover,
  &.sn-category-trigger--active {
    background: rgba(255, 255, 255, 0.16);
  }
}

.sn-category-menu,
.sn-subcategory-menu {
  border-radius: 6px;
  border: 1px solid rgba(194, 24, 91, 0.12);
  box-shadow: 0 8px 24px rgba(136, 14, 79, 0.2);
}

.sn-category-menu-item {
  min-height: 42px;
}

.sn-footer-stores {
  display: flex;
  flex-direction: column;
}

.sn-footer-store {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

@media (max-width: 959px) {
  .sn-footer-stores {
    grid-template-columns: 1fr;
  }
}

.sn-subcategory-menu {
  min-width: 240px;
  max-height: 70vh;
  overflow-y: auto;
}
</style>
