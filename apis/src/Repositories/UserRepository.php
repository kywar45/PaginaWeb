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
            'SELECT id, nombre, apellido, correo, pass, token_registro, user_vip
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
            'SELECT id, nombre, apellido, correo, token_registro, user_vip
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
            WHERE COALESCE(id_usuario, (SELECT i.usuario_id FROM web_guest_registrations i WHERE i.folio_id = folios.id AND i.used_at IS NOT NULL)) = :user_id
              AND fecha_pago IS NOT NULL
              AND fecha_pago <> '0000-00-00 00:00:00'");
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countOrders($userId)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM folios WHERE COALESCE(id_usuario, (SELECT i.usuario_id FROM web_guest_registrations i WHERE i.folio_id = folios.id AND i.used_at IS NOT NULL)) = :user_id');
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function ordersPage($userId, $limit, $offset)
    {
        $stmt = $this->pdo->prepare('SELECT id, orden, fecha, fecha_pago, total, estatus
            FROM folios WHERE COALESCE(id_usuario, (SELECT i.usuario_id FROM web_guest_registrations i WHERE i.folio_id = folios.id AND i.used_at IS NOT NULL)) = :user_id
            ORDER BY fecha DESC, id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':user_id', (int) $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function startVerifiedSession($id, $sessionHash, $passwordHash)
    {
        // Refuse a login validated against a password that a concurrent reset replaced.
        $stmt = $this->pdo->prepare("UPDATE usuario SET token_sesion = ?
            WHERE id = ? AND BINARY pass = ? AND token_registro IS NOT NULL AND TRIM(token_registro) <> ''");
        $stmt->execute(array($sessionHash, $id, $passwordHash));
        return $stmt->rowCount() === 1;
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
