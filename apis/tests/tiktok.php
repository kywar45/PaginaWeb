<?php

require __DIR__ . '/../src/Repositories/TikTokVideoRepository.php';
require __DIR__ . '/../src/Services/TikTokVideoService.php';

class FakeTikTokVideos extends \App\Repositories\TikTokVideoRepository
{
    public function __construct() {}
    public function listActive()
    {
        $base = array('id' => '1', 'titulo' => 'Novedades', 'portada_url' => null, 'posicion' => '2');
        return array(
            $base + array('url' => 'https://www.tiktok.com/@supernova/video/1234567890123456789?foo=bar'),
            $base + array('url' => 'https://tiktok.com.evil.test/@supernova/video/1234567890123456789'),
            $base + array('url' => 'https://vm.tiktok.com/short'),
            $base + array('url' => 'javascript:alert(1)'),
        );
    }
}

$service = new \App\Services\TikTokVideoService(new FakeTikTokVideos());
$videos = $service->listVideos()['videos'];
if (count($videos) !== 1 || $videos[0]['video_id'] !== '1234567890123456789'
    || $videos[0]['url'] !== 'https://www.tiktok.com/@supernova/video/1234567890123456789') {
    throw new \RuntimeException('La validación de URLs de TikTok falló.');
}
echo "TikTok URL tests passed.\n";
