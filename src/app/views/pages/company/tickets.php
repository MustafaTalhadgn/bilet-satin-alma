<?php
$active_tickets = [];
$canceled_tickets = [];
$expired_tickets = [];
$current_time = time(); 

foreach ($company_tickets as $ticket) {
    if ($ticket['status'] === 'canceled') {
        $canceled_tickets[] = $ticket;
    } else {
        $departure_timestamp = strtotime($ticket['departure_time']);
        if ($departure_timestamp < $current_time) {
            $expired_tickets[] = $ticket;
        } else {
            $active_tickets[] = $ticket;
        }
    }
}
?>
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

<?php require_once __DIR__ . '/../../partials/header.php'; ?>

<main class="container my-5">
    <h1 class="mb-4">Bilet Yönetimi</h1>
    <p class="lead">Firmanıza ait tüm seferler için satılan biletleri burada yönetebilirsiniz.</p>

    <?php if ($flash_message): ?>
    <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

   
    <ul class="nav nav-tabs" id="companyTicketsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tickets-pane" type="button" role="tab">Aktif Biletler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-tickets-pane" type="button" role="tab">Geçmiş Biletler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="canceled-tab" data-bs-toggle="tab" data-bs-target="#canceled-tickets-pane" type="button" role="tab">İptal Edilenler</button>
        </li>
    </ul>

    <div class="tab-content py-4" id="companyTicketsTabContent">
        
   
        <div class="tab-pane fade show active" id="active-tickets-pane" role="tabpanel">
            <?php if (empty($active_tickets)): ?>
                <div class="alert alert-info">Firmanıza ait aktif bilet bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Yolcu</th>
                                <th>Sefer</th>
                                <th>Koltuk</th>
                                <th>Sefer Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($ticket['user_email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                                    <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></td>
                                    <td><?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></td>
                                    <td>
                                        <a href="/admin/printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Bileti Görüntüle/Yazdır">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php
                                       
                                        $departure_timestamp = strtotime($ticket['departure_time']);
                                        if (($departure_timestamp - time()) > 3600) :
                                        ?>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Bilet ücreti kullanıcıya iade edilecektir.');">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" name="admin_cancel_ticket" class="btn btn-danger btn-sm" title="Bileti İptal Et">
                                                <i class="bi bi-x-circle"></i>
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
        </div>

       
        <div class="tab-pane fade" id="expired-tickets-pane" role="tabpanel">
            <?php if (empty($expired_tickets)): ?>
                <div class="alert alert-info">Firmanıza ait geçmiş bilet bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle table-secondary text-muted">
                        <thead class="table-dark">
                            <tr><th>Yolcu</th><th>Sefer</th><th>Koltuk</th><th>Sefer Tarihi</th><th>Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expired_tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></td>
                                    <td><?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></td>
                                    <td><span class="badge bg-secondary">Tamamlandı</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    
        <div class="tab-pane fade" id="canceled-tickets-pane" role="tabpanel">
             <?php if (empty($canceled_tickets)): ?>
                <div class="alert alert-info">Firmanıza ait iptal edilmiş bilet bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle table-danger text-muted">
                         <thead class="table-dark">
                            <tr><th>Yolcu</th><th>Sefer</th><th>Koltuk</th><th>Sefer Tarihi</th><th>Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canceled_tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></td>
                                    <td><?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></td>
                                    <td><span class="badge bg-danger">İptal Edilmiş</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>