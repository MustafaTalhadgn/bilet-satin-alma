<?php
// src/app/controllers/admin/AdminCompanyController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class AdminCompanyController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Firma yönetimi sayfasını gösterir ve POST isteklerini işler.
     */
    public function showCompaniesPage() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        // Bu kontrol zaten giriş noktasında (public/admin/companies.php) yapılıyor,
        // bu yüzden burada tekrar yapılması zorunlu değil ama ekstra bir katman olarak kalabilir.
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

            if (isset($_POST['add_company'])) {
                $this->handleAddCompany();
            } elseif (isset($_POST['edit_company'])) {
                $this->handleEditCompany();
            } elseif (isset($_POST['delete_company'])) {
                $this->handleDeleteCompany();
            }
            // İşlem sonrası sayfayı yeniden yönlendir (PRG Pattern)
            // Yolu /admin/companies.php olarak düzelt
            header("Location: /admin/companies.php");
            exit();
        }

        // --- VERİLERİ ÇEKME (View için) ---
        try {
            $companies = $this->pdo->query("SELECT * FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Firmalar çekilemedi: " . $e->getMessage());
            $companies = [];
            $flash_message = "Firmalar listelenirken bir hata oluştu.";
            $flash_type = "danger";
        }


        // View'a gönderilecek veriler
        $data = [
            'companies' => $companies,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

        // İlgili view dosyasını yükle
        $this->loadView('admin/companies', $data);
    }

    /**
     * Yeni firma ekleme isteğini işler.
     */
    private function handleAddCompany() {
        $name = trim($_POST['name']);
        // Logo yolunu web kökünden başlatıyoruz
        $logo_path = '/assets/images/logos/default-logo.png';
        $upload_error = false;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            list($success, $result) = $this->uploadLogo($_FILES['logo']);
            if ($success) {
                $logo_path = $result; // uploadLogo zaten kök dizine göre yolu verecek
            } else {
                $_SESSION['flash_message'] = "Logo yükleme hatası: " . $result;
                $_SESSION['flash_type'] = "danger";
                $upload_error = true;
            }
        }

        if (!$upload_error && !empty($name)) {
            try{
                // Firma adının benzersiz olup olmadığını kontrol et
                 $check_stmt = $this->pdo->prepare("SELECT id FROM Bus_Company WHERE name = ?");
                 $check_stmt->execute([$name]);
                 if ($check_stmt->fetch()) {
                     $_SESSION['flash_message'] = "Bu firma adı zaten kullanılıyor.";
                     $_SESSION['flash_type'] = "danger";
                 } else {
                    $stmt = $this->pdo->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([bin2hex(random_bytes(16)), $name, $logo_path, date('Y-m-d H:i:s')]);
                    $_SESSION['flash_message'] = "Firma başarıyla eklendi.";
                 }
            } catch (PDOException $e) {
                 error_log("Firma ekleme hatası: " . $e->getMessage());
                 $_SESSION['flash_message'] = "Firma eklenirken bir veritabanı hatası oluştu.";
                 $_SESSION['flash_type'] = "danger";
            }
        } elseif (!$upload_error && empty($name)) { // Hata mesajı düzeltmesi
             $_SESSION['flash_message'] = "Firma adı boş bırakılamaz.";
             $_SESSION['flash_type'] = "danger";
        }
    }

    /**
     * Firma düzenleme isteğini işler.
     */
    private function handleEditCompany() {
        $id = $_POST['company_id'];
        $name = trim($_POST['name']);

         if (empty($name)) {
            $_SESSION['flash_message'] = "Firma adı boş bırakılamaz.";
            $_SESSION['flash_type'] = "danger";
            return; // Fonksiyondan çık
         }

         try {
            // Başka bir firmanın bu adı kullanıp kullanmadığını kontrol et
            $check_stmt = $this->pdo->prepare("SELECT id FROM Bus_Company WHERE name = ? AND id != ?");
            $check_stmt->execute([$name, $id]);
            if ($check_stmt->fetch()) {
                $_SESSION['flash_message'] = "Bu firma adı başka bir firma tarafından kullanılıyor.";
                $_SESSION['flash_type'] = "danger";
                return;
            }

            // Adı güncelle
            $stmt = $this->pdo->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $updated = true;

            // Yeni logo yüklendiyse onu da güncelle
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                list($success, $new_logo_path) = $this->uploadLogo($_FILES['logo']);
                if ($success) {
                    $stmt = $this->pdo->prepare("UPDATE Bus_Company SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$new_logo_path, $id]);
                } else {
                    $_SESSION['flash_message'] = "Firma adı güncellendi ancak logo yüklenemedi: " . $new_logo_path;
                    $_SESSION['flash_type'] = "warning";
                    $updated = false;
                }
            }

            if ($updated && !isset($_SESSION['flash_message'])) {
                 $_SESSION['flash_message'] = "Firma başarıyla güncellendi.";
            }

         } catch (PDOException $e) {
             error_log("Firma güncelleme hatası: " . $e->getMessage());
             $_SESSION['flash_message'] = "Firma güncellenirken bir veritabanı hatası oluştu.";
             $_SESSION['flash_type'] = "danger";
         }
    }

    /**
     * Firma silme isteğini işler.
     */
    private function handleDeleteCompany() {
        $id = $_POST['company_id'];
        try {
            // İlişkili sefer var mı kontrol et
            $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
            $check_stmt->execute([$id]);
            if ($check_stmt->fetchColumn() > 0) {
                $_SESSION['flash_message'] = "Bu firmaya ait seferler bulunduğu için firma silinemez!";
                $_SESSION['flash_type'] = "danger";
            } else {
                // İlişkili firma admini var mı kontrol et
                $check_user_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM User WHERE company_id = ?");
                $check_user_stmt->execute([$id]);
                if ($check_user_stmt->fetchColumn() > 0) {
                     $_SESSION['flash_message'] = "Bu firmaya atanmış yetkililer bulunduğu için firma silinemez! Önce yetkilileri silin veya başka firmaya atayın.";
                     $_SESSION['flash_type'] = "danger";
                } else {
                    $stmt = $this->pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash_message'] = "Firma başarıyla silindi.";
                }
            }
        } catch (PDOException $e) {
             error_log("Firma silme hatası: " . $e->getMessage());
             $_SESSION['flash_message'] = "Firma silinirken bir veritabanı hatası oluştu.";
             $_SESSION['flash_type'] = "danger";
        }
    }


    /**
     * Logo dosyasını yükler ve güvenli kontroller yapar.
     * @param array $file $_FILES dizisinden gelen dosya bilgisi
     * @return array [bool $success, string $result_path_or_error_message]
     */
    private function uploadLogo($file) {
        // DOCUMENT_ROOT, /var/www/html/public olarak ayarlandı
        $base_path = $_SERVER['DOCUMENT_ROOT'];
        $target_dir_relative = '/assets/images/logos/'; // Veritabanına kaydedilecek yol
        $target_dir_absolute = $base_path . $target_dir_relative; // Dosyanın fiziksel olarak yazılacağı yol

        if (!is_dir($target_dir_absolute)) {
            if (!mkdir($target_dir_absolute, 0775, true)) {
                 return [false, "Logo klasörü oluşturulamadı. İzinleri kontrol edin."];
            }
        }

        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $safe_filename = uniqid('logo_', true) . '.' . $file_extension;
        $target_file_absolute = $target_dir_absolute . $safe_filename;
        $target_file_relative = $target_dir_relative . $safe_filename;

        $check = getimagesize($file["tmp_name"]);
        if ($check === false) return [false, "Yüklenen dosya bir resim değil."];

        $allowed_types = ['jpg', 'png', 'jpeg'];
        if (!in_array($file_extension, $allowed_types)) return [false, "Sadece JPG, JPEG & PNG izin verilir."];

        if ($file["size"] > 1000000) return [false, "Dosya boyutu çok büyük (Maks 1MB)."];

        if (move_uploaded_file($file["tmp_name"], $target_file_absolute)) {
            return [true, $target_file_relative]; // Başarılı, web köküne göre göreceli yolu döndür
        } else {
            error_log("move_uploaded_file hatası: " . print_r(error_get_last(), true));
            return [false, "Dosya yüklenirken sunucu hatası oluştu."];
        }
    }

    /**
     * Belirtilen view dosyasını yükler ve verileri ona aktarır.
     */
    protected function loadView($viewName, $data = []) {
        extract($data);
        // Admin view'ları için yolu düzelt
        require __DIR__ . '/../../views/pages/' . $viewName . '.php';
    }
}
?>
