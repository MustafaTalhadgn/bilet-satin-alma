<?php
ini_set('session.cookie_httponly', 1);
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- CSRF TOKEN OLUŞTURMA ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Flash Mesajlarını Yönetme ---
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);


// --- FORM GÖNDERİM İŞLEMLERİ (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die("Geçersiz işlem denemesi!");
    }

    // --- PROFİL BİLGİLERİNİ GÜNCELLEME ---
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);

        if (!empty($full_name)) {
            $stmt = $pdo->prepare("UPDATE User SET full_name = :full_name WHERE id = :user_id");
            $stmt->execute([':full_name' => $full_name, ':user_id' => $user_id]);
            
            // Session'daki ismi de anında güncelle
            $_SESSION['user_fullname'] = $full_name;

            $_SESSION['flash_message'] = "Profil bilgileriniz başarıyla güncellendi.";
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = "Ad Soyad alanı boş bırakılamaz.";
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: my-account.php");
        exit();
    }

    // --- ŞİFRE DEĞİŞTİRME ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            // 1. Mevcut şifreyi veritabanından çek
            $stmt = $pdo->prepare("SELECT password FROM User WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Mevcut şifre doğru mu diye kontrol et
            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception("Mevcut şifreniz yanlış.");
            }
            
            // 3. Yeni şifreler eşleşiyor mu ve yeterli uzunlukta mı?
            if (strlen($new_password) < 8) {
                throw new Exception("Yeni şifre en az 8 karakter olmalıdır.");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("Yeni şifreler eşleşmiyor.");
            }

            // 4. Yeni şifreyi hash'le ve veritabanını güncelle
            $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
            $update_stmt = $pdo->prepare("UPDATE User SET password = :password WHERE id = :user_id");
            $update_stmt->execute([':password' => $hashed_password, ':user_id' => $user_id]);

            $_SESSION['flash_message'] = "Şifreniz başarıyla değiştirildi.";
            $_SESSION['flash_type'] = 'success';

        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Hata: " . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header("Location: my-account.php");
        exit();
    }
}


// --- GÜNCEL KULLANICI BİLGİLERİNİ ÇEKME ---
$stmt = $pdo->prepare("SELECT full_name, email, balance FROM User WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>

<?php require_once 'assets/partials/header.php'; ?>

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
                <div class="card-header">
                    <h4>Profil Bilgileri</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
                <div class="card-header">
                    <h4>Şifre Değiştir</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
