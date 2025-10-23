<?php
// src/app/controllers/PaymentController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class PaymentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ödeme sayfasını gösterir ve işlemleri yönetir.
     */
    public function showPaymentPage() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
            session_write_close(); // Yönlendirmeden önce kapat
            header("Location: /login.php");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        global $csrf_token; // session.php'den
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);


        // --- ÖDEME BAĞLAMINI (CONTEXT) YÖNETME ---
        // Bu metod, trips.php'den POST geldiğinde context'i oluşturur ve GET'e yönlendirir.
        $this->ensurePaymentContext();

        // Context kontrolü (Eğer ensurePaymentContext yönlendirmediyse, context vardır)
         if (!isset($_SESSION['payment_context'])) {
             // Bu noktaya gelinmemeli ama gelirse diye ekstra kontrol
             error_log("Payment context missing after ensurePaymentContext for user: " . $user_id);
             session_write_close();
             header("Location: /index.php");
             exit();
         }

        // --- DEĞİŞKENLERİ HAZIRLA (Context'ten) ---
        $trip_id = $_SESSION['payment_context']['trip_id'];
        $selected_seat = $_SESSION['payment_context']['selected_seat'];

        // --- SEFER VE KULLANICI BİLGİLERİNİ ÇEKME ---
        try {
            $trip_stmt = $this->pdo->prepare("SELECT * FROM Trips WHERE id = :trip_id");
            $trip_stmt->execute([':trip_id' => $trip_id]);
            $trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);

            $user_stmt = $this->pdo->prepare("SELECT balance FROM User WHERE id = :user_id");
            $user_stmt->execute([':user_id' => $user_id]);
            $user_balance = $user_stmt->fetchColumn();

            if (!$trip) {
                unset($_SESSION['payment_context']); // Geçersiz context'i temizle
                $_SESSION['flash_message'] = "Ödeme yapılmak istenen sefer bulunamadı.";
                $_SESSION['flash_type'] = 'danger';
                session_write_close();
                header("Location: /index.php"); // Veya /my-tickets.php
                exit();
            }
        } catch (PDOException $e) {
             error_log("Ödeme sayfası veri çekme hatası: " . $e->getMessage());
             die("Ödeme bilgileri yüklenirken bir veritabanı hatası oluştu.");
        }


        // --- SAYFA İÇİ POST İŞLEMLERİNİ YÖNETME ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("⚠️ Geçersiz veya eksik CSRF token! İşlem durduruldu.");
            }
            if (isset($_POST['apply_coupon'])) {
                $this->handleApplyCoupon($user_id);
                session_write_close();
                header("Location: /pay.php"); // Sayfayı yeniden yükle
                exit();
            }
            if (isset($_POST['confirm_payment'])) {
                // handleConfirmPayment içindeki header çağrılarından önce session_write_close() var.
                $this->handleConfirmPayment($user_id, $trip, $selected_seat, $user_balance);
            }
        }

        // --- GÖRSEL İÇİN FİYAT BİLGİLERİNİ HAZIRLA ---
        $original_price = $trip['price'];
        $display_price = $original_price;
        $applied_coupon_code = '';
        if (isset($_SESSION['applied_coupon'])) {
            $discount = $_SESSION['applied_coupon']['discount'] ?? 0;
            if (is_numeric($discount) && $discount > 0) {
                 $display_price = max(0, $original_price - ($original_price * ($discount / 100))); // Fiyat negatif olmasın
            }
            $applied_coupon_code = $_SESSION['applied_coupon']['code'] ?? '';
        }

        // --- View'a Gönderilecek Veriler ---
        $data = [
            'trip' => $trip,
            'selected_seat' => $selected_seat,
            'user_balance' => $user_balance,
            'original_price' => $original_price,
            'display_price' => $display_price,
            'applied_coupon_code' => $applied_coupon_code,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

        // --- View Yükleme ---
        $this->loadView('pay', $data);
    } // showPaymentPage sonu


    /**
     * trips.php'den gelen ilk POST isteğini kontrol eder, context oluşturur ve GET'e yönlendirir.
     * Eğer GET isteği ise veya context zaten varsa bir şey yapmaz.
     */
    private function ensurePaymentContext() {
         $is_initial_payment_post = $_SERVER['REQUEST_METHOD'] === 'POST'
                                   && isset($_POST['trip_id'], $_POST['selected_seat'])
                                   && !isset($_POST['apply_coupon']) // Bunlar pay.php içinden gelen POST'lar
                                   && !isset($_POST['confirm_payment']);

        if ($is_initial_payment_post) {
            // CSRF KONTROLÜ (trips.php'den gelen istek için)
             if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                // Token yoksa veya eşleşmiyorsa işlemi durdur
                 $_SESSION['flash_error_trips'] = "Geçersiz istek! Lütfen tekrar deneyin.";
                 $last_search = $_SESSION['last_search'] ?? [];
                 $query_params = http_build_query($last_search);
                 session_write_close();
                 header("Location: /trips.php?" . $query_params);
                 exit();
            }

            $trip_id_from_post = $_POST['trip_id'];
            $selected_seat_from_post = $_POST['selected_seat'];
            $selected_seat_int = filter_var($selected_seat_from_post, FILTER_VALIDATE_INT);

            if ($selected_seat_int === false || $selected_seat_int <= 0) {
                 $_SESSION['flash_error_trips'] = "Lütfen ödemeye geçmeden önce geçerli bir koltuk seçin.";
                 $last_search = $_SESSION['last_search'] ?? [];
                 $query_params = http_build_query($last_search);
                 session_write_close();
                 header("Location: /trips.php?" . $query_params);
                 exit();
            }

            $_SESSION['payment_context'] = ['trip_id' => $trip_id_from_post, 'selected_seat' => $selected_seat_int];
            unset($_SESSION['applied_coupon']);

            session_write_close();
            header("Location: /pay.php"); // Redirect to GET
            exit();
        }
    }


    /**
     * Kupon uygulama isteğini işler.
     * @param string $user_id
     */
    private function handleApplyCoupon($user_id) {
         // ... (Bu fonksiyonun içeriği öncekiyle aynı, değişiklik yok) ...
         $coupon_code = trim($_POST['coupon_code'] ?? '');
        unset($_SESSION['applied_coupon']);

        if (!empty($coupon_code)) {
            $coupon_stmt = $this->pdo->prepare("SELECT * FROM Coupons WHERE code = :code AND usage_limit > 0 AND expire_date > datetime('now')");
            $coupon_stmt->execute([':code' => $coupon_code]);
            $coupon = $coupon_stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                $usage_check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE user_id = :user_id AND coupon_id = :coupon_id");
                $usage_check_stmt->execute([':user_id' => $user_id, ':coupon_id' => $coupon['id']]);
                if ($usage_check_stmt->fetchColumn() > 0) {
                    $_SESSION['flash_message'] = "Bu kupon kodunu daha önce kullandınız.";
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $_SESSION['applied_coupon'] = ['id' => $coupon['id'], 'code' => $coupon['code'], 'discount' => $coupon['discount']];
                    $_SESSION['flash_message'] = "Kupon başarıyla uygulandı!";
                    $_SESSION['flash_type'] = 'success';
                }
            } else {
                $_SESSION['flash_message'] = "Geçersiz veya süresi dolmuş kupon kodu.";
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_message'] = "Lütfen bir kupon kodu girin.";
            $_SESSION['flash_type'] = 'warning';
        }
    }

    /**
     * Ödeme onaylama isteğini işler ve veritabanı işlemlerini yapar.
     */
    private function handleConfirmPayment($user_id, $trip, $selected_seat, $user_balance) {
         // ... (Bu fonksiyonun içeriği öncekiyle aynı, değişiklik yok) ...
        $final_price = $trip['price'];
        $coupon_id = null;

        if (isset($_SESSION['applied_coupon'])) {
            $final_price = $trip['price'] - ($trip['price'] * ($_SESSION['applied_coupon']['discount'] / 100));
            $final_price = max(0, $final_price); // Fiyat negatif olmasın
            $coupon_id = $_SESSION['applied_coupon']['id'];
        }

        try {
            $this->pdo->beginTransaction();

            if ($user_balance < $final_price) {
                throw new Exception("Yetersiz bakiye. Lütfen bakiyenizi güncelleyin.");
            }

            // Bakiye Güncelle
            $this->pdo->prepare("UPDATE User SET balance = balance - ? WHERE id = ?")->execute([$final_price, $user_id]);

            // Bilet Oluştur
            $ticket_id = bin2hex(random_bytes(16));
            $this->pdo->prepare("INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) VALUES (?, ?, ?, 'active', ?, ?)")
                ->execute([$ticket_id, $trip['id'], $user_id, $final_price, date('Y-m-d H:i:s')]);

            // Koltuk Kaydet
            $this->pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, ?)")
                ->execute([bin2hex(random_bytes(16)), $ticket_id, $selected_seat, date('Y-m-d H:i:s')]);

            // Kupon Kullanıldıysa Güncelle
            if ($coupon_id) {
                $update_coupon_stmt = $this->pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ? AND usage_limit > 0");
                $update_coupon_stmt->execute([$coupon_id]);
                if ($update_coupon_stmt->rowCount() === 0) {
                    throw new Exception("Kupon son anda tükendi. İşlem iptal edildi.");
                }
                $this->pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (?, ?, ?)")
                    ->execute([bin2hex(random_bytes(16)), $coupon_id, $user_id]);
            }

            $this->pdo->commit();
            unset($_SESSION['payment_context'], $_SESSION['applied_coupon']);

            session_write_close();
            header("Location: /payment_success.php?ticket_id=" . $ticket_id);
            exit();

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $_SESSION['flash_message'] = "Ödeme sırasında bir hata oluştu: " . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            session_write_close();
            header("Location: /pay.php");
            exit();
        }
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
} // Class sonu
?>