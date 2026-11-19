<?php
namespace App\Services { function setcookie($name, $value, $options) { $_COOKIE[$name] = $value; return true; } }
namespace App\Http {
    class Request { public static $data = array(); public static function json() { return self::$data; } }
    class Response {
        public static function success($data, $status) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code, 'message' => $message); }
    }
}
namespace {
    foreach (array('Exceptions/ApiException','Repositories/UserRepository','Repositories/PasswordResetRepository',
        'Services/CartIdentity','Services/AuthService','Services/RegistrationData','Services/RegistrationLimiter','Services/PasswordResetLimiter',
        'Services/GuestOrderEmailService','Services/PasswordResetEmailService','Services/PasswordResetService','Controllers/PasswordResetController') as $file) require __DIR__ . '/../src/' . $file . '.php';
    class ResetPdo extends \PDO {
        public function __construct() {
            parent::__construct('sqlite::memory:'); $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->exec("CREATE TABLE usuario (id INTEGER PRIMARY KEY,nombre TEXT,apellido TEXT,correo TEXT UNIQUE,pass TEXT,
                token_reset TEXT,expira_reset TEXT,token_sesion TEXT,token_registro TEXT,user_vip INTEGER DEFAULT 0)");
        }
        #[\ReturnTypeWillChange]
        public function prepare($sql, $options=array()) { return parent::prepare(str_replace(array(' FOR UPDATE','BINARY pass'), array('','pass COLLATE BINARY'),$sql),$options); }
    }
    class NoResetLimit extends \App\Services\RegistrationLimiter { public function consume($email) {} }
    class ResetMail extends \App\Services\GuestOrderEmailService {
        public $sent=array(); public $accepted=true;
        public function __construct() {}
        public function sendTransactional($to,$subject,$html) { $this->sent[]=array($to,$subject,$html); return $this->accepted; }
    }
    function check($value,$message) { if(!$value)throw new \RuntimeException($message); }
    function rejects($callback,$status) {
        try { $callback(); } catch(\App\Exceptions\ApiException $error) { check($error->getStatus()===$status,'Unexpected failure status'); return; }
        throw new \RuntimeException('Expected rejection');
    }
    function token($transport) { preg_match('/restablecer-contrasena\?token=([a-f0-9]{64})/',end($transport->sent)[2],$match); return $match[1]; }
    $pdo=new ResetPdo();
    $oldHash=password_hash('old-password',PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO usuario VALUES (1,?,?,?,?,NULL,NULL,?,?,0)')->execute(array('Cliente','Prueba','test@example.com',$oldHash,hash('sha256','old-session'),'confirmed'));
    $pdo->prepare('INSERT INTO usuario VALUES (2,?,?,?,?,NULL,NULL,?,?,0)')->execute(array('Pendiente','Prueba','pending@example.com',$oldHash,'',''));
    $repository=new \App\Repositories\PasswordResetRepository($pdo);
    $mail=new ResetMail();
    $service=new \App\Services\PasswordResetService($repository,new \App\Services\PasswordResetEmailService($mail),new NoResetLimit());
    $auth=new \App\Services\AuthService($pdo,new \App\Repositories\UserRepository($pdo));
    $row=function($id=1)use($pdo){return $pdo->query('SELECT * FROM usuario WHERE id='.(int)$id)->fetch(\PDO::FETCH_ASSOC);};
    $reply=$service->request(array('correo'=>' TEST@EXAMPLE.COM ','id_usuario'=>2));
    $first=token($mail);
    $snapshot=$row(); $sentBefore=count($mail->sent);
    check($service->validate(array('token'=>$first))['valid'] && $row()===$snapshot && count($mail->sent)===$sentBefore,'Preflight validation is read-only and never consumes the reset link or sends email');
    check($row()['token_reset']==='reset1:'.hash('sha256',$first) && $row()['token_reset']!==$first,'Only versioned token hash stored in token_reset');
    check(strtotime($row()['expira_reset'])>time()+1700 && strtotime($row()['expira_reset'])<=time()+1800,'Expiry set to thirty minutes');
    check($row()['pass']===$oldHash && $row()['token_sesion']===hash('sha256','old-session'),'Requesting does not change password or end session');
    check($mail->sent[0][0]==='test@example.com' && strpos($mail->sent[0][2],'30 minutos')!==false,'Stored recipient and correct email expiry');
    check($service->request(array('correo'=>'missing@example.com'))===$reply && count($mail->sent)===1,'Unknown email has identical public response and no email');
    rejects(function()use($service){$service->request(array('correo'=>'bad'));},422);
    $change=array('token'=>$first,'password'=>'new-password','password_confirmation'=>'new-password');
    rejects(function()use($service,$change){$service->reset(array_replace($change,array('password_confirmation'=>'other')));},422);
    check($row()['token_reset']!==null,'Invalid password does not consume token');
    rejects(function()use($service,$change){$service->reset(array_replace($change,array('token'=>str_repeat('b',64))));},410);
    $pdo->exec("UPDATE usuario SET expira_reset='2000-01-01 00:00:00' WHERE id=1");
    rejects(function()use($service,$change){$service->validate(array('token'=>$change['token']));},410);
    rejects(function()use($service,$change){$service->reset($change);},410);
    check($row()['pass']===$oldHash,'Expired token cannot change password');
    $service->request(array('correo'=>'test@example.com'));
    $second=token($mail);
    check($second!==$first,'Resend rotates token');
    rejects(function()use($service,$change){$service->reset($change);},410);
    $change['token']=$second;
    $_COOKIE['sn_session']='old-session';
    check($auth->currentUser()['usuario']['id']===1,'Original session works before reset');
    check($service->reset($change)['reset']===true,'Valid new token resets password');
    check(password_verify('new-password',$row()['pass']) && $row()['pass']!=='new-password','New password stored hashed');
    check($row()['token_reset']===null && $row()['expira_reset']===null && $row()['token_sesion']==='','Reset consumes link and ends sessions');
    rejects(function()use($service,$change){$service->validate(array('token'=>$change['token']));},410);
    rejects(function()use($service,$change){$service->reset($change);},410);
    rejects(function()use($auth){$auth->currentUser();},401);
    rejects(function()use($auth){$auth->login('test@example.com','old-password',false);},401);
    check($auth->login('test@example.com','new-password',false)['usuario']['id']===1,'Only new password can log in');
    $users=new \App\Repositories\UserRepository($pdo);
    $currentSession=$row()['token_sesion'];
    check(!$users->startVerifiedSession(1,'stale-login',$oldHash) && $row()['token_sesion']===$currentSession,'A login validated before reset cannot create a session afterward');
    $service->request(array('correo'=>'pending@example.com'));
    $service->reset(array_replace($change,array('token'=>token($mail))));
    check($row(2)['token_registro']==='','Reset does not bypass email confirmation');
    rejects(function()use($auth){$auth->login('pending@example.com','new-password',false);},403);
    $mail->accepted=false;
    check($service->request(array('correo'=>'test@example.com'))===$reply && $row()['token_reset']===null,'Transport failure has generic response and clears the failed link');
    $repository->issue('test@example.com','reset1:older',date('Y-m-d H:i:s',time()+1800));
    $repository->issue('test@example.com','reset1:newer',date('Y-m-d H:i:s',time()+1800));
    $repository->clearFailed(1,'reset1:older');
    check($row()['token_reset']==='reset1:newer','Old send failure cannot erase newer link');
    // A used link must be rejected before any cooldown or password hashing occurs.
    $blockingLimiter = new class extends \App\Services\RegistrationLimiter { public function consume($email) { throw new \RuntimeException('Cooldown must not run for an invalid link'); } };
    $strict = new \App\Services\PasswordResetService($repository,new \App\Services\PasswordResetEmailService($mail),$blockingLimiter);
    rejects(function()use($strict,$change){$strict->reset($change);},410);
    $controller=new \App\Controllers\PasswordResetController($service);
    $_SERVER['CONTENT_TYPE']='text/plain'; check($controller->request()['status']===415,'Simple cross-site form requests rejected');
    $_SERVER['CONTENT_TYPE']='application/json'; \App\Http\Request::$data='bad'; check($controller->reset()['status']===422,'Invalid JSON shape rejected');
    echo "Password recovery passed: existing columns, generic responses, hashed expiring single-use tokens, resends, session revocation, stale-login prevention and preserved email verification.\n";
}
