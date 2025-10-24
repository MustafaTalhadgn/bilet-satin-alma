<?php
/**
 * src/app/views/pages/admin/dashboard.php
 * Admin paneli ana sayfasının HTML yapısı.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Paneli'); ?></title>
    <!-- CSS yolları web kökünden (/assets/) başlamalı -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php
// Partial'ı doğru yerden çağır (app/views/partials/)
require_once __DIR__ . '/../../partials/header.php';
?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php
            // Admin sidebar'ını doğru yerden çağır
            require_once __DIR__ . '/../../partials/sidebar_admin.php';
            ?>
        </div>
        <div class="col-md-9">
            <h1>Admin Paneline Hoş Geldiniz!</h1>
            <p class="lead">Lütfen yönetmek istediğiniz bölümü sol taraftaki menüden seçin.</p>
            <hr>
        
            <div class="alert alert-info mt-3">
                Bu panel üzerinden yeni otobüs firmaları ekleyebilir, mevcut firmaları düzenleyebilir, firma yetkilileri atayabilir ve tüm firmalarda geçerli indirim kuponları oluşturabilirsiniz.
            </div>
        </div>
    </div>
</main>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../../partials/footer.php';
?>

<!-- JS yolları web kökünden (/assets/) başlamalı -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
