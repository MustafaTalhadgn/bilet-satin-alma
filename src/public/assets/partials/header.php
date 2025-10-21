<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
    <nav class="navbar navbar-expand-md bg-info-subtle">
        <div class="container">
            <a class="navbar-brand header-image" href="/">
                <i class="bi bi-bus-front"></i> 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Anasayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/my-tickets.php">Biletlerim</a>
                    </li>
                    </ul>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-fill me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_fullname']); ?>
                            </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="/my-account.php">Hesabım</a></li>
                                    
                                    <?php if ($_SESSION['user_role'] === 'user'): ?>
                                        <li><a class="dropdown-item" href="/my-tickets.php">Biletlerim</a></li>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_role'] === 'company'): ?>
                                        <li><a class="dropdown-item" href="/companyAdmin.php">Firma Panelim</a></li>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <li><a class="dropdown-item" href="/adminPanel.php">Admin Paneli</a></li>
                                    <?php endif; ?>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="assets/partials/logout.php">Çıkış Yap</a></li>
                                </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Giriş Yap</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>