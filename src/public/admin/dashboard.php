<?php
// src/public/admin_panel.php

/**
 * Admin paneli ana sayfası için giriş noktası.
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
require_once __DIR__ . '/../../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../../app/config/config.php';

// 3. Admin Controller'ı çağır
require_once __DIR__ . '/../../app/controllers/admin/AdminDashboardController.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
// Controller'ı çağırmadan önce burada da bir kontrol yapalım!
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /login.php"); // Yetkisi yoksa login'e gönder
    exit();
}

// 4. Controller nesnesini $pdo ile oluştur
$adminController = new AdminController($pdo);

// 5. Controller'a admin paneli ana sayfasını göstermesini söyle
$adminController->showDashboard();

?>
