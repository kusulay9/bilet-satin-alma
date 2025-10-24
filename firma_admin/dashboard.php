<?php
require_once '../includes/config.php';

$auth->requireRole('firma_admin');
$db = new Database();
$user = $auth->getUser();

$pageTitle = 'Firma Yönetimi';


$firm = $db->fetchOne("SELECT * FROM firms WHERE id = ?", [$user['firm_id']]);


if (!$firm) {
    flashMessage('Firma bilgileri bulunamadı. Lütfen admin ile iletişime geçin.', 'error');
    redirect('/logout.php');
}


$tripCount = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE firm_id = ?", [$user['firm_id']])['count'];
$couponCount = $db->fetchOne("SELECT COUNT(*) as count FROM coupons WHERE firm_id = ?", [$user['firm_id']])['count'];
$ticketCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM tickets t 
     JOIN trips tr ON t.trip_id = tr.id 
     WHERE tr.firm_id = ? AND t.status = 'active'", 
    [$user['firm_id']]
)['count'];

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card admin-sidebar">
            <div class="card-body p-0">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="/firma_admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="/firma_admin/trips.php">
                        <i class="fas fa-bus"></i> Seferler
                    </a>
                    <a class="nav-link" href="/firma_admin/coupons.php">
                        <i class="fas fa-tags"></i> Kuponlar
                    </a>
                    <a class="nav-link" href="/firma_admin/reports.php">
                        <i class="fas fa-chart-bar"></i> Raporlar
                    </a>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Toplam Sefer</h6>
                                <div class="stats-number"><?php echo $tripCount; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-bus fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Aktif Kupon</h6>
                                <div class="stats-number"><?php echo $couponCount; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tags fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Satılan Bilet</h6>
                                <div class="stats-number"><?php echo $ticketCount; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bus"></i> Son Seferler</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recentTrips = $db->fetchAll(
                            "SELECT * FROM trips WHERE firm_id = ? ORDER BY created_at DESC LIMIT 5",
                            [$user['firm_id']]
                        );
                        ?>
                        
                        <?php if (empty($recentTrips)): ?>
                            <p class="text-muted">Henüz sefer eklenmemiş.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentTrips as $trip): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($trip['from_city']); ?> → 
                                                <?php echo htmlspecialchars($trip['to_city']); ?>
                                            </h6>
                                            <small><?php echo formatPrice($trip['price']); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <?php echo formatDate($trip['date']); ?> - 
                                            <?php echo date('H:i', strtotime($trip['time'])); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/firma_admin/trips.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Yeni Sefer Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tags"></i> Aktif Kuponlar</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $activeCoupons = $db->fetchAll(
                            "SELECT * FROM coupons WHERE firm_id = ? AND expiry_date >= date('now') ORDER BY created_at DESC LIMIT 5",
                            [$user['firm_id']]
                        );
                        ?>
                        
                        <?php if (empty($activeCoupons)): ?>
                            <p class="text-muted">Henüz kupon eklenmemiş.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($activeCoupons as $coupon): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($coupon['code']); ?></h6>
                                            <small>%<?php echo $coupon['discount_percent']; ?></small>
                                        </div>
                                        <p class="mb-1">
                                            Limit: <?php echo $coupon['usage_limit'] ?? 'Sınırsız'; ?> | 
                                            Son Tarih: <?php echo formatDate($coupon['expiry_date']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/firma_admin/coupons.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Yeni Kupon Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Firma Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Firma Adı:</strong></td>
                                        <td><?php echo htmlspecialchars($firm['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kayıt Tarihi:</strong></td>
                                        <td><?php echo formatDate($firm['created_at']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Admin:</strong></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>E-posta:</strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php  ?>
