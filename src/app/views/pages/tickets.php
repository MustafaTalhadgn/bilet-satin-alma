<?php
/**
 * src/app/views/pages/my-tickets.php
 * Kullanıcının biletlerini listeleyen HTML yapısı.
 * Gerekli değişkenler ($active_tickets, $csrf_token vb.) TicketController tarafından sağlanır.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/my-tickets.css">
    <link rel="stylesheet" href="/assets/css/style.css"> 

</head>
<body>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../partials/header.php';
?>

<main class="container my-5">
    <h1 class="mb-4">Biletlerim</h1>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="myTicketsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tickets-pane" type="button" role="tab">Aktif Biletlerim</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-tickets-pane" type="button" role="tab">Geçmiş Biletler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="canceled-tab" data-bs-toggle="tab" data-bs-target="#canceled-tickets-pane" type="button" role="tab">İptal Edilenler</button>
        </li>
    </ul>

    <div class="tab-content py-4" id="myTicketsTabContent">
        <div class="tab-pane fade show active" id="active-tickets-pane" role="tabpanel">
            <?php if (empty($active_tickets)): ?>
                <div class="alert alert-info">Aktif biletiniz bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($active_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm ticket-card status-active">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <img src="/<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                            <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-primary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                            <p class="card-text"><strong>Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <div>
                                            <a href="/printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Yazdır/PDF</a>
                                            <?php
                                            $departure_timestamp = strtotime($ticket['departure_time']);
                                            if (($departure_timestamp - time()) > 3600) :
                                            ?>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Ücret iadesi hesabınıza yapılacaktır.');">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                    <button type="submit" name="cancel_ticket" class="btn btn-danger btn-sm">Bileti İptal Et</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-pane fade" id="expired-tickets-pane" role="tabpanel">
             <?php if (empty($expired_tickets)): ?>
                <div class="alert alert-info">Geçmiş seyahatiniz bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($expired_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                             <div class="card shadow-sm ticket-card status-expired">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center text-muted">
                                        <img src="/<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px; opacity: 0.6;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
                                    <span class="badge text-bg-secondary">Tamamlandı</span>
                                </div>
                                <div class="card-body text-muted">
                                     <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                            <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-secondary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                            <p class="card-text"><strong>Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <a href="/printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Yazdır/PDF</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="canceled-tickets-pane" role="tabpanel">
             <?php if (empty($canceled_tickets)): ?>
                <div class="alert alert-info">Daha önce iptal ettiğiniz bir bilet bulunmamaktadır.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($canceled_tickets as $ticket): ?>
                        <div class="col-lg-6 mb-4">
                             <div class="card shadow-sm ticket-card status-canceled">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center text-muted">
                                        <img src="/<?php echo htmlspecialchars($ticket['logo_path'] ?? 'assets/images/default-logo.png'); ?>" alt="" style="height: 25px; opacity: 0.6;" class="me-2">
                                        <?php echo htmlspecialchars($ticket['company_name']); ?>
                                    </h5>
                                    <span class="badge text-bg-danger">İptal Edildi</span>
                                </div>
                                <div class="card-body text-muted">
                                     <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="card-text mb-0"><strong>Kalkış:</strong> <?php echo htmlspecialchars($ticket['departure_city']); ?></p>
                                            <p class="card-text"><strong>Varış:</strong> <?php echo htmlspecialchars($ticket['destination_city']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="card-text mb-0"><strong>Koltuk No:</strong> <span class="badge text-bg-secondary fs-6"><?php echo htmlspecialchars($ticket['seat_number']); ?></span></p>
                                            <p class="card-text"><strong>İade Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><strong>Sefer Tarihi:</strong> <?php echo date("d.m.Y H:i", strtotime($ticket['departure_time'])); ?></small>
                                        <a href="/printable_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" target="_blank" class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-printer"></i> Yazdır/PDF</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Partial'ı doğru yerden çağır
require_once __DIR__ . '/../partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>