<?php

namespace App\Services;

use App\Repositories\TikTokVideoRepository;

class TikTokVideoService
{
    private $videos;

    public function __construct(TikTokVideoRepository $videos)
    {
        $this->videos = $videos;
    }

    public function listVideos()
    {
        $items = array();
        foreach ($this->videos->listActive() as $row) {
            $url = trim((string) $row['url']);
            $parts = parse_url($url);
            if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['path'])
                || strtolower($parts['scheme']) !== 'https'
                || !in_array(strtolower($parts['host']), array('tiktok.com', 'www.tiktok.com', 'm.tiktok.com'), true)
                || !preg_match('~^/@[A-Za-z0-9._-]+/video/([0-9]{10,25})/?$~D', $parts['path'], $match)) {
                continue;
            }

            $cover = trim((string) $row['portada_url']);
            if ($cover !== '' && (!filter_var($cover, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($cover, PHP_URL_SCHEME)) !== 'https')) {
                $cover = '';
            }
            $items[] = array(
                'id' => (int) $row['id'],
                'video_id' => $match[1],
                'url' => 'https://www.tiktok.com' . $parts['path'],
                'titulo' => trim((string) $row['titulo']),
                'portada_url' => $cover !== '' ? $cover : null,
                'posicion' => (int) $row['posicion'],
            );
        }
        return array('videos' => $items);
    }
}
