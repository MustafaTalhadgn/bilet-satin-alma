<?php
// src/public/logout.php

/**
 * Kullanıcı çıkış işlemini gerçekleştirir.
 */

// 1. Session'ı başlat (Ayarları session.php'den alır)
// Bu dosya tek başına çalıştığı için session.php'yi çağırmalıyız.
require_once __DIR__ . '/../app/core/session.php';

// 2. Tüm session değişkenlerini sil
$_SESSION = array();

// 3. Session cookie'sini sil (Tarayıcı tarafı için)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Session'ı sunucu tarafında yok et
session_destroy();

// 5. Kullanıcıyı anasayfaya yönlendir
header("Location: /index.php");
exit();
?>
