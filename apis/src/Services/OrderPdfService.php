<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\OrderPdfRepository;

class OrderPdfService
{
    private $orders;
    public function __construct(OrderPdfRepository $orders) { $this->orders = $orders; }

    private function clean($value)
    {
        return trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function cents($value)
    {
        $number = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($number) && is_finite((float) $number) ? (int) round((float) $number * 100) : null;
    }

    private function priceUnits($value)
    {
        if (!preg_match('/^([0-9]{1,6})(?:\.([0-9]{1,4}))?$/D', trim((string) $value), $parts)) return null;
        return (int) $parts[1] * 10000 + (int) str_pad(isset($parts[2]) ? $parts[2] : '', 4, '0');
    }

    public function prepare($id, $userId, $email = null)
    {
        $order = $this->orders->findOwned($id, $userId);
        if (!$order) throw new ApiException('ORDER_NOT_FOUND', 'El pedido no existe o no pertenece a tu cuenta.', 404);
        $reference = trim((string) $order['orden']);
        if ($reference === '') {
            throw new ApiException('ORDER_REFERENCE_CONFLICT', 'No pudimos identificar el detalle de este pedido. Comunicate con Supernova.', 409);
        }
        $rows = $this->orders->itemsForOrder($reference, $userId);
        $items = array();
        $subtotalUnits = 0;
        $displaySubtotal = 0;
        $pieces = 0;
        foreach ($rows as $row) {
            $quantity = filter_var($row['cantidad'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
            $price = $this->priceUnits($row['precio']);
            if ($quantity === false || $price === null || ($price > 0 && $quantity > intdiv(PHP_INT_MAX - 50, $price))) {
                throw new ApiException('ORDER_DATA_INVALID', 'No pudimos preparar los importes de este pedido. Comunicate con Supernova.', 409);
            }
            $amount = $quantity * $price;
            if ($amount > PHP_INT_MAX - 50 - $subtotalUnits) throw new ApiException('ORDER_DATA_INVALID', 'Importe de pedido fuera de rango.', 409);
            $items[] = array('codigo' => $this->clean($row['codigo']), 'nombre' => $this->clean($row['nombre']),
                'cantidad' => $quantity, 'precio_unidades' => $price, 'precio_centavos' => intdiv($price + 50, 100), 'importe_centavos' => intdiv($amount + 50, 100));
            $subtotalUnits += $amount;
            $displaySubtotal += intdiv($amount + 50, 100);
            $pieces += $quantity;
        }
        $subtotal = intdiv($subtotalUnits + 50, 100);
        $total = $this->cents($order['total']);
        if ($total === null) $total = $subtotal;
        $paidDate = trim((string) $order['fecha_pago']);
        return array('id' => (int) $order['id'], 'orden' => $reference, 'fecha' => $order['fecha'],
            'cliente' => $this->clean($order['nombres']) ?: 'Cliente sin registrar',
            'correo' => $this->clean($email),
            'entrega_tipo' => $order['carrier_envio'] === null ? 'Método por confirmar'
                : ((int) $order['carrier_envio'] === 1 ? 'Envío por paquetería' : 'Entrega en punto'),
            'transportista' => $this->clean($order['carrier_name']),
            'pagado' => $paidDate !== '' && substr($paidDate, 0, 10) !== '0000-00-00',
            'estatus' => $this->clean($order['estatus']), 'productos' => $items, 'piezas' => $pieces,
            'subtotal_centavos' => $subtotal, 'redondeo_centavos' => $subtotal - $displaySubtotal,
            'ajustes_centavos' => $total - $subtotal, 'total_centavos' => $total);
    }
}
