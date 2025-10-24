<?php
require_once 'includes/config.php';
require_once 'includes/cities.php';

$pageTitle = 'Ana Sayfa';
$db = new Database();

$fromCity = $auth->sanitizeInput($_GET['from_city'] ?? '');
$toCity = $auth->sanitizeInput($_GET['to_city'] ?? '');
$date = $auth->sanitizeInput($_GET['date'] ?? '');
$trips = [];

if ($fromCity && $toCity && $date) {
    $trips = $db->fetchAll(
        "SELECT t.*, f.name as firm_name 
         FROM trips t 
         JOIN firms f ON t.firm_id = f.id 
         WHERE t.from_city LIKE ? AND t.to_city LIKE ? AND t.date = ?
         ORDER BY t.time ASC",
        ["%$fromCity%", "%$toCity%", $date]
    );
}

$cities = array_map(function($city) {
    return ['city' => $city];
}, $turkishCities);

include 'includes/header.php';
?>

<div class="search-form">
    <h2 class="text-center mb-4">
        <i class="fas fa-search"></i> Otobüs Seferi Ara
    </h2>
    
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label for="from_city" class="form-label">Nereden</label>
            <select class="form-select" id="from_city" name="from_city" required>
                <option value="">Şehir Seçin</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city['city']); ?>" 
                            <?php echo $fromCity === $city['city'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($city['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="to_city" class="form-label">Nereye</label>
            <select class="form-select" id="to_city" name="to_city" required>
                <option value="">Şehir Seçin</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city['city']); ?>" 
                            <?php echo $toCity === $city['city'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($city['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="date" class="form-label">Tarih</label>
            <input type="date" class="form-control" id="date" name="date" 
                   value="<?php echo htmlspecialchars($date); ?>" required>
        </div>
        
        <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Ara
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($trips)): ?>
    <div class="row">
        <div class="col-12">
            <h3>Bulunan Seferler (<?php echo count($trips); ?> adet)</h3>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($trips as $trip): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card trip-card h-100">
                    <div class="card-body">
                        <div class="trip-info">
                            <div class="trip-route">
                                <div class="from-to"><?php echo htmlspecialchars($trip['from_city']); ?></div>
                                <div class="arrow">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <div class="from-to"><?php echo htmlspecialchars($trip['to_city']); ?></div>
                            </div>
                            <div class="price"><?php echo formatPrice($trip['price']); ?></div>
                        </div>
                        
                        <div class="trip-details">
                            <div>
                                <i class="fas fa-calendar"></i> <?php echo formatDate($trip['date']); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($trip['time'])); ?>
                            </div>
                        </div>
                        
                        <div class="trip-details">
                            <div>
                                <i class="fas fa-clock"></i> Varış: <?php echo date('H:i', strtotime($trip['time'] . ' +' . ($trip['duration'] ?? 480) . ' minutes')); ?>
                            </div>
                            <div>
                                <i class="fas fa-hourglass-half"></i> <?php echo floor(($trip['duration'] ?? 480) / 60); ?>s <?php echo ($trip['duration'] ?? 480) % 60; ?>dk
                            </div>
                        </div>
                        
                        <div class="trip-details">
                            <div>
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($trip['firm_name']); ?>
                            </div>
                            <div>
                                <i class="fas fa-chair"></i> <?php echo $trip['seat_count']; ?> koltuk
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <?php if ($auth->isLoggedIn()): ?>
                                <a href="/trip-details.php?id=<?php echo $trip['id']; ?>" 
                                   class="btn btn-buy w-100">
                                    <i class="fas fa-ticket-alt"></i> Bilet Al
                                </a>
                            <?php else: ?>
                                <a href="/login.php?redirect=<?php echo urlencode('/trip-details.php?id=' . $trip['id']); ?>" class="btn btn-buy w-100">
                                    <i class="fas fa-ticket-alt"></i> Bilet Al
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($fromCity && $toCity && $date): ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i>
        <strong>Sefer Bulunamadı</strong><br>
        Belirtilen kriterlere uygun sefer bulunamadı. Lütfen farklı tarih veya şehir seçiniz.
        
        <div class="mt-3">
            <p class="mb-2">Alternatif tarihleri deneyin:</p>
            <div class="btn-group" role="group">
                <?php
                $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
                $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                ?>
                <a href="?from_city=<?php echo urlencode($fromCity); ?>&to_city=<?php echo urlencode($toCity); ?>&date=<?php echo $prevDate; ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Önceki Gün (<?php echo formatDate($prevDate); ?>)
                </a>
                <a href="?from_city=<?php echo urlencode($fromCity); ?>&to_city=<?php echo urlencode($toCity); ?>&date=<?php echo $nextDate; ?>" 
                   class="btn btn-outline-primary">
                    Sonraki Gün (<?php echo formatDate($nextDate); ?>) <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        
        <div class="mt-3">
            <p class="mb-2">Daha fazla alternatif:</p>
            <div class="btn-group" role="group">
                <?php
                $prevWeek = date('Y-m-d', strtotime($date . ' -7 days'));
                $nextWeek = date('Y-m-d', strtotime($date . ' +7 days'));
                ?>
                <a href="?from_city=<?php echo urlencode($fromCity); ?>&to_city=<?php echo urlencode($toCity); ?>&date=<?php echo $prevWeek; ?>" 
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-calendar-minus"></i> 1 Hafta Önce
                </a>
                <a href="?from_city=<?php echo urlencode($fromCity); ?>&to_city=<?php echo urlencode($toCity); ?>&date=<?php echo $nextWeek; ?>" 
                   class="btn btn-outline-secondary btn-sm">
                    1 Hafta Sonra <i class="fas fa-calendar-plus"></i>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php  ?>
