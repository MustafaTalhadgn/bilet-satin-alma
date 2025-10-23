<?php
// src/public/admin/companies.php

/**
 * Admin Firma Yönetimi sayfası için giriş noktası.
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
require_once __DIR__ . '/../../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../../app/config/config.php';

// 3. İlgili Admin Controller'ını çağır
require_once __DIR__ . '/../../app/controllers/admin/AdminCompanyController.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
// Controller'ı çağırmadan önce yetki kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /login.php"); // Yetkisi yoksa login'e gönder
    exit();
}

// 4. Controller nesnesini $pdo ile oluştur
$adminCompanyController = new AdminCompanyController($pdo);

// 5. Controller'a firma yönetimi sayfasını göstermesini söyle
$adminCompanyController->showCompaniesPage();

?>
