<?php



class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function showAccountPage() {

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login.php"); 
            exit();
        }

        if ($_SESSION['user_role'] === 'admin') {
            session_write_close();
            header("Location: /admin/index.php"); 
            exit();
        }
        

        $user_id = $_SESSION['user_id'];


        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("Geçersiz işlem denemesi!");
            }


            if (isset($_POST['update_profile'])) {
                $full_name = trim($_POST['full_name']);

                if (!empty($full_name)) {
                    $stmt = $this->pdo->prepare("UPDATE User SET full_name = :full_name WHERE id = :user_id");
                    $stmt->execute([':full_name' => $full_name, ':user_id' => $user_id]);

                    $_SESSION['user_fullname'] = $full_name; 
                    $_SESSION['flash_message'] = "Profil bilgileriniz başarıyla güncellendi.";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = "Ad Soyad alanı boş bırakılamaz.";
                    $_SESSION['flash_type'] = 'danger';
                }
                header("Location: /my-account.php"); 
                exit();
            }

            if (isset($_POST['change_password'])) {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                try {
                    $stmt = $this->pdo->prepare("SELECT password FROM User WHERE id = :user_id");
                    $stmt->execute([':user_id' => $user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user || !password_verify($current_password, $user['password'])) {
                        throw new Exception("Mevcut şifreniz yanlış.");
                    }
                    if (strlen($new_password) < 8) {
                        throw new Exception("Yeni şifre en az 8 karakter olmalıdır.");
                    }
                    if ($new_password !== $confirm_password) {
                        throw new Exception("Yeni şifreler eşleşmiyor.");
                    }

                    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                    $update_stmt = $this->pdo->prepare("UPDATE User SET password = :password WHERE id = :user_id");
                    $update_stmt->execute([':password' => $hashed_password, ':user_id' => $user_id]);

                    $_SESSION['flash_message'] = "Şifreniz başarıyla değiştirildi.";
                    $_SESSION['flash_type'] = 'success';

                } catch (Exception $e) {
                    $_SESSION['flash_message'] = "Hata: " . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                }
                header("Location: /my-account.php"); 
                exit();
            }
        }

        try {
            $stmt = $this->pdo->prepare("SELECT full_name, email, balance FROM User WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
             if (!$current_user) {

                 session_destroy();
                 header("Location: /login.php");
                 exit();
             }
        } catch (PDOException $e) {
             error_log("Kullanıcı bilgileri çekilemedi: " . $e->getMessage());

             die("Hesap bilgileri yüklenirken bir sorun oluştu.");
        }


        $data = [
            'current_user' => $current_user,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];


        $this->loadView('account', $data);
    }



    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>