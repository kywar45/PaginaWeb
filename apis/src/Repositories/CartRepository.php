<?php

namespace App\Repositories;

use App\Exceptions\ApiException;

class CartRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Serializa cambios del mismo propietario, incluso cuando su carrito aun esta vacio.
    public function withOwnerLock($owner, callable $callback)
    {
        return $this->withOwnersLock(array($owner), $callback);
    }

    public function withOwnersLock($owners, callable $callback)
    {
        $locks = array();
        foreach ($owners as $owner) {
            $identity = $owner['user_id'] > 0 ? 'user:' . $owner['user_id'] : 'guest:' . $owner['guest_hash'];
            $locks[] = 'sncart:' . substr(hash('sha256', $identity), 0, 56);
        }
        $locks = array_unique($locks);
        sort($locks, SORT_STRING);
        $acquired = array();
        try {
            foreach ($locks as $lock) {
                $stmt = $this->pdo->prepare('SELECT GET_LOCK(:cart_lock, 10)');
                $stmt->execute(array(':cart_lock' => $lock));
                if ((int) $stmt->fetchColumn() !== 1) {
                    throw new ApiException('CART_BUSY', 'El carrito esta actualizandose. Intenta nuevamente.', 409);
                }
                $acquired[] = $lock;
            }
            $this->pdo->beginTransaction();
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        } finally {
            foreach (array_reverse($acquired) as $lock) {
                $stmt = $this->pdo->prepare('SELECT RELEASE_LOCK(:cart_lock)');
                $stmt->execute(array(':cart_lock' => $lock));
            }
        }
    }

    private function scope($owner, &$params)
    {
        $params[':owner_id'] = $owner['user_id'];
        if ($owner['user_id'] > 0) return 'id_usuario = :owner_id AND guest_hash IS NULL';
        $params[':guest_hash'] = $owner['guest_hash'];
        return 'id_usuario = :owner_id AND guest_hash = :guest_hash';
    }

    public function lines($owner)
    {
        $params = array();
        $where = $this->scope($owner, $params);
        $stmt = $this->pdo->prepare('SELECT * FROM carrito WHERE ' . $where . ' AND activo = 1 ORDER BY id ASC FOR UPDATE');
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deactivate($owner, $ids)
    {
        if (!$ids) return;
        $params = array();
        $where = $this->scope($owner, $params);
        $placeholders = array();
        foreach (array_values($ids) as $index => $id) {
            $key = ':row_' . $index;
            $placeholders[] = $key;
            $params[$key] = (int) $id;
        }
        $stmt = $this->pdo->prepare('UPDATE carrito SET activo = 0 WHERE ' . $where . ' AND id IN (' . implode(',', $placeholders) . ')');
        $stmt->execute($params);
    }

    public function save($owner, $rowId, $product, $quantity)
    {
        $params = array(
            ':product_id' => $product['id_producto'], ':name' => $product['nombre'],
            ':code' => $product['codigo'], ':price' => number_format($product['precio_final'], 2, '.', ''),
            ':quantity' => $quantity,
        );
        if ($rowId !== null) {
            $where = $this->scope($owner, $params);
            $params[':row_id'] = $rowId;
            $stmt = $this->pdo->prepare('UPDATE carrito SET id_producto = :product_id, nombre = :name,
                codigo = :code, precio = :price, cantidad = :quantity
                WHERE ' . $where . ' AND id = :row_id AND activo = 1');
        } else {
            $params[':owner_id'] = $owner['user_id'];
            $params[':guest_hash'] = $owner['guest_hash'];
            $stmt = $this->pdo->prepare('INSERT INTO carrito
                (id_usuario, guest_hash, id_producto, nombre, codigo, precio, cantidad, fecha, color, huella, activo)
                VALUES (:owner_id, :guest_hash, :product_id, :name, :code, :price, :quantity, NOW(), \'\', 0, 1)');
        }
        $stmt->execute($params);
    }
}
