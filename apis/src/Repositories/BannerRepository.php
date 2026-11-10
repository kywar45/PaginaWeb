<?php

namespace App\Repositories;

class BannerRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listPage($tipo, $limit, $offset)
    {
        $sql = 'SELECT id, nombre_web, nombre_movil, posicion,
                       redireccion_web, redireccion_movil, tipo
                FROM admin_banners';

        if ($tipo !== null) {
            $sql .= ' WHERE tipo = :tipo';
        }

        $sql .= ' ORDER BY posicion ASC, id ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);

        if ($tipo !== null) {
            $stmt->bindValue(':tipo', $tipo, \PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
