<?php
require_once '../includes/config.php';

$auth->requireRole('user');
$db = new Database();
$user = $auth->getUser();

$pageTitle = 'Biletlerim';


$tickets = $db->fetchAll(
    "SELECT t.*, tr.from_city, tr.to_city, tr.date, tr.time, f.name as firm_name
     FROM tickets t
     JOIN trips tr ON t.trip_id = tr.id
     JOIN firms f ON tr.firm_id = f.id
     WHERE t.user_id = ?
     ORDER BY t.purchase_time DESC",
    [$user['id']]
);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h5><i class="fas fa-wallet"></i> Kredi Bakiyem</h5>
                <h3 class="text-success"><?php echo formatPrice($user['credit']); ?></h3>
                <small class="text-muted">Mevcut krediniz</small>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body text-center">
                <h5><i class="fas fa-ticket-alt"></i> Toplam Bilet</h5>
                <h3 class="text-primary"><?php echo count($tickets); ?></h3>
                <small class="text-muted">Satın aldığınız biletler</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-ticket-alt"></i> Biletlerim</h4>
            </div>
            <div class="card-body">
                <?php if (empty($tickets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                        <h5>Henüz bilet satın almadınız</h5>
                        <p class="text-muted">İlk biletinizi satın almak için <a href="/">sefer arayın</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card ticket-card ticket-status <?php echo $ticket['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title">
                                                <i class="fas fa-bus"></i> 
                                                <?php echo htmlspecialchars($ticket['from_city']); ?> → 
                                                <?php echo htmlspecialchars($ticket['to_city']); ?>
                                            </h6>
                                            <span class="badge bg-<?php echo $ticket['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $ticket['status'] === 'active' ? 'Aktif' : 'İptal'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Tarih:</small><br>
                                                <?php echo formatDate($ticket['date']); ?>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Saat:</small><br>
                                                <?php echo date('H:i', strtotime($ticket['time'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Koltuk:</small><br>
                                                <strong><?php echo $ticket['seat_no']; ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Fiyat:</small><br>
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
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <small class="text-muted">Firma:</small><br>
                                                <?php echo htmlspecialchars($ticket['firm_name']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <?php if ($ticket['status'] === 'active'): ?>
                                                <?php 
                                                $tripDateTime = $ticket['date'] . ' ' . $ticket['time'];
                                                $canCancel = !isTimeCloseToDeparture($tripDateTime);
                                                ?>
                                                
                                                <?php if (!$canCancel): ?>
                                                    <div class="alert alert-warning alert-sm mb-2">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <strong>Uyarı:</strong> Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez.
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="btn-group w-100">
                                                    <a href="/user/ticket.php?id=<?php echo $ticket['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> Görüntüle
                                                    </a>
                                                    
                                                    <a href="/user/download-ticket.php?id=<?php echo $ticket['id']; ?>" 
                                                       class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-download"></i> PDF İndir
                                                    </a>
                                                    
                                                    <?php if ($canCancel): ?>
                                                        <button class="btn btn-outline-danger btn-sm btn-delete" 
                                                                onclick="cancelTicket(<?php echo $ticket['id']; ?>)">
                                                            <i class="fas fa-times"></i> İptal Et
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary btn-sm" disabled 
                                                                title="Kalkış saatine 1 saatten az kaldığı için iptal edilemez">
                                                            <i class="fas fa-lock"></i> İptal Edilemez
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar-times"></i>
                                                        İptal edildi: <?php echo formatDateTime($ticket['purchase_time']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Ticket Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bilet İptali</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu bileti iptal etmek istediğinizden emin misiniz?</p>
                <p class="text-muted">İptal edilen biletler için ödeme kredinize iade edilir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hayır</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Evet, İptal Et</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Ticket Cancellation -->
<form id="cancelForm" method="POST" action="/user/cancel-ticket.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="ticket_id" id="cancel_ticket_id">
</form>


<script>
function cancelTicket(ticketId) {
    document.getElementById('cancel_ticket_id').value = ticketId;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

document.getElementById('confirmCancel').addEventListener('click', function() {
    document.getElementById('cancelForm').submit();
});
</script>

<?php  ?>
