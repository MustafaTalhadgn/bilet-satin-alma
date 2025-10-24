<?php

class CompanyAdminController {
    private $pdo;
    private $company_id;

    public function __construct($pdo) {
        $this->pdo = $pdo;

        $this->initialize();
    }


    private function initialize() {
       
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login.php");
            exit();
        }
        if ($_SESSION['user_role'] !== 'company') {
            die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
        }
        

       
        $company_admin_id = $_SESSION['user_id'];
        $stmt = $this->pdo->prepare("SELECT company_id FROM User WHERE id = :id");
        $stmt->execute([':id' => $company_admin_id]);
        $this->company_id = $stmt->fetchColumn();
        if (!$this->company_id) {
            die("Kullanıcıya atanmış bir firma bulunamadı.");
        }
    }



    public function showDashboard() {
     
        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("Geçersiz CSRF token!");
            }

            if (isset($_POST['add_trip'])) {
                $this->handleAddTrip();
            } elseif (isset($_POST['edit_trip'])) {
                $this->handleEditTrip();
            } elseif (isset($_POST['delete_trip'])) {
                $this->handleDeleteTrip();
            } elseif (isset($_POST['add_coupon'])) {
                $this->handleAddCoupon();
            } elseif (isset($_POST['edit_coupon'])) { 
                $this->handleEditCoupon();
            } elseif (isset($_POST['delete_coupon'])) {
                $this->handleDeleteCoupon();
            }
          
            header("Location: /companyAdmin.php"); 
            exit();
        }

         try {
           
            $company_trips_stmt = $this->pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
            $company_trips_stmt->execute([$this->company_id]);
            $company_trips = $company_trips_stmt->fetchAll(PDO::FETCH_ASSOC);

            
            $company_coupons_stmt = $this->pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
            $company_coupons_stmt->execute([$this->company_id]);
            $company_coupons = $company_coupons_stmt->fetchAll(PDO::FETCH_ASSOC);

           
            $cities_json = file_get_contents(__DIR__ . '/../data/city.json');
            $cities = json_decode($cities_json);
            if ($cities === null) { throw new Exception("Şehir listesi okunamadı veya JSON formatı bozuk."); }

        } catch (Exception $e) {
            error_log("Firma paneli verileri çekilemedi: " . $e->getMessage());
            $company_trips = [];
            $company_coupons = [];
            $cities = [];
            $flash_message = "Veriler listelenirken bir hata oluştu: " . $e->getMessage();
            $flash_type = "danger";
        }


     
        $data = [
            'company_trips' => $company_trips,
            'company_coupons' => $company_coupons,
            'cities' => $cities,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

       
        $this->loadView('company/dashboard', $data);
    }


    private function handleAddTrip() {
        $departure_city = trim($_POST['departure_city']);
        $destination_city = trim($_POST['destination_city']);
        $departure_time = trim($_POST['departure_time']);
        $arrival_time = trim($_POST['arrival_time']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

        $departure_timestamp = strtotime($departure_time);
        $arrival_timestamp = strtotime($arrival_time);

        if ($price === false || $capacity === false || $price <= 0 || $capacity <= 0 || empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time)) {
             $_SESSION['flash_message'] = "Hata: Tüm sefer alanları doğru şekilde doldurulmalıdır.";
             $_SESSION['flash_type'] = "danger";
             return;
        }
         if ($arrival_timestamp <= $departure_timestamp) {
             $_SESSION['flash_message'] = "Hata: Varış zamanı, kalkış zamanından daha önce veya aynı olamaz.";
             $_SESSION['flash_type'] = "danger";
             return; 
        }
        
        try {
            $sql = "INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date)
                    VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity, :created_date)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => bin2hex(random_bytes(16)),
                ':company_id' => $this->company_id,
                ':departure_city' => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time' => date('Y-m-d H:i:s', strtotime($departure_time)),
                ':arrival_time' => date('Y-m-d H:i:s', strtotime($arrival_time)),
                ':price' => $price,
                ':capacity' => $capacity,
                ':created_date' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['flash_message'] = "Sefer başarıyla eklendi.";
        } catch (PDOException $e) {
            error_log("Sefer ekleme hatası: " . $e->getMessage());
            $_SESSION['flash_message'] = "Sefer eklenirken bir veritabanı hatası oluştu.";
            $_SESSION['flash_type'] = "danger";
        }
    }


    private function handleEditTrip() {
        $trip_id = $_POST['trip_id'];
        $departure_city = trim($_POST['departure_city']);
        $destination_city = trim($_POST['destination_city']);
        $departure_time = trim($_POST['departure_time']);
        $arrival_time = trim($_POST['arrival_time']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

         if ($price === false || $capacity === false || $price <= 0 || $capacity <= 0 || empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || empty($trip_id)) {
             $_SESSION['flash_message'] = "Hata: Tüm sefer alanları doğru şekilde doldurulmalıdır.";
             $_SESSION['flash_type'] = "danger";
             return;
        }

        try {
           
            $sql = "UPDATE Trips SET departure_city=:departure_city, destination_city=:destination_city,
                    departure_time=:departure_time, arrival_time=:arrival_time, price=:price, capacity=:capacity
                    WHERE id = :id AND company_id = :company_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':departure_city' => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time' => date('Y-m-d H:i:s', strtotime($departure_time)),
                ':arrival_time' => date('Y-m-d H:i:s', strtotime($arrival_time)),
                ':price' => $price,
                ':capacity' => $capacity,
                ':id' => $trip_id,
                ':company_id' => $this->company_id 
            ]);
             if ($stmt->rowCount() > 0) {
                 $_SESSION['flash_message'] = "Sefer başarıyla güncellendi.";
             } else {
                  $_SESSION['flash_message'] = "Güncelleme yapılamadı (Belki de sefer size ait değil?).";
                  $_SESSION['flash_type'] = "warning";
             }
        } catch (PDOException $e) {
            error_log("Sefer güncelleme hatası: " . $e->getMessage());
            $_SESSION['flash_message'] = "Sefer güncellenirken bir veritabanı hatası oluştu.";
            $_SESSION['flash_type'] = "danger";
        }
    }


    private function handleDeleteTrip() {
        $trip_id_to_delete = $_POST['trip_id'];
        if (empty($trip_id_to_delete)) return;

         try {
           
            $sql = "DELETE FROM Trips WHERE id = :id AND company_id = :company_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $trip_id_to_delete, ':company_id' => $this->company_id]);

             if ($stmt->rowCount() > 0) {
                $_SESSION['flash_message'] = "Sefer başarıyla silindi.";
             } else {
                 $_SESSION['flash_message'] = "Silme işlemi başarısız oldu (Belki de sefer size ait değil?).";
                 $_SESSION['flash_type'] = "warning";
             }
        } catch (PDOException $e) {
             error_log("Sefer silme hatası: " . $e->getMessage());
            
             if ($e->getCode() == 23000) {
                  $_SESSION['flash_message'] = "Hata: Bu sefere ait biletler bulunduğu için sefer silinemez.";
             } else {
                 $_SESSION['flash_message'] = "Sefer silinirken bir veritabanı hatası oluştu.";
             }
             $_SESSION['flash_type'] = "danger";
         }
    }


    private function handleAddCoupon() {
        $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
        $expire_date = trim($_POST['expire_date']);
        $code = strtoupper(bin2hex(random_bytes(6)));

        if ($discount === false || $usage_limit === false || empty($expire_date) || $discount <= 0 || $usage_limit <= 0) {
            $_SESSION['flash_message'] = "Hata: İndirim oranı ve kullanım limiti geçerli, 0'dan büyük bir sayı olmalı ve tarih boş bırakılmamalıdır.";
            $_SESSION['flash_type'] = "danger";
        } else {
            try {
             
                $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at, company_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    bin2hex(random_bytes(16)), $code, $discount, $usage_limit,
                    date('Y-m-d H:i:s', strtotime($expire_date)), 
                    date('Y-m-d H:i:s'), $this->company_id
                ]);
                $_SESSION['flash_message'] = "Kupon başarıyla oluşturuldu.";
            } catch (PDOException $e) {
                 error_log("Firma kuponu ekleme hatası: " . $e->getMessage());
                 $_SESSION['flash_message'] = "Kupon eklenirken bir veritabanı hatası oluştu.";
                 $_SESSION['flash_type'] = "danger";
            }
        }
    }


    private function handleDeleteCoupon() {
        $coupon_id_to_delete = $_POST['coupon_id'];
        if (empty($coupon_id_to_delete)) return;

        try {
       
            $sql = "DELETE FROM Coupons WHERE id = :id AND company_id = :company_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $coupon_id_to_delete, ':company_id' => $this->company_id]);

             if ($stmt->rowCount() > 0) {
                 $_SESSION['flash_message'] = "Kupon başarıyla silindi.";
             } else {
                 $_SESSION['flash_message'] = "Silme işlemi başarısız oldu (Belki de kupon size ait değil?).";
                 $_SESSION['flash_type'] = "warning";
             }
        } catch (PDOException $e) {
             error_log("Firma kuponu silme hatası: " . $e->getMessage());
             if ($e->getCode() == 23000) {
                 $_SESSION['flash_message'] = "Hata: Bu kupon daha önce kullanıldığı için silinemez.";
             } else {
                 $_SESSION['flash_message'] = "Kupon silinirken bir veritabanı hatası oluştu.";
             }
             $_SESSION['flash_type'] = "danger";
         }
    }
    private function handleEditCoupon() {
        $coupon_id = $_POST['coupon_id'];
        $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
        $expire_date = trim($_POST['expire_date']);

        if ($discount === false || $usage_limit === false || empty($expire_date) || $discount <= 0 || $usage_limit < 0) {  
             $_SESSION['flash_message'] = "Hata: İndirim oranı, kullanım limiti ve tarih alanları doğru doldurulmalıdır.";
             $_SESSION['flash_type'] = "danger";
             return;
        }

        try {
            
            $sql = "UPDATE Coupons SET discount = ?, usage_limit = ?, expire_date = ? 
                    WHERE id = ? AND company_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$discount, $usage_limit, date('Y-m-d H:i:s', strtotime($expire_date)), $coupon_id, $this->company_id]);

            if ($stmt->rowCount() > 0) {
                 $_SESSION['flash_message'] = "Kupon başarıyla güncellendi.";
            } else {
                 $_SESSION['flash_message'] = "Güncelleme yapılamadı (Belki de kupon size ait değil?).";
                 $_SESSION['flash_type'] = "warning";
            }
        } catch (PDOException $e) {
             error_log("Firma kuponu güncelleme hatası: " . $e->getMessage());
             $_SESSION['flash_message'] = "Kupon güncellenirken bir veritabanı hatası oluştu.";
             $_SESSION['flash_type'] = "danger";
        }
    }
    
    public function showTicketsPage() {
        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

      
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_cancel_ticket'])) {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("Geçersiz işlem denemesi!");
            }
            $this->handleAdminTicketCancel($_POST['ticket_id']);
            session_write_close();
            header("Location: /company-tickets.php");
            exit();
        }

        
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    t.id AS ticket_id, t.status, t.total_price,
                    u.full_name AS user_name, u.email AS user_email,
                    tr.departure_city, tr.destination_city, tr.departure_time,
                    bs.seat_number
                FROM Tickets t
                JOIN User u ON t.user_id = u.id
                JOIN Trips tr ON t.trip_id = tr.id
                JOIN Booked_Seats bs ON bs.ticket_id = t.id
                WHERE tr.company_id = :company_id
                ORDER BY tr.departure_time DESC
            ");
            $stmt->execute([':company_id' => $this->company_id]);
            $company_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

          
            $current_time = time();
            foreach ($company_tickets as $key => $ticket) {
                $status_text = 'Bilinmiyor';
                $status_class = 'bg-secondary';
                $departure_timestamp = strtotime($ticket['departure_time']);
                $can_cancel = false; 

                if ($ticket['status'] === 'canceled') {
                    $status_text = 'İptal Edilmiş';
                    $status_class = 'bg-danger';
                } elseif ($departure_timestamp < $current_time) {
                    $status_text = 'Tarihi Geçti';
                    $status_class = 'bg-secondary';
                } else {
                    $status_text = 'Aktif';
                    $status_class = 'bg-success';

                    $can_cancel = true; 
                }
                
                $company_tickets[$key]['display_status_text'] = $status_text;
                $company_tickets[$key]['display_status_class'] = $status_class;
                $company_tickets[$key]['can_cancel'] = $can_cancel;
            }
            

        } catch (PDOException $e) {
            error_log("Firma biletleri çekilemedi: " . $e->getMessage());
            $company_tickets = [];
            $flash_message = "Biletler listelenirken bir hata oluştu.";
            $flash_type = "danger";
        }

        $data = [
            'pageTitle' => 'Bilet Yönetimi',
            'activePage' => 'company_tickets',
            'company_tickets' => $company_tickets,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

        $this->loadView('company/tickets', $data);
    }

 
    private function handleAdminTicketCancel($ticket_id) {
        if (empty($ticket_id)) {
             $_SESSION['flash_message'] = "Geçersiz Bilet ID.";
             $_SESSION['flash_type'] = 'danger';
             return;
        }

        try {
            $this->pdo->beginTransaction();


            $stmt = $this->pdo->prepare("
                SELECT t.id, t.total_price, t.user_id
                FROM Tickets t
                JOIN Trips tr ON t.trip_id = tr.id
                WHERE t.id = :ticket_id AND tr.company_id = :company_id AND t.status = 'active'
            ");
            $stmt->execute([':ticket_id' => $ticket_id, ':company_id' => $this->company_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                throw new Exception("Geçersiz, zaten iptal edilmiş veya firmanıza ait olmayan bir bilet.");
            }
            
            $user_id_to_refund = $ticket['user_id'];
            $refund_amount = $ticket['total_price'];


            $update_ticket_stmt = $this->pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
            $update_ticket_stmt->execute([$ticket_id]);


            $refund_stmt = $this->pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
            $refund_stmt->execute([$refund_amount, $user_id_to_refund]);



            $this->pdo->commit();
            $_SESSION['flash_message'] = "Bilet başarıyla iptal edildi ve ücreti kullanıcıya iade edildi.";
            $_SESSION['flash_type'] = 'success';

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $_SESSION['flash_message'] = "İptal sırasında bir hata oluştu: " . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    }


    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>
