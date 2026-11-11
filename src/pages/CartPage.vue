<template>
  <q-page class="cart-page">
    <PageBreadcrumbs title="Mi carrito" />
    <h1>
      Mi carrito <small>{{ cartCount }} piezas</small>
    </h1>
    <div v-if="!items.length" class="cart-empty">
      <q-icon name="shopping_bag" size="48px" />
      <p>Tu carrito está vacío.</p>
      <q-btn no-caps color="primary" unelevated label="Explorar productos" to="/categorias" />
    </div>
    <template v-else>
      <div class="cart-items">
        <article v-for="item in items" :key="item.id_producto" class="cart-item">
          <RouterLink :to="productLocation(item)"
            ><img :src="item.imagen || fallback" :alt="item.nombre" @error="imageFailed"
          /></RouterLink>
          <div class="cart-item__details">
            <RouterLink :to="productLocation(item)">{{ item.nombre }}</RouterLink>
            <small>{{ item.codigo }}</small>
            <span>{{ money(item.precio_final) }} por pieza</span>
            <div class="cart-item__quantity">
              <q-btn
                flat
                round
                dense
                icon="remove"
                :disable="item.quantity <= 1"
                :aria-label="`Quitar una pieza de ${item.nombre}`"
                @click="setQuantity(item.id_producto, item.quantity - 1)"
              />
              <span>{{ item.quantity }}</span>
              <q-btn
                flat
                round
                dense
                icon="add"
                :disable="item.quantity >= MAX_CART_QUANTITY"
                :aria-label="`Añadir una pieza de ${item.nombre}`"
                @click="setQuantity(item.id_producto, item.quantity + 1)"
              />
            </div>
          </div>
          <div class="cart-item__end">
            <q-btn
              flat
              round
              dense
              icon="delete_outline"
              :aria-label="`Eliminar ${item.nombre}`"
              @click="removeProduct(item.id_producto)"
            />
            <strong>{{ money(item.precio_final * item.quantity) }}</strong>
          </div>
        </article>
      </div>
      <div class="cart-total">
        <span>Total</span><strong>{{ money(total) }}</strong>
      </div>
    </template>
  </q-page>
</template>

<script setup>
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { useCart, MAX_CART_QUANTITY } from 'src/services/cart'
import { productLocation } from 'src/utils/productRoutes'
import { publicAsset } from 'src/utils/publicAsset'
const { items, cartCount, total, setQuantity, removeProduct } = useCart()
const fallback = publicAsset('img/product-no-image.svg')
const money = (value) =>
  Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
function imageFailed(event) {
  if (event.target.getAttribute('src') !== fallback) event.target.src = fallback
}
</script>

<style scoped>
.cart-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 16px 24px 110px;
  background: #fff;
}
.cart-page h1 {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin: 18px 0;
  color: #503340;
  font-size: 25px;
  font-weight: 700;
  line-height: 1.3;
}
.cart-page h1 small {
  color: #a18492;
  font-size: 12px;
  font-weight: 400;
}
.cart-empty {
  padding: 50px 16px;
  text-align: center;
  color: #bb8298;
}
.cart-items {
  display: grid;
  gap: 12px;
}
.cart-item {
  display: flex;
  gap: 14px;
  padding: 16px;
  border: 1px solid #f0dfe7;
  border-radius: 16px;
}
.cart-item img {
  width: 80px;
  height: 90px;
  object-fit: contain;
}
.cart-item__details {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
}
.cart-item__details > a {
  color: #59414e;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}
.cart-item__details > small {
  color: #ed396b;
  text-transform: uppercase;
  font-weight: 600;
}
.cart-item__details > span {
  color: #927784;
  font-size: 12px;
}
.cart-item__quantity {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 5px;
  border: 1px solid #f0dfe7;
  border-radius: 10px;
  color: #c92c5b;
}
.cart-item__end {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: space-between;
  gap: 8px;
  color: #dd3768;
}
.cart-item__end strong {
  white-space: nowrap;
  font-size: 17px;
}
.cart-total {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-top: 20px;
  padding: 20px;
  border-radius: 16px;
  background: #fff1f6;
  color: #743c53;
}
.cart-total strong {
  color: #df2f61;
  font-size: 24px;
}
@media (max-width: 599px) {
  .cart-page {
    padding: 10px 12px 110px;
  }
  .cart-item {
    gap: 8px;
    padding: 10px;
  }
  .cart-item img {
    width: 55px;
    height: 70px;
  }
  .cart-item__details > a {
    font-size: 12px;
  }
  .cart-item__end strong {
    font-size: 14px;
  }
}
</style>
