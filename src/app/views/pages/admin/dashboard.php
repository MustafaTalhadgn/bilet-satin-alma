<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Paneli'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>

<?php
require_once __DIR__ . '/../../partials/header.php';
?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php
            require_once __DIR__ . '/../../partials/sidebar_admin.php';
            ?>
        </div>
        <div class="col-md-9">
            <h1>Admin Paneline Hoş Geldiniz!</h1>
            <p class="lead">Lütfen yönetmek istediğiniz bölümü sol taraftaki menüden seçin.</p>
            <hr>
        
            <div class="alert alert-secondary mt-3">
                Bu panel üzerinden yeni otobüs firmaları ekleyebilir, mevcut firmaları düzenleyebilir,mevcut firmaları silebilir,yeni firma yetkilisi ekleyebilir, firma yetkilileri atayabilir ,Kullanıcıları düzenleyebilir,kullanıcılara yetki atayabilir,Kullanıcıları silebilir,Kuponları düzenleyebilir, tüm firmalarda geçerli indirim kuponları oluşturabilirsiniz.
            </div>
        </div>
    </div>
</main>

<?php

require_once __DIR__ . '/../../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
