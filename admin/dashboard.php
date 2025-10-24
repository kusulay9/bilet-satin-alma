<?php
require_once '../includes/config.php';

$auth->requireRole('admin');
$db = new Database();

$pageTitle = 'Admin Panel';

$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$firmCount = $db->fetchOne("SELECT COUNT(*) as count FROM firms")['count'];
$tripCount = $db->fetchOne("SELECT COUNT(*) as count FROM trips")['count'];
$ticketCount = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE status = 'active'")['count'];

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card admin-sidebar">
            <div class="card-body p-0">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="/admin/dashboard.php">
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
                    <a class="nav-link" href="/admin/reports.php">
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
                                <h6 class="card-title">Toplam Kullanıcı</h6>
                                <div class="stats-number"><?php echo $userCount; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
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
                                <div class="stats-number"><?php echo $firmCount; ?></div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-building fa-2x"></i>
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
                                <h6 class="card-title">Aktif Bilet</h6>
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
                        <h5><i class="fas fa-building"></i> Son Eklenen Firmalar</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recentFirms = $db->fetchAll(
                            "SELECT f.*, u.name as admin_name 
                             FROM firms f 
                             LEFT JOIN users u ON f.id = u.firm_id AND u.role = 'firma_admin'
                             ORDER BY f.created_at DESC LIMIT 5"
                        );
                        ?>
                        
                        <?php if (empty($recentFirms)): ?>
                            <p class="text-muted">Henüz firma eklenmemiş.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentFirms as $firm): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($firm['name']); ?></h6>
                                            <small><?php echo formatDate($firm['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <?php if ($firm['admin_name']): ?>
                                                Admin: <?php echo htmlspecialchars($firm['admin_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Admin atanmamış</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/admin/firms.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Yeni Firma Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tags"></i> Global Kuponlar</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $globalCoupons = $db->fetchAll(
                            "SELECT * FROM coupons WHERE is_global = 1 ORDER BY created_at DESC LIMIT 5"
                        );
                        ?>
                        
                        <?php if (empty($globalCoupons)): ?>
                            <p class="text-muted">Henüz global kupon eklenmemiş.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($globalCoupons as $coupon): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($coupon['code']); ?></h6>
                                            <small>%<?php echo $coupon['discount_percent']; ?></small>
                                        </div>
                                        <p class="mb-1">
                                            Limit: <?php echo $coupon['usage_limit']; ?> | 
                                            Son Tarih: <?php echo formatDate($coupon['expiry_date']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/admin/coupons.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Yeni Global Kupon Ekle
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
                        <h5><i class="fas fa-chart-line"></i> Sistem İstatistikleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h6>Toplam Gelir</h6>
                                <h4 class="text-success">
                                    <?php 
                                    $totalRevenue = $db->fetchOne("SELECT SUM(price) as total FROM tickets WHERE status = 'active'")['total'] ?? 0;
                                    echo formatPrice($totalRevenue); 
                                    ?>
                                </h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6>Ortalama Bilet Fiyatı</h6>
                                <h4 class="text-info">
                                    <?php 
                                    $avgPrice = $db->fetchOne("SELECT AVG(price) as avg FROM tickets WHERE status = 'active'")['avg'] ?? 0;
                                    echo formatPrice($avgPrice); 
                                    ?>
                                </h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6>En Popüler Güzergah</h6>
                                <h4 class="text-warning">
                                    <?php 
                                    $popularRoute = $db->fetchOne(
                                        "SELECT from_city || ' → ' || to_city as route, COUNT(*) as count 
                                         FROM trips GROUP BY from_city, to_city 
                                         ORDER BY count DESC LIMIT 1"
                                    );
                                    echo $popularRoute ? htmlspecialchars($popularRoute['route']) : 'Veri Yok';
                                    ?>
                                </h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6>Sistem Durumu</h6>
                                <h4 class="text-success">
                                    <i class="fas fa-check-circle"></i> Aktif
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php  ?>
