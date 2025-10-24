<?php
// src/public/my-account.php

/**
 * "Hesabım" sayfası için giriş noktası.
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
require_once __DIR__ . '/../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../app/config/config.php';

// 3. İlgili Controller'ı çağır
require_once __DIR__ . '/../app/controllers/UserController.php';

// 4. Controller nesnesini $pdo ile oluştur
$userController = new UserController($pdo);

// 5. Controller'a hesap sayfasını göstermesini söyle
$userController->showAccountPage();

?>
