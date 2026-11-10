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
