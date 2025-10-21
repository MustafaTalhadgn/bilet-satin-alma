<div class="list-group">
    <a href="adminPanel.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_panel.php') ? 'active' : ''; ?>">
        <i class="bi bi-house-door-fill me-2"></i> Panel Anasayfa
    </a>
    <a href="manage_companies.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_companies.php') ? 'active' : ''; ?>">
        <i class="bi bi-bus-front-fill me-2"></i> Firma Yönetimi
    </a>
    <a href="manage_users.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'active' : ''; ?>">
        <i class="bi bi-people-fill me-2"></i> Yetkili Yönetimi
    </a>
    <a href="manage_coupons.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_coupons.php') ? 'active' : ''; ?>">
        <img style="height: 16px;" src="/assets/images/kupon.png" alt=""> Kupon Yönetimi
    </a>
</div>
