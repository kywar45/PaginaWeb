<?php

namespace App\Repositories;

class InstagramReelRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listActive()
    {
        $stmt = $this->pdo->query('SELECT id, url, titulo, posicion
            FROM web_instagram_reels WHERE activo = 1 ORDER BY posicion ASC, id DESC LIMIT 50');
        return $stmt->fetchAll();
    }
}
