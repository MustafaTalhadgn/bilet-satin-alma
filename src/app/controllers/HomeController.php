<?php

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function showHomePage() {
        $departure_cities = [];
        $destination_cities = [];
        $popular_routes = [];
        $today = date('Y-m-d');

        try {

            $stmt_dep = $this->pdo->query("SELECT DISTINCT departure_city FROM Trips ORDER BY departure_city ASC");
            $departure_cities = $stmt_dep->fetchAll(PDO::FETCH_COLUMN);


            $stmt_dest = $this->pdo->query("SELECT DISTINCT destination_city FROM Trips ORDER BY destination_city ASC");
            $destination_cities = $stmt_dest->fetchAll(PDO::FETCH_COLUMN);


            $popular_routes_stmt = $this->pdo->query("
                SELECT departure_city, destination_city, COUNT(*) as trip_count
                FROM Trips
                GROUP BY departure_city, destination_city
                ORDER BY trip_count DESC
                LIMIT 8
            ");
            $popular_routes = $popular_routes_stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
           
            error_log("Anasayfa verileri çekilemedi: " . $e->getMessage());
        }


        $data = [
            'departure_cities' => $departure_cities,
            'destination_cities' => $destination_cities,
            'popular_routes' => $popular_routes,
            'today' => $today

        ];

        $this->loadView('index', $data);
    }


    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>