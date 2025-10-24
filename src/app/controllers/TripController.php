<?php

class TripController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

   
    public function showTripResults() {
        
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        $date = trim($_GET['date'] ?? ''); 


        $trips = [];
        $booked_seats_map = []; 
        $search_error = '';

   
        $flash_error_trips = $_SESSION['flash_error_trips'] ?? null;
        unset($_SESSION['flash_error_trips']);


   
        if (!empty($from) && !empty($to) && !empty($date)) {
            try {
                
                $sql = "SELECT
                            t.*,
                            bc.name as bus_name,
                            bc.logo_path
                        FROM Trips t
                        JOIN Bus_Company bc ON t.company_id = bc.id
                        WHERE t.departure_city = :from
                        AND t.destination_city = :to
                        AND DATE(t.departure_time) = :date";

                
                if ($date === date('Y-m-d')) {
                    $sql .= " AND t.departure_time > datetime('now', 'localtime')";
                }

                $sql .= " ORDER BY t.departure_time ASC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':from' => $from, ':to' => $to, ':date' => $date]);
                $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

                
                if (!empty($trips)) {
                    $trip_ids = array_column($trips, 'id');
                    if (!empty($trip_ids)) { 
                        $placeholders = implode(',', array_fill(0, count($trip_ids), '?'));
                        $booked_sql = "SELECT t.trip_id, bs.seat_number
                                       FROM Booked_Seats bs
                                       JOIN Tickets t ON bs.ticket_id = t.id
                                       WHERE t.trip_id IN ($placeholders) AND t.status = 'active'";
                        $booked_stmt = $this->pdo->prepare($booked_sql);
                        $booked_stmt->execute($trip_ids);
                        
                        $booked_results = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($booked_results as $row) {
                             $booked_seats_map[$row['trip_id']][] = $row['seat_number'];
                        }
                    }
                }

            } catch (PDOException $e) {
                error_log("Sefer arama hatası: " . $e->getMessage());
                $search_error = "Seferler getirilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            }
        } else {
 
            $_SESSION['flash_message'] = "Lütfen kalkış, varış noktası ve tarih seçin.";
            $_SESSION['flash_type'] = "warning";
            session_write_close(); 
            header("Location: /index.php");
            exit();
        }

        global $csrf_token; 
        $data = [
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'trips' => $trips,
            'booked_seats_map' => $booked_seats_map,
            'search_error' => $search_error,
            'flash_error_trips' => $flash_error_trips, 
            'csrf_token' => $csrf_token 
        ];

     
        $this->loadView('trips', $data);
    }


    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>