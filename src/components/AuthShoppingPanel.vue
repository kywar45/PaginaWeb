<template>
  <aside class="shopping-panel">
    <img
      v-image-skeleton
      :src="publicAsset('img/logob.png')"
      alt="Importadora Supernova"
      class="shopping-panel__logo"
      width="220"
      height="76"
    />
    <div class="shopping-panel__art" aria-hidden="true">
      <span class="shopping-panel__spark"><q-icon name="auto_awesome" /></span>
      <span class="shopping-panel__bag"><q-icon name="local_mall" /></span>
      <span class="shopping-panel__heart"><q-icon name="favorite" /></span>
      <span class="shopping-panel__tag">De todo para ti</span>
    </div>
    <div class="shopping-panel__content">
      <p class="shopping-panel__eyebrow">BELLEZA, CUIDADO Y MUCHO MÁS</p>
      <h2 v-if="!register">Qué gusto tenerte de vuelta.</h2>
      <p v-if="register" class="shopping-panel__copy">
        Crea tu cuenta y encuentra ese algo que te encanta, a precios para ti.
      </p>
      <ul class="shopping-panel__benefits">
        <li>
          <q-icon name="shopping_bag" />
          {{ register ? 'Tus compras, en un solo lugar' : 'Consulta cómo va tu pedido' }}
        </li>
        <li>
          <q-icon name="local_shipping" />
          {{
            register ? 'Tus datos listos para próximos pedidos' : 'Vuelve por eso que te encantó'
          }}
        </li>
      </ul>
    </div>
  </aside>
</template>

<script setup>
import { publicAsset } from 'src/utils/publicAsset'

defineProps({ register: Boolean })
</script>

<style scoped lang="scss">
.shopping-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  padding: 26px 24px;
  overflow: hidden;
  background:
    linear-gradient(
      158deg,
      transparent 0 17%,
      rgba(255, 255, 255, 0.08) 17.4% 21%,
      transparent 21.4% 44%,
      rgba(255, 255, 255, 0.06) 44.4% 48%,
      transparent 48.4%
    ),
    radial-gradient(
      110% 72% at -2% 10%,
      transparent 63%,
      rgba(255, 255, 255, 0.12) 64%,
      rgba(255, 255, 255, 0.035) 66%,
      transparent 68%
    ),
    radial-gradient(
      115% 76% at 103% 94%,
      transparent 60%,
      rgba(255, 255, 255, 0.13) 61%,
      transparent 65%
    ),
    linear-gradient(112deg, #ff4f79, #f43f6f 48%, #df2f61);
  color: #fff;
  border-radius: 20px;
}
.shopping-panel__logo {
  width: min(180px, 100%);
  height: 58px;
  object-fit: contain;
  object-position: left center;
  filter: brightness(0) invert(1);
}
.shopping-panel__art {
  position: relative;
  display: grid;
  place-items: center;
  height: 132px;
  width: 180px;
  max-width: 100%;
  margin: 14px auto 20px;
  border-radius: 50%;
  background: radial-gradient(ellipse, rgba(255, 255, 255, 0.18) 0 59%, transparent 60%);
}
.shopping-panel__bag {
  display: grid;
  place-items: center;
  width: 80px;
  height: 90px;
  color: #e73368;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 14px 26px #bb39661a;
  transform: rotate(-9deg);
  font-size: 58px;
}
.shopping-panel__spark,
.shopping-panel__heart {
  position: absolute;
  z-index: 1;
  display: grid;
  place-items: center;
  color: #df3264;
}
.shopping-panel__spark {
  color: #fff;
  top: 14px;
  left: 20px;
  font-size: 24px;
}
.shopping-panel__heart {
  top: 22px;
  right: 18px;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #fff;
  font-size: 18px;
  transform: rotate(12deg);
}
.shopping-panel__tag {
  position: absolute;
  bottom: 4px;
  right: 8px;
  padding: 5px 12px;
  border-radius: 20px;
  background: #dc2d60;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  transform: rotate(-5deg);
}
.shopping-panel__eyebrow {
  margin: 0 0 12px;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
}
h2 {
  margin: 0 0 12px;
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.025em;
}
.shopping-panel__copy {
  margin: 0;
  color: #fff;
  font-size: 16px;
  line-height: 1.5;
}
.shopping-panel__benefits {
  display: grid;
  gap: 10px;
  margin: 18px 0 0;
  padding: 16px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.3);
  list-style: none;
  li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    line-height: 1.5;
  }
  .q-icon {
    color: #fff;
    font-size: 22px;
    flex-shrink: 0;
  }
}
</style>
