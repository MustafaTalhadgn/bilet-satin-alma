<?php
// src/app/controllers/AdminController.php

// Gerekli dosyalar (config ve session giriş noktasında çağrılacak)

class AdminController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Admin paneli ana sayfasını gösterir.
     */
    public function showDashboard() {
        // --- GÜVENLİK GÖREVLİSİ (GUARD) ---
        // Bu kontrol aslında giriş noktasında yapıldığı için burada tekrar şart değil,
        // ama ekstra güvenlik katmanı olarak kalabilir.
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            
            header("Location: /login.php");
            exit();
        }

        // Gelecekte buraya toplam firma sayısı, kullanıcı sayısı gibi
        // istatistikler veritabanından çekilip $data dizisine eklenebilir.
        $data = [
            'pageTitle' => 'Admin Paneli Anasayfa'
            // 'totalCompanies' => $this->getTotalCompanies(),
            // 'totalUsers' => $this->getTotalUsers(),
        ];

        // İlgili view dosyasını yükle
        $this->loadView('/dashboard', $data); // View dosyasının adını dashboard yapalım
    }
    


    protected function loadView($viewName, $data = []) {
        extract($data);
        // View yolu: src/app/views/pages/admin/dashboard.php
        require __DIR__ . '/../../views/pages/admin/' . $viewName . '.php';
    }


}
?>
