<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sayfası</title>
    <!-- CSS yolları web kökünden (/assets/) başlamalı -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php

require_once __DIR__ . '/../partials/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Ödeme Bilgileri</h4></div>
                <div class="card-body">
                    <?php if ($flash_message): ?>
                        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                             <?php echo htmlspecialchars($flash_message); ?>
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
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

                    <!-- KUPON UYGULAMA FORMU -->
                    <form method="POST" action="" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <label for="coupon_code" class="form-label">İndirim Kuponu</label>
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Kupon kodunu girin" value="<?php echo htmlspecialchars($applied_coupon_code); ?>">
                            <button class="btn btn-outline-secondary" type="submit" name="apply_coupon">Uygula</button>
                        </div>
                    </form>

                    <!-- ÖDEME ONAYLAMA FORMU -->
                    <form method="POST" action="">
                         <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="d-grid mt-4">
                            <button type="submit" name="confirm_payment" class="btn btn-primary btn-lg">Ödemeyi Onayla ve Bileti Al</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php

require_once __DIR__ . '/../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>