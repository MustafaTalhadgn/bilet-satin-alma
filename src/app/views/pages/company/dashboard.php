<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/company-panel.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>

<?php

require_once __DIR__ . '/../../partials/header.php';
?>

<main class="container my-5">
    <h1>Firma Yönetim Paneli</h1>

    <?php if ($flash_message): ?>
    <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mt-4" id="companyAdminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="trips-tab" data-bs-toggle="tab" data-bs-target="#trips-panel" type="button" role="tab">Sefer Yönetimi</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link " id="coupons-tab" data-bs-toggle="tab" data-bs-target="#coupons-panel" type="button" role="tab">Kupon Yönetimi</button>
        </li>
    </ul>

    <div class="tab-content pt-3" id="companyAdminTabContent">
        
        <div class="tab-pane fade show active" id="trips-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Seferleriniz</h4>
                <button class="btn btn-first" data-bs-toggle="modal" data-bs-target="#addTripModal"><i class="bi bi-plus-circle"></i> Yeni Sefer Ekle</button>
            </div>
             <?php if (empty($company_trips)): ?>
                <div class="alert alert-info">Henüz firmanıza ait sefer bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>Kalkış</th><th>Varış</th><th>Kalkış Zamanı</th><th>Fiyat</th><th>Kapasite</th><th>İşlemler</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($company_trips as $trip): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['departure_city']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['destination_city']); ?></td>
                                    <td><?php echo date("d.m.Y H:i", strtotime($trip['departure_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['price']); ?> TL</td>
                                    <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm edit-trip-btn" data-bs-toggle="modal" data-bs-target="#editTripModal"
                                                data-id="<?php echo $trip['id']; ?>"
                                                data-from="<?php echo htmlspecialchars($trip['departure_city']); ?>"
                                                data-to="<?php echo htmlspecialchars($trip['destination_city']); ?>"
                                                data-departure="<?php echo $trip['departure_time']; ?>"
                                                data-arrival="<?php echo $trip['arrival_time']; ?>"
                                                data-price="<?php echo $trip['price']; ?>"
                                                data-capacity="<?php echo $trip['capacity']; ?>">Düzenle</button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu seferi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" name="delete_trip" class="btn btn-danger btn-sm">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

 
        <div class="tab-pane fade" id="coupons-panel" role="tabpanel">
             <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Firmanızın Kuponları</h4>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addCouponModal"><i class="bi bi-ticket-percent"></i> Yeni Kupon Oluştur</button>
            </div>
             <?php if (empty($company_coupons)): ?>
                 <div class="alert alert-info">Henüz firmanıza ait kupon bulunmamaktadır.</div>
             <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>Kupon Kodu</th><th>İndirim (%)</th><th>Limit</th><th>Son Geçerlilik</th><th>İşlemler</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($company_coupons as $coupon): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
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
                                                data-expiry="<?php echo htmlspecialchars($coupon['expire_date']); ?>">
                                            Düzenle
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
             <?php endif; ?>
        </div>
    </div>
</main>


<datalist id="cityList">
    <?php foreach ($cities as $city): ?>
        <option value="<?php echo htmlspecialchars($city); ?>">
    <?php endforeach; ?>
</datalist>


<div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Sefer Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" name="departure_city" class="form-control" placeholder="Kalkış Şehri" list="cityList" required></div>
                        <div class="col-md-6"><input type="text" name="destination_city" class="form-control" placeholder="Varış Şehri" list="cityList" required></div>
                        <div class="col-md-6"><label class="form-label">Kalkış Zamanı</label><input type="datetime-local" name="departure_time" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Varış Zamanı</label><input type="datetime-local" name="arrival_time" class="form-control" required></div>
                        <div class="col-md-6"><input type="number" name="price" step="0.01" min="1" class="form-control" placeholder="Fiyat (TL)" required></div>
                        <div class="col-md-6"><input type="number" name="capacity" min="1" class="form-control" placeholder="Koltuk Kapasitesi" required></div>
                    </div>
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="add_trip" class="btn btn-success">Seferi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editTripModal" tabindex="-1">
     <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Seferi Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="" id="editTripForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="trip_id" id="edit_trip_id" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" id="edit_departure_city" name="departure_city" class="form-control" placeholder="Kalkış Şehri" list="cityList" required></div>
                        <div class="col-md-6"><input type="text" id="edit_destination_city" name="destination_city" class="form-control" placeholder="Varış Şehri" list="cityList" required></div>
                        <div class="col-md-6"><label class="form-label">Kalkış Zamanı</label><input type="datetime-local" id="edit_departure_time" name="departure_time" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Varış Zamanı</label><input type="datetime-local" id="edit_arrival_time" name="arrival_time" class="form-control" required></div>
                        <div class="col-md-6"><input type="number" id="edit_price" name="price" step="0.01" min="1" class="form-control" placeholder="Fiyat (TL)" required></div>
                        <div class="col-md-6"><input type="number" id="edit_capacity" name="capacity" min="1" class="form-control" placeholder="Koltuk Kapasitesi" required></div>
                    </div>
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="edit_trip" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addCouponModal" tabindex="-1">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Firma Kuponu Oluştur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3"><label class="form-label">İndirim Oranı (%)</label><input type="number" name="discount" step="0.5" min="1" max="100" class="form-control" placeholder="Örn: 10" required></div>
                    <div class="mb-3"><label class="form-label">Kullanım Limiti</label><input type="number" name="usage_limit" min="1" class="form-control" placeholder="Toplam kaç defa kullanılabilir?" required></div>
                    <div class="mb-3"><label class="form-label">Son Kullanma Tarihi</label><input type="date" name="expire_date" class="form-control" required></div>
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="add_coupon" class="btn btn-success">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editCouponModal" tabindex="-1">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Firma Kuponunu Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="" id="editCouponForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="coupon_id" id="edit_coupon_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Kupon Kodu (Değiştirilemez)</label>
                        <p><code id="edit_coupon_code"></code></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İndirim Oranı (%)</label>
                        <input type="number" name="discount" id="edit_coupon_discount" step="0.5" min="1" max="100" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" name="usage_limit" id="edit_coupon_limit" min="0" class="form-control" required> <!-- 0 olabilir -->
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Son Kullanma Tarihi</label>
                        <input type="date" name="expire_date" id="edit_coupon_expiry" class="form-control" required>
                    </div>
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="edit_coupon" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php

require_once __DIR__ . '/../../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="/assets/js/company-admin.js"></script>
</body>
</html>