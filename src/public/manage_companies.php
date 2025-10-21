<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
}

// --- CSRF TOKEN ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Flash mesajları
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);


// --- GÜÇLENDİRİLMİŞ DOSYA YÜKLEME FONKSİYONU ---
function upload_logo($file) {
    $target_dir = "assets/images/logos/";

    // 1. Klasör var mı diye kontrol et, yoksa oluştur.
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // 2. Güvenli ve benzersiz bir dosya adı oluştur
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $safe_filename = uniqid('logo_', true) . '.' . $file_extension;
    $target_file = $target_dir . $safe_filename;

    // 3. MIME Tipi ve İçerik Kontrolü: Dosyanın gerçekten bir resim olup olmadığını doğrula
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return [false, "Yüklenen dosya bir resim değil."];
    }
    
    // 4. Beyaz Liste (Whitelist) Kontrolü
    $allowed_types = ['jpg', 'png', 'jpeg'];
    if (!in_array($file_extension, $allowed_types)) {
        return [false, "Sadece JPG, JPEG & PNG dosyalarına izin verilir."];
    }

    // 5. Dosya Boyutu Kontrolü
    if ($file["size"] > 1000000) { // 1MB limit
        return [false, "Dosya boyutu çok büyük (Maksimum 1MB)."];
    }

    // 6. Dosyayı taşı
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [true, $target_file]; // Başarılı, dosya yolunu döndür
    } else {
        return [false, "Dosya yüklenirken bir hata oluştu."];
    }
}


// --- POST İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) { die("Geçersiz CSRF token!"); }

    // YENİ FİRMA EKLEME
    if (isset($_POST['add_company'])) {
        $name = trim($_POST['name']);
        $logo_path = 'assets/images/logos/default-logo.png'; // Varsayılan
        $upload_error = false;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            list($success, $result) = upload_logo($_FILES['logo']);
            if ($success) {
                $logo_path = $result;
            } else {
                $_SESSION['flash_message'] = "Logo yükleme hatası: " . $result;
                $_SESSION['flash_type'] = "danger";
                $upload_error = true;
            }
        }

        if (!$upload_error && !empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([bin2hex(random_bytes(16)), $name, $logo_path, date('Y-m-d H:i:s')]);
            $_SESSION['flash_message'] = "Firma başarıyla eklendi.";
        }
        header("Location: manage_companies.php"); exit();
    }

    // FİRMA DÜZENLEME
    if (isset($_POST['edit_company'])) {
        // ... (Diğer düzenleme kodların aynı kalıyor, buraya da benzer hata yönetimi eklenebilir) ...
    }

    // FİRMA SİLME
    if (isset($_POST['delete_company'])) {
       // ... (Silme kodun aynı kalıyor) ...
    }
}

// --- VERİLERİ ÇEKME ---
$companies = $pdo->query("SELECT * FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- ... (HTML kodunun geri kalanı aynı kalıyor, dokunmaya gerek yok) ... -->
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
<?php require_once 'assets/partials/header.php'; ?>
<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php require_once 'assets/partials/admin_sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Firma Yönetimi</h1>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCompanyModal"><i class="bi bi-plus-circle"></i> Yeni Firma Ekle</button>
            </div>
            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash_message); ?></div>
            <?php endif; ?>
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Logo</th><th>Firma Adı</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="" style="height: 40px;"></td>
                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-company-btn"
                                    data-bs-toggle="modal" data-bs-target="#editCompanyModal"
                                    data-id="<?php echo $company['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($company['name']); ?>"
                                    data-logo="<?php echo htmlspecialchars($company['logo_path']); ?>">Düzenle</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <button type="submit" name="delete_company" class="btn btn-danger btn-sm">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<!-- Yeni Firma Ekleme Modalı -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Firma Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3"><label class="form-label">Firma Adı</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Firma Logosu (PNG, JPG)</label><input type="file" name="logo" class="form-control"></div>
                    <div class="modal-footer"><button type="submit" name="add_company" class="btn btn-success">Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Firma Düzenleme Modalı -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Firma Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="editCompanyForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="company_id" id="edit_company_id">
                    <div class="mb-3"><label class="form-label">Firma Adı</label><input type="text" name="name" id="edit_company_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Mevcut Logo</label><div><img src="" id="current_logo" style="height: 50px;"></div></div>
                    <div class="mb-3"><label class="form-label">Yeni Logo Yükle (İsteğe Bağlı)</label><input type="file" name="logo" class="form-control"></div>
                    <div class="modal-footer"><button type="submit" name="edit_company" class="btn btn-primary">Değişiklikleri Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin-company.js"></script>
</body>
</html>