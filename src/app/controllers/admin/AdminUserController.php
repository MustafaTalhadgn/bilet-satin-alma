<?php
// src/app/controllers/admin/AdminUserController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class AdminUserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcı yönetimi sayfasını gösterir ve POST isteklerini işler.
     */
    public function showUsersPage() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
        }

        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        
        // --- POST İŞLEMLERİ ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) { die("Geçersiz CSRF token!"); }

            if (isset($_POST['add_user'])) {
                $this->handleAddUser();
            } elseif (isset($_POST['edit_user'])) {
                $this->handleEditUser();
            } elseif (isset($_POST['delete_user'])) {
                $this->handleDeleteUser();
            }
            
            header("Location: /admin/users.php"); 
            exit();
        }

        // --- VERİLERİ ÇEKME (View için) ---
        try {
            // GÜNCELLEME: Firma Yetkilileri (company rolü) için 'u.balance' eklendi
            $company_users = $this->pdo->query("
                SELECT u.id, u.full_name, u.email, u.company_id, u.balance, bc.name as company_name 
                FROM User u LEFT JOIN Bus_Company bc ON u.company_id = bc.id
                WHERE u.role = 'company' ORDER BY u.full_name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // GÜNCELLEME: Normal Kullanıcılar (user rolü) için 'balance' zaten vardı, teyit edildi.
            $regular_users = $this->pdo->query(
                "SELECT id, full_name, email, balance 
                 FROM User WHERE role = 'user' ORDER BY full_name ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Firma listesi (Dropdown'lar için)
            $companies = $this->pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Kullanıcılar/Firmalar çekilemedi: " . $e->getMessage());
            $company_users = [];
            $regular_users = [];
            $companies = [];
            $flash_message = "Veriler listelenirken bir hata oluştu.";
            $flash_type = "danger";
        }

    
        // View'a gönderilecek veriler
        $data = [
            'company_users' => $company_users,
            'regular_users' => $regular_users,
            'companies' => $companies,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

        
        $this->loadView('admin/users', $data);
    }

    
    private function handleAddUser() {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $company_id = $_POST['company_id'];

        if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || empty($company_id)) {
            $_SESSION['flash_message'] = "Lütfen tüm alanları doğru bir şekilde doldurun. Şifre en az 8 karakter olmalıdır.";
            $_SESSION['flash_type'] = "danger";
        } else {
            try {
                $check_stmt = $this->pdo->prepare("SELECT id FROM User WHERE email = ?");
                $check_stmt->execute([$email]);
                if ($check_stmt->fetch()) {
                    $_SESSION['flash_message'] = "Bu e-posta adresi zaten kullanılıyor.";
                    $_SESSION['flash_type'] = "danger";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                    // Not: Yeni firma yetkilisi varsayılan bakiye ile başlar (DB'de 800 olarak ayarlı)
                    $stmt = $this->pdo->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at) VALUES (?, ?, ?, ?, 'company', ?, ?)");
                    $stmt->execute([bin2hex(random_bytes(16)), $full_name, $email, $hashed_password, $company_id, date('Y-m-d H:i:s')]);
                    $_SESSION['flash_message'] = "Firma yetkilisi başarıyla eklendi.";
                }
            } catch (PDOException $e) {
                error_log("Yetkili ekleme hatası: " . $e->getMessage());
                $_SESSION['flash_message'] = "Yetkili eklenirken bir veritabanı hatası oluştu.";
                $_SESSION['flash_type'] = "danger";
            }
        }
    }

    /**
     * GÜNCELLEME: Kullanıcı (user veya company) düzenleme isteğini işler (Bakiye eklendi)
     */
    private function handleEditUser() {
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $company_id = ($role === 'company' && isset($_POST['company_id'])) ? $_POST['company_id'] : null;
        $new_password = $_POST['new_password'];
        
        // GÜNCELLEME: Bakiye verisini al ve doğrula
        $balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT);

        // Doğrulama
        if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['user', 'company', 'admin'])) {
             $_SESSION['flash_message'] = "Lütfen Ad Soyad, E-posta ve Rol alanlarını doğru doldurun.";
             $_SESSION['flash_type'] = "danger";
             return;
        }
        if ($balance === false || $balance < 0) { // Bakiye false veya negatif olamaz
             $_SESSION['flash_message'] = "Lütfen geçerli bir bakiye (0 veya daha büyük) girin.";
             $_SESSION['flash_type'] = "danger";
             return;
        }
        if ($role === 'company' && empty($company_id)) {
            $_SESSION['flash_message'] = "Firma Yetkilisi rolü için bir firma seçmelisiniz.";
            $_SESSION['flash_type'] = "danger";
            return;
        }


        try {
            // E-posta benzersizlik kontrolü (kendisi hariç)
            $check_stmt = $this->pdo->prepare("SELECT id FROM User WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $user_id]);
            if ($check_stmt->fetch()) {
                $_SESSION['flash_message'] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
                $_SESSION['flash_type'] = "danger";
            } else {
                // GÜNCELLEME: Temel bilgileri güncelle (Bakiye eklendi)
                $sql = "UPDATE User SET full_name = ?, email = ?, company_id = ?, role = ?, balance = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$full_name, $email, $company_id, $role, $balance, $user_id]);
                $updated = true;

                // Yeni şifre varsa güncelle
                if (!empty($new_password)) {
                    if (strlen($new_password) >= 8) {
                        $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                        $pw_stmt = $this->pdo->prepare("UPDATE User SET password = ? WHERE id = ?");
                        $pw_stmt->execute([$hashed_password, $user_id]);
                    } else {
                         $_SESSION['flash_message'] = "Kullanıcı bilgileri güncellendi ancak yeni şifre en az 8 karakter olmalıydı (Şifre değiştirilmedi).";
                         $_SESSION['flash_type'] = "warning";
                         $updated = false;
                    }
                }

                if ($updated && !isset($_SESSION['flash_message'])) {
                    $_SESSION['flash_message'] = "Kullanıcı bilgileri başarıyla güncellendi.";
                }
            }
         } catch (PDOException $e) {
             error_log("Kullanıcı güncelleme hatası: " . $e->getMessage());
             $_SESSION['flash_message'] = "Kullanıcı güncellenirken bir veritabanı hatası oluştu.";
             $_SESSION['flash_type'] = "danger";
         }
    }

    /**
     * Kullanıcı silme isteğini işler.
     */
    private function handleDeleteUser() {
        $user_id = $_POST['user_id'];
        try {
            // Admin rolündeki kullanıcıların silinmesini engelle
            $stmt = $this->pdo->prepare("DELETE FROM User WHERE id = ? AND role != 'admin'");
            $deleted = $stmt->execute([$user_id]);

            if ($deleted && $stmt->rowCount() > 0) {
                $_SESSION['flash_message'] = "Kullanıcı başarıyla silindi.";
            } elseif ($deleted) {
                $_SESSION['flash_message'] = "Silme işlemi başarısız oldu (Belki de admin kullanıcısını silmeye çalıştınız?).";
                $_SESSION['flash_type'] = "warning";
            } else {
                throw new PDOException("Silme sorgusu çalıştırılamadı.");
            }
        } catch (PDOException $e) {
            error_log("Kullanıcı silme hatası: " . $e->getMessage());
             // İlişkisel veri hatası (örn: kullanıcının bileti varsa)
            if ($e->getCode() == 23000) {
                $_SESSION['flash_message'] = "Hata: Bu kullanıcıya ait biletler veya başka ilişkili veriler bulunduğu için silinemez.";
            } else {
                $_SESSION['flash_message'] = "Kullanıcı silinirken bir veritabanı hatası oluştu.";
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