<?php

class AuthController {
    private $pdo; 
   
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    
    public function showLoginPage() {
        
        if (isset($_SESSION['user_id'])) {
            $redirect_url = ($_SESSION['user_role'] === 'admin') ? '/admin/dashboard.php' : '/index.php';
            header('Location: ' . $redirect_url);
            exit();
        }

        $login_error = '';
        $register_error = '';
        $register_success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("⚠️ Geçersiz veya eksik CSRF token! İşlem durduruldu.");
            }

            if (isset($_POST['login'])) {
                $email = trim($_POST['login-email']);
                $password = $_POST['login-password'];

                if (empty($email) || empty($password)) {
                    $login_error = "Lütfen tüm alanları doldurun.";
                } else {
                    $stmt = $this->pdo->prepare("SELECT * FROM User WHERE email = :email");
                    $stmt->execute([':email' => $email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_fullname'] = $user['full_name'];

                       
                        $redirect_url = ($user['role'] === 'admin') ? '/admin/dashboard.php' : '/index.php';
                        header("Location: " . $redirect_url);
                        exit();
                    } else {
                        $login_error = "Geçersiz e-posta veya şifre.";
                    }
                }
            }

            
            if (isset($_POST['register'])) {
                $fullname = trim($_POST['register-fullname']);
                $email = trim($_POST['register-email']);
                $password = $_POST['register-password'];
                $password_confirm = $_POST['register-password-confirm'];

              
                if (empty($fullname) || empty($email) || empty($password) || empty($password_confirm)) {
                    $register_error = "Lütfen tüm alanları doldurun.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $register_error = "Geçersiz e-posta formatı.";
                } elseif ($password !== $password_confirm) {
                    $register_error = "Şifreler eşleşmiyor.";
                } elseif (strlen($password) < 8) {
                    $register_error = "Şifre en az 8 karakter olmalıdır.";
                } else {
                   
                    $stmt = $this->pdo->prepare("SELECT id FROM User WHERE email = :email");
                    $stmt->execute([':email' => $email]);
                    if ($stmt->fetch()) {
                        $register_error = "Bu e-posta zaten kullanılıyor.";
                    } else {
                       
                        $safe_fullname = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
                        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                        $user_id = bin2hex(random_bytes(16));
                        $role = 'user';
                        $balance = 800;
                        $created_at = date('Y-m-d H:i:s');

                        $insert_stmt = $this->pdo->prepare("
                            INSERT INTO User (id, full_name, email, password, role, balance, created_at)
                            VALUES (:id, :full_name, :email, :password, :role, :balance, :created_at)
                        ");
                        $success = $insert_stmt->execute([
                            ':id' => $user_id,
                            ':full_name' => $safe_fullname,
                            ':email' => $email,
                            ':password' => $hashed_password,
                            ':role' => $role,
                            ':balance' => $balance,
                            ':created_at' => $created_at
                        ]);

                        if ($success) {
                            $register_success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
                        } else {
                            $register_error = "Kayıt sırasında bir veritabanı hatası oluştu.";
                        }
                    }
                }
            }
        }

        
        global $csrf_token; 
        $data = [
            'login_error' => $login_error,
            'register_error' => $register_error,
            'register_success' => $register_success,
            'csrf_token' => $csrf_token
        ];

        
        $this->loadView('login', $data);
    }

 
    protected function loadView($viewName, $data = []) {
        extract($data);

        require __DIR__ . '/../views/pages/' . $viewName . '.php';
    }
}
?>