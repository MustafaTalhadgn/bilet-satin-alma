<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>

<?php require_once 'assets/partials/header.php'; ?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php require_once 'assets/partials/admin_sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <h1>Admin Paneline Hoş Geldiniz!</h1>
            <p class="lead">Lütfen yönetmek istediğiniz bölümü sol taraftaki menüden seçin.</p>
            <hr>
            <!-- Buraya ileride özet istatistik kartları eklenebilir -->
            <div class="alert alert-info">
                Bu panel üzerinden yeni otobüs firmaları ekleyebilir, mevcut firmaları düzenleyebilir, firma yetkilileri atayabilir ve tüm firmalarda geçerli indirim kuponları oluşturabilirsiniz.
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
