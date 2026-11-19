<?php
require __DIR__ . '/../src/Repositories/TikTokVideoRepository.php';
require __DIR__ . '/../src/Services/TikTokVideoService.php';
class FakeInspirationVideos extends \App\Repositories\TikTokVideoRepository {
    public $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function listActive() { return $this->rows; }
}
$base = array('id' => '1', 'titulo' => 'Novedades', 'portada_url' => 'https://www.importadorasupernova.com/videos/portada.webp', 'posicion' => '2');
$rows = array();
foreach (array(
    'https://www.importadorasupernova.com/videos/tutorial.mp4',
    'https://importadorasupernova.com:443/videos/TUTORIAL.MP4?v=2',
    'https://www.tiktok.com/@supernova/video/1234567890123456789',
    'https://www.importadorasupernova.com.evil.test/video.mp4',
    'https://evil.test/video.mp4',
    'https://www.importadorasupernova.com@evil.test/video.mp4',
    'https://user:password@www.importadorasupernova.com/video.mp4',
    'http://www.importadorasupernova.com/videos/tutorial.mp4',
    'https://www.importadorasupernova.com:444/video.mp4',
    'https://www.importadorasupernova.com/video.mp4.php',
    'https://www.importadorasupernova.com/video.mp4#fragment',
    'javascript:alert(1)',
) as $url) $rows[] = array_merge($base, array('url' => $url));
foreach (array(null, '', 'https://evil.test/poster.jpg', 'javascript:alert(1)', 'https://www.importadorasupernova.com/poster.php') as $cover) {
    $rows[] = array_merge($base, array('url' => $rows[0]['url'], 'portada_url' => $cover));
}
$repo = new FakeInspirationVideos($rows);
$videos = (new \App\Services\TikTokVideoService($repo))->listVideos()['videos'];
if (count($videos) !== 2 || $videos[0]['url'] !== $rows[0]['url'] || isset($videos[0]['video_id'])
    || $videos[0]['portada_url'] !== $base['portada_url'] || $videos[0]['id'] !== 1 || $videos[0]['posicion'] !== 2) {
    throw new RuntimeException('Own video URL validation or normalization failed');
}
$repo->rows = array();
if ((new \App\Services\TikTokVideoService($repo))->listVideos() !== array('videos' => array())) throw new RuntimeException('Empty state failed');
echo "Inspiration API tests passed: own HTTPS MP4 files, thumbnails, rejected external/legacy/unsafe URLs and empty state.\n";
