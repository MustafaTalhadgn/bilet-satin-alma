<?php
/**
 * src/app/views/pages/trips.php
 * Sefer arama sonuçlarının HTML yapısı.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Sonuçları</title>
    <!-- CSS yolları web kökünden (/assets/) başlamalı -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/trips.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/../partials/header.php'; ?>
<?php require_once __DIR__ . '/../../data/translateDate.php'?>
<main class="container my-5">
    <h2 class="mb-4">
        Sefer Sonuçları: <strong><?php echo htmlspecialchars($from); ?> &rarr; <?php echo htmlspecialchars($to); ?></strong>
        <span class="text-muted fs-5"><?php echo translateDate($date); ?></span>
    </h2>

    <?php if ($search_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($search_error); ?></div>
    <?php elseif (empty($trips)): ?>
        <div class="alert alert-warning">Aradığınız kriterlere uygun sefer bulunamadı.</div>
    <?php else: ?>
        <?php foreach ($trips as $trip): ?>
            <?php $booked_seats = $booked_seats_map[$trip['id']] ?? []; ?>
            <div class="card text-center mb-4 trip-card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details-<?php echo $trip['id']; ?>">Sefer Detayları</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#seats-<?php echo $trip['id']; ?>">Koltuklar</a></li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="details-<?php echo $trip['id']; ?>">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2"><img src="/<?php echo htmlspecialchars($trip['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="<?php echo htmlspecialchars($trip['bus_name']); ?>" class="company-logo"></div>
                                <div class="col-md-2">
                                    <div class="fw-bold fs-5"><?php echo date("H:i", strtotime($trip['departure_time'])); ?></div>
                                    <small class="text-muted">Varış: <?php echo date("H:i", strtotime($trip['arrival_time'])); ?></small>
                                </div>
                                <div class="col-md-4 text-center"><span class="fw-bold"><?php echo htmlspecialchars($trip['departure_city']); ?></span> &rarr; <span class="fw-bold"><?php echo htmlspecialchars($trip['destination_city']); ?></span></div>
                                <div class="col-md-2 fw-bold fs-4"><?php echo htmlspecialchars($trip['price']); ?> TL</div>
                                <div class="col-md-2"><button class="btn btn-primary w-100 select-seat-btn" data-bs-toggle="tab" data-bs-target="#seats-<?php echo $trip['id']; ?>">Koltuk Seç</button></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="seats-<?php echo $trip['id']; ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10">
                                   <div class="bus-layout-container row">
                                        <div class="bus-front col-md-1"><img class="bus-front-image" src="/assets/images/front-bus.png" alt="Otobüs Önü"></div>
                                        <div class="bus-grid col-md-11">
                                            <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                                                <?php $seat_class = in_array($i, $booked_seats) ? 'occupied' : 'available'; ?>
                                                <div class="seat <?php echo $seat_class; ?>" data-seat-number="<?php echo $i; ?>"><?php echo $i; ?></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center gap-4 mt-3">
                                        <div><span class="seat-legend available"></span> Boş</div>
                                        <div><span class="seat-legend occupied"></span> Dolu</div>
                                        <div><span class="seat-legend selected"></span> Seçilen</div>
                                    </div>
                                </div>
                                <div class="col-md-2 selection-summary-container">
                                    <div class="selection-summary p-3 border rounded" style="display: none;">
                                        <h5>Koltuk Seçimi</h5>
                                        <p>Seçilen Koltuk: <strong class="selected-seat-number"></strong></p>
                                        <p>Toplam Tutar: <strong class="total-price"><?php echo htmlspecialchars($trip['price']); ?> TL</strong></p>
                                        <?php
                                            // Rol bazlı buton mantığı
                                            $form_action = '/login.php'; // Varsayılan: Giriş yapmamışsa login'e
                                            $button_text = 'Ödemeye Geç';
                                            $button_class = 'btn-success';
                                            $button_attributes = '';

                                            if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
                                                if ($_SESSION['user_role'] === 'user') {
                                                    $form_action = '/pay.php'; // Rol user ise pay.php'ye
                                                } else {
                                                    $form_action = '#'; // Diğer roller bilet alamaz
                                                    $button_text = 'Yönetici Bilet Alamaz';
                                                    $button_class = 'btn-secondary';
                                                    $button_attributes = 'disabled';
                                                }
                                            }
                                        ?>
                                        <!-- ÖDEME FORMU - DİKKAT! -->
                                        <form action="<?php echo $form_action; ?>" method="POST">
                                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                            <input type="hidden" name="selected_seat" class="selected-seat-input" value="">
                                            <!-- CSRF TOKEN EKLENDİ -->
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="btn <?php echo $button_class; ?> w-100" <?php echo $button_attributes; ?>>
                                                <?php echo $button_text; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/trips.js"></script>
</body>
</html>

