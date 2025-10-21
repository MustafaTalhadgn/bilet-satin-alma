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


// --- POST İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) { die("Geçersiz CSRF token!"); }

    // YENİ YETKİLİ EKLEME
    if (isset($_POST['add_user'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $company_id = $_POST['company_id'];

        if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || empty($company_id)) {
            $_SESSION['flash_message'] = "Lütfen tüm alanları doğru bir şekilde doldurun. Şifre en az 8 karakter olmalıdır.";
            $_SESSION['flash_type'] = "danger";
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
            $check_stmt->execute([$email]);
            if ($check_stmt->fetch()) {
                $_SESSION['flash_message'] = "Bu e-posta adresi zaten kullanılıyor.";
                $_SESSION['flash_type'] = "danger";
            } else {
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([bin2hex(random_bytes(16)), $full_name, $email, $hashed_password, 'company', $company_id, date('Y-m-d H:i:s')]);
                $_SESSION['flash_message'] = "Firma yetkilisi başarıyla eklendi.";
            }
        }
        header("Location: manage_users.php"); exit();
    }

    // KULLANICI DÜZENLEME (HEM USER HEM COMPANY)
    if (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $company_id = ($role === 'company') ? $_POST['company_id'] : null;
        $new_password = $_POST['new_password'];

        $check_stmt = $pdo->prepare("SELECT id FROM User WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->fetch()) {
            $_SESSION['flash_message'] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
            $_SESSION['flash_type'] = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE User SET full_name = ?, email = ?, company_id = ?, role = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $company_id, $role, $user_id]);

            if (!empty($new_password)) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                    $pw_stmt = $pdo->prepare("UPDATE User SET password = ? WHERE id = ?");
                    $pw_stmt->execute([$hashed_password, $user_id]);
                }
            }
            $_SESSION['flash_message'] = "Kullanıcı bilgileri başarıyla güncellendi.";
        }
        header("Location: manage_users.php"); exit();
    }

    // KULLANICI SİLME
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        // Admin rolündeki kullanıcıların silinmesini engelle (ek güvenlik)
        $stmt = $pdo->prepare("DELETE FROM User WHERE id = ? AND role != 'admin'");
        $stmt->execute([$user_id]);
        $_SESSION['flash_message'] = "Kullanıcı başarıyla silindi.";
        header("Location: manage_users.php"); exit();
    }
}

// --- VERİLERİ ÇEKME ---
$company_users = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.company_id, bc.name as company_name 
    FROM User u LEFT JOIN Bus_Company bc ON u.company_id = bc.id
    WHERE u.role = 'company' ORDER BY u.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$regular_users = $pdo->query(query: "SELECT id, full_name, email FROM User WHERE role = 'user' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi</title>
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
                <h1>Kullanıcı Yönetimi</h1>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus-fill"></i> Yeni Yetkili Ekle</button>
            </div>
            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- SEKMELİ YAPI -->
            <ul class="nav nav-tabs" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="company-users-tab" data-bs-toggle="tab" data-bs-target="#company-users" type="button" role="tab">Firma Yetkilileri</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="regular-users-tab" data-bs-toggle="tab" data-bs-target="#regular-users" type="button" role="tab">Kullanıcılar</button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="userTabsContent">
                <!-- FİRMA YETKİLİLERİ SEKMESİ -->
                <div class="tab-pane fade show active" id="company-users" role="tabpanel">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>Ad Soyad</th><th>E-posta</th><th>Atanan Firma</th><th>İşlemler</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($company_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['company_name'] ?? 'Atanmamış'); ?></span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="company"
                                            data-company-id="<?php echo $user['company_id']; ?>">Düzenle</button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bu yetkiliyi silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KULLANICILAR SEKMESİ -->
                <div class="tab-pane fade" id="regular-users" role="tabpanel">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>Ad Soyad</th><th>E-posta</th><th>İşlemler</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($regular_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(string: $user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="user" data-company-id="">Düzenle</button>
                                    
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Yeni Yetkili Ekleme Modalı (company rolü için) -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Firma Yetkilisi Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3"><label class="form-label">Ad Soyad</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Şifre (En az 8 karakter)</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Atanacak Firma</label>
                        <select name="company_id" class="form-select" required>
                            <option value="" disabled selected>Firma Seçin...</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer"><button type="submit" name="add_user" class="btn btn-success">Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- KULLANICI DÜZENLEME MODALI (Genel) -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kullanıcıyı Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3"><label class="form-label">Ad Soyad</label><input type="text" name="full_name" id="edit_full_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Kullanıcı Rolü</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="user">Kullanıcı</option>
                            <option value="company">Firma Yetkilisi</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="company_assignment_wrapper" style="display: none;">
                        <label class="form-label">Atanacak Firma</label>
                        <select name="company_id" id="edit_company_id" class="form-select">
                            <option value="" disabled selected>Firma Seçin...</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label><input type="password" name="new_password" class="form-control"></div>
                    <div class="modal-footer"><button type="submit" name="edit_user" class="btn btn-primary">Değişiklikleri Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/admin-users.js"></script>
</body>
</html>

