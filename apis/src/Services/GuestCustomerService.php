<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\GuestCustomerRepository;

class GuestCustomerService
{
    private const STATES = array(
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango',
        'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán',
        'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
        'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco',
        'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
    );

    private $customers;
    private $auth;
    public function __construct(GuestCustomerRepository $customers, AuthService $auth)
    {
        $this->customers = $customers;
        $this->auth = $auth;
    }

    public function register(array $data)
    {
        if ($this->auth->optionalUser() !== null) {
            throw new ApiException('GUEST_ONLY', 'Esta opción es para compras sin cuenta.', 409);
        }
        $limits = array('nombre' => 100, 'apellido' => 100, 'correo' => 150, 'estado' => 100,
            'ciudad' => 150, 'colonia' => 150, 'direccion' => 255, 'codigo_postal' => 10, 'telefono' => 20);
        $customer = array();
        foreach ($limits as $field => $limit) {
            $value = $data[$field] ?? null;
            if (!is_string($value) || !preg_match('//u', $value)) $this->invalid('Revisa el campo ' . $field . '.');
            $value = trim($value);
            if ($value === '' || preg_match_all('/./us', $value) > $limit || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                $this->invalid('Revisa el campo ' . $field . ' (máximo ' . $limit . ' caracteres).');
            }
            $customer[$field] = $value;
        }
        if (!filter_var($customer['correo'], FILTER_VALIDATE_EMAIL)) $this->invalid('Ingresa un correo electrónico válido.');
        if (!in_array($customer['estado'], self::STATES, true)) $this->invalid('Selecciona un estado de la lista.');
        if (!preg_match('/^[0-9]{5}$/D', $customer['codigo_postal'])) $this->invalid('El código postal debe tener 5 dígitos.');
        $digits = preg_replace('/\D/', '', $customer['telefono']);
        if (!preg_match('/^\+?[0-9() .-]{7,20}$/D', $customer['telefono']) || strlen($digits) < 7 || strlen($digits) > 15) {
            $this->invalid('Ingresa un teléfono válido con lada.');
        }
        return array('cliente_invitado_id' => $this->customers->register($customer));
    }

    private function invalid($message) { throw new ApiException('VALIDATION_ERROR', $message, 422); }
}
