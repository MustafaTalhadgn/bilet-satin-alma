<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/admin.css"> 
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/footer.css">

<body>
<?php

require_once __DIR__ . '/../../partials/header.php';
?>
<main class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <?php
         
            require_once __DIR__ . '/../../partials/sidebar_admin.php';
            ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Firma Yönetimi</h1>
                <button class="btn btn-first" data-bs-toggle="modal" data-bs-target="#addCompanyModal"><i class="bi bi-plus-circle"></i> Yeni Firma Ekle</button>
            </div>

            <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Logo</th><th>Firma Adı</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr><td colspan="3" class="text-center">Henüz firma eklenmemiş.</td></tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><img src="/<?php echo htmlspecialchars($company['logo_path']);  ?>" alt="" style="height: 40px; width: 40px;"></td>
                            <td><?php echo htmlspecialchars($company['name']); ?></td>
                            <td>
                                <button class="btn btn-secondary btn-sm edit-company-btn"
                                        data-bs-toggle="modal" data-bs-target="#editCompanyModal"
                                        data-id="<?php echo $company['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($company['name']); ?>"
                                        data-logo="/<?php echo htmlspecialchars($company['logo_path']);  ?>">Düzenle</button>
                               
                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <button type="submit" name="delete_company" class="btn btn-danger btn-sm">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Yeni Firma Ekle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
               
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3"><label class="form-label">Firma Adı</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Firma Logosu (PNG, JPG, Maks 1MB)</label><input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg"></div>
                    <div class="modal-footer"><button type="submit" name="add_company" class="btn btn-dark">Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Firma Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                
                <form method="POST" action="" enctype="multipart/form-data" id="editCompanyForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="company_id" id="edit_company_id">
                    <div class="mb-3"><label class="form-label">Firma Adı</label><input type="text" name="name" id="edit_company_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Mevcut Logo</label><div><img src="" id="current_logo" style="max-height: 50px; max-width: 100px;"></div></div>
                    <div class="mb-3"><label class="form-label">Yeni Logo Yükle (Değiştirmek istemiyorsanız boş bırakın)</label><input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg"></div>
                    <div class="modal-footer"><button type="submit" name="edit_company" class="btn btn-dark">Değişiklikleri Kaydet</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php

require_once __DIR__ . '/../../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/admin-company.js"></script> 
</body>
</html>
