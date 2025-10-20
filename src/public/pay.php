<?php
ini_set('session.cookie_httponly', 1);
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
// 1. Session ve Rol Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// 2. Gerekli POST verilerinin kontrolü
if (!isset($_POST['trip_id'], $_POST['selected_seat']) || empty($_POST['trip_id']) || empty($_POST['selected_seat'])) {
    // Eğer gerekli bilgiler olmadan gelinirse anasayfaya yönlendir.
    header("Location: index.php");
    exit();
}

// --- DEĞİŞKENLERİ VE VERİLERİ HAZIRLAMA ---
$user_id = $_SESSION['user_id'];
$trip_id = $_POST['trip_id'];
$selected_seat = filter_var($_POST['selected_seat'], FILTER_VALIDATE_INT);

// Flash mesajları için (hata/başarı)
$error_message = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

// --- SEFER VE KULLANICI BİLGİLERİNİ ÇEKME ---
try {
    // Sefer bilgilerini çek
    $trip_stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = :trip_id");
    $trip_stmt->execute([':trip_id' => $trip_id]);
    $trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcı bakiyesini çek
    $user_stmt = $pdo->prepare("SELECT balance FROM User WHERE id = :user_id");
    $user_stmt->execute([':user_id' => $user_id]);
    $user_balance = $user_stmt->fetchColumn();

    if (!$trip) {
        die("Hata: Geçersiz sefer bilgisi.");
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}


// --- ÖDEME ONAY FORMU GÖNDERİLDİYSE İŞLEMLERİ YAP ---
if (isset($_POST['confirm_payment'])) {
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $final_price = $trip['price'];
    $coupon_id = null;
    $coupon_discount = 0;

    try {
        // ---- VERİTABANI TRANSACTION BAŞLAT: Tüm işlemler ya başarılı olur ya da hiçbiri olmaz. ----
        $pdo->beginTransaction();

        // 1. KUPON KONTROLÜ (Eğer girildiyse)
        if (!empty($coupon_code)) {
            // Race condition'a karşı daha güvenli olması için "FOR UPDATE" kilidi eklenir (PostgreSQL/MySQL'de daha etkilidir).
            // SQLite'ta transaction'ın kendisi kilitleme sağlar.
            $coupon_stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = :code AND usage_limit > 0 AND expire_date > datetime('now')");
            $coupon_stmt->execute([':code' => $coupon_code]);
            $coupon = $coupon_stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                // Kupon bulundu, fiyatı yeniden hesapla
                $coupon_id = $coupon['id'];
                $coupon_discount = $coupon['discount'];
                $final_price = $trip['price'] - ($trip['price'] * ($coupon_discount / 100));
            } else {
                $_SESSION['flash_error'] = "Geçersiz veya süresi dolmuş kupon kodu.";
                header("Location: pay.php"); // Sayfayı yeniden yükle, form verileri kaybolacak. Bu yüzden POST'u tekrar göndermek gerekir.
                exit();
            }
        }

        // 2. BAKİYE KONTROLÜ
        if ($user_balance < $final_price) {
            throw new Exception("Yetersiz bakiye. Lütfen bakiyenizi güncelleyin.");
        }

        // 3. KULLANICI BAKİYESİNİ GÜNCELLE
        $update_balance_stmt = $pdo->prepare("UPDATE User SET balance = balance - :paid_amount WHERE id = :user_id");
        $update_balance_stmt->execute([':paid_amount' => $final_price, ':user_id' => $user_id]);

        // 4. BİLETİ (Tickets) TABLOSUNA EKLE
        $ticket_id = bin2hex(random_bytes(16));
        $insert_ticket_stmt = $pdo->prepare(
            "INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) VALUES (:id, :trip_id, :user_id, 'active', :total_price, :created_at)"
        );
        $insert_ticket_stmt->execute([
            ':id' => $ticket_id,
            ':trip_id' => $trip_id,
            ':user_id' => $user_id,
            ':total_price' => $final_price,
            ':created_at' => date('Y-m-d H:i:s')
        ]);

        // 5. SATIN ALINAN KOLTUĞU (Booked_Seats) TABLOSUNA EKLE
        $insert_seat_stmt = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (:id, :ticket_id, :seat_number, :created_at)");
        $insert_seat_stmt->execute([
            ':id' => bin2hex(random_bytes(16)),
            ':ticket_id' => $ticket_id,
            ':seat_number' => $selected_seat,
            ':created_at' => date('Y-m-d H:i:s')
        ]);

        // 6. KUPON KULLANILDIYSA GÜNCELLEME YAP (Race Condition Korumalı)
        if ($coupon_id) {
            $update_coupon_stmt = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = :id AND usage_limit > 0");
            $update_coupon_stmt->execute([':id' => $coupon_id]);
            
            // Eğer güncelleme 0 satırı etkilediyse, biz işlemi yaparken başka biri son kuponu kullandı demektir.
            if ($update_coupon_stmt->rowCount() === 0) {
                throw new Exception("Kupon son anda tükendi. İşlem iptal edildi.");
            }
            
            // Kullanıcının bu kuponu kullandığını User_Coupons tablosuna kaydet
            $log_coupon_stmt = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (:id, :coupon_id, :user_id)");
            $log_coupon_stmt->execute([
                ':id' => bin2hex(random_bytes(16)),
                ':coupon_id' => $coupon_id,
                ':user_id' => $user_id
            ]);
        }
        
        // ---- TÜM İŞLEMLER BAŞARILI, TRANSACTION'I ONAYLA ----
        $pdo->commit();

        // PDF Oluşturma ve Başarı Sayfasına Yönlendirme
        // Bu kısım normalde ayrı bir PDF oluşturma fonksiyonunu çağırır.
        // Şimdilik sadece başarı sayfasına yönlendirelim.
        header("Location: ./assets/partials/payment_success.php?ticket_id=" . $ticket_id);
        exit();

    } catch (Exception $e) {
        // ---- BİR HATA OLDU, TÜM İŞLEMLERİ GERİ AL ----
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Ödeme sırasında bir hata oluştu: " . $e->getMessage();
        // Hata sonrası POST verilerini session'a kaydederek formu tekrar doldurabiliriz, şimdilik basit tutalım.
        header("Location: /pay.php"); // Sayfayı yeniden yükleyerek hata mesajını göster.
        exit();
    }
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
                <div class="card-header">
                    <h4 class="mb-0">Ödeme Bilgileri</h4>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <h5 class="card-title">Sefer Özeti</h5>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Güzergah:</span>
                            <strong><?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Kalkış Zamanı:</span>
                            <strong><?php echo date("d.m.Y H:i", strtotime($trip['departure_time'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Seçilen Koltuk:</span>
                            <strong><?php echo htmlspecialchars($selected_seat); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span>Bilet Fiyatı:</span>
                            <strong class="fs-5"><?php echo htmlspecialchars($trip['price']); ?> TL</strong>
                        </li>
                    </ul>

                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5">Mevcut Bakiyeniz:</span>
                        <span class="fs-5 fw-bold text-success"><?php echo htmlspecialchars($user_balance); ?> TL</span>
                    </div>

                    <form method="POST" action="pay.php">
                        <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip_id); ?>">
                        <input type="hidden" name="selected_seat" value="<?php echo htmlspecialchars($selected_seat); ?>">
                        
                        <div class="mb-3">
                            <label for="coupon_code" class="form-label">İndirim Kuponu (İsteğe Bağlı)</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Kupon kodunu girin">
                                <button class="btn btn-outline-secondary" type="submit" name="apply_coupon">Uygula</button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" name="confirm_payment" class="btn btn-primary btn-lg">
                                Ödemeyi Onayla ve Bileti Al
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>