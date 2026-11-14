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
        $stmt = $this->pdo->prepare('SELECT id, id_usuario, id_producto, nombre, codigo, precio, cantidad, orden
            FROM registro_usuario WHERE orden = :reference AND id_usuario = :user_id
            ORDER BY codigo ASC, nombre ASC, id ASC');
        $stmt->bindValue(':reference', $reference, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
