<?php
// src/app/controllers/admin/AdminCouponController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class AdminCouponController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Kupon yönetimi sayfasını gösterir ve POST isteklerini işler.
     */
    public function showCouponsPage() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
        }

        // Flash mesajları ve CSRF token'ı session.php'den al
        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        // --- POST İŞLEMLERİ ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("Geçersiz CSRF token!");
            }

            if (isset($_POST['add_coupon'])) {
                $this->handleAddCoupon();
            } elseif (isset($_POST['edit_coupon'])) {
                $this->handleEditCoupon();
            } elseif (isset($_POST['delete_coupon'])) {
                $this->handleDeleteCoupon();
            }
            // İşlem sonrası sayfayı yeniden yönlendir (PRG Pattern)
            header("Location: /admin/coupons.php"); // Kök dizine göre
            exit();
        }

        // --- VERİLERİ ÇEKME (View için) ---
        try {
            // Tüm kuponları ve ait oldukları firma isimlerini çek
            $coupons = $this->pdo->query("
                SELECT c.*, bc.name as company_name
                FROM Coupons c
                LEFT JOIN Bus_Company bc ON c.company_id = bc.id
                ORDER BY c.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Kuponlar çekilemedi: " . $e->getMessage());
            $coupons = [];
            $flash_message = "Kuponlar listelenirken bir hata oluştu.";
            $flash_type = "danger";
        }

        // View'a gönderilecek veriler
        $data = [
            'coupons' => $coupons,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

        // İlgili view dosyasını yükle
        $this->loadView('admin/coupons', $data);
    }

    /**
     * Yeni global kupon ekleme isteğini işler.
     */
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
                // company_id'yi NULL olarak ekliyoruz (global kupon)
                $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at, company_id) VALUES (?, ?, ?, ?, ?, ?, NULL)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([bin2hex(random_bytes(16)), $code, $discount, $usage_limit, $expire_date, date('Y-m-d H:i:s')]);
                $_SESSION['flash_message'] = "Global kupon başarıyla oluşturuldu.";
            } catch (PDOException $e) {
                 error_log("Kupon ekleme hatası: " . $e->getMessage());
                 $_SESSION['flash_message'] = "Kupon eklenirken bir veritabanı hatası oluştu.";
                 $_SESSION['flash_type'] = "danger";
            }
        }
    }

    /**
     * Kupon düzenleme isteğini işler.
     */
    private function handleEditCoupon() {
        $coupon_id = $_POST['coupon_id'];
        $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
        $expire_date = trim($_POST['expire_date']);

        if ($discount === false || $usage_limit === false || empty($expire_date) || $discount <= 0 || $usage_limit <= 0) {
            $_SESSION['flash_message'] = "Hata: İndirim oranı ve kullanım limiti geçerli, 0'dan büyük bir sayı olmalı ve tarih boş bırakılmamalıdır.";
            $_SESSION['flash_type'] = "danger";
        } else {
             try {
                // Admin her kuponu güncelleyebilir.
                $sql = "UPDATE Coupons SET discount = ?, usage_limit = ?, expire_date = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$discount, $usage_limit, $expire_date, $coupon_id]);
                $_SESSION['flash_message'] = "Kupon başarıyla güncellendi.";
             } catch (PDOException $e) {
                 error_log("Kupon güncelleme hatası: " . $e->getMessage());
                 $_SESSION['flash_message'] = "Kupon güncellenirken bir veritabanı hatası oluştu.";
                 $_SESSION['flash_type'] = "danger";
             }
        }
    }

    /**
     * Kupon silme isteğini işler.
     */
    private function handleDeleteCoupon() {
        $coupon_id = $_POST['coupon_id'];
        try {
            // Admin her kuponu silebilir.
            $stmt = $this->pdo->prepare("DELETE FROM Coupons WHERE id = ?");
            $stmt->execute([$coupon_id]);
            $_SESSION['flash_message'] = "Kupon başarıyla silindi.";
        } catch (PDOException $e) {
             error_log("Kupon silme hatası: " . $e->getMessage());
             // İlişkisel veri hatası olabilir (User_Coupons'da kullanılmışsa)
             if ($e->getCode() == 23000) { // Integrity constraint violation
                 $_SESSION['flash_message'] = "Hata: Bu kupon daha önce kullanıldığı için silinemez.";
             } else {
                 $_SESSION['flash_message'] = "Kupon silinirken bir veritabanı hatası oluştu.";
             }
             $_SESSION['flash_type'] = "danger";
        }
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../../views/pages/' . $viewName . '.php';
    }
}
?>
