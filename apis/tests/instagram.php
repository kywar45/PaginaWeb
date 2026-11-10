<?php

require __DIR__ . '/../src/Repositories/InstagramReelRepository.php';
require __DIR__ . '/../src/Services/InstagramReelService.php';

class FakeInstagramReels extends \App\Repositories\InstagramReelRepository
{
    public function __construct() {}
    public function listActive()
    {
        $base = array('id' => '1', 'titulo' => 'Novedades', 'posicion' => '2');
        return array(
            $base + array('url' => 'https://www.instagram.com/reel/DffWdm_DBgV/?vrfl=ejemplo'),
            $base + array('url' => 'https://instagram.com.evil.test/reel/DffWdm_DBgV/'),
            $base + array('url' => 'http://www.instagram.com/reel/DffWdm_DBgV/'),
            $base + array('url' => 'https://www.instagram.com/p/DffWdm_DBgV/'),
        );
    }
}

$service = new \App\Services\InstagramReelService(new FakeInstagramReels());
$videos = $service->listReels()['videos'];
if (count($videos) !== 1 || $videos[0]['shortcode'] !== 'DffWdm_DBgV'
    || $videos[0]['url'] !== 'https://www.instagram.com/reel/DffWdm_DBgV/') {
    throw new \RuntimeException('La validación de URLs de Instagram falló.');
}
echo "Instagram URL tests passed.\n";
