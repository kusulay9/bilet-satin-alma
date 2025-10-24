<?php
require_once '../includes/config.php';

$auth->requireRole('user');
$db = new Database();
$user = $auth->getUser();

$ticketId = (int)($_GET['id'] ?? 0);

if (!$ticketId) {
    flashMessage('Geçersiz bilet ID.', 'error');
    redirect('/user/dashboard.php');
}


$ticket = $db->fetchOne(
    "SELECT t.*, tr.from_city, tr.to_city, tr.date, tr.time, f.name as firm_name
     FROM tickets t
     JOIN trips tr ON t.trip_id = tr.id
     JOIN firms f ON tr.firm_id = f.id
     WHERE t.id = ? AND t.user_id = ?",
    [$ticketId, $user['id']]
);

if (!$ticket) {
    flashMessage('Bilet bulunamadı.', 'error');
    redirect('/user/dashboard.php');
}

$pageTitle = 'Bilet Detayları';

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-ticket-alt"></i> Bilet Detayları</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Bilet Bilgileri</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Bilet No:</strong></td>
                                <td><?php echo 'TKT' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Koltuk No:</strong></td>
                                <td><?php echo $ticket['seat_no']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Fiyat:</strong></td>
                                <td>
                                    <?php if ($ticket['discount_amount'] > 0): ?>
                                        <div>
                                            <span class="text-decoration-line-through text-muted"><?php echo formatPrice($ticket['original_price']); ?></span><br>
                                            <strong class="text-success"><?php echo formatPrice($ticket['price']); ?></strong>
                                        </div>
                                        <small class="text-success">
                                            <i class="fas fa-tag"></i> <?php echo formatPrice($ticket['discount_amount']); ?> indirim
                                            <?php if ($ticket['coupon_code']): ?>
                                                (<?php echo htmlspecialchars($ticket['coupon_code']); ?>)
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <strong><?php echo formatPrice($ticket['price']); ?></strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Durum:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $ticket['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo $ticket['status'] === 'active' ? 'Aktif' : 'İptal'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Satın Alma:</strong></td>
                                <td><?php echo formatDateTime($ticket['purchase_time']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Sefer Bilgileri</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Güzergah:</strong></td>
                                <td>
                                    <?php echo htmlspecialchars($ticket['from_city']); ?> → 
                                    <?php echo htmlspecialchars($ticket['to_city']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tarih:</strong></td>
                                <td><?php echo formatDate($ticket['date']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Saat:</strong></td>
                                <td><?php echo date('H:i', strtotime($ticket['time'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Firma:</strong></td>
                                <td><?php echo htmlspecialchars($ticket['firm_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5>Yolcu Bilgileri</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Ad Soyad:</strong></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>E-posta:</strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/user/download-ticket.php?id=<?php echo $ticket['id']; ?>" 
                       class="btn btn-success me-2">
                        <i class="fas fa-download"></i> PDF İndir
                    </a>
                    <a href="/user/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php  ?>
