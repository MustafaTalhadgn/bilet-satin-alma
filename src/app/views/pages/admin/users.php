<?php
/**
 * src/app/views/pages/admin/users.php
 * Kullanıcı yönetimi sayfasının HTML yapısı.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/style.css"> 

</head>
<body>
<?php
// Partial'ı doğru yerden çağır (app/views/partials/)
require_once __DIR__ . '/../../partials/header.php';
?>
<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php
            // Admin sidebar'ını doğru yerden çağır
            require_once __DIR__ . '/../../partials/sidebar_admin.php';
            ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Kullanıcı Yönetimi</h1>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus-fill"></i> Yeni Firma Yetkilisi Ekle</button>
            </div>

            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <ul class="nav nav-tabs" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="company-users-tab" data-bs-toggle="tab" data-bs-target="#company-users" type="button" role="tab">Firma Yetkilileri</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="regular-users-tab" data-bs-toggle="tab" data-bs-target="#regular-users" type="button" role="tab">Kullanıcılar</button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="userTabsContent">
                <div class="tab-pane fade show active" id="company-users" role="tabpanel">
                    <?php if (empty($company_users)): ?>
                        <div class="alert alert-info">Henüz firma yetkilisi eklenmemiş.</div>
                    <?php else: ?>
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th>Ad Soyad</th><th>E-posta</th><th>Bakiye</th><th>Atanan Firma</th><th>İşlemler</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($company_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($user['balance'], 2, ',', '.')); ?> TL</td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['company_name'] ?? 'Atanmamış'); ?></span></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="company"
                                                data-company-id="<?php echo $user['company_id']; ?>"
                                                data-balance="<?php echo htmlspecialchars($user['balance']); ?>"> Düzenle
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu yetkiliyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="regular-users" role="tabpanel">
                     <?php if (empty($regular_users)): ?>
                        <div class="alert alert-info">Henüz kayıtlı kullanıcı bulunmuyor.</div>
                    <?php else: ?>
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th>Ad Soyad</th><th>E-posta</th><th>Bakiye</th><th>İşlemler</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($regular_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($user['balance'], 2, ',', '.')); ?> TL</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="user" data-company-id=""
                                                data-balance="<?php echo htmlspecialchars($user['balance']); ?>"> Düzenle
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Firma Yetkilisi Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kullanıcıyı Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3"><label class="form-label">Ad Soyad</label><input type="text" name="full_name" id="edit_full_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                    
                    <div class="mb-3"><label class="form-label">Bakiye</label><input type="number" name="balance" id="edit_balance" step="0.01" min="0" class="form-control" required></div>

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
                            <option value="" selected>Firma Seçin...</option>
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

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/admin-users.js"></script>
</body>
</html>