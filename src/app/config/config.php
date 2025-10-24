<?php

date_default_timezone_set('Europe/Istanbul');


$db_path = __DIR__ . '/../storage/app.db'; 

try {
    $pdo = new PDO("sqlite:" . $db_path);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("PRAGMA encoding = 'UTF-8';");
} catch (PDOException $e) {

    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}
?>