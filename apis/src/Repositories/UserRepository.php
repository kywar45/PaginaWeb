<?php

namespace App\Repositories;

class UserRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByCorreo($correo)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, apellido, correo, pass, tipo, nivel, user_vip
             FROM usuario
             WHERE correo = ?
             LIMIT 1'
        );

        $stmt->execute(array($correo));
        $usuario = $stmt->fetch();

        return $usuario ? $usuario : null;
    }

    public function findBySessionHash($sessionHash)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, apellido, correo, tipo, nivel, user_vip
             FROM usuario
             WHERE token_sesion = ?
             LIMIT 1'
        );

        $stmt->execute(array($sessionHash));
        $usuario = $stmt->fetch();

        return $usuario ? $usuario : null;
    }

    public function countPaidOrders($userId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM folios
            WHERE id_usuario = :user_id
              AND fecha_pago IS NOT NULL
              AND fecha_pago <> '0000-00-00 00:00:00'");
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countOrders($userId)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM folios WHERE id_usuario = :user_id');
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function ordersPage($userId, $limit, $offset)
    {
        $stmt = $this->pdo->prepare('SELECT id, orden, fecha, fecha_pago, total, estatus
            FROM folios WHERE id_usuario = :user_id
            ORDER BY fecha DESC, id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateSessionHash($id, $sessionHash)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuario
             SET token_sesion = ?
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute(array($sessionHash, $id));
    }

    public function clearSessionHash($sessionHash)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuario
             SET token_sesion = ?
             WHERE token_sesion = ?
             LIMIT 1'
        );

        $stmt->execute(array('', $sessionHash));
    }

    public function updatePasswordHash($id, $passwordHash)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuario
             SET pass = ?
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute(array($passwordHash, $id));
    }
}
