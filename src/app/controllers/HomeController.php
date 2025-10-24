<?php
// src/app/controllers/HomeController.php

// Gerekli dosyaları doğru yollarla çağır
// (Model'ler olsaydı burada çağrılırdı)
// session.php ve config.php zaten giriş noktasında çağrılacak.

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Anasayfayı göstermek için gerekli verileri hazırlar ve view'ı yükler.
     */
    public function showHomePage() {
        $departure_cities = [];
        $destination_cities = [];
        $popular_routes = [];
        $today = date('Y-m-d'); // Bugünün tarihini al

        try {
            // Kalkış şehirlerini çek
            $stmt_dep = $this->pdo->query("SELECT DISTINCT departure_city FROM Trips ORDER BY departure_city ASC");
            $departure_cities = $stmt_dep->fetchAll(PDO::FETCH_COLUMN);

            // Varış şehirlerini çek
            $stmt_dest = $this->pdo->query("SELECT DISTINCT destination_city FROM Trips ORDER BY destination_city ASC");
            $destination_cities = $stmt_dest->fetchAll(PDO::FETCH_COLUMN);

            // Popüler rotaları çek
            $popular_routes_stmt = $this->pdo->query("
                SELECT departure_city, destination_city, COUNT(*) as trip_count
                FROM Trips
                GROUP BY departure_city, destination_city
                ORDER BY trip_count DESC
                LIMIT 8
            ");
            $popular_routes = $popular_routes_stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Hata durumunda loglama yapılabilir, kullanıcıya boş listeler gösterilir.
            error_log("Anasayfa verileri çekilemedi: " . $e->getMessage());
        }

        // View'a gönderilecek verileri hazırla
        $data = [
            'departure_cities' => $departure_cities,
            'destination_cities' => $destination_cities,
            'popular_routes' => $popular_routes,
            'today' => $today
            // CSRF token'ına anasayfada (şimdilik) ihtiyaç yok,
            // ama olsaydı buraya eklerdik: 'csrf_token' => $GLOBALS['csrf_token']
        ];

        // İlgili view dosyasını yükle ve verileri gönder
        $this->loadView('index', $data);
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     * @param string $viewName app/views/pages klasörü içindeki dosya adı (uzantısız)
     * @param array $data View içinde kullanılacak değişkenler ['degisken_adi' => deger]
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>