<?php
ini_set('session.cookie_httponly', 1);
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user_role'] !== 'company') {
    die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
}

// --- CSRF TOKEN OLUŞTURMA ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- FİRMA BİLGİLERİNİ ALMA ---
$company_admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = :id");
$stmt->execute([':id' => $company_admin_id]);
$company_id = $stmt->fetchColumn();
if (!$company_id) {
    die("Kullanıcıya atanmış bir firma bulunamadı.");
}

// --- POST İŞLEMLERİ (TÜM CRUD İŞLEMLERİ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Geçersiz işlem denemesi! CSRF token hatası.");
    }

    // YENİ SEFER EKLEME
    if (isset($_POST['add_trip'])) {
        $departure_city = trim($_POST['departure_city']);
        $destination_city = trim($_POST['destination_city']);
        $departure_time = trim($_POST['departure_time']);
        $arrival_time = trim($_POST['arrival_time']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

        if ($price > 0 && $capacity > 0 && !empty($departure_city) && !empty($destination_city)) {
            $sql = "INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date) 
                    VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity, :created_date)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => bin2hex(random_bytes(16)),
                ':company_id' => $company_id,
                ':departure_city' => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time' => date('Y-m-d H:i:s', strtotime($departure_time)),
                ':arrival_time' => date('Y-m-d H:i:s', strtotime($arrival_time)),
                ':price' => $price,
                ':capacity' => $capacity,
                ':created_date' => date('Y-m-d H:i:s')
            ]);
        }
        header("Location: companyAdmin.php");
        exit();
    }

    // SEFER DÜZENLEME
    if (isset($_POST['edit_trip'])) {
        $trip_id = $_POST['trip_id'];
        $departure_city = trim($_POST['departure_city']);
        $destination_city = trim($_POST['destination_city']);
        $departure_time = trim($_POST['departure_time']);
        $arrival_time = trim($_POST['arrival_time']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

        if ($price > 0 && $capacity > 0) {
            $sql = "UPDATE Trips SET departure_city=:departure_city, destination_city=:destination_city, departure_time=:departure_time, arrival_time=:arrival_time, price=:price, capacity=:capacity 
                    WHERE id = :id AND company_id = :company_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':departure_city' => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time' => date('Y-m-d H:i:s', strtotime($departure_time)),
                ':arrival_time' => date('Y-m-d H:i:s', strtotime($arrival_time)),
                ':price' => $price,
                ':capacity' => $capacity,
                ':id' => $trip_id,
                ':company_id' => $company_id
            ]);
        }
        header("Location: companyAdmin.php");
        exit();
    }

    // SEFER SİLME
    if (isset($_POST['delete_trip'])) {
        $trip_id_to_delete = $_POST['trip_id'];
        $sql = "DELETE FROM Trips WHERE id = :id AND company_id = :company_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $trip_id_to_delete, ':company_id' => $company_id]);
        header("Location: companyAdmin.php");
        exit();
    }

    // YENİ KUPON EKLEME
   if (isset($_POST['add_coupon'])) {
        // EKLENEN GÜVENLİK KONTROLÜ: Gerekli tüm alanların geldiğinden emin ol
        if (isset($_POST['discount'], $_POST['usage_limit'], $_POST['expire_date'])) {
            $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
            $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
            $expire_date = trim($_POST['expire_date']);
            $code = strtoupper(bin2hex(random_bytes(6)));

            if ($discount > 0 && $usage_limit > 0 && !empty($expire_date)) {
                $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at, company_id) 
                        VALUES (:id, :code, :discount, :usage_limit, :expire_date, :created_at, :company_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => bin2hex(random_bytes(16)),
                    ':code' => $code,
                    ':discount' => $discount,
                    ':usage_limit' => $usage_limit,
                    ':expire_date' => date('Y-m-d H:i:s', strtotime($expire_date)),
                    ':created_at' => date('Y-m-d H:i:s'),
                    ':company_id' => $company_id
                ]);
            }
        }
        // Hatalı bir form gönderilse bile, her durumda sayfayı yenile ve hatayı engelle
        header("Location: companyAdmin.php");
        exit();
    }
    // KUPON SİLME
    if (isset($_POST['delete_coupon'])) {
        $coupon_id_to_delete = $_POST['coupon_id'];
        $sql = "DELETE FROM Coupons WHERE id = :id AND company_id = :company_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $coupon_id_to_delete, ':company_id' => $company_id]);
        header("Location: companyAdmin.php");
        exit();
    }
}

// --- VERİLERİ SAYFA İÇİN ÇEKME ---
$company_trips_stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
$company_trips_stmt->execute([$company_id]);
$company_trips = $company_trips_stmt->fetchAll(PDO::FETCH_ASSOC);

$company_coupons_stmt = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
$company_coupons_stmt->execute([$company_id]);
$company_coupons = $company_coupons_stmt->fetchAll(PDO::FETCH_ASSOC);

$cities_json = file_get_contents('./assets/data/city.json');
$cities = json_decode($cities_json);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>

<?php require_once 'assets/partials/header.php'; ?>

<main class="container my-5">
    <h1>Firma Yönetim Paneli</h1>

    <ul class="nav nav-tabs mt-4" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="trips-tab" data-bs-toggle="tab" data-bs-target="#trips-panel" type="button" role="tab">Seferler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="coupons-tab" data-bs-toggle="tab" data-bs-target="#coupons-panel" type="button" role="tab">Kuponlar</button>
        </li>
    </ul>

    <div class="tab-content pt-3" id="adminTabContent">
        <!-- SEFERLER SEKMESİ İÇERİĞİ -->
        <div class="tab-pane fade show active" id="trips-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Mevcut Seferler</h4>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTripModal"><i class="bi bi-plus-circle"></i> Yeni Sefer Ekle</button>
            </div>
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
                                    <button class="btn btn-primary btn-sm edit-trip-btn"
                                            data-id="<?php echo $trip['id']; ?>"
                                            data-from="<?php echo htmlspecialchars($trip['departure_city']); ?>"
                                            data-to="<?php echo htmlspecialchars($trip['destination_city']); ?>"
                                            data-departure="<?php echo $trip['departure_time']; ?>"
                                            data-arrival="<?php echo $trip['arrival_time']; ?>"
                                            data-price="<?php echo $trip['price']; ?>"
                                            data-capacity="<?php echo $trip['capacity']; ?>">Düzenle</button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bu seferi silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" name="delete_trip" class="btn btn-danger btn-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KUPONLAR SEKMESİ İÇERİĞİ -->
        <div class="tab-pane fade" id="coupons-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Oluşturulan Kuponlar</h4>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addCouponModal"><i class="bi bi-ticket-percent"></i> Yeni Kupon Oluştur</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr><th>Kupon Kodu</th><th>İndirim Oranı</th><th>Kullanım Limiti</th><th>Son Geçerlilik</th><th>İşlemler</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($company_coupons as $coupon): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                <td>%<?php echo htmlspecialchars($coupon['discount']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                <td><?php echo date("d.m.Y", strtotime($coupon['expire_date'])); ?></td>
                                <td>
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
    </div>
</main>

<!-- Şehirler için Datalist -->
<datalist id="cityList">
    <?php foreach ($cities as $city): ?>
        <option value="<?php echo htmlspecialchars($city); ?>">
    <?php endforeach; ?>
</datalist>

<!-- YENİ SEFER EKLEME MODALI -->
<div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Sefer Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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

<!-- SEFER DÜZENLEME MODALI -->
<div class="modal fade" id="editTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Seferi Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" id="editTripForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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

<!-- YENİ KUPON EKLEME MODALI -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Kupon Oluştur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label class="form-label">İndirim Oranı (%)</label>
                        <input type="number" name="discount" step="0.5" min="1" max="100" class="form-control" placeholder="Örn: 15" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" name="usage_limit" min="1" class="form-control" placeholder="Toplam kaç defa kullanılabilir?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Son Kullanma Tarihi</label>
                        <input type="date" name="expire_date" class="form-control" required>
                    </div>
                    <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="add_coupon" class="btn btn-success">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/company-admin.js"></script>
</body>
</html>

