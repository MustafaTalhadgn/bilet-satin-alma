<?php
/**
 * src/app/views/pages/my-account.php
 * Kullanıcının hesap bilgilerini ve formlarını gösteren HTML yapısı.
 * Gerekli değişkenler ($current_user, $csrf_token vb.) UserController tarafından sağlanır.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım</title>
    <!-- CSS yolları web kökünden (/assets/) başlamalı -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/index.php">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../partials/header.php';
?>

<main class="container my-5">
    <h1 class="mb-4">Hesap Bilgilerim</h1>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Bakiye Bilgisi -->
        <div class="col-12">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-muted">Mevcut Bakiyeniz</h5>
                    <p class="card-text display-4 fw-bold text-success">
                        <?php echo htmlspecialchars(number_format($current_user['balance'], 2, ',', '.')); ?> TL
                    </p>
                </div>
            </div>
        </div>

        <!-- Profil Bilgileri Güncelleme Formu -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h4>Profil Bilgileri</h4></div>
                <div class="card-body">
                    <!-- action="" -> Formu kendine POST eder (/my-account.php'ye) -->
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta Adresi (Değiştirilemez)</label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled readonly>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Ad Soyad</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary w-100">Bilgileri Güncelle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Şifre Değiştirme Formu -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h4>Şifre Değiştir</h4></div>
                <div class="card-body">
                     <!-- action="" -> Formu kendine POST eder -->
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifre</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre (En az 8 karakter)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning w-100">Şifreyi Değiştir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../partials/footer.php';
?>

<!-- JS yolları web kökünden (/assets/) başlamalı -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bu sayfa için özel bir JS dosyası yoksa bu satırı silebilirsin -->
<!-- <script src="/assets/js/my-account.js"></script> -->

</body>
</html>
