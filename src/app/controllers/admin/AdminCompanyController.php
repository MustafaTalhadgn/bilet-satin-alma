<?php

class AdminCompanyController {
    private $pdo;

   
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

 
    public function showCompaniesPage() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
        }


        global $csrf_token;
        $flash_message = $_SESSION['flash_message'] ?? null;
        $flash_type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

       
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
            
           
            session_write_close(); 
            header("Location: /admin/companies.php");
            exit();
        }

    
        try {
            $companies = $this->pdo->query("SELECT * FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Firmalar çekilemedi: " . $e->getMessage());
            $companies = [];
            $flash_message = "Firmalar listelenirken bir hata oluştu.";
            $flash_type = "danger";
        }



        $data = [
            'companies' => $companies,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
            'csrf_token' => $csrf_token
        ];

     
        $this->loadView('admin/companies', $data);
    }

    private function handleAddCompany() {
        $name = trim($_POST['name']);
        
       
        $logo_path = 'assets/images/logos/default-logo.png';
        $upload_error = false;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            list($success, $result) = $this->uploadLogo($_FILES['logo']);
            if ($success) {
                $logo_path = $result; 
            } else {
                $_SESSION['flash_message'] = "Logo yükleme hatası: " . $result;
                $_SESSION['flash_type'] = "danger";
                $upload_error = true;
            }
        }

        if (!$upload_error && !empty($name)) {
            try{
                 
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
        } elseif (!$upload_error && empty($name)) {
             $_SESSION['flash_message'] = "Firma adı boş bırakılamaz.";
             $_SESSION['flash_type'] = "danger";
        }
    }

    
    private function handleEditCompany() {
        $id = $_POST['company_id'];
        $name = trim($_POST['name']);

         if (empty($name)) {
            $_SESSION['flash_message'] = "Firma adı boş bırakılamaz.";
            $_SESSION['flash_type'] = "danger";
            return;
         }

         try {
          
            $check_stmt = $this->pdo->prepare("SELECT id FROM Bus_Company WHERE name = ? AND id != ?");
            $check_stmt->execute([$name, $id]);
            if ($check_stmt->fetch()) {
                $_SESSION['flash_message'] = "Bu firma adı başka bir firma tarafından kullanılıyor.";
                $_SESSION['flash_type'] = "danger";
                return;
            }

          
            $stmt = $this->pdo->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $updated = true;

         
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

    
    private function handleDeleteCompany() {
        $company_id_to_delete = $_POST['company_id'];
        if (empty($company_id_to_delete)) return;

        try {
         
            $this->pdo->beginTransaction();

          
            $tripIdsStmt = $this->pdo->prepare("SELECT id FROM Trips WHERE company_id = ?");
            $tripIdsStmt->execute([$company_id_to_delete]);
            $tripIds = $tripIdsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($tripIds)) {
                
                $placeholders = implode(',', array_fill(0, count($tripIds), '?'));
                $ticketIdsStmt = $this->pdo->prepare("SELECT id FROM Tickets WHERE trip_id IN ($placeholders)");
                $ticketIdsStmt->execute($tripIds);
                $ticketIds = $ticketIdsStmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($ticketIds)) {
                    
                    $placeholders_tickets = implode(',', array_fill(0, count($ticketIds), '?'));
                    $this->pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN ($placeholders_tickets)")->execute($ticketIds);
                    
                
                    $this->pdo->prepare("DELETE FROM Tickets WHERE id IN ($placeholders_tickets)")->execute($ticketIds);
                }
                
            
                $this->pdo->prepare("DELETE FROM Trips WHERE company_id = ?")->execute([$company_id_to_delete]);
            }
            
            
            $couponIdsStmt = $this->pdo->prepare("SELECT id FROM Coupons WHERE company_id = ?");
            $couponIdsStmt->execute([$company_id_to_delete]);
            $couponIds = $couponIdsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($couponIds)) {
             
                $placeholders_coupons = implode(',', array_fill(0, count($couponIds), '?'));
                $this->pdo->prepare("DELETE FROM User_Coupons WHERE coupon_id IN ($placeholders_coupons)")->execute($couponIds);

                
                $this->pdo->prepare("DELETE FROM Coupons WHERE company_id = ?")->execute([$company_id_to_delete]);
            }

            
            $updateUserStmt = $this->pdo->prepare("UPDATE User SET company_id = NULL WHERE company_id = ?");
            $updateUserStmt->execute([$company_id_to_delete]);

            
            $deleteCompanyStmt = $this->pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
            $deleteCompanyStmt->execute([$company_id_to_delete]);
            
         
            $this->pdo->commit();
            $_SESSION['flash_message'] = "Firma ve ilişkili tüm verileri (seferler, biletler, kuponlar) başarıyla silindi.";

        } catch (Exception $e) {
        
            $this->pdo->rollBack();
            error_log("Firma silinirken zincirleme hata: " . $e->getMessage());
            $_SESSION['flash_message'] = "Firma silinirken bir hata oluştu: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }


  
    private function uploadLogo($file) {
      
        $base_path = $_SERVER['DOCUMENT_ROOT']; 
        
        
        $target_dir_relative = 'assets/images/logos/'; 
    
        $target_dir_absolute = $base_path . '/' . $target_dir_relative; 

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
            return [true, $target_file_relative]; // Başarılı, BAŞINDA / OLMADAN yolu döndür
        } else {
            error_log("move_uploaded_file hatası: " . print_r(error_get_last(), true));
            return [false, "Dosya yüklenirken sunucu hatası oluştu."];
        }
    }

  
    protected function loadView($viewName, $data = []) {
        extract($data);
        require __DIR__ . '/../../views/pages/' . $viewName . '.php';
    }
}
?>