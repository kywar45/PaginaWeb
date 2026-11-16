<?php
namespace App\Services {
    function mail($recipient, $subject, $html, $headers) {
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
    echo "Order mail transport checks passed: default sender, HTML, UTF-8 subject and disable switch.\n";
}
