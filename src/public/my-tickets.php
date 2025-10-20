<?php
ini_set('session.cookie_httponly', 1);
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- CSRF TOKEN OLUŞTURMA ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Flash mesajları için (hata/başarı)
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// --- BİLET İPTAL ETME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    // CSRF Kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die("Geçersiz işlem denemesi!");
    }

    $ticket_id_to_cancel = $_POST['ticket_id'];

    try {
        // --- TRANSACTION BAŞLAT ---
        $pdo->beginTransaction();

        // 1. İptal edilecek bileti ve sefer bilgilerini çek (IDOR Korumalı)
        $stmt = $pdo->prepare("
            SELECT t.id, t.total_price, tr.departure_time 
            FROM Tickets t 
            JOIN Trips tr ON t.trip_id = tr.id
            WHERE t.id = :ticket_id AND t.user_id = :user_id AND t.status = 'active'
        ");
        $stmt->execute([':ticket_id' => $ticket_id_to_cancel, ':user_id' => $user_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            throw new Exception("Geçersiz veya daha önce iptal edilmiş bilet.");
        }

        // 2. Zaman Kontrolü: Sefer saatine 1 saatten az mı kalmış?
        $departure_timestamp = strtotime($ticket['departure_time']);
        $current_timestamp = time();
        if (($departure_timestamp - $current_timestamp) <= 3600) {
            throw new Exception("Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez.");
        }

        // 3. Biletin durumunu 'canceled' olarak güncelle
        $update_ticket_stmt = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :ticket_id");
        $update_ticket_stmt->execute([':ticket_id' => $ticket_id_to_cancel]);

        // 4. Bilet ücretini kullanıcının hesabına iade et
        $refund_stmt = $pdo->prepare("UPDATE User SET balance = balance + :refund_amount WHERE id = :user_id");
        $refund_stmt->execute([':refund_amount' => $ticket['total_price'], ':user_id' => $user_id]);

        // --- TRANSACTION'I ONAYLA ---
        $pdo->commit();
        $_SESSION['flash_message'] = "Biletiniz başarıyla iptal edildi ve ücreti hesabınıza iade edildi.";
        $_SESSION['flash_type'] = 'success';

    } catch (Exception $e) {
        // --- HATA OLDU, GERİ AL ---
        $pdo->rollBack();
        $_SESSION['flash_message'] = "İptal sırasında bir hata oluştu: " . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }

    header("Location: my-tickets.php");
    exit();
}


// --- KULLANICININ BİLETLERİNİ ÇEKME ---
$tickets_stmt = $pdo->prepare("
    SELECT
        t.id AS ticket_id, t.status, t.total_price,
        tr.departure_city, tr.destination_city, tr.departure_time,
        bc.name AS company_name, bc.logo_path,
        bs.seat_number
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    JOIN Booked_Seats bs ON bs.ticket_id = t.id
    WHERE t.user_id = :user_id
    ORDER BY tr.departure_time DESC
");
$tickets_stmt->execute([':user_id' => $user_id]);
$tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>

<?php require_once 'assets/partials/header.php'; ?>

<main class="container my-5">
    <h1 class="mb-4">Biletlerim</h1>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash_message); ?></div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">Henüz satın alınmış bir biletiniz bulunmamaktadır.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px;" class="me-2">
                                <?php echo htmlspecialchars($ticket['company_name']); ?>
                            </h5>
                            
                           
                            <?php if ($ticket['status'] === 'canceled'): ?>
                                <span class="badge text-bg-danger">İptal Edildi</span>
                            <?php endif; ?>
                            

                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                    <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                </div>
                                <div class="text-end">
                                    <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-primary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                    <p class="card-text"><strong>Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><strong>Sefer Tarihi:</strong> 
                                <?php
                                    
                                    require_once __DIR__ . '/assets/data/translateDate.php';
                                    ?>
                                <span class="text-muted "><?php echo translateDate($ticket['departure_time']); ?></span>
                                
                                </small>
                            <div>
                                    <a href="generate_pdf.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF İndir</a>
                                    <?php
                                    // İptal Et butonu için zaman kontrolü
                                    $can_cancel = false;
                                    if ($ticket['status'] === 'active') {
                                        $departure_timestamp = strtotime($ticket['departure_time']);
                                        if (($departure_timestamp - time()) > 3600) {
                                            $can_cancel = true;
                                        }
                                    }
                                    if ($can_cancel):
                                    ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Ücret iadesi hesabınıza yapılacaktır.');">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <button type="submit" name="cancel_ticket" class="btn btn-danger btn-sm">Bileti İptal Et</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>