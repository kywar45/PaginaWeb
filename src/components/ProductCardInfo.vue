<template>
  <div class="product-card-info" :class="{ 'product-card-info--compact': compact }">
    <h3 class="product-card-info__name" :title="product.nombre">{{ product.nombre }}</h3>
    <div v-if="product.codigo" class="product-card-info__code" :title="`Código: ${product.codigo}`">
      <strong>{{ product.codigo }}</strong>
    </div>
    <ProductViews :product="product" />
    <ProductAvailability :product="product" />
    <div class="product-card-info__pricing">
      <div class="product-card-info__bottom">
        <del v-if="product.tiene_descuento">{{ money(product.precio_normal) }}</del>
        <strong
          class="product-card-info__price"
          :class="{ 'product-card-info__price--sale': product.tiene_descuento }"
          >{{ money(product.precio_final) }}</strong
        >
        <span class="product-card-info__arrow" aria-hidden="true"
          ><q-icon name="arrow_forward"
        /></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import ProductViews from 'src/components/ProductViews.vue'
import ProductAvailability from 'src/components/ProductAvailability.vue'
defineProps({ product: { type: Object, required: true }, compact: Boolean })
const money = (value) =>
  Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
</script>

<style scoped lang="scss">
.product-card-info {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  padding: 12px 13px;
  text-align: left;
  border-top: 1px solid #f5ecee;
  background: #fff;
}
.product-card-info__name {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  margin: 0;
  height: 2.6em;
  flex-shrink: 0;
  color: #29242b;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}
.product-card-info__code {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  font-size: 12px;
  line-height: 1.4;
  color: #746a72;
}
.product-card-info__code strong {
  text-transform: uppercase;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #f4456f;
  font-size: 11px;
  font-weight: 700;
}
.product-card-info__pricing {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: auto;
  padding-top: 6px;
}
.product-card-info__visits {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #82727b;
  font-size: 11px;
  line-height: 1.4;
}
.product-card-info__visits .q-icon {
  color: #b98b9b;
  font-size: 14px;
}
.product-card-info__pricing del {
  color: #81757d;
  font-size: 12px;
  line-height: 1.4;
}
.product-card-info__price {
  color: #f12e68;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.4px;
}
.product-card-info__bottom {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 5px;
  flex-wrap: nowrap;
  white-space: nowrap;
}
.product-card-info__bottom > del {
  flex-shrink: 0;
  font-size: 10px;
}
.product-card-info__bottom > del + .product-card-info__price {
  font-size: 17px;
}
.product-card-info__bottom > .product-card-info__arrow {
  margin-left: auto;
  width: 22px;
  height: 22px;
  font-size: 15px;
}
.product-card-info__price--sale {
  color: #16a34a;
}
.product-card-info__arrow {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fbdce6;
  color: #f12e68;
  font-size: 18px;
}
@media (max-width: 599px) {
  .product-card-info {
    gap: 6px;
    padding: 12px 10px;
  }
  .product-card-info__name {
    font-size: 13px;
    line-height: 1.3;
  }
  .product-card-info__price {
    font-size: 20px;
  }
}
.product-card-info--compact {
  padding: 8px;
  gap: 0px;
}
.product-card-info--compact .product-card-info__name {
  font-size: 11px;
}
.product-card-info--compact .product-card-info__price {
  font-size: 16px;
}
.product-card-info--compact .product-card-info__pricing del {
  font-size: 10px;
}
.product-card-info--compact .product-card-info__arrow {
  width: 23px;
  height: 23px;
  font-size: 15px;
}
</style>
