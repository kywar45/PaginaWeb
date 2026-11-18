<template>
  <q-page class="order-complete-page">
    <PageBreadcrumbs
      title="Pedido completado"
      back-to="/carrito"
      :parents="[{ label: 'Mi carrito', to: '/carrito' }]"
    />
    <section v-if="order" class="order-complete" aria-labelledby="completion-title">
      <div class="order-complete__check"><q-icon name="check_circle" /></div>
      <h1 id="completion-title">¡Tu pedido se ha registrado exitosamente!</h1>
      <div class="order-complete__reference">
        Número de orden: <strong>{{ order.orden }}</strong>
      </div>
      <p>Gracias por comprar en <strong>Importadora Supernova</strong>.</p>
      <p>
        Hemos recibido tu pedido correctamente. Uno de nuestros asesores de ventas se pondrá en
        contacto contigo a la brevedad para brindarte seguimiento y proporcionarte la información
        necesaria para continuar con tu compra.
      </p>
      <div class="order-complete__hours">
        <q-icon name="schedule" />
        <div>
          <strong>Nuestro horario de atención es:</strong
          ><span>Lunes a sábado, de 9:00 a. m. a 5:00 p. m.</span>
        </div>
      </div>
      <p>
        Si realizaste tu pedido fuera de este horario, nos comunicaremos contigo durante nuestro
        siguiente horario de atención.
      </p>
      <p>¡Agradecemos tu confianza y preferencia!</p>
      <div class="order-complete__actions">
        <q-btn
          tag="a"
          :href="receiptUrl"
          target="_blank"
          rel="noopener noreferrer"
          unelevated
          no-caps
          color="primary"
          icon="description"
          label="Ver mi pedido"
        />
        <q-btn outline no-caps color="primary" label="Seguir comprando" to="/" />
      </div>
      <p class="order-complete__signature">
        <strong>Importadora Supernova</strong><br /><em>De todo para ti, a precios para ti.</em>
      </p>
      <a class="order-complete__website" href="https://www.importadorasupernova.com"
        >importadorasupernova.com</a
      >
    </section>
  </q-page>
</template>

<script setup>
import { computed, nextTick, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageBreadcrumbs from 'src/components/PageBreadcrumbs.vue'
import { getCompletedOrder } from 'src/services/checkoutCompletion'

const router = useRouter()
const order = computed(() => getCompletedOrder())
onMounted(async () => {
  await nextTick()
  window.scrollTo({ left: 0, top: 0, behavior: 'instant' })
})
const receiptUrl = computed(() =>
  order.value
    ? router.resolve({
        name: 'pedido-invitado-pdf',
        params: { cliente: String(order.value.cliente_invitado_id), token: order.value.pdf_token },
      }).href
    : '',
)
</script>

<style scoped>
.order-complete-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 16px 24px 64px;
}
.order-complete {
  max-width: 650px;
  margin: 24px auto 0;
  padding: 32px;
  border: 1px solid #e6e6ec;
  border-radius: 22px;
  background: #fff;
  text-align: center;
}
.order-complete__check {
  display: grid;
  place-items: center;
  width: 68px;
  height: 68px;
  margin: 0 auto 18px;
  border-radius: 50%;
  background: #ebf8f0;
  color: #249365;
  font-size: 50px;
}
.order-complete h1 {
  margin: 0 0 18px;
  color: #30343b;
  font-size: 26px;
  font-weight: 750;
  line-height: 1.3;
}
.order-complete__reference {
  display: inline-block;
  margin-bottom: 22px;
  padding: 10px 18px;
  border-radius: 10px;
  background: #f6f7f8;
  color: #52515b;
}
.order-complete__reference strong {
  margin-left: 5px;
  color: #30343b;
  font-size: 19px;
}
.order-complete p {
  margin: 0 0 16px;
  color: #74727e;
  font-size: 14px;
  line-height: 1.65;
}
.order-complete__hours {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding: 15px;
  border-radius: 10px;
  background: #f7f7f9;
  text-align: left;
  color: #62616d;
  font-size: 14px;
}
.order-complete__hours .q-icon {
  color: #f63876;
  font-size: 25px;
}
.order-complete__hours span {
  display: block;
  margin-top: 4px;
}
.order-complete__actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin: 24px 0;
}
.order-complete__actions .q-btn {
  min-height: 44px;
  border-radius: 10px;
}
.order-complete p.order-complete__signature {
  margin-bottom: 6px;
  font-size: 13px;
}
.order-complete__website {
  color: #df2f65;
  text-decoration: none;
  font-size: 13px;
}
@media (max-width: 599px) {
  .order-complete-page {
    padding: 12px 16px 40px;
  }
  .order-complete {
    margin-top: 12px;
    padding: 22px 18px;
    border-radius: 16px;
  }
  .order-complete h1 {
    font-size: 21px;
  }
  .order-complete p {
    font-size: 13px;
    line-height: 1.5;
  }
  .order-complete__check {
    width: 50px;
    height: 50px;
    font-size: 40px;
    margin-bottom: 14px;
  }
  .order-complete__actions {
    flex-direction: column;
  }
  .order-complete__hours {
    font-size: 12px;
    padding: 12px;
  }
}
</style>
