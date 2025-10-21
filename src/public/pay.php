<?php
ini_set('session.cookie_httponly', 1);
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// --- ÖDEME BAĞLAMINI (CONTEXT) YÖNETME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trip_id'], $_POST['selected_seat'])) {
    $_SESSION['payment_context'] = [
        'trip_id' => $_POST['trip_id'],
        'selected_seat' => filter_var($_POST['selected_seat'], FILTER_VALIDATE_INT)
    ];
    unset($_SESSION['applied_coupon']); // Yeni ödeme süreci başlarken eski kuponu temizle
    header("Location: pay.php");
    exit();
}

if (!isset($_SESSION['payment_context'])) {
    header("Location: index.php");
    exit();
}

// --- DEĞİŞKENLERİ VE VERİLERİ SESSION'DAN GÜVENLİ BİR ŞEKİLDE AL ---
$user_id = $_SESSION['user_id'];
$trip_id = $_SESSION['payment_context']['trip_id'];
$selected_seat = $_SESSION['payment_context']['selected_seat'];

// Flash mesajları için
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// --- SEFER VE KULLANICI BİLGİLERİNİ ÇEKME ---
try {
    $trip_stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = :trip_id");
    $trip_stmt->execute([':trip_id' => $trip_id]);
    $trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);

    $user_stmt = $pdo->prepare("SELECT balance FROM User WHERE id = :user_id");
    $user_stmt->execute([':user_id' => $user_id]);
    $user_balance = $user_stmt->fetchColumn();

    if (!$trip) { die("Hata: Geçersiz sefer bilgisi."); }
} catch (PDOException $e) { die("Veritabanı hatası: " . $e->getMessage()); }


// --- POST İŞLEMLERİNİ YÖNETME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- KUPON UYGULAMA İŞLEMİ ---
    if (isset($_POST['apply_coupon'])) {
        $coupon_code = trim($_POST['coupon_code'] ?? '');
        unset($_SESSION['applied_coupon']); 

        if (!empty($coupon_code)) {
            $coupon_stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = :code AND usage_limit > 0 AND expire_date > datetime('now')");
            $coupon_stmt->execute([':code' => $coupon_code]);
            $coupon = $coupon_stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                // DÜZELTME: KULLANICININ BU KUPONU DAHA ÖNCE KULLANIP KULLANMADIĞINI KONTROL ET
                $usage_check_stmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE user_id = :user_id AND coupon_id = :coupon_id");
                $usage_check_stmt->execute([':user_id' => $user_id, ':coupon_id' => $coupon['id']]);
                $is_already_used = $usage_check_stmt->fetchColumn();

                if ($is_already_used > 0) {
                    $_SESSION['flash_message'] = "Bu kupon kodunu daha önce kullandınız.";
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    // Kupon geçerliyse ve daha önce kullanılmamışsa, bilgileri session'a kaydet
                    $_SESSION['applied_coupon'] = [
                        'id' => $coupon['id'],
                        'code' => $coupon['code'],
                        'discount' => $coupon['discount']
                    ];
                    $_SESSION['flash_message'] = "Kupon başarıyla uygulandı!";
                    $_SESSION['flash_type'] = 'success';
                }
            } else {
                $_SESSION['flash_message'] = "Geçersiz veya süresi dolmuş kupon kodu.";
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_message'] = "Lütfen bir kupon kodu girin.";
            $_SESSION['flash_type'] = 'warning';
        }
        header("Location: pay.php");
        exit();
    }

    // --- ÖDEME ONAYLAMA İŞLEMİ ---
    if (isset($_POST['confirm_payment'])) {
        $final_price = $trip['price'];
        $coupon_id = null;

        if (isset($_SESSION['applied_coupon'])) {
            $final_price = $trip['price'] - ($trip['price'] * ($_SESSION['applied_coupon']['discount'] / 100));
            $coupon_id = $_SESSION['applied_coupon']['id'];
        }

        try {
            $pdo->beginTransaction();

            if ($user_balance < $final_price) {
                throw new Exception("Yetersiz bakiye. Lütfen bakiyenizi güncelleyin.");
            }

            $pdo->prepare("UPDATE User SET balance = balance - ? WHERE id = ?")->execute([$final_price, $user_id]);

            $ticket_id = bin2hex(random_bytes(16));
            $pdo->prepare("INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) VALUES (?, ?, ?, 'active', ?, ?)")
                ->execute([$ticket_id, $trip_id, $user_id, $final_price, date('Y-m-d H:i:s')]);

            $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, ?)")
                ->execute([bin2hex(random_bytes(16)), $ticket_id, $selected_seat, date('Y-m-d H:i:s')]);

            if ($coupon_id) {
                $update_coupon_stmt = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ? AND usage_limit > 0");
                $update_coupon_stmt->execute([$coupon_id]);
                if ($update_coupon_stmt->rowCount() === 0) {
                    throw new Exception("Kupon son anda tükendi. İşlem iptal edildi.");
                }
                $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (?, ?, ?)")
                    ->execute([bin2hex(random_bytes(16)), $coupon_id, $user_id]);
            }
            
            $pdo->commit();
            
            unset($_SESSION['payment_context'], $_SESSION['applied_coupon']);
            header("Location: ./assets/partials/payment_success.php?ticket_id=" . $ticket_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "Ödeme sırasında bir hata oluştu: " . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header("Location: pay.php");
            exit();
        }
    }
}

// --- GÖRSEL İÇİN FİYAT BİLGİLERİNİ HAZIRLA ---
$original_price = $trip['price'];
$display_price = $original_price;
$applied_coupon_code = '';
if (isset($_SESSION['applied_coupon'])) {
    $display_price = $original_price - ($original_price * ($_SESSION['applied_coupon']['discount'] / 100));
    $applied_coupon_code = $_SESSION['applied_coupon']['code'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
<?php require_once 'assets/partials/header.php'; ?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Ödeme Bilgileri</h4></div>
                <div class="card-body">
                    <?php if ($flash_message): ?>
                        <div class="alert alert-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash_message); ?></div>
                    <?php endif; ?>

                    <h5 class="card-title">Sefer Özeti</h5>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between"><span>Güzergah:</span><strong><?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Kalkış Zamanı:</span><strong><?php echo date("d.m.Y H:i", strtotime($trip['departure_time'])); ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Seçilen Koltuk:</span><strong><?php echo htmlspecialchars($selected_seat); ?></strong></li>
                        
                        <li class="list-group-item d-flex justify-content-between bg-light align-items-center">
                            <span>Bilet Fiyatı:</span>
                            <?php if (isset($_SESSION['applied_coupon'])): ?>
                                <div>
                                    <small class="text-danger text-decoration-line-through me-2"><?php echo htmlspecialchars($original_price); ?> TL</small>
                                    <strong class="fs-5 text-success"><?php echo htmlspecialchars(number_format($display_price, 2, ',', '.')); ?> TL</strong>
                                </div>
                            <?php else: ?>
                                <strong class="fs-5"><?php echo htmlspecialchars($original_price); ?> TL</strong>
                            <?php endif; ?>
                        </li>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5">Mevcut Bakiyeniz:</span>
                        <span class="fs-5 fw-bold text-success"><?php echo htmlspecialchars($user_balance); ?> TL</span>
                    </div>

                    <form method="POST" action="pay.php" class="mb-4">
                        <label for="coupon_code" class="form-label">İndirim Kuponu</label>
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Kupon kodunu girin" value="<?php echo htmlspecialchars($applied_coupon_code); ?>">
                            <button class="btn btn-outline-secondary" type="submit" name="apply_coupon">Uygula</button>
                        </div>
                    </form>

                    <form method="POST" action="pay.php">
                        <div class="d-grid mt-4">
                            <button type="submit" name="confirm_payment" class="btn btn-primary btn-lg">Ödemeyi Onayla ve Bileti Al</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>