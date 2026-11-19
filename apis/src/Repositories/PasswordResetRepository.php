<?php
namespace App\Repositories;
use App\Exceptions\ApiException;

class PasswordResetRepository
{
    private $pdo;
    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }
    public function issue($email, $hash, $expires)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, nombre, correo FROM usuario WHERE LOWER(TRIM(correo)) = ? LIMIT 1 FOR UPDATE');
            $stmt->execute(array($email));
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) {
                $stmt = $this->pdo->prepare('UPDATE usuario SET token_reset = ?, expira_reset = ? WHERE id = ?');
                $stmt->execute(array($hash, $expires, $user['id']));
            }
            $this->pdo->commit();
            return $user ?: null;
        } catch (\Throwable $error) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $error; }
    }
    public function clearFailed($id, $hash)
    {
        // Do not erase a newer link issued while an older send was in progress.
        $stmt = $this->pdo->prepare('UPDATE usuario SET token_reset = NULL, expira_reset = NULL WHERE id = ? AND token_reset = ?');
        $stmt->execute(array($id, $hash));
    }
    public function validate($hash)
    {
        $stmt = $this->pdo->prepare('SELECT id, expira_reset FROM usuario WHERE token_reset = ? LIMIT 1');
        $stmt->execute(array($hash));
        $this->assertActive($stmt->fetch(\PDO::FETCH_ASSOC));
    }
    private function assertActive($user)
    {
        if (!$user || !$user['expira_reset'] || strtotime($user['expira_reset']) <= time()) {
            throw new ApiException('RESET_LINK_INVALID', 'Este enlace ya fue utilizado, venció o fue reemplazado. Solicita uno nuevo.', 410);
        }
    }
    public function reset($hash, callable $hashPassword)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, expira_reset FROM usuario WHERE token_reset = ? LIMIT 1 FOR UPDATE');
            $stmt->execute(array($hash));
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->assertActive($user);
            $passwordHash = call_user_func($hashPassword);
            $stmt = $this->pdo->prepare("UPDATE usuario SET pass = ?, token_reset = NULL, expira_reset = NULL, token_sesion = '' WHERE id = ? AND token_reset = ?");
            $stmt->execute(array($passwordHash, $user['id'], $hash));
            $this->pdo->commit();
        } catch (\Throwable $error) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $error; }
    }
}
