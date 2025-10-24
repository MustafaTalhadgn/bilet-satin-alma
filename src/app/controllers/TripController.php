<?php
// src/app/controllers/TripController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class TripController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Sefer arama sonuçlarını gösterir.
     */
    public function showTripResults() {
        // --- GÜVENLİK: GET parametrelerini al ve doğrula ---
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        $date = trim($_GET['date'] ?? ''); // YYYY-MM-DD formatında bekleniyor

        $trips = [];
        $booked_seats_map = []; // Her trip_id için dolu koltukları tutacak dizi
        $search_error = '';

        // Flash mesajı al (eğer pay.php'den yönlendirildiyse)
        // Session'ı burada başlatmaya gerek yok, giriş noktasında başlatılıyor.
        $flash_error_trips = $_SESSION['flash_error_trips'] ?? null;
        unset($_SESSION['flash_error_trips']);


        // Gelen veriler boş değilse arama yap
        if (!empty($from) && !empty($to) && !empty($date)) {
            try {
                // Ana SQL sorgusu
                $sql = "SELECT
                            t.*,
                            bc.name as bus_name,
                            bc.logo_path
                        FROM Trips t
                        JOIN Bus_Company bc ON t.company_id = bc.id
                        WHERE t.departure_city = :from
                        AND t.destination_city = :to
                        AND DATE(t.departure_time) = :date";

                // Eğer arama yapılan tarih bugün ise, sadece kalkış saati geçmemiş olan seferleri göster.
                if ($date === date('Y-m-d')) {
                    $sql .= " AND t.departure_time > datetime('now', 'localtime')";
                }

                $sql .= " ORDER BY t.departure_time ASC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':from' => $from, ':to' => $to, ':date' => $date]);
                $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Dolu koltukları çek (Optimize edilmiş sorgu)
                if (!empty($trips)) {
                    $trip_ids = array_column($trips, 'id');
                    if (!empty($trip_ids)) { // trip_ids boş değilse sorguyu çalıştır
                        $placeholders = implode(',', array_fill(0, count($trip_ids), '?'));
                        $booked_sql = "SELECT t.trip_id, bs.seat_number
                                       FROM Booked_Seats bs
                                       JOIN Tickets t ON bs.ticket_id = t.id
                                       WHERE t.trip_id IN ($placeholders) AND t.status = 'active'";
                        $booked_stmt = $this->pdo->prepare($booked_sql);
                        $booked_stmt->execute($trip_ids);
                        // fetchAll kullanarak daha verimli hale getirelim
                        $booked_results = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);
                        // Dolu koltukları trip_id bazında grupla
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
            // Eksik parametre varsa anasayfaya yönlendir (daha kullanıcı dostu)
            $_SESSION['flash_message'] = "Lütfen kalkış, varış noktası ve tarih seçin.";
            $_SESSION['flash_type'] = "warning";
            session_write_close(); // Yönlendirmeden önce session'ı kapat
            header("Location: /index.php");
            exit();
        }

        // View'a gönderilecek veriler
        global $csrf_token; // session.php'den gelen token
        $data = [
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'trips' => $trips,
            'booked_seats_map' => $booked_seats_map,
            'search_error' => $search_error,
            'flash_error_trips' => $flash_error_trips, // Hata mesajını view'a gönder
            'csrf_token' => $csrf_token // Ödeme formu için CSRF token'ı ekle
        ];

        // İlgili view dosyasını yükle
        $this->loadView('trips', $data);
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     * @param string $viewName app/views/pages klasörü içindeki dosya adı (uzantısız)
     * @param array $data View içinde kullanılacak değişkenler ['degisken_adi' => deger]
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        // View yolu: src/app/views/pages/trips.php
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>