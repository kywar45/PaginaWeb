<?php

namespace App\Repositories;

class TikTokVideoRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listActive()
    {
        $stmt = $this->pdo->query('SELECT id, url, titulo, portada_url, posicion
            FROM web_tiktok_videos WHERE activo = 1 ORDER BY posicion ASC, id DESC LIMIT 50');
        return $stmt->fetchAll();
    }
}
