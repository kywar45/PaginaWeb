<?php
namespace App\Services {
    function mail($recipient, $subject, $html, $headers) {
        if (($GLOBALS['mail_mode'] ?? '') === 'throw') throw new \RuntimeException('Simulated provider failure');
        if (($GLOBALS['mail_mode'] ?? '') === 'reject') {
            trigger_error('Simulated hosting warning', E_USER_WARNING);
            return false;
        }
        $GLOBALS['captured_mail'][] = array($recipient, $subject, $html, $headers);
        return true;
    }
}
namespace {
    require __DIR__ . '/../src/Services/GuestOrderEmailService.php';
    function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
    // Exercise the real transport method while intercepting mail(), with no real delivery.
    $reflection = new \ReflectionClass(\App\Services\GuestOrderEmailService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $send = $reflection->getMethod('send');
    $send->setAccessible(true);
    putenv('ORDER_MAIL_FROM');
    putenv('ORDER_MAIL_ENABLED');
    check($send->invoke($service, 'guest@example.com', 'Confirmación INV-7', '<p>Pedido</p>') === true, 'Existing mail transport is enabled by default');
    $captured = $GLOBALS['captured_mail'][0];
    check(strpos($captured[3], 'From: Importadora Supernova <no-reply@importadorasupernova.com>') !== false, 'Existing sender used');
    check(strpos($captured[3], 'Content-Type: text/html; charset=UTF-8') !== false, 'HTML UTF-8 headers');
    check(base64_decode(substr($captured[1], 10, -2)) === 'Confirmación INV-7', 'UTF-8 subject preserved');
    putenv('ORDER_MAIL_ENABLED=0');
    check($send->invoke($service, 'guest@example.com', 'Pedido', 'body') === false, 'Can disable sends');
    putenv('ORDER_MAIL_ENABLED=1');
    putenv("ORDER_MAIL_FROM=sender@example.com\r\nBcc: other@example.com");
    check($send->invoke($service, 'guest@example.com', 'Pedido', 'body') === false, 'Header injection rejected');
    check(count($GLOBALS['captured_mail']) === 1, 'Disabled or invalid configuration does not send');
    putenv('ORDER_MAIL_FROM');
    $_ENV['ORDER_MAIL_FROM'] = 'configured@example.com';
    check($send->invoke($service, 'guest@example.com', 'Pedido', 'body'), 'Reads private dotenv configuration, not only getenv');
    check(strpos($GLOBALS['captured_mail'][1][3], 'configured@example.com') !== false, 'Configured sender is actually used');
    unset($_ENV['ORDER_MAIL_FROM']);
    $diagnostic = $reflection->getProperty('diagnostic');
    $diagnostic->setAccessible(true);
    $GLOBALS['mail_mode'] = 'reject';
    check(!$send->invoke($service, 'guest@example.com', 'Pedido', 'body') && $diagnostic->getValue($service)['code'] === 'MAIL_REJECTED', 'Warnings are captured and rejection remains visible');
    $GLOBALS['mail_mode'] = 'throw';
    check(!$send->invoke($service, 'guest@example.com', 'Pedido', 'body') && $diagnostic->getValue($service)['code'] === 'MAIL_EXCEPTION', 'Provider exception becomes a diagnostic');
    echo "Order mail transport checks passed: default sender, HTML, UTF-8 subject and disable switch.\n";
}
