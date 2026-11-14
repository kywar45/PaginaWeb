<?php

namespace App\Repositories;

use App\Exceptions\ApiException;

class AddressRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function all($userId)
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, apellido, colonia, ciudad, estado, cp, direccion, telefono, defecto
            FROM direccion_envio WHERE id_usuario = ? AND activo = 1 ORDER BY defecto DESC, id DESC');
        $stmt->execute(array($userId));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['defecto'] = (int) $row['defecto'] === 1;
        }
        return $rows;
    }

    public function mutate($userId, $action, $id, array $address)
    {
        $this->pdo->beginTransaction();
        try {
            // Serialize changes per customer, including their first address.
            $lock = $this->pdo->prepare('SELECT id FROM usuario WHERE id = ? FOR UPDATE');
            $lock->execute(array($userId));
            if (!$lock->fetchColumn()) throw new ApiException('UNAUTHORIZED', 'Inicia sesión nuevamente.', 401);
            if ($id !== null) {
                $owned = $this->pdo->prepare('SELECT id FROM direccion_envio WHERE id = ? AND id_usuario = ? AND activo = 1 FOR UPDATE');
                $owned->execute(array($id, $userId));
                if (!$owned->fetchColumn()) throw new ApiException('NOT_FOUND', 'No encontramos esa dirección.', 404);
            }
            if ($action === 'guardar') {
                $values = array();
                foreach (array('nombre', 'apellido', 'colonia', 'ciudad', 'estado', 'cp', 'direccion', 'telefono') as $field) $values[] = $address[$field];
                if ($id === null) {
                    $stmt = $this->pdo->prepare('INSERT INTO direccion_envio
                        (nombre, apellido, colonia, ciudad, estado, cp, direccion, telefono, id_usuario, activo, fecha_creacion, defecto)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), 0)');
                    $stmt->execute(array_merge($values, array($userId)));
                    $id = (int) $this->pdo->lastInsertId();
                } else {
                    $stmt = $this->pdo->prepare('UPDATE direccion_envio SET nombre = ?, apellido = ?, colonia = ?, ciudad = ?, estado = ?, cp = ?, direccion = ?, telefono = ?
                        WHERE id = ? AND id_usuario = ? AND activo = 1');
                    $stmt->execute(array_merge($values, array($id, $userId)));
                }
            } elseif ($action === 'eliminar') {
                // Preserve historical references; never delete the row physically.
                $stmt = $this->pdo->prepare('UPDATE direccion_envio SET activo = 0, defecto = 0 WHERE id = ? AND id_usuario = ? AND activo = 1');
                $stmt->execute(array($id, $userId));
            }
            if ($action === 'predeterminada' || ($action === 'guardar' && $address['defecto'])) {
                $stmt = $this->pdo->prepare('UPDATE direccion_envio SET defecto = CASE WHEN id = ? AND activo = 1 THEN 1 ELSE 0 END WHERE id_usuario = ?');
                $stmt->execute(array($id, $userId));
            }
            // Normalize legacy duplicates, select a replacement after removal, and
            // make the first address the default automatically.
            $candidate = $this->pdo->prepare('SELECT id FROM direccion_envio WHERE id_usuario = ? AND activo = 1 ORDER BY defecto DESC, id ASC LIMIT 1');
            $candidate->execute(array($userId));
            $defaultId = (int) $candidate->fetchColumn();
            $normalize = $this->pdo->prepare('UPDATE direccion_envio SET defecto = CASE WHEN id = ? AND activo = 1 THEN 1 ELSE 0 END WHERE id_usuario = ?');
            $normalize->execute(array($defaultId, $userId));
            $rows = $this->all($userId);
            $this->pdo->commit();
            return $rows;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
}
