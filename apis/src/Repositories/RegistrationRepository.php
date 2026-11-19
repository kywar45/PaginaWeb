<?php
namespace App\Repositories;

use App\Exceptions\ApiException;

class RegistrationRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function findEmail($email)
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, correo, pass, token_registro FROM usuario WHERE LOWER(TRIM(correo)) = ? LIMIT 1');
        $stmt->execute(array($email));
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data, $passwordHash, $tokenHash, $expires)
    {
        // Also serialize this API's writes while a legacy installation adds its unique index.
        $lockName = 'sn:signup:' . substr(hash('sha256', $data['correo']), 0, 54);
        $lock = $this->pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute(array($lockName));
        if ((int) $lock->fetchColumn() !== 1) throw new ApiException('REGISTER_BUSY', 'El registro está ocupado. Intenta de nuevo.', 409);
        try {
            $this->pdo->beginTransaction();
            if ($this->findEmail($data['correo'])) throw new ApiException('EMAIL_EXISTS', 'Ya existe una cuenta con este correo. Inicia sesión o reenvía la confirmación.', 409);
            $fields = array('nombre', 'apellido', 'direccion', 'colonia', 'ciudad', 'estado', 'codigop', 'telefono', 'correo', 'rfc');
            $values = array();
            foreach ($fields as $field) $values[] = $data[$field];
            $stmt = $this->pdo->prepare("INSERT INTO usuario
                (nombre, apellido, direccion, colonia, ciudad, estado, codigop, telefono, correo, rfc, pass, orden, token_sesion, fecha_registro, token_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', '', ?, '')");
            $stmt->execute(array_merge($values, array($passwordHash, date('Y-m-d H:i:s'))));
            $id = (int) $this->pdo->lastInsertId();
            $this->insertToken($id, $tokenHash, $expires);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($error instanceof \PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new ApiException('EMAIL_EXISTS', 'Ya existe una cuenta con este correo.', 409);
            }
            throw $error;
        } finally { $this->pdo->prepare('SELECT RELEASE_LOCK(?)')->execute(array($lockName)); }
    }

    private function insertToken($id, $hash, $expires)
    {
        $stmt = $this->pdo->prepare('INSERT INTO web_registro_confirmaciones (usuario_id, token_hash, expires_at, requested_at) VALUES (?, ?, ?, ?)');
        $stmt->execute(array($id, $hash, $expires, date('Y-m-d H:i:s')));
    }

    public function renew($id, $hash, $expires)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT token_registro FROM usuario WHERE id = ? FOR UPDATE');
            $stmt->execute(array($id));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) throw new ApiException('INVALID_CREDENTIALS', 'Revisa tu correo y contraseña.', 401);
            if (trim((string) $row['token_registro']) !== '') { $this->pdo->commit(); return false; }
            $this->pdo->prepare('DELETE FROM web_registro_confirmaciones WHERE usuario_id = ?')->execute(array($id));
            $this->insertToken($id, $hash, $expires);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $error; }
    }

    public function markSent($id, $hash, $accepted)
    {
        $stmt = $this->pdo->prepare('UPDATE web_registro_confirmaciones SET mail_attempts = mail_attempts + 1, mail_accepted_at = ? WHERE usuario_id = ? AND token_hash = ?');
        $stmt->execute(array($accepted ? date('Y-m-d H:i:s') : null, $id, $hash));
    }

    public function validate($hash)
    {
        $stmt = $this->pdo->prepare('SELECT c.usuario_id, c.expires_at, c.confirmed_at, u.token_registro
            FROM web_registro_confirmaciones c JOIN usuario u ON u.id = c.usuario_id WHERE c.token_hash = ?');
        $stmt->execute(array($hash));
        $this->assertPending($stmt->fetch(\PDO::FETCH_ASSOC));
    }
    private function assertPending($row)
    {
        if (!$row) throw new ApiException('CONFIRMATION_INVALID', 'El enlace no es válido o fue reemplazado. Solicita otro correo de confirmación.', 404);
        if ($row['confirmed_at'] !== null || trim((string) $row['token_registro']) !== '') {
            throw new ApiException('CONFIRMATION_USED', 'Este correo ya fue confirmado. Puedes iniciar sesión.', 410);
        }
        if (strtotime($row['expires_at']) <= time()) throw new ApiException('CONFIRMATION_EXPIRED', 'El enlace venció. Solicita otro correo de confirmación.', 410);
    }
    public function confirm($hash)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT c.usuario_id, c.expires_at, c.confirmed_at, u.token_registro
                FROM web_registro_confirmaciones c JOIN usuario u ON u.id = c.usuario_id WHERE c.token_hash = ? FOR UPDATE');
            $stmt->execute(array($hash));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->assertPending($row);
            $this->pdo->prepare("UPDATE usuario SET token_registro = ?, token_sesion = '' WHERE id = ?")->execute(array($hash, $row['usuario_id']));
            $this->pdo->prepare('UPDATE web_registro_confirmaciones SET confirmed_at = ? WHERE usuario_id = ? AND token_hash = ?')->execute(array(date('Y-m-d H:i:s'), $row['usuario_id'], $hash));
            $this->pdo->commit();
        } catch (\Throwable $error) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $error; }
    }
}
