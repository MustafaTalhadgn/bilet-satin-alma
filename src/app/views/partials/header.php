<?php
 $activePage = $activePage ?? '';
?>
<header class="header">

    <nav class="navbar navbar-expand-md navbar-color">
        <div class="container">
            <a class="navbar-brand header-image" href="/index.php"> 
                <i class="bi bi-bus-front"></i> 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
               
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                      
                        <a class="nav-link <?php echo ($activePage === 'index') ? 'active' : ''; ?>" aria-current="page" href="/index.php">Anasayfa</a>
                    </li>
                    
                   
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'user'): ?>
                    <li class="nav-item">
                      
                        <a class="nav-link <?php echo ($activePage === 'tickets') ? 'active' : ''; ?>" href="/tickets.php">Biletlerim</a>
                    </li>
                    <?php endif; ?>
                </ul>


                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_fullname'])): ?>
                     
                        <li class="nav-item dropdown">
                        
                            <a class="nav-link dropdown-toggle d-flex align-items-center <?php echo (in_array($activePage, ['my-account', 'company_admin', 'company_tickets', 'admin_panel'])) ? 'active' : ''; ?>" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-fill me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_fullname']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item <?php echo ($activePage === 'my-account') ? 'active' : ''; ?>" href="/account.php">Hesabım</a></li>
                                
                                <?php 
                                if ($_SESSION['user_role'] === 'user'): ?>
                                    <li><a class="dropdown-item <?php echo ($activePage === 'my-tickets') ? 'active' : ''; ?>" href="/tickets.php">Biletlerim</a></li>
                                
                                <?php elseif ($_SESSION['user_role'] === 'company'): ?>
                                    <li><a class="dropdown-item <?php echo ($activePage === 'company_admin') ? 'active' : ''; ?>" href="/companyAdmin.php">Sefer/Kupon Yönetimi</a></li>
                                   
                                    <li><a class="dropdown-item <?php echo ($activePage === 'company_tickets') ? 'active' : ''; ?>" href="/company-tickets.php">Bilet Yönetimi</a></li>
                                
                                <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                                 
                                    <li><a class="dropdown-item <?php echo (str_starts_with($activePage, 'admin_')) ? 'active' : ''; ?>" href="/admin/dashboard.php">Admin Paneli</a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/logout.php">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                      
                        <li class="nav-item">
                         
                            <a class="nav-link <?php echo ($activePage === 'login') ? 'active' : ''; ?>" href="/login.php">Giriş Yap</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
