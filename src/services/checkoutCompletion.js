import { shallowRef } from 'vue'

// Kept only in this running application; a direct visit or refresh cannot create a confirmation.
const completedOrder = shallowRef(null)

export function rememberCompletedOrder(order) {
  if (
    !Number.isSafeInteger(order?.folio_id) ||
    order.folio_id < 1 ||
    !Number.isSafeInteger(order.cliente_invitado_id) ||
    order.cliente_invitado_id < 1 ||
    order.orden !== `INV-${order.cliente_invitado_id}` ||
    !/^p1\.[A-Za-z0-9_-]{54}$/.test(order.pdf_token || '') ||
    order.carrito_vaciado !== true
  ) {
    throw new Error('No pudimos preparar la confirmación del pedido.')
  }
  completedOrder.value = Object.freeze({
    orden: order.orden,
    cliente_invitado_id: order.cliente_invitado_id,
    pdf_token: order.pdf_token,
    cantidad: order.cantidad,
    total: order.total,
  })
}

export function getCompletedOrder() {
  return completedOrder.value
}
