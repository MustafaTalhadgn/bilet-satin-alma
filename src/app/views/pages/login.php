<?php

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap & Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php

require_once __DIR__ . '/../partials/header.php';
?>
<main class="main bg-light">
    <div class="form-container">
        <div class="top row text-center">
            <div class="login-top col active"><h3 class="fw-bold">Giriş Yap</h3></div>
            <div class="register-top col border-start border-3"><h3 class="fw-bold">Kayıt Ol</h3></div>
        </div>

        <div class="showlogin">
           
            <form class="p-4" method="POST" action="">
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($register_success)): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($register_success); ?></div>
                <?php endif; ?>

                <div class="login mt-3 mb-4"><h2 class="text-center fw-bold">Giriş Yap</h2></div>
                <div class="mb-4 text-center"><input type="email" name="login-email" class="form-control" placeholder="Emailinizi giriniz" required/></div>
                <div class="mb-4 position-relative">
                    <input id="passwordInput" type="password" name="login-password" class="form-control" placeholder="Şifrenizi giriniz" required/>
                    <i class="bi bi-eye-slash toggle-password" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i>
                </div>

                <div class="btn-submit d-flex justify-content-center p-4"><button type="submit" name="login" class="btn btn-primary px-4">Giriş Yap</button></div>
            </form>
        </div>

        <div class="showregister passive">
           
            <form class="px-3" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <?php if (!empty($register_error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($register_error); ?></div>
                <?php endif; ?>

                 <div class="login mt-3 mb-4"><h2 class="text-center fw-bold">Kayıt Ol</h2></div>
                 <div class="mb-4 text-center"><input type="text" name="register-fullname" class="form-control" placeholder="Adınız Soyadınız" required/></div>
                 <div class="mb-4 text-center"><input type="email" name="register-email" class="form-control" placeholder="Emailinizi giriniz" required/></div>
                 <div class="mb-4 position-relative"><input id="passwordInputRegister"type="password" name="register-password" class="form-control" placeholder="Şifreniz (min. 8 karakter)" required/><i class="bi bi-eye-slash toggle-password-register" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i></div>
                 <div class="mb-4 position-relative"><input type="password" name="register-password-confirm" class="form-control" placeholder="Şifrenizi tekrar girin" required/></div>
                 <div class="btn-submit d-flex justify-content-center p-4"><button type="submit" name="register" class="btn btn-primary px-4">Kayıt Ol</button></div>
            </form>
        </div>
    </div>
</main>



<script src="/assets/js/login.js"></script>
<script src="/assets/js/register.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>