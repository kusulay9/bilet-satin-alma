<?php
require_once '../includes/config.php';

$auth->requireRole('admin');
$db = new Database();

$pageTitle = 'Raporlar';


$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$totalFirms = $db->fetchOne("SELECT COUNT(*) as count FROM firms")['count'];
$totalTrips = $db->fetchOne("SELECT COUNT(*) as count FROM trips")['count'];
$totalTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets")['count'];
$activeTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE status = 'active'")['count'];
$cancelledTickets = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE status = 'cancelled'")['count'];


$totalRevenue = $db->fetchOne("SELECT SUM(price) as total FROM tickets WHERE status = 'active'")['total'] ?? 0;
$avgTicketPrice = $db->fetchOne("SELECT AVG(price) as avg FROM tickets WHERE status = 'active'")['avg'] ?? 0;


$recentTickets = $db->fetchAll(
    "SELECT t.*, u.name as user_name, tr.from_city, tr.to_city, f.name as firm_name
     FROM tickets t
     JOIN users u ON t.user_id = u.id
     JOIN trips tr ON t.trip_id = tr.id
     JOIN firms f ON tr.firm_id = f.id
     ORDER BY t.purchase_time DESC LIMIT 10"
);


$popularRoutes = $db->fetchAll(
    "SELECT from_city, to_city, COUNT(*) as trip_count
     FROM trips
     GROUP BY from_city, to_city
     ORDER BY trip_count DESC LIMIT 10"
);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card admin-sidebar">
            <div class="card-body p-0">
                <nav class="nav flex-column">
                    <a class="nav-link" href="/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="/admin/firms.php">
                        <i class="fas fa-building"></i> Firmalar
                    </a>
                    <a class="nav-link" href="/admin/users.php">
                        <i class="fas fa-users"></i> Kullanıcılar
                    </a>
                    <a class="nav-link" href="/admin/coupons.php">
                        <i class="fas fa-tags"></i> Global Kuponlar
                    </a>
                    <a class="nav-link active" href="/admin/reports.php">
                        <i class="fas fa-chart-bar"></i> Raporlar
                    </a>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-chart-bar"></i> Sistem Raporları</h4>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Toplam Kullanıcı</h6>
                                <div class="stats-number"><?php echo $totalUsers; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x text-primary"></i>
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
                                <h6 class="card-title">Toplam Firma</h6>
                                <div class="stats-number"><?php echo $totalFirms; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-building fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Toplam Sefer</h6>
                            <div class="stats-number"><?php echo $totalTrips; ?></div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bus fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Toplam Bilet</h6>
                            <div class="stats-number"><?php echo $totalTickets; ?></div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-ticket-alt fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6>Toplam Gelir</h6>
                        <h4 class="text-success"><?php echo formatPrice($totalRevenue); ?></h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6>Ortalama Bilet Fiyatı</h6>
                        <h4 class="text-info"><?php echo formatPrice($avgTicketPrice); ?></h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6>Aktif Biletler</h6>
                        <h4 class="text-primary"><?php echo $activeTickets; ?></h4>
                        <small class="text-muted">İptal: <?php echo $cancelledTickets; ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-ticket-alt"></i> Son Biletler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTickets)): ?>
                            <p class="text-muted">Henüz bilet satın alınmamış.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($ticket['from_city']); ?> → 
                                                <?php echo htmlspecialchars($ticket['to_city']); ?>
                                            </h6>
                                            <small><?php echo formatPrice($ticket['price']); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <strong><?php echo htmlspecialchars($ticket['user_name']); ?></strong> - 
                                            <?php echo htmlspecialchars($ticket['firm_name']); ?>
                                        </p>
                                        <small><?php echo formatDateTime($ticket['purchase_time']); ?></small>
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
                        <h5><i class="fas fa-route"></i> Popüler Güzergahlar</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($popularRoutes)): ?>
                            <p class="text-muted">Henüz sefer eklenmemiş.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($popularRoutes as $route): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($route['from_city']); ?> → 
                                                <?php echo htmlspecialchars($route['to_city']); ?>
                                            </h6>
                                            <span class="badge bg-primary"><?php echo $route['trip_count']; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php  ?>
