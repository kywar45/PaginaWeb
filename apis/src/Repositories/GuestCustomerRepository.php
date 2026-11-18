<?php

namespace App\Repositories;

use App\Exceptions\ApiException;

class GuestCustomerRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function register(array $customer, $requestHash = null)
    {
        if ($requestHash !== null) $requestHash = hash('sha256', $requestHash . json_encode($customer));
        // Checkout reuses only the same purchase attempt, never another order's contact record.
        // Standalone guest registration keeps its existing behavior.
        $lockName = 'snguest:' . substr(hash('sha256', ($requestHash ?? '') . json_encode($customer)), 0, 56);
        $lock = $this->pdo->prepare('SELECT GET_LOCK(:guest_lock, 10)');
        $lock->execute(array(':guest_lock' => $lockName));
        if ((int) $lock->fetchColumn() !== 1) {
            throw new ApiException('GUEST_CUSTOMER_BUSY', 'Tus datos se están guardando. Intenta de nuevo.', 409);
        }
        try {
            $this->pdo->beginTransaction();
            $params = array();
            $conditions = array();
            foreach ($customer as $field => $value) {
                $params[':' . $field] = $value;
                $conditions[] = $field . ' = :' . $field;
            }
            $find = $this->pdo->prepare('SELECT id FROM web_clientes_invitados WHERE ' . ($requestHash === null ? implode(' AND ', $conditions) : 'checkout_request_hash = :request_hash') . ' ORDER BY id ASC LIMIT 1');
            $find->execute($requestHash === null ? $params : array(':request_hash' => $requestHash));
            $id = $find->fetchColumn();
            if (!$id) {
                $insert = $this->pdo->prepare('INSERT INTO web_clientes_invitados
                    (nombre, apellido, correo, estado, ciudad, colonia, direccion, codigo_postal, telefono' . ($requestHash === null ? '' : ', checkout_request_hash') . ')
                    VALUES (:nombre, :apellido, :correo, :estado, :ciudad, :colonia, :direccion, :codigo_postal, :telefono' . ($requestHash === null ? '' : ', :request_hash') . ')');
                if ($requestHash !== null) $params[':request_hash'] = $requestHash;
                $insert->execute($params);
                $id = $this->pdo->lastInsertId();
            }
            $this->pdo->commit();
            return (int) $id;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        } finally {
            $release = $this->pdo->prepare('SELECT RELEASE_LOCK(:guest_lock)');
            $release->execute(array(':guest_lock' => $lockName));
        }
    }
}
