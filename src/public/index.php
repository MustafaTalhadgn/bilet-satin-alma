<?php
// src/public/index.php

/**
 * Anasayfa için giriş noktası (Entry Point / Bootstrapper).
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur, gerçi bu sayfada kullanılmıyor)
require_once __DIR__ . '/../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../app/config/config.php';

// 3. İlgili Controller'ı çağır
require_once __DIR__ . '/../app/controllers/HomeController.php';

// 4. Controller nesnesini $pdo ile oluştur
$homeController = new HomeController($pdo);

// 5. Controller'a anasayfayı göstermesini söyle
$homeController->showHomePage();

?>
