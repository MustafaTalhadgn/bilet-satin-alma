<?php
// src/public/my-tickets.php

/**
 * "Biletlerim" sayfası için giriş noktası.
 */

// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
require_once __DIR__ . '/../app/core/session.php';

// 2. Veritabanı bağlantısını kur ($pdo değişkeni)
require_once __DIR__ . '/../app/config/config.php';

// 3. İlgili Controller'ı çağır
require_once __DIR__ . '/../app/controllers/TicketController.php';

// 4. Controller nesnesini $pdo ile oluştur
$ticketController = new TicketController($pdo);

// 5. Controller'a kullanıcının biletlerini göstermesini söyle
$ticketController->showMyTickets();

?>