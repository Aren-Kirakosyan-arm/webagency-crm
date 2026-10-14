<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode','1');
    session_set_cookie_params([
        'httponly'=>true,
        'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off',
        'samesite'=>'Lax','path'=>'/'
    ]);
    session_start();
}
function require_login(): void {
    if(empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(): void {
    $submitted=$_POST['csrf_token']??''; $stored=$_SESSION['csrf_token']??'';
    if(!is_string($submitted)||!is_string($stored)||$stored===''||!hash_equals($stored,$submitted)) {
        http_response_code(403); exit('Form security check failed. Refresh the page and try again.');
    }
}
function current_user(): ?array {
    if(empty($_SESSION['user_id'])) return null;
    return ['id'=>(int)$_SESSION['user_id'],'name'=>(string)($_SESSION['user_name']??''),'email'=>(string)($_SESSION['user_email']??'')];
}
