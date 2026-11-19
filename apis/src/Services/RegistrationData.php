<?php

namespace App\Services;

use App\Exceptions\ApiException;

class RegistrationData
{
    private const STATES = array(
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango',
        'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán',
        'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
        'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco',
        'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
    );

    public static function validate(array $data)
    {
        $limits = array('nombre' => 250, 'apellido' => 250, 'correo' => 250, 'telefono' => 25,
            'direccion' => 500, 'colonia' => 250, 'ciudad' => 250, 'estado' => 250, 'codigop' => 5, 'rfc' => 13);
        $result = array();
        foreach ($limits as $field => $limit) {
            $value = $data[$field] ?? '';
            if (!is_string($value) || !preg_match('//u', $value)) self::invalid('Revisa el campo ' . $field . '.');
            $value = trim($value);
            if (($field !== 'rfc' && $value === '') || preg_match_all('/./us', $value) > $limit || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                self::invalid('Revisa el campo ' . $field . '.');
            }
            // These existing columns use MySQL latin1 (Windows-1252). Avoid silently
            // replacing unsupported characters when saving a delivery address.
            if (in_array($field, array('direccion', 'colonia', 'ciudad', 'estado', 'codigop', 'telefono', 'correo'), true)
                && @iconv('UTF-8', 'Windows-1252', $value) === false) {
                self::invalid('El campo ' . $field . ' contiene caracteres no compatibles. Usa letras, números y signos comunes.');
            }
            $result[$field] = $value;
        }
        $result['correo'] = self::email($result['correo']);
        if (!in_array($result['estado'], self::STATES, true)) self::invalid('Selecciona un estado de la lista.');
        if (!preg_match('/^[0-9]{5}$/D', $result['codigop'])) self::invalid('El código postal debe tener 5 dígitos.');
        $digits = preg_replace('/\D/', '', $result['telefono']);
        if (!preg_match('/^\+?[0-9() .-]{7,25}$/D', $result['telefono']) || strlen($digits) < 7 || strlen($digits) > 15) self::invalid('Ingresa un teléfono válido con lada.');
        $result['rfc'] = str_replace('ñ', 'Ñ', strtoupper($result['rfc']));
        if ($result['rfc'] !== '' && !preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/uD', $result['rfc'])) self::invalid('Revisa el RFC o deja este campo vacío.');
        $result['password'] = self::password($data);
        return $result;
    }

    public static function password(array $data)
    {
        $password = $data['password'] ?? null;
        if (!is_string($password) || !preg_match('//u', $password) || preg_match_all('/./us', $password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
            self::invalid('Usa una contraseña de al menos 8 caracteres y evita una contraseña demasiado larga.');
        }
        if (!isset($data['password_confirmation']) || !is_string($data['password_confirmation']) || !hash_equals($password, $data['password_confirmation'])) self::invalid('Las contraseñas no coinciden.');
        return $password;
    }

    public static function email($value)
    {
        if (!is_string($value)) self::invalid('Ingresa un correo electrónico válido.');
        $value = strtolower(trim($value));
        if (strlen($value) > 250 || !filter_var($value, FILTER_VALIDATE_EMAIL)) self::invalid('Ingresa un correo electrónico válido.');
        return $value;
    }

    private static function invalid($message) { throw new ApiException('VALIDATION_ERROR', $message, 422); }
}
