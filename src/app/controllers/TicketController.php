<?php
// src/app/controllers/TicketController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class TicketController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcının biletlerini listeler ve iptal işlemlerini yönetir.
     */
    public function showMyTickets() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
            header("Location: /login.php"); // Kök dizine göre yönlendir
            exit();
        }

        $user_id = $_SESSION['user_id'];

        // Flash mesajları ve CSRF token'ı session.php'den alıyoruz (global $csrf_token)
        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        // --- BİLET İPTAL ETME İŞLEMİ ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("Geçersiz işlem denemesi!");
            }

            $ticket_id_to_cancel = $_POST['ticket_id'];

            try {
                $this->pdo->beginTransaction();

                // İptal edilecek bileti ve sefer bilgilerini çek (IDOR Korumalı)
                $stmt = $this->pdo->prepare("
                    SELECT t.id, t.total_price, tr.departure_time 
                    FROM Tickets t 
                    JOIN Trips tr ON t.trip_id = tr.id
                    WHERE t.id = :ticket_id AND t.user_id = :user_id AND t.status = 'active'
                ");
                $stmt->execute([':ticket_id' => $ticket_id_to_cancel, ':user_id' => $user_id]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$ticket) throw new Exception("Geçersiz veya daha önce iptal edilmiş bilet.");

                // Zaman Kontrolü
                $departure_timestamp = strtotime($ticket['departure_time']);
                if (($departure_timestamp - time()) <= 3600) {
                    throw new Exception("Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez.");
                }

                // Bileti güncelle
                $this->pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?")->execute([$ticket_id_to_cancel]);

                // Ücreti iade et
                $this->pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?")->execute([$ticket['total_price'], $user_id]);

                $this->pdo->commit();
                $_SESSION['flash_message'] = "Biletiniz başarıyla iptal edildi ve ücreti hesabınıza iade edildi.";
                $_SESSION['flash_type'] = 'success';

            } catch (Exception $e) {
                $this->pdo->rollBack();
                $_SESSION['flash_message'] = "İptal sırasında bir hata oluştu: " . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }

            header("Location: /tickets.php"); // Kök dizine göre yönlendir
            exit();
        }

        // --- KULLANICININ TÜM BİLETLERİNİ ÇEKME ---
        try {
             $tickets_stmt = $this->pdo->prepare("
                SELECT
                    t.id AS ticket_id, t.status, t.total_price,
                    tr.departure_city, tr.destination_city, tr.departure_time,
                    bc.name AS company_name, bc.logo_path,
                    bs.seat_number
                FROM Tickets t
                JOIN Trips tr ON t.trip_id = tr.id
                JOIN Bus_Company bc ON tr.company_id = bc.id
                JOIN Booked_Seats bs ON bs.ticket_id = t.id
                WHERE t.user_id = :user_id
                ORDER BY tr.departure_time DESC
            ");
            $tickets_stmt->execute([':user_id' => $user_id]);
            $all_tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
             error_log("Biletler çekilemedi: " . $e->getMessage());
             $all_tickets = []; // Hata durumunda boş dizi ata
             $flash_message = "Biletleriniz yüklenirken bir sorun oluştu."; // Kullanıcıya bilgi ver
             $flash_type = 'danger';
        }


        // Biletleri durumlarına göre ayır
        $active_tickets = [];
        $canceled_tickets = [];
        $expired_tickets = [];
        $current_time = time();

        
        foreach ($all_tickets as $ticket) {
            if ($ticket['status'] === 'canceled') {
                $canceled_tickets[] = $ticket;
            } else {
                $departure_timestamp = strtotime($ticket['departure_time']);
                if ($departure_timestamp < $current_time) {
                    $expired_tickets[] = $ticket;
                } else {
                    $active_tickets[] = $ticket;
                }
            }
        }

        // View'a gönderilecek veriler
        $data = [
            'active_tickets' => $active_tickets,
            'canceled_tickets' => $canceled_tickets,
            'expired_tickets' => $expired_tickets,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token // session.php'den gelen global token
        ];

        // İlgili view dosyasını yükle
        $this->loadView('tickets', $data);
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>