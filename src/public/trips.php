<?php
// src/public/trips.php

/**
 * Sefer arama sonuçları sayfası için giriş noktası.
 */

// 1. Session'ı başlat (CSRF token bu sayfada POST olmadığı için kritik değil ama yine de başlatalım)
require_once __DIR__ . '/../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../app/config/config.php';

// 3. İlgili Controller'ı çağır
require_once __DIR__ . '/../app/controllers/TripController.php';

// 4. Controller nesnesini $pdo ile oluştur
$tripController = new TripController($pdo);

// 5. Controller'a sefer sonuçlarını göstermesini söyle
$tripController->showTripResults();

?>
