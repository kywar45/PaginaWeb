<?php
require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/RegistrationLimiter.php';
class TestRegisterLimiter extends \App\Services\RegistrationLimiter {
    public $path;
    protected function directory() { return $this->path; }
}
function denied($callback, $status) {
    try { $callback(); } catch (\App\Exceptions\ApiException $error) {
        if ($error->getStatus() !== $status) throw new \RuntimeException('Wrong status'); return;
    }
    throw new \RuntimeException('Expected limit');
}
$limiter = new TestRegisterLimiter();
$limiter->path = sys_get_temp_dir() . '/sn-register-limit-' . bin2hex(random_bytes(8));
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
try {
    $limiter->consume('test@example.com');
    denied(function () use ($limiter) { $limiter->consume('test@example.com'); }, 429);
    $file = $limiter->path . '/limits.json';
    $entries = json_decode(file_get_contents($file), true);
    if (strpos(file_get_contents($file), 'test@example.com') !== false) throw new \RuntimeException('Plain email stored');
    $entries[0]['at'] = time() - 61;
    file_put_contents($file, json_encode($entries));
    $limiter->consume('test@example.com');
    $entries = json_decode(file_get_contents($file), true);
    foreach ($entries as &$entry) $entry['at'] = time() - 61;
    unset($entry);
    file_put_contents($file, json_encode($entries));
    $limiter->consume('test@example.com');
    $_SERVER['REMOTE_ADDR'] = '127.0.0.2';
    denied(function () use ($limiter) { $limiter->consume('test@example.com'); }, 429);
    file_put_contents($file, 'broken');
    denied(function () use ($limiter) { $limiter->consume('other@example.com'); }, 503);
    echo "Registration rate limits passed: persistent cooldown, hourly destination limit across IPs, hashed metadata and fail-closed storage.\n";
} finally {
    if (is_file($limiter->path . '/limits.json')) unlink($limiter->path . '/limits.json');
    if (is_dir($limiter->path)) rmdir($limiter->path);
}
