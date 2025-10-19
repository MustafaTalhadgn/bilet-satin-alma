<?php
ini_set('session.cookie_httponly', 1);
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
require_once __DIR__ . '/../app/config.php'; 


// Burası kalkacak
$login_error = '';
$register_error = '';
$register_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  
    if (isset($_POST['login'])) {
        $email = trim($_POST['login-email']);
        $password = $_POST['login-password'];

        
        if (empty($email) || empty($password)) {
            $login_error = "Lütfen tüm alanları doldurun.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            
            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);


                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_fullname'] = $user['full_name'];

                
                header("Location: index.php"); 
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
            // E-posta'nın zaten kayıtlı olup olmadığını kontrol et
            $stmt = $pdo->prepare("SELECT id FROM User WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetch()) {
                $register_error = "Bu e-posta adresi zaten kullanılıyor.";
            } else {

                $safe_fullname = htmlspecialchars($fullname);

                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                
      
                $user_id = bin2hex(random_bytes(16)); 

              
                $sql = "INSERT INTO User (id, full_name, email, password, role, balance, created_at) 
                        VALUES (:id, :full_name, :email, :password, :role, :balance, :created_at)";
                $stmt = $pdo->prepare($sql);

                $role = 'user'; 
                $balance = 800; 
                $created_at = date('Y-m-d H:i:s');

                $stmt->execute([
                    ':id' => $user_id,
                    ':full_name' => $safe_fullname,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role,
                    ':balance' => $balance,
                    ':created_at' => $created_at
                ]);
                
                $register_success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="./assets/css/login.css">
</head>
<body>

<?php
require_once './assets/partials/header.php';
?>
<main class="main bg-light">
    <div class="form-container">
      <div class="top row text-center">
        <div class="login-top col active">
          <h3 class="fw-bold">Giriş Yap</h3>
        </div>
        <div class="register-top col border-start border-3">
          <h3 class="fw-bold">Kayıt Ol</h3>
        </div>
      </div>

      <div class="showlogin">
        <form class="p-4 " method="POST" action="login.php">
          
          <?php if (!empty($login_error)): ?>
              <div class="alert alert-danger" role="alert"><?php echo $login_error; ?></div>
          <?php endif; ?>
          <?php if (!empty($register_success)): ?>
              <div class="alert alert-success" role="alert"><?php echo $register_success; ?></div>
          <?php endif; ?>

          <div class="login mt-3 mb-4">
            <h2 class="text-center fw-bold">Giriş Yap</h2>
          </div>
          <div class="mb-4 text-center">
            <input type="email" name="login-email" class="form-control" id="exampleInputEmail1" placeholder="Emailinizi giriniz" required/>
          </div>
          <div class="mb-4 position-relative">
            <input type="password" name="login-password" class="form-control" id="passwordInput" placeholder="Şifrenizi giriniz" required/>
            <i class="bi bi-eye-slash toggle-password" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i>
          </div>
          <div class="mb-3 form-check text-center p-4">
            <a href="#">Şifremi unuttum</a>
          </div>
          <div class="btn-submit d-flex justify-content-center p-4">
            <button type="submit" name="login" class="btn btn-primary px-4">Giriş Yap</button>
          </div>
        </form>
      </div>

      <div class="showregister passive">
        <form class="px-3 " method="POST" action="login.php">

          <?php if (!empty($register_error)): ?>
              <div class="alert alert-danger" role="alert"><?php echo $register_error; ?></div>
          <?php endif; ?>

          <div class="login mt-3 mb-4">
            <h2 class="text-center fw-bold">Kayıt Ol</h2>
          </div>
          <div class="mb-4 text-center">
            <input type="text" name="register-fullname" class="form-control" placeholder="Adınız Soyadınız" required/>
          </div>
          <div class="mb-4 text-center">
            <input type="email" name="register-email" class="form-control" id="register-email" placeholder="Emailinizi giriniz" required/>
          </div>
          <div class="mb-4 position-relative">
            <input type="password" name="register-password" class="form-control" id="passwordInputRegister" placeholder="Şifreniz (min. 8 karakter)" required/>
            <i class="bi bi-eye-slash toggle-password-register" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i>
          </div>
          <div class="mb-4 position-relative">
            <input type="password" name="register-password-confirm" class="form-control" id="passwordInputRegisterConfirm" placeholder="Şifrenizi tekrar girin" required/>
          </div>
          <div class="btn-submit d-flex justify-content-center p-4">
            <button type="submit" name="register" class="btn btn-primary px-4">Kayıt Ol</button>
          </div>
        </form>
      </div>
    </div>
</main>
  
    <footer class="bg-info-subtle fixed-bottom">
        <div class="container ">
        <div class="contact col">
            <ul class="footer-contact ">
            <p><strong>E-posta:</strong> destek@ticketbuy.com</p>
        </ul>
        <div class="footer-message text-center col ">    
        <p>© 2025 TicketBuy. Tüm hakları saklıdır.</p>
        </div>
        </div>
    </footer>
    
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/register.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>



