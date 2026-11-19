<?php
namespace App\Services;
use App\Exceptions\ApiException;

class RegistrationLimiter
{
    protected function directory() { return dirname(__DIR__, 2) . '/storage/registration'; }
    public function consume($email)
    {
        $dir = $this->directory();
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) $this->unavailable();
        $file = @fopen($dir . '/limits.json', 'c+');
        if (!$file) $this->unavailable();
        try {
            if (!flock($file, LOCK_EX | LOCK_NB)) throw new ApiException('REGISTER_BUSY', 'Hay otra solicitud en curso. Intenta de nuevo.', 429);
            $raw = stream_get_contents($file);
            $entries = $raw === '' ? array() : json_decode($raw, true);
            if (!is_array($entries)) $this->unavailable();
            $now = time();
            $entries = array_values(array_filter($entries, function ($e) use ($now) { return is_array($e) && ($e['at'] ?? 0) > $now - 3600; }));
            $ip = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $email = hash('sha256', $email);
            $byIp = $byEmail = 0;
            foreach ($entries as $e) {
                if (($e['ip'] ?? '') === $ip) $byIp++;
                if (($e['email'] ?? '') === $email) {
                    $byEmail++;
                    if ($e['at'] > $now - 60) throw new ApiException('REGISTER_RATE_LIMIT', 'Espera un minuto antes de solicitar otro correo.', 429);
                }
            }
            if ($byIp >= 10 || $byEmail >= 3 || count($entries) >= 500) throw new ApiException('REGISTER_RATE_LIMIT', 'Se alcanzó el límite de solicitudes. Intenta más tarde.', 429);
            $entries[] = array('at' => $now, 'ip' => $ip, 'email' => $email);
            $json = json_encode($entries);
            if (!ftruncate($file, 0) || !rewind($file) || fwrite($file, $json) !== strlen($json) || !fflush($file)) $this->unavailable();
        } finally { flock($file, LOCK_UN); fclose($file); }
    }
    private function unavailable() { throw new ApiException('REGISTER_UNAVAILABLE', 'No pudimos preparar el registro. Intenta más tarde.', 503); }
}
