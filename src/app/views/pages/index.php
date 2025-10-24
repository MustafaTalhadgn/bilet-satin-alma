<?php
/**
 * src/app/views/pages/index.php
 * Anasayfanın HTML yapısı. Gerekli tüm değişkenler ($departure_cities vb.)
 * HomeController tarafından sağlanır.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anasayfa - Otobüs Bileti</title>
    <!-- CSS yolları web kökünden (/assets/) başlamalı -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/index.css">
</head>
<body>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../partials/header.php';
?>

<section class="search-trips background-image">
    <div class="cover">
        <div class="container">
            <section class="hero-section">
                <div class="container">
                    <!-- Formun action'ı artık /trips.php olmalı (kök dizine göre) -->
                    <form action="/trips.php" method="GET" class="search-card shadow">
                        <div class="search-card-header text-black">
                            <i class="bi bi-bus-front"></i> Otobüs Bileti
                        </div>
                        <div class="search-card-body row gx-0 align-items-center">
                            
                            <div class="col-lg-3 col-md-6 form-field">
                                <i class="bi bi-geo-alt-fill"></i>
                                <select name="from" class="form-select" style="padding-left: 3rem;" required>
                                    <option value="" selected disabled>Nereden Seçin...</option>
                                    <?php foreach ($departure_cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>">
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 form-field">
                                <i class="bi bi-geo-alt"></i>
                                <select name="to" class="form-select" style="padding-left: 3rem;" required>
                                    <option value="" selected disabled>Nereye Seçin...</option>
                                    <?php foreach ($destination_cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>">
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-3 col-md-6 form-field">
                                <i class="bi bi-calendar-event"></i>
                                <input type="date" name="date" id="departure-date" class="form-control" value="<?php echo htmlspecialchars($today); ?>" min="<?php echo htmlspecialchars($today); ?>" required>
                            </div>

                            <div class="col-lg-3 col-md-6 d-flex align-items-center p-3">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="date_shortcut" id="radio-today" checked>
                                    <label class="form-check-label" for="radio-today">Bugün</label>
                                </div>
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="radio" name="date_shortcut" id="radio-tomorrow">
                                    <label class="form-check-label" for="radio-tomorrow">Yarın</label>
                                </div>
                                <button type="submit" class="btn btn-success flex-grow-1">Bileti Bul <i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<section class="popular-journeys ">
    <div class="container">
        <h2 class="text-center fw-bold pop-routes ">Popüler Rotalar</h2>
        <div class="row g-4">
            <?php if (empty($popular_routes)): ?>
                <p class="text-center text-muted">Popüler rota bulunamadı.</p>
            <?php else: ?>
                <?php foreach ($popular_routes as $route): ?>
                    <div class="col-md-3">
                       
                        <a href="/trips.php?from=<?php echo urlencode($route['departure_city']); ?>&to=<?php echo urlencode($route['destination_city']); ?>&date=<?php echo date('Y-m-d'); ?>" class="route-card-link">
                            <div class="card route-card h-100 shadow-sm">
                                <div class="card-body text-center d-flex align-items-center justify-content-center">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($route['departure_city']); ?> &rarr; <?php echo htmlspecialchars($route['destination_city']); ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>

<!-- JS yolları web kökünden (/assets/) başlamalı -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/city.js"></script> <!-- Varsa, yolunu kontrol et -->
</body>
</html>
