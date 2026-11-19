<?php
namespace App\Controllers;
use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\RegistrationService;
use App\Services\GuestOrderEmailService;

class RegistrationController
{
    private $service;
    public function __construct(RegistrationService $service) { $this->service = $service; }
    public function register() { return $this->execute('register'); }
    public function resend() { return $this->execute('resend'); }
    public function confirm() { return $this->execute('confirm'); }
    public function validate() { return $this->execute('validate'); }
    private function execute($action)
    {
        header('Cache-Control: private, no-store');
        try {
            if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') throw new ApiException('UNSUPPORTED_MEDIA_TYPE', 'Envía los datos como JSON.', 415);
            $data = Request::json();
            if (!is_array($data)) throw new ApiException('VALIDATION_ERROR', 'Revisa los datos enviados.', 422);
            return Response::success($this->service->$action($data), 200);
        } catch (ApiException $error) { return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus()); }
        catch (\Throwable $error) {
            $ref = bin2hex(random_bytes(8));
            $driver = $error instanceof \PDOException ? (int) ($error->errorInfo[1] ?? 0) : 0;
            $reasons = array(1146 => 'missing_table', 1054 => 'missing_column', 1364 => 'required_column_without_default', 1062 => 'duplicate_key');
            GuestOrderEmailService::log($ref . ' Registration ' . $action . ' ' . get_class($error) . ' [' . $error->getCode() . '] driver=' . $driver . ' ' . ($reasons[$driver] ?? ''));
            return Response::error('REGISTER_UNAVAILABLE', 'No pudimos completar la solicitud. Referencia: ' . $ref, 503);
        }
    }
}
