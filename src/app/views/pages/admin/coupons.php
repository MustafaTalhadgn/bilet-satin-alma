<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi</title>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Kupon Yönetimi</h1>
                <button class="btn btn-first" data-bs-toggle="modal" data-bs-target="#addCouponModal"><i class="bi bi-plus-circle"></i> Yeni  Kupon</button>
            </div>

            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Kupon Kodu</th><th>Ait Olduğu Firma</th><th>İndirim (%)</th><th>Limit</th><th>Son Geçerlilik</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr><td colspan="6" class="text-center">Henüz kupon oluşturulmamış.</td></tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                            <td>
                                <?php if ($coupon['company_name']): ?>
                                    <span class="text-dark"><?php echo htmlspecialchars($coupon['company_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-dark">Tüm Firmalar </span>
                                <?php endif; ?>
                            </td>
                            <td>%<?php echo htmlspecialchars($coupon['discount']); ?></td>
                            <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                            <td><?php echo date("d.m.Y", strtotime($coupon['expire_date'])); ?></td>
                            <td>
                                <button class="btn btn-secondary btn-sm edit-coupon-btn"
                                        data-bs-toggle="modal" data-bs-target="#editCouponModal"
                                        data-id="<?php echo $coupon['id']; ?>"
                                        data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                        data-discount="<?php echo htmlspecialchars($coupon['discount']); ?>"
                                        data-limit="<?php echo htmlspecialchars($coupon['usage_limit']); ?>"
                                        data-expiry="<?php echo htmlspecialchars($coupon['expire_date']); ?>"
                                        data-company="<?php echo htmlspecialchars($coupon['company_name'] ?? '');  ?>">Düzenle</button>
                               
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Kupon Oluştur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3"><label class="form-label">İndirim Oranı (%)</label><input type="number" name="discount" step="0.5" min="1" max="100" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Kullanım Limiti</label><input type="number" name="usage_limit" min="1" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Son Kullanma Tarihi</label><input type="date" name="expire_date" class="form-control" required></div>
                    <div class="modal-footer"><button type="submit" name="add_coupon" class="btn btn-dark">Oluştur</button></div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kupon Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
          
                <form method="POST" action="" id="editCouponForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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

<?php

require_once __DIR__ . '/../../partials/footer.php';
?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/admin-coupons.js"></script>
</body>
</html>
