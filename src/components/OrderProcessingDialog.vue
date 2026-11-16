<template>
  <q-dialog
    :model-value="open"
    persistent
    no-route-dismiss
    transition-show="scale"
    transition-hide="fade"
  >
    <q-card
      class="order-processing"
      :class="{ 'order-processing--success': state === 'success' }"
      role="status"
      aria-live="polite"
      :aria-busy="state === 'processing'"
    >
      <div class="order-processing__visual" :class="`is-${state}`" aria-hidden="true">
        <template v-if="state === 'processing'">
          <span class="order-processing__orbit"></span>
          <span class="order-processing__orbit order-processing__orbit--second"></span>
          <q-icon name="shopping_bag" class="order-processing__bag" />
          <span class="order-processing__spark order-processing__spark--one">✦</span>
          <span class="order-processing__spark order-processing__spark--two">✧</span>
        </template>
        <q-icon v-else :name="state === 'success' ? 'check_circle' : 'cloud_off'" size="64px" />
      </div>
      <span class="order-processing__brand">IMPORTADORA SUPERNOVA</span>
      <h2>
        {{
          state === 'processing'
            ? 'Estamos preparando tu pedido'
            : state === 'success'
              ? '¡Tu pedido se ha registrado exitosamente! ✅'
              : 'Necesitamos intentarlo de nuevo'
        }}
      </h2>
      <template v-if="state === 'processing'">
        <p>Estamos guardando tu pedido y preparando su comprobante. Espera un momento.</p>
        <q-spinner-dots color="primary" size="36px" />
        <small>No cierres esta ventana mientras terminamos.</small>
      </template>
      <template v-else-if="state === 'success'">
        <div class="order-processing__reference">
          <span>Número de orden:</span> <strong>{{ order?.orden }}</strong>
        </div>
        <div class="order-processing__message order-processing__message--full">
          <p>Gracias por comprar en <strong>Importadora Supernova</strong>.</p>
          <p>
            Hemos recibido tu pedido correctamente. Uno de nuestros asesores de ventas se pondrá en
            contacto contigo a la brevedad para brindarte seguimiento y proporcionarte la
            información necesaria para continuar con tu compra.
          </p>
          <p class="order-processing__hours">
            <strong>Nuestro horario de atención es:</strong><br />Lunes a sábado, de 9:00 a. m. a
            5:00 p. m.
          </p>
          <p>
            Si realizaste tu pedido fuera de este horario, nos comunicaremos contigo durante nuestro
            siguiente horario de atención.
          </p>
          <p>¡Agradecemos tu confianza y preferencia!</p>
          <p class="order-processing__signature">
            <strong>Importadora Supernova</strong><br /><em>De todo para ti, a precios para ti.</em>
          </p>
          <a
            class="order-processing__website"
            href="https://www.importadorasupernova.com"
            target="_blank"
            rel="noopener noreferrer"
            >🌐 <strong>importadorasupernova.com</strong></a
          >
        </div>
        <div class="order-processing__message order-processing__message--compact">
          <p>Gracias por comprar en <strong>Importadora Supernova</strong>.</p>
          <p>
            Un asesor te contactará pronto para dar seguimiento y ayudarte a continuar tu compra.
          </p>
          <p class="order-processing__hours">
            <strong>Horario de atención</strong><br />Lunes a sábado · 9:00 a. m. a 5:00 p. m.
          </p>
          <p>
            Si compraste fuera de horario, te contactaremos en nuestro siguiente horario de
            atención.
          </p>
          <p class="order-processing__signature"><em>¡Gracias por tu confianza!</em></p>
        </div>
        <p v-if="error" class="order-processing__error" role="alert">{{ error }}</p>
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="description"
          label="Ver mi pedido"
          @click="$emit('print')"
        />
        <q-btn flat no-caps color="grey-7" label="Cerrar" @click="$emit('close')" />
      </template>
      <template v-else>
        <p>
          {{
            error ||
            'No pudimos confirmar el registro del pedido. Revisa tu conexión y vuelve a intentar.'
          }}
        </p>
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Reintentar"
          icon="refresh"
          @click="$emit('retry')"
        />
        <q-btn flat no-caps color="grey-7" label="Volver a mis datos" @click="$emit('close')" />
      </template>
    </q-card>
  </q-dialog>
</template>

<script setup>
defineProps({
  open: Boolean,
  state: { type: String, default: 'processing' },
  order: { type: Object, default: null },
  error: { type: String, default: '' },
})
defineEmits(['print', 'retry', 'close'])
</script>

<style scoped>
.order-processing {
  width: 420px;
  max-width: calc(100vw - 32px);
  padding: 30px 26px 24px;
  max-height: calc(100dvh - 32px);
  overflow-y: auto;
  border-radius: 24px;
  background: #fff;
  text-align: center;
  box-shadow: 0 20px 80px #30202b30;
}
.order-processing--success {
  width: 520px;
  padding: 24px;
}
.order-processing--success .order-processing__visual {
  width: 70px;
  height: 70px;
  margin-bottom: 14px;
}
.order-processing--success h2 {
  font-size: 22px;
}
.order-processing__message {
  text-align: left;
}
.order-processing__message--compact {
  display: none;
}
.order-processing__hours {
  padding: 12px 14px;
  border-left: 3px solid #ec3c70;
  border-radius: 8px;
  background: #f7f7f9;
}
.order-processing__signature {
  text-align: center;
}
.order-processing__website {
  display: block;
  margin-bottom: 16px;
  color: #dd2d63;
  text-align: center;
  text-decoration: none;
  font-size: 13px;
}
.order-processing p.order-processing__error {
  color: #b42318;
}
.order-processing__visual {
  position: relative;
  display: grid;
  place-items: center;
  width: 114px;
  height: 114px;
  margin: 0 auto 22px;
  border-radius: 50%;
  background: #fff2f6;
  color: #ec3c70;
}
.order-processing__orbit {
  position: absolute;
  inset: 0;
  border: 2px solid transparent;
  border-top-color: #ec3c70;
  border-right-color: #f8bbcf;
  border-radius: 50%;
  animation: order-orbit 1.8s linear infinite;
}
.order-processing__orbit--second {
  inset: 9px;
  border-top-color: #f6afc6;
  border-right-color: transparent;
  animation-direction: reverse;
  animation-duration: 2.7s;
}
.order-processing__bag {
  font-size: 43px;
  animation: order-float 1.8s ease-in-out infinite;
}
.order-processing__spark {
  position: absolute;
  font-size: 24px;
  animation: order-float 2.2s ease-in-out infinite;
}
.order-processing__spark--one {
  top: 8px;
  right: -5px;
}
.order-processing__spark--two {
  bottom: 10px;
  left: -8px;
  animation-delay: 0.5s;
}
.order-processing__visual.is-success {
  background: #ecf8f0;
  color: #249365;
}
.order-processing__visual.is-error {
  background: #fff3ef;
  color: #cb5d40;
}
.order-processing__brand {
  color: #98909a;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1.6px;
}
.order-processing h2 {
  margin: 10px 0 12px;
  color: #30343b;
  font-size: 23px;
  font-weight: 750;
  line-height: 1.3;
}
.order-processing p {
  margin: 0 0 14px;
  color: #777681;
  font-size: 13px;
  line-height: 1.7;
}
.order-processing small {
  display: block;
  margin-top: 5px;
  color: #99939c;
  font-size: 11px;
}
.order-processing__reference {
  display: inline-block;
  margin: 0 0 14px;
  padding: 7px 18px;
  border-radius: 9px;
  background: #f6f7f8;
  color: #30343b;
  font-size: 17px;
  letter-spacing: 1px;
}
.order-processing__reference span {
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0;
}
.order-processing .q-btn {
  width: 100%;
  min-height: 42px;
  margin-top: 8px;
  border-radius: 11px;
}
@media (max-width: 599px) {
  .order-processing--success {
    width: 100%;
    padding: 18px;
    border-radius: 20px;
  }
  .order-processing--success .order-processing__visual {
    width: 44px;
    height: 44px;
    margin-bottom: 8px;
  }
  .order-processing--success .order-processing__visual .q-icon {
    font-size: 40px !important;
  }
  .order-processing--success h2 {
    margin: 8px 0 12px;
    font-size: 19px;
  }
  .order-processing__message--full {
    display: none;
  }
  .order-processing__message--compact {
    display: block;
  }
  .order-processing--success p {
    margin-bottom: 10px;
    line-height: 1.45;
  }
  .order-processing__reference {
    margin-bottom: 12px;
    padding: 6px 12px;
    font-size: 16px;
  }
  .order-processing__hours {
    padding: 9px 10px;
  }
  .order-processing--success .q-btn {
    margin-top: 4px;
  }
}
@keyframes order-orbit {
  to {
    transform: rotate(360deg);
  }
}
@keyframes order-float {
  50% {
    transform: translateY(-5px);
  }
}
@media (prefers-reduced-motion: reduce) {
  .order-processing__orbit,
  .order-processing__bag,
  .order-processing__spark {
    animation: none;
  }
}
</style>
