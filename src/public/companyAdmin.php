<?php
// src/public/companyAdmin.php

/**
 * Firma Yönetim Paneli için giriş noktası.
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
require_once __DIR__ . '/../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../app/config/config.php';

// 3. İlgili Controller'ı çağır
require_once __DIR__ . '/../app/controllers/CompanyController.php';

// 4. Controller nesnesini $pdo ile oluştur
// Controller'ın __construct metodu zaten gerekli güvenlik kontrollerini yapacak
$companyAdminController = new CompanyAdminController($pdo);

// 5. Controller'a firma paneli ana sayfasını göstermesini söyle
$companyAdminController->showDashboard();

?>
