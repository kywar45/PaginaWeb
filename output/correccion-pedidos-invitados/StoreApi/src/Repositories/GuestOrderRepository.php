<?php

namespace App\Repositories;

use App\Exceptions\ApiException;

class GuestOrderRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function withCustomerLock($id, callable $callback)
    {
        $name = 'snfolio:guest:' . $id;
        $lock = $this->pdo->prepare('SELECT GET_LOCK(:folio_lock, 10)');
        $lock->execute(array(':folio_lock' => $name));
        if ((int) $lock->fetchColumn() !== 1) throw new ApiException('ORDER_BUSY', 'El folio se está registrando. Intenta de nuevo.', 409);
        try { return $callback(); }
        finally {
            $release = $this->pdo->prepare('SELECT RELEASE_LOCK(:folio_lock)');
            $release->execute(array(':folio_lock' => $name));
        }
    }

    // Called inside CartService's transaction and guest-cart lock.
    public function create($customerId, $carrierId, $envio, $quantity, $cents, $branchId)
    {
        if (!$this->pdo->inTransaction()) throw new \LogicException('Guest folio requires a cart transaction');
        $customer = $this->pdo->prepare('SELECT nombre, apellido FROM web_clientes_invitados WHERE id = :id FOR UPDATE');
        $customer->execute(array(':id' => $customerId));
        $person = $customer->fetch(\PDO::FETCH_ASSOC);
        if (!$person) throw new ApiException('GUEST_CUSTOMER_NOT_FOUND', 'Vuelve a revisar tus datos de contacto.', 409);
        $carrier = $this->pdo->prepare('SELECT id FROM shipping_carriers WHERE id = :id AND envio = :envio AND estatus = 1 FOR UPDATE');
        $carrier->execute(array(':id' => $carrierId, ':envio' => $envio));
        if (!$carrier->fetchColumn()) throw new ApiException('DELIVERY_UNAVAILABLE', 'El método de entrega ya no está disponible. Selecciona otro.', 409);
        $status = $this->pdo->query('SELECT id FROM imp_order_statuses WHERE id = 1')->fetchColumn();
        if (!$status) throw new ApiException('ORDER_STATUS_UNAVAILABLE', 'No está disponible el estado inicial del pedido.', 503);
        $reference = 'INV-' . $customerId;
        $existing = $this->pdo->prepare('SELECT id, id_usuario, id_cliente_invitado, shipping_carrier_id, cantidad, total, store_pickup
            FROM folios WHERE orden = :orden ORDER BY id ASC FOR UPDATE');
        $existing->execute(array(':orden' => $reference));
        $rows = $existing->fetchAll(\PDO::FETCH_ASSOC);
        if ($rows) {
            $row = $rows[0];
            if (count($rows) !== 1 || $row['id_usuario'] !== null || (int) $row['id_cliente_invitado'] !== $customerId
                || (int) $row['shipping_carrier_id'] !== $carrierId || (int) $row['cantidad'] !== $quantity
                || ($row['store_pickup'] === null ? null : (int) $row['store_pickup']) !== $branchId
                || (int) round((float) $row['total'] * 100) !== $cents) {
                throw new ApiException('GUEST_ORDER_CONFLICT', 'Ya existe un folio para estos datos con un pedido diferente. Comunícate con soporte.', 409);
            }
            return array('folio_id' => (int) $row['id'], 'orden' => $reference, 'cliente_invitado_id' => $customerId,
                'cantidad' => $quantity, 'total' => $cents / 100);
        }
        $insert = $this->pdo->prepare('INSERT INTO folios
            (id_usuario, id_cliente_invitado, nombres, orden, shipping_carrier_id, store_pickup, cantidad, total, order_status_id,
             fecha, estatus_ventas, envio, paqueteria, estatus, nota, efectivo, monto, cajas, id_operador, recojo,
             medidas, vendedora, pendiente, venta_paqueteria, iva, saldo_pendiente, marca, empaquetado, verificado,
             guia_gratis, marcado, seguro, id_detalle_orden)
            VALUES (NULL, :cliente, :nombres, :orden, :carrier, :store_pickup, :cantidad, :total, 1, NOW(), :estatus_ventas,
             :envio, :paqueteria, :estatus, :nota, :efectivo, :monto, :cajas, :id_operador, :recojo,
             :medidas, :vendedora, :pendiente, :venta_paqueteria, :iva, :saldo_pendiente, :marca, :empaquetado,
             :verificado, :guia_gratis, :marcado, :seguro, 0)');
        $params = array(':cliente' => $customerId, ':nombres' => trim($person['nombre'] . ' ' . $person['apellido']),
            ':orden' => $reference, ':carrier' => $carrierId, ':store_pickup' => $branchId, ':cantidad' => $quantity,
            ':total' => number_format($cents / 100, 2, '.', ''), ':estatus_ventas' => 'no contactado', ':marcado' => '0');
        foreach (array('envio', 'paqueteria', 'estatus', 'nota', 'efectivo', 'monto', 'cajas', 'id_operador', 'recojo',
            'medidas', 'vendedora', 'pendiente', 'venta_paqueteria', 'iva', 'saldo_pendiente', 'marca', 'empaquetado',
            'verificado', 'guia_gratis', 'seguro') as $field) $params[':' . $field] = '';
        $insert->execute($params);
        return array('folio_id' => (int) $this->pdo->lastInsertId(), 'orden' => $reference,
            'cliente_invitado_id' => $customerId, 'cantidad' => $quantity, 'total' => $cents / 100);
    }

    public function saveItems($customerId, $reference, array $items)
    {
        if (!$this->pdo->inTransaction()) throw new \LogicException('Guest order lines require a cart transaction');
        $expected = array();
        foreach ($items as $item) {
            $id = (int) $item['id_producto'];
            $price = (float) $item['precio_final'];
            if ($id < 1 || isset($expected[$id]) || !is_finite($price) || $price < 0 || $price > 999999.9999
                || !is_int($item['quantity']) || $item['quantity'] < 1 || $item['quantity'] > 2147483647) {
                throw new ApiException('ORDER_ITEM_INVALID', 'Revisa los precios y cantidades de los productos del carrito.', 422);
            }
            foreach (array('nombre', 'codigo') as $field) {
                if (!is_string($item[$field]) || !preg_match('//u', $item[$field]) || preg_match_all('/./us', $item[$field]) > 250
                    || preg_match('/[\x{10000}-\x{10FFFF}]/u', $item[$field])) {
                    throw new ApiException('ORDER_ITEM_INVALID', 'El nombre o código de un producto no es compatible con el registro del pedido. Comunícate con soporte.', 422);
                }
            }
            $expected[$id] = array('nombre' => $item['nombre'], 'codigo' => $item['codigo'],
                'precio' => number_format($price, 4, '.', ''), 'cantidad' => $item['quantity']);
        }
        $find = $this->pdo->prepare('SELECT id_usuario, id_cliente_invitado, id_producto, nombre, codigo, precio, cantidad
            FROM registro_usuario WHERE orden = :orden ORDER BY id ASC FOR UPDATE');
        $find->execute(array(':orden' => $reference));
        $existing = $find->fetchAll(\PDO::FETCH_ASSOC);
        if ($existing) {
            $seen = array();
            foreach ($existing as $row) {
                $id = (int) $row['id_producto'];
                $line = $expected[$id] ?? null;
                if ($row['id_usuario'] !== null || (int) $row['id_cliente_invitado'] !== $customerId || $line === null
                    || isset($seen[$id]) || $row['nombre'] !== $line['nombre'] || $row['codigo'] !== $line['codigo']
                    || number_format((float) $row['precio'], 4, '.', '') !== $line['precio'] || (int) $row['cantidad'] !== $line['cantidad']) {
                    throw new ApiException('GUEST_ORDER_ITEMS_CONFLICT', 'Los productos del folio ya registrado son diferentes. Comunícate con soporte.', 409);
                }
                $seen[$id] = true;
            }
            if (count($seen) !== count($expected)) throw new ApiException('GUEST_ORDER_ITEMS_CONFLICT', 'El detalle de este folio está incompleto. Comunícate con soporte.', 409);
            return count($seen);
        }
        $insert = $this->pdo->prepare('INSERT INTO registro_usuario
            (id_usuario, id_cliente_invitado, id_producto, nombre, codigo, precio, cantidad, fecha, orden,
             color, envio, paqueteria, rfc, nombred, apellidod, direcciond, coloniad, ciudadd, estadod, codigopd, telefonod, estatus, almacen)
            VALUES (NULL, :cliente, :producto, :nombre, :codigo, :precio, :cantidad, NOW(), :orden,
             :color, :envio, :paqueteria, :rfc, :nombred, :apellidod, :direcciond, :coloniad, :ciudadd, :estadod, :codigopd, :telefonod, :estatus, :almacen)');
        foreach ($expected as $id => $line) {
            $params = array(':cliente' => $customerId, ':producto' => $id, ':nombre' => $line['nombre'],
                ':codigo' => $line['codigo'], ':precio' => $line['precio'], ':cantidad' => $line['cantidad'], ':orden' => $reference);
            foreach (array('color', 'envio', 'paqueteria', 'rfc', 'nombred', 'apellidod', 'direcciond', 'coloniad',
                'ciudadd', 'estadod', 'codigopd', 'telefonod', 'estatus', 'almacen') as $field) $params[':' . $field] = '';
            $insert->execute($params);
        }
        return count($expected);
    }

    public function validateBranch($id)
    {
        $branch = $this->pdo->prepare('SELECT id FROM admin_tiendas WHERE id = :id AND store = 1 AND estatus = 1 FOR UPDATE');
        $branch->execute(array(':id' => $id));
        if (!$branch->fetchColumn()) throw new ApiException('BRANCH_UNAVAILABLE', 'Selecciona una sucursal disponible.', 409);
    }
}
