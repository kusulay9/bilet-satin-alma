<?php
require_once '../includes/config.php';

$auth->requireRole('firma_admin');
$db = new Database();
$user = $auth->getUser();

$pageTitle = 'Firma Raporları';

$firm = $db->fetchOne("SELECT * FROM firms WHERE id = ?", [$user['firm_id']]);

if (!$firm) {
    flashMessage('Firma bilgileri bulunamadı.', 'error');
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

$totalRevenue = $db->fetchOne(
    "SELECT SUM(t.price) as total FROM tickets t 
     JOIN trips tr ON t.trip_id = tr.id 
     WHERE tr.firm_id = ? AND t.status = 'active'", 
    [$user['firm_id']]
)['total'] ?? 0;

$popularRoutes = $db->fetchAll(
    "SELECT from_city, to_city, COUNT(*) as count
     FROM trips 
     WHERE firm_id = ?
     GROUP BY from_city, to_city
     ORDER BY count DESC
     LIMIT 5",
    [$user['firm_id']]
);

$monthlyTrips = $db->fetchAll(
    "SELECT strftime('%Y-%m', date) as month, COUNT(*) as count
     FROM trips 
     WHERE firm_id = ? AND date >= date('now', '-12 months')
     GROUP BY strftime('%Y-%m', date)
     ORDER BY month DESC",
    [$user['firm_id']]
);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card admin-sidebar">
            <div class="card-body p-0">
                <nav class="nav flex-column">
                    <a class="nav-link" href="/firma_admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="/firma_admin/trips.php">
                        <i class="fas fa-bus"></i> Seferler
                    </a>
                    <a class="nav-link" href="/firma_admin/coupons.php">
                        <i class="fas fa-tags"></i> Kuponlar
                    </a>
                    <a class="nav-link active" href="/firma_admin/reports.php">
                        <i class="fas fa-chart-bar"></i> Raporlar
                    </a>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-3 mb-3">
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
            
            <div class="col-md-3 mb-3">
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
            
            <div class="col-md-3 mb-3">
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
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Toplam Gelir</h6>
                                <div class="stats-number"><?php echo formatPrice($totalRevenue); ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-lira-sign fa-2x"></i>
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
                        <h5><i class="fas fa-route"></i> Popüler Güzergahlar</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($popularRoutes)): ?>
                            <p class="text-muted">Henüz sefer verisi yok.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($popularRoutes as $route): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($route['from_city']); ?> → 
                                                <?php echo htmlspecialchars($route['to_city']); ?>
                                            </h6>
                                            <span class="badge bg-primary"><?php echo $route['count']; ?> sefer</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Aylık Sefer Dağılımı</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthlyTrips)): ?>
                            <p class="text-muted">Henüz sefer verisi yok.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($monthlyTrips as $month): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></h6>
                                            <span class="badge bg-success"><?php echo $month['count']; ?> sefer</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Firma Özeti</h5>
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
                                        <td><strong>Toplam Sefer:</strong></td>
                                        <td><?php echo $tripCount; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Toplam Gelir:</strong></td>
                                        <td><?php echo formatPrice($totalRevenue); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Satılan Bilet:</strong></td>
                                        <td><?php echo $ticketCount; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Aktif Kupon:</strong></td>
                                        <td><?php echo $couponCount; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kayıt Tarihi:</strong></td>
                                        <td><?php echo formatDate($firm['created_at']); ?></td>
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
