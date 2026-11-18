<?php
namespace App\Http {
    class Request { public static function json() { return array(); } }
    class Response {
        public static function success($data, $status) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Services/GuestOrderService.php';
    require __DIR__ . '/../src/Services/GuestOrderPdfLink.php';
    require __DIR__ . '/../src/Services/GuestOrderEmailService.php';
    require __DIR__ . '/../src/Controllers/GuestOrderController.php';
    class ProbeOrder extends \App\Services\GuestOrderService {
        public function __construct() {}
        public function create(array $data) { return array('folio_id' => 42, 'cliente_invitado_id' => 7); }
    }
    class ProbeLinks extends \App\Services\GuestOrderPdfLink {
        public function __construct() {}
        public function issue($id, $customer) { return 'test-token'; }
    }
    class ProbeEmail extends \App\Services\GuestOrderEmailService {
        public $calls = 0;
        public $fail = false;
        public function __construct() {}
        public function confirm(array $order) {
            $this->calls++;
            if ($this->fail) throw new \RuntimeException('Simulated queue table missing');
            return 'sent';
        }
    }
    function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $email = new ProbeEmail();
    $controller = new \App\Controllers\GuestOrderController(new ProbeOrder(), new ProbeLinks(), $email);
    putenv('ORDER_MAIL_AUTO_ENABLED=0');
    $result = $controller->create();
    check($result['status'] === 200 && $result['data']['email_status'] === 'disabled' && $email->calls === 0, 'Manual configuration stage does not send during processing');
    putenv('ORDER_MAIL_AUTO_ENABLED=1');
    check($controller->create()['data']['email_status'] === 'sent' && $email->calls === 1, 'Can re-enable automatic email without changing purchase code');
    $email->fail = true;
    $result = $controller->create();
    check($result['status'] === 200 && $result['data']['folio_id'] === 42 && $result['data']['email_status'] === 'pending', 'Queue errors never undo or hide the committed order');
    putenv('ORDER_MAIL_AUTO_ENABLED');
    echo "Order email controller tests passed: staged activation and safe queue failures.\n";
}
