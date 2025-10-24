
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Bilet Yönetimi'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body>

<?php
require_once __DIR__ . '/../../partials/header.php';
?>

<main class="container my-5">
    <h1 class="mb-4">Bilet Yönetimi</h1>


    <?php if ($flash_message): ?>
    <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($company_tickets)): ?>
        <div class="alert alert-info">Firmanıza ait satılmış bilet bulunmamaktadır.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Yolcu Adı</th>
                        <th>Yolcu E-posta</th>
                        <th>Sefer</th>
                        <th>Koltuk</th>
                        <th>Sefer Tarihi</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($company_tickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></td>
                            <td><?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></td>
                            <td>
                                <?php if ($ticket['status'] === 'active'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">İptal Edilmiş</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Bileti Görüntüle/Yazdır">
                                    <i class="bi bi-printer"></i> Yazdır
                                </a>
                                
                                <?php if ($ticket['status'] === 'active'): ?>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Bilet ücreti kullanıcıya iade edilecektir.');">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" name="admin_cancel_ticket" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> İptal Et
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php
require_once __DIR__ . '/../../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
