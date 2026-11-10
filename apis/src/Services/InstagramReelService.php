<?php

namespace App\Services;

use App\Repositories\InstagramReelRepository;

class InstagramReelService
{
    private $reels;

    public function __construct(InstagramReelRepository $reels)
    {
        $this->reels = $reels;
    }

    public function listReels()
    {
        $items = array();
        foreach ($this->reels->listActive() as $row) {
            $parts = parse_url(trim((string) $row['url']));
            if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['path'])
                || strtolower($parts['scheme']) !== 'https'
                || !in_array(strtolower($parts['host']), array('instagram.com', 'www.instagram.com'), true)
                || !preg_match('~^/reel/([A-Za-z0-9_-]{1,30})/?$~D', $parts['path'], $match)) {
                continue;
            }
            $items[] = array(
                'id' => (int) $row['id'],
                'shortcode' => $match[1],
                'url' => 'https://www.instagram.com/reel/' . $match[1] . '/',
                'titulo' => trim((string) $row['titulo']),
                'posicion' => (int) $row['posicion'],
            );
        }
        return array('videos' => $items);
    }
}
