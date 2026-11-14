<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\AddressRepository;

class AddressService
{
    const STATES = array(
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango',
        'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán',
        'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
        'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco',
        'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
    );
    private $auth;
    private $addresses;
    public function __construct(AuthService $auth, AddressRepository $addresses)
    {
        $this->auth = $auth;
        $this->addresses = $addresses;
    }

    public function listing()
    {
        $user = $this->auth->currentUser()['usuario'];
        return array('direcciones' => $this->addresses->all((int) $user['id']));
    }

    public function change(array $data)
    {
        $user = $this->auth->currentUser()['usuario'];
        $action = isset($data['accion']) ? $data['accion'] : '';
        if (!in_array($action, array('guardar', 'eliminar', 'predeterminada'), true)) $this->invalid('Acción inválida.');
        $id = null;
        if (array_key_exists('id', $data) && $data['id'] !== null) {
            if ((!is_int($data['id']) && !is_string($data['id'])) || !preg_match('/^[1-9][0-9]*$/D', (string) $data['id'])) $this->invalid('Dirección inválida.');
            $id = filter_var($data['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 2147483647)));
            if ($id === false) $this->invalid('Dirección inválida.');
        }
        if ($action !== 'guardar' && $id === null) $this->invalid('Selecciona una dirección.');
        $address = array();
        if ($action === 'guardar') {
            foreach (array('nombre', 'apellido', 'colonia', 'ciudad', 'estado', 'cp', 'direccion', 'telefono') as $field) {
                $value = isset($data[$field]) ? $data[$field] : '';
                if (!is_string($value) || !preg_match('//u', $value)) $this->invalid('Revisa el campo ' . $field . '.');
                $value = trim($value);
                if (($field !== 'apellido' && $value === '') || preg_match_all('/./us', $value) > 255 || preg_match('/[\x00-\x1F\x7F]/', $value)) $this->invalid('Revisa el campo ' . $field . '.');
                $address[$field] = $value;
            }
            if (!in_array($address['estado'], self::STATES, true)) $this->invalid('Selecciona un estado de la lista.');
            if (!preg_match('/^[0-9]{5}$/D', $address['cp'])) $this->invalid('El código postal debe tener 5 dígitos.');
            if (!preg_match('/^\+?[0-9() .-]{7,25}$/D', $address['telefono']) || strlen(preg_replace('/\D/', '', $address['telefono'])) < 7) $this->invalid('Revisa el teléfono de contacto.');
            if (isset($data['defecto']) && !is_bool($data['defecto'])) $this->invalid('Preferencia de dirección inválida.');
            $address['defecto'] = !empty($data['defecto']);
        }
        return array('direcciones' => $this->addresses->mutate((int) $user['id'], $action, $id, $address));
    }

    private function invalid($message) { throw new ApiException('VALIDATION_ERROR', $message, 422); }
}
