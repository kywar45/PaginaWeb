<?php
require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/CartIdentity.php';
require __DIR__ . '/../src/Services/GuestOrderEmailService.php';

// No SQL allowed: a transport probe must not create a customer, order or queue row.
class NoMailTestDatabase extends PDO { public function __construct() {} }
class MailTestFixture extends \App\Services\GuestOrderEmailService {
    public $directory;
    protected function testBudgetDirectory() { return $this->directory; }
}
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
function rejected($callback, $status) {
    try { $callback(); } catch (\App\Exceptions\ApiException $error) {
        check($error->getStatus() === $status, 'Unexpected status'); return;
    }
    throw new RuntimeException('Expected rejection');
}
$directory = sys_get_temp_dir() . '/sn-mail-test-' . bin2hex(random_bytes(8));
$sent = array();
$accepted = true;
$service = new MailTestFixture(new NoMailTestDatabase(), function ($to, $subject, $html) use (&$sent, &$accepted) {
    $sent[] = array($to, $subject, $html); return $accepted;
});
$service->directory = $directory;
$input = array('nombre' => '<img src=x>', 'correo' => 'test@example.com', 'id_usuario' => 99, 'folio_id' => 999);
try {
    putenv('ORDER_MAIL_TEST_ENABLED=0');
    rejected(function () use ($service, $input) { $service->test($input); }, 403);
    putenv('ORDER_MAIL_TEST_ENABLED=1');
    $_COOKIE = array();
    rejected(function () use ($service, $input) { $service->test($input); }, 401);
    $_COOKIE['sn_guest_cart'] = str_repeat('a', 64);
    foreach (array('bad', "valid@example.com\r\nBcc: other@example.com", array()) as $email) {
        rejected(function () use ($service, $input, $email) { $service->test(array_replace($input, array('correo' => $email))); }, 422);
    }
    check(!$sent && !is_dir($directory), 'Invalid or disabled tests do not consume budget or send');
    putenv('ORDER_MAIL_AUTO_ENABLED=0');
    $result = $service->test($input);
    check($result['accepted'] && $result['code'] === 'MAIL_ACCEPTED' && !$result['automatic_enabled'], 'Diagnostic separates manual and automatic mode');
    check($sent[0][0] === 'test@example.com' && strpos($sent[0][2], '&lt;img') !== false && strpos($sent[0][2], '<img') === false, 'Typed destination and escaped name');
    check(strpos($sent[0][2], 'token=') === false && strpos($sent[0][2], 'confirma un pago') !== false, 'Test does not expose a real receipt or claim a purchase');
    rejected(function () use ($service, $input) { $service->test($input); }, 429);
    // Another cookie cannot bypass the per-recipient hourly budget.
    $_COOKIE['sn_guest_cart'] = str_repeat('b', 64);
    $accepted = false;
    check($service->test($input)['code'] === 'MAIL_REJECTED', 'Rejected provider is visible');
    $_COOKIE['sn_guest_cart'] = str_repeat('c', 64);
    $service->test($input);
    $_COOKIE['sn_guest_cart'] = str_repeat('d', 64);
    rejected(function () use ($service, $input) { $service->test($input); }, 429);
    $stored = file_get_contents($directory . '/limits.json');
    check(strpos($stored, 'test@example.com') === false && count($sent) === 3, 'Persistent budget stores hashes and bounds sends');
    file_put_contents($directory . '/limits.json', 'broken');
    rejected(function () use ($service, $input) { $service->test($input); }, 503);
    echo "Manual mail tests passed: no order writes, feature gate, validation, report, escaping and persistent throttling.\n";
} finally {
    if (is_file($directory . '/limits.json')) unlink($directory . '/limits.json');
    if (is_dir($directory)) rmdir($directory);
    putenv('ORDER_MAIL_AUTO_ENABLED'); putenv('ORDER_MAIL_TEST_ENABLED');
}
