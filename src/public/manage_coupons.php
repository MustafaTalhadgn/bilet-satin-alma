<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
}

// --- CSRF TOKEN ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Flash mesajları
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);


// --- POST İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) { die("Geçersiz CSRF token!"); }

    // YENİ KUPON EKLEME
    if (isset($_POST['add_coupon'])) {
        $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
        $expire_date = trim($_POST['expire_date']);
        $code = strtoupper(bin2hex(random_bytes(6)));

        if ($discount > 0 && $usage_limit > 0 && !empty($expire_date)) {
            $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at, company_id) VALUES (?, ?, ?, ?, ?, ?, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([bin2hex(random_bytes(16)), $code, $discount, $usage_limit, $expire_date, date('Y-m-d H:i:s')]);
            $_SESSION['flash_message'] = " kupon başarıyla oluşturuldu.";
        }
        header("Location: manage_coupons.php"); exit();
    }

    // KUPON DÜZENLEME
    if (isset($_POST['edit_coupon'])) {
        $coupon_id = $_POST['coupon_id'];
        $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
        $expire_date = trim($_POST['expire_date']);

        if ($discount > 0 && $usage_limit > 0 && !empty($expire_date)) {
            $sql = "UPDATE Coupons SET discount = ?, usage_limit = ?, expire_date = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$discount, $usage_limit, $expire_date, $coupon_id]);
            $_SESSION['flash_message'] = "Kupon başarıyla güncellendi.";
        }
        header("Location: manage_coupons.php"); exit();
    }

    // KUPON SİLME
    if (isset($_POST['delete_coupon'])) {
        $coupon_id = $_POST['coupon_id'];
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        $_SESSION['flash_message'] = "Kupon başarıyla silindi.";
        header("Location: manage_coupons.php"); exit();
    }
}

// --- VERİLERİ ÇEKME ---
$coupons = $pdo->query("
    SELECT c.*, bc.name as company_name
    FROM Coupons c
    LEFT JOIN Bus_Company bc ON c.company_id = bc.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi</title>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Kupon Yönetimi</h1>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCouponModal"><i class="bi bi-plus-circle"></i> Yeni Kupon</button>
            </div>
            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash_message); ?></div>
            <?php endif; ?>
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Kupon Kodu</th><th>Ait Olduğu Firma</th><th>İndirim (%)</th><th>Limit</th><th>Son Geçerlilik</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                        <td>
                            <?php if ($coupon['company_name']): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($coupon['company_name']); ?></span>
                            <?php else: ?>
                                <span class="badge bg-info">Tüm Firmalar </span>
                            <?php endif; ?>
                        </td>
                        <td>%<?php echo htmlspecialchars($coupon['discount']); ?></td>
                        <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                        <td><?php echo date("d.m.Y", strtotime($coupon['expire_date'])); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-coupon-btn"
                                    data-bs-toggle="modal" data-bs-target="#editCouponModal"
                                    data-id="<?php echo $coupon['id']; ?>"
                                    data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                    data-discount="<?php echo htmlspecialchars($coupon['discount']); ?>"
                                    data-limit="<?php echo htmlspecialchars($coupon['usage_limit']); ?>"
                                    data-expiry="<?php echo htmlspecialchars($coupon['expire_date']); ?>"
                                    data-company="<?php echo htmlspecialchars($coupon['company_name'] ?? ''); ?>">Düzenle</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Yeni Kupon Ekleme Modalı -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Global Kupon Oluştur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3"><label class="form-label">İndirim Oranı (%)</label><input type="number" name="discount" step="0.5" min="1" max="100" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Kullanım Limiti</label><input type="number" name="usage_limit" min="1" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Son Kullanma Tarihi</label><input type="date" name="expire_date" class="form-control" required></div>
                    <div class="modal-footer"><button type="submit" name="add_coupon" class="btn btn-success">Oluştur</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Kupon Düzenleme Modalı -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kupon Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" id="editCouponForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="coupon_id" id="edit_coupon_id">
                    <div class="mb-3"><label class="form-label">Kupon Kodu (Değiştirilemez)</label><p><code id="edit_coupon_code"></code></p></div>
                    <div class="mb-3"><label class="form-label">Ait Olduğu Firma</label><p><strong id="edit_company_name"></strong></p></div>
                    <div class="mb-3"><label class="form-label">İndirim Oranı (%)</label><input type="number" name="discount" id="edit_discount" step="0.5" min="1" max="100" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Kullanım Limiti</label><input type="number" name="usage_limit" id="edit_usage_limit" min="1" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Son Kullanma Tarihi</label><input type="date" name="expire_date" id="edit_expire_date" class="form-control" required></div>
                    <div class="modal-footer"><button type="submit" name="edit_coupon" class="btn btn-primary">Değişiklikleri Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin-coupons.js"></script>
</body>
</html>