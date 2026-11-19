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
            $url = $this->mediaUrl($row['url'], 'mp4');
            $cover = $this->mediaUrl($row['portada_url'], 'image');
            if ($url === null || $cover === null) continue;
            $items[] = array(
                'id' => (int) $row['id'],
                'url' => $url,
                'titulo' => trim((string) $row['titulo']),
                'portada_url' => $cover,
                'posicion' => (int) $row['posicion'],
            );
        }
        return array('videos' => $items);
    }

    private function mediaUrl($value, $type)
    {
        $url = trim((string) $value);
        if (!filter_var($url, FILTER_VALIDATE_URL) || preg_match('/[\x00-\x20\x7F]/', $url)) return null;
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['path'])
            || strtolower($parts['scheme']) !== 'https'
            || !in_array(strtolower($parts['host']), array('importadorasupernova.com', 'www.importadorasupernova.com'), true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)) return null;
        $pattern = $type === 'mp4' ? '~\.mp4$~iD' : '~\.(?:jpe?g|png|webp|avif)$~iD';
        return preg_match($pattern, $parts['path']) ? $url : null;
    }
}
