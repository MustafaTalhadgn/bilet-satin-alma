<?php
session_start();
require_once __DIR__ . '/../app/config.php'; 

$departure_cities = [];
$destination_cities = [];

try {
  
    $stmt_dep = $pdo->query("SELECT DISTINCT departure_city FROM Trips ORDER BY departure_city ASC");
    $departure_cities = $stmt_dep->fetchAll(PDO::FETCH_COLUMN);

    
    $stmt_dest = $pdo->query("SELECT DISTINCT destination_city FROM Trips ORDER BY destination_city ASC");
    $destination_cities = $stmt_dest->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    
    error_log("Şehirler çekilemedi: " . $e->getMessage());
  
}
// Tarih input'u için bugünün tarihini alalım (YYYY-MM-DD formatında)
$today = date('Y-m-d');

try {
    $popular_routes_stmt = $pdo->query("
        SELECT departure_city, destination_city, COUNT(*) as trip_count
        FROM Trips
        GROUP BY departure_city, destination_city
        ORDER BY trip_count DESC
        LIMIT 4
    ");
    $popular_routes = $popular_routes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popular_routes = []; // Hata durumunda boş göster
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="./assets/css/index.css">
  </head>
<body>

 <?php
   require_once 'assets/partials/header.php';
  ?>


 <section class="search-trips backgroun-image">
    <div class="cover">
        <div class="container">
            <section class="hero-section">
                <div class="container">
                    <form action="trips.php" method="GET" class="search-card shadow">
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
                                <input type="date" name="date" id="departure-date" class="form-control" value="<?php echo $today; ?>" min="<?php echo $today; ?>" required>
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
        <section class="popular-journeys py-5">
            <div class="container">
                </div>
        </section>
      </div>
</div>
   </section>

   <section class="popular-journeys py-5">
    <div class="container">
        <h2 class="text-center mb-4 fw-bold">Popüler Rotalar</h2>
        <div class="row g-4">
            <?php foreach ($popular_routes as $route): ?>
                <div class="col-md-3">
                    <a href="trips.php?from=<?php echo urlencode($route['departure_city']); ?>&to=<?php echo urlencode($route['destination_city']); ?>&date=<?php echo date('Y-m-d'); ?>" class="route-card-link">
                        <div class="card route-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($route['departure_city']); ?> &rarr; <?php echo htmlspecialchars($route['destination_city']); ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



 


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/city.js"></script>
</body>
</html>

