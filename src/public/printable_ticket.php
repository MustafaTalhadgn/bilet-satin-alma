<?php
session_start();
require_once __DIR__ . '/../app/config/config.php';


if (!isset($_SESSION['user_id']) || !isset($_GET['ticket_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['ticket_id'];

// --- BİLET BİLGİLERİNİ ÇEKME (IDOR KORUMALI) ---
try {
    $stmt = $pdo->prepare("
        SELECT
            t.id AS ticket_id, t.total_price,
            u.full_name AS user_name,
            tr.departure_city, tr.destination_city, tr.departure_time,
            bc.name AS company_name, bc.logo_path,
            bs.seat_number
        FROM Tickets t
        JOIN User u ON t.user_id = u.id
        JOIN Trips tr ON t.trip_id = tr.id
        JOIN Bus_Company bc ON tr.company_id = bc.id
        JOIN Booked_Seats bs ON bs.ticket_id = t.id
        WHERE t.id = :ticket_id AND t.user_id = :user_id
    ");
    $stmt->execute([':ticket_id' => $ticket_id, ':user_id' => $user_id]);
    $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_data) {
        die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.");
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: Bilet bilgileri alınamadı.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet - <?php echo htmlspecialchars($ticket_data['ticket_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .ticket-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .ticket-header {
            background-color: white;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .ticket-body {
            padding: 30px;
        }
        .ticket-body h4 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .print-button-container {
            text-align: center;
            margin-top: 20px;
        }

        /* YAZDIRMA STİLLERİ: Yazdırma sırasında gereksiz alanları gizler */
        @media print {
            body {
                background-color: #fff;
            }
            .print-button-container, .navbar {
                display: none !important;
            }
            .ticket-container {
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="ticket-container">
    <div class="ticket-header row align-items-center">
        <div class="col-4">
            <img src="<?php echo htmlspecialchars($ticket_data['logo_path']); ?>" alt="<?php echo htmlspecialchars($ticket_data['company_name']); ?>" style="max-height: 50px;">
        </div>
        <div class="col-8 text-end">
            <h3 class="mb-0"><?php echo htmlspecialchars($ticket_data['company_name']); ?></h3>
            <p class="mb-0 text-muted">Yolcu Bileti</p>
        </div>
    </div>

    <div class="ticket-body">
        <h4>Yolcu Bilgileri</h4>
        <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($ticket_data['user_name']); ?></p>
        <p><strong>Koltuk Numarası:</strong> <?php echo htmlspecialchars($ticket_data['seat_number']); ?></p>

        <h4 class="mt-4">Sefer Bilgileri</h4>
        <p><strong>Güzergah:</strong> <?php echo htmlspecialchars($ticket_data['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket_data['destination_city']); ?></p>
        <p><strong>Kalkış Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket_data['departure_time'])); ?></p>

        <h4 class="mt-4">Ödeme Bilgileri</h4>
        <p><strong>Ödenen Tutar:</strong> <?php echo htmlspecialchars(number_format($ticket_data['total_price'], 2, ',', '.')); ?> TL</p>
    </div>

    <div class="ticket-footer border-top p-3 text-center text-muted">
        İyi yolculuklar dileriz!
    </div>
</div>

<div class="print-button-container">
    <button class="btn btn-primary btn-lg" onclick="window.print();">
        <i class="bi bi-printer-fill"></i> Bileti Yazdır / PDF Olarak Kaydet
    </button>
</div>

</body>
</html>