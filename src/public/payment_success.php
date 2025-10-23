<?php
// 1. Session'ı başlat (ve CSRF token'ı al/oluştur)
// Bu dosya tek başına çalıştığı için session.php'yi çağırmalıyız.
require_once __DIR__ . '/../app/core/session.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
// 2. Güvenlik: Giriş yapmamış biri bu sayfaya gelemez.
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php"); // Kök dizine göre yönlendir
    exit();
}

// 3. Veriyi al
$ticket_id = $_GET['ticket_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Başarılı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- İsteğe bağlı: Anasayfadaki header'ı buraya ekleyebilirsin -->
    <!-- <link rel="stylesheet" href="/assets/css/header.css"> -->
</head>
<body class="bg-light">
<!-- İsteğe bağlı: Header'ı buraya ekleyebilirsin -->
<?php // require_once __DIR__ . '/../app/views/partials/header.php'; ?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    <h2 class="mt-3">Biletiniz Başarıyla Oluşturuldu!</h2>
                    <p class="lead">Ödemeniz başarıyla tamamlandı. Bilet bilgilerinizi aşağıdan yazdırabilir veya "Biletlerim" sayfasından görüntüleyebilirsiniz.</p>
                    <div class="d-grid gap-2 mt-4">
                        <!-- Yolları kök dizine göre düzelt -->
                        <a href="/printable_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket_id ?? ''); ?>" target="_blank" class="btn btn-primary btn-lg"><i class="bi bi-printer"></i> Bileti Yazdır/PDF Kaydet</a>
                        <a href="/tickets.php" class="btn btn-secondary btn-lg">Biletlerim Sayfasına Git</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>