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
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die("Geçersiz işlem denemesi!");
    }

    $ticket_id_to_cancel = $_POST['ticket_id'];

    try {
        $pdo->beginTransaction();

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

        $departure_timestamp = strtotime($ticket['departure_time']);
        $current_timestamp = time();
        if (($departure_timestamp - $current_timestamp) <= 3600) {
            throw new Exception("Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez.");
        }

        $update_ticket_stmt = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :ticket_id");
        $update_ticket_stmt->execute([':ticket_id' => $ticket_id_to_cancel]);

        $refund_stmt = $pdo->prepare("UPDATE User SET balance = balance + :refund_amount WHERE id = :user_id");
        $refund_stmt->execute([':refund_amount' => $ticket['total_price'], ':user_id' => $user_id]);

        $pdo->commit();
        $_SESSION['flash_message'] = "Biletiniz başarıyla iptal edildi ve ücreti hesabınıza iade edildi.";
        $_SESSION['flash_type'] = 'success';

    } catch (Exception $e) {
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
$all_tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Biletleri durumlarına göre ayır
$active_tickets = [];
$canceled_tickets = [];
$expired_tickets = [];
$current_time = time();

foreach ($all_tickets as $ticket) {
    if ($ticket['status'] === 'canceled') {
        $canceled_tickets[] = $ticket;
    } else {
        $departure_timestamp = strtotime($ticket['departure_time']);
        if ($departure_timestamp < $current_time) {
            $expired_tickets[] = $ticket;
        } else {
            $active_tickets[] = $ticket;
        }
    }
}
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
    <link rel="stylesheet" href="assets/css/my-tickets.css">
</head>
<body>

<?php require_once 'assets/partials/header.php'; ?>

<main class="container my-5">
    <h1 class="mb-4">Biletlerim</h1>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- SEKMELİ YAPI -->
    <ul class="nav nav-tabs" id="myTicketsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tickets-pane" type="button" role="tab">Aktif Biletlerim</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-tickets-pane" type="button" role="tab">Geçmiş Biletler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="canceled-tab" data-bs-toggle="tab" data-bs-target="#canceled-tickets-pane" type="button" role="tab">İptal Edilenler</button>
        </li>
    </ul>

    <div class="tab-content py-4" id="myTicketsTabContent">
        <!-- AKTİF BİLETLER SEKMESİ -->
        <div class="tab-pane fade show active" id="active-tickets-pane" role="tabpanel">
            <?php if (empty($active_tickets)): ?>
                <div class="alert alert-info">Aktif biletiniz bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($active_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm ticket-card status-active">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
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
                                        <small class="text-muted"><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <div>
                                            <a href="printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF İndir</a>
                                            <?php
                                            $departure_timestamp = strtotime($ticket['departure_time']);
                                            if (($departure_timestamp - time()) > 3600) :
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
        </div>
        
        <!-- GEÇMİŞ BİLETLER SEKMESİ -->
        <div class="tab-pane fade" id="expired-tickets-pane" role="tabpanel">
            <?php if (empty($expired_tickets)): ?>
                <div class="alert alert-info">Geçmiş seyahatiniz bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($expired_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                             <div class="card shadow-sm ticket-card status-expired">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center text-muted">
                                        <img src="<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px; opacity: 0.6;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
                                    <span class="badge text-bg-secondary">Tamamlandı</span>
                                </div>
                                <div class="card-body text-muted">
                                     <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                            <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-secondary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                            <p class="card-text"><strong>Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <a href="generate_pdf.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF İndir</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- İPTAL EDİLEN BİLETLER SEKMESİ -->
        <div class="tab-pane fade" id="canceled-tickets-pane" role="tabpanel">
            <?php if (empty($canceled_tickets)): ?>
                <div class="alert alert-info">Daha önce iptal ettiğiniz bir bilet bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($canceled_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                             <div class="card shadow-sm ticket-card status-canceled">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center text-muted">
                                        <img src="<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px; opacity: 0.6;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
                                    <span class="badge text-bg-danger">İptal Edildi</span>
                                </div>
                                <div class="card-body text-muted">
                                     <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                            <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-secondary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                            <p class="card-text"><strong>İade Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <a href="generate_pdf.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-file-earmark-pdf"></i> PDF İndir</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
