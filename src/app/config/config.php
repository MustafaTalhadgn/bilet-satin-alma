<?php
// test ortamından sonra kaldırılacak
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


date_default_timezone_set('Europe/Istanbul');


$db_path = __DIR__ . '/../storage/app.db'; // Yolu düzelttim: /../storage/app.db

try {
    $pdo = new PDO("sqlite:" . $db_path);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("PRAGMA encoding = 'UTF-8';");
} catch (PDOException $e) {
//canlıya çıkınca log dosyasına yazılacak
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}
?>