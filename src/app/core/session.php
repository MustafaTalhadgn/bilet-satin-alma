<?php


ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1); 


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}





function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}


$csrf_token = generate_csrf_token();

?>
