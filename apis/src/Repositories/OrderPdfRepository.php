<?php

namespace App\Repositories;

class OrderPdfRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function findOwned($id, $userId)
    {
        $stmt = $this->pdo->prepare('SELECT f.id, f.id_usuario, f.orden, f.nombres, f.fecha,
            f.fecha_pago, f.total, f.estatus, c.nombre AS carrier_name, c.envio AS carrier_envio
            FROM folios f
            LEFT JOIN shipping_carriers c ON c.id = f.shipping_carrier_id
            WHERE f.id = :id AND f.id_usuario = :user_id LIMIT 1');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function itemsForOrder($reference, $userId)
    {
        $stmt = $this->pdo->prepare('SELECT r.id, r.id_usuario, r.id_producto, r.nombre, r.codigo, r.precio, r.cantidad, r.orden,
            p.preciou AS precio_normal
            FROM registro_usuario r LEFT JOIN productos p ON p.id = r.id_producto
            WHERE r.orden = :reference AND r.id_usuario = :user_id
            ORDER BY r.codigo ASC, r.nombre ASC, r.id ASC');
        $stmt->bindValue(':reference', $reference, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findGuestOwned($id, $customerId)
    {
        $stmt = $this->pdo->prepare('SELECT f.id, f.orden, f.nombres, f.fecha, f.fecha_pago, f.total, f.estatus,
            g.correo, c.envio AS carrier_envio,
            CASE WHEN c.envio = 0 THEN COALESCE(t.nombre, c.nombre) ELSE c.nombre END AS carrier_name
            FROM folios f
            INNER JOIN web_clientes_invitados g ON g.id = f.id_cliente_invitado
            LEFT JOIN shipping_carriers c ON c.id = f.shipping_carrier_id
            LEFT JOIN admin_tiendas t ON t.id = f.store_pickup
            WHERE f.id = :id AND f.id_cliente_invitado = :cliente AND f.id_usuario IS NULL LIMIT 1');
        $stmt->execute(array(':id' => $id, ':cliente' => $customerId));
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function itemsForGuestOrder($reference, $customerId)
    {
        $stmt = $this->pdo->prepare('SELECT r.codigo, r.nombre, r.precio, r.cantidad, p.preciou AS precio_normal
            FROM registro_usuario r LEFT JOIN productos p ON p.id = r.id_producto
            WHERE r.orden = :orden AND r.id_cliente_invitado = :cliente AND r.id_usuario IS NULL
            ORDER BY r.codigo ASC, r.nombre ASC, r.id ASC');
        $stmt->execute(array(':orden' => $reference, ':cliente' => $customerId));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
