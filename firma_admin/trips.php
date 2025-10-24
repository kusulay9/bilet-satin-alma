<?php
require_once '../includes/config.php';
require_once '../includes/cities.php';

$auth->requireRole('firma_admin');
$db = new Database();
$user = $auth->getUser();

$pageTitle = 'Sefer Yönetimi';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && validateCSRF()) {
        $fromCity = $auth->sanitizeInput($_POST['from_city'] ?? '');
        $toCity = $auth->sanitizeInput($_POST['to_city'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $seatCount = (int)($_POST['seat_count'] ?? 40);
        
        if ($fromCity && $toCity && $date && $time && $duration > 0 && $price > 0) {
            $tripId = $db->insert('trips', [
                'firm_id' => $user['firm_id'],
                'from_city' => $fromCity,
                'to_city' => $toCity,
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
                'price' => $price,
                'seat_count' => $seatCount
            ]);
            
            if ($tripId) {
                flashMessage('Sefer başarıyla eklendi.', 'success');
            } else {
                flashMessage('Sefer eklenirken bir hata oluştu.', 'error');
            }
        } else {
            flashMessage('Lütfen tüm alanları doldurun.', 'error');
        }
    } elseif ($action === 'edit' && validateCSRF()) {
        $tripId = (int)($_POST['trip_id'] ?? 0);
        $fromCity = $auth->sanitizeInput($_POST['from_city'] ?? '');
        $toCity = $auth->sanitizeInput($_POST['to_city'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $seatCount = (int)($_POST['seat_count'] ?? 40);
        
        if ($tripId && $fromCity && $toCity && $date && $time && $duration > 0 && $price > 0) {
            $result = $db->update('trips', [
                'from_city' => $fromCity,
                'to_city' => $toCity,
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
                'price' => $price,
                'seat_count' => $seatCount
            ], 'id = ? AND firm_id = ?', [$tripId, $user['firm_id']]);
            
            if ($result) {
                flashMessage('Sefer başarıyla güncellendi.', 'success');
            } else {
                flashMessage('Sefer güncellenirken bir hata oluştu.', 'error');
            }
        } else {
            flashMessage('Lütfen tüm alanları doldurun.', 'error');
        }
    } elseif ($action === 'delete' && validateCSRF()) {
        $tripId = (int)($_POST['trip_id'] ?? 0);
        
        if ($tripId) {

            $activeTickets = $db->fetchOne(
                "SELECT COUNT(*) as count FROM tickets WHERE trip_id = ? AND status = 'active'",
                [$tripId]
            );
            
            if ($activeTickets['count'] > 0) {
                flashMessage('Bu seferde aktif biletler bulunduğu için silinemez.', 'error');
            } else {
                $result = $db->delete('trips', 'id = ? AND firm_id = ?', [$tripId, $user['firm_id']]);
                
                if ($result) {
                    flashMessage('Sefer başarıyla silindi.', 'success');
                } else {
                    flashMessage('Sefer silinirken bir hata oluştu.', 'error');
                }
            }
        }
    }
}


$trips = $db->fetchAll(
    "SELECT t.*, 
     (SELECT COUNT(*) FROM tickets WHERE trip_id = t.id AND status = 'active') as sold_tickets
     FROM trips t 
     WHERE t.firm_id = ? 
     ORDER BY t.date DESC, t.time DESC",
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
                    <a class="nav-link active" href="/firma_admin/trips.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-bus"></i> Sefer Yönetimi</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTripModal">
                <i class="fas fa-plus"></i> Yeni Sefer Ekle
            </button>
        </div>
        
        <?php if (empty($trips)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                    <h5>Henüz sefer eklenmemiş</h5>
                    <p class="text-muted">İlk seferinizi eklemek için yukarıdaki butona tıklayın.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Güzergah</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Fiyat</th>
                                    <th>Koltuk</th>
                                    <th>Satılan</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trips as $trip): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($trip['from_city']); ?> → 
                                            <?php echo htmlspecialchars($trip['to_city']); ?>
                                        </td>
                                        <td><?php echo formatDate($trip['date']); ?></td>
                                        <td><?php echo date('H:i', strtotime($trip['time'])); ?></td>
                                        <td><?php echo formatPrice($trip['price']); ?></td>
                                        <td><?php echo $trip['seat_count']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $trip['sold_tickets'] > 0 ? 'success' : 'secondary'; ?>">
                                                <?php echo $trip['sold_tickets']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editTrip(<?php echo htmlspecialchars(json_encode($trip)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                    onclick="deleteTrip(<?php echo $trip['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Trip Modal -->
<div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sefer Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="from_city" class="form-label">Nereden</label>
                            <select class="form-control" id="from_city" name="from_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($turkishCities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="to_city" class="form-label">Nereye</label>
                            <select class="form-control" id="to_city" name="to_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($turkishCities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">Saat</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Süre (Dakika)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Fiyat (₺)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="seat_count" class="form-label">Koltuk Sayısı</label>
                            <input type="number" class="form-control" id="seat_count" name="seat_count" min="1" max="60" value="40" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Trip Modal -->
<div class="modal fade" id="editTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sefer Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="trip_id" id="edit_trip_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_from_city" class="form-label">Nereden</label>
                            <select class="form-control" id="edit_from_city" name="from_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($turkishCities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_to_city" class="form-label">Nereye</label>
                            <select class="form-control" id="edit_to_city" name="to_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($turkishCities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_time" class="form-label">Saat</label>
                            <input type="time" class="form-control" id="edit_time" name="time" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_duration" class="form-label">Süre (Dakika)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_price" class="form-label">Fiyat (₺)</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_seat_count" class="form-label">Koltuk Sayısı</label>
                            <input type="number" class="form-control" id="edit_seat_count" name="seat_count" min="1" max="60" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Trip Modal -->
<div class="modal fade" id="deleteTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sefer Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu seferi silmek istediğinizden emin misiniz?</p>
                <p class="text-danger"><strong>Bu işlem geri alınamaz!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="trip_id" id="delete_trip_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function editTrip(trip) {
    document.getElementById('edit_trip_id').value = trip.id;
    document.getElementById('edit_from_city').value = trip.from_city;
    document.getElementById('edit_to_city').value = trip.to_city;
    document.getElementById('edit_date').value = trip.date;
    document.getElementById('edit_time').value = trip.time;
    document.getElementById('edit_duration').value = trip.duration;
    document.getElementById('edit_price').value = trip.price;
    document.getElementById('edit_seat_count').value = trip.seat_count;
    
    const modal = new bootstrap.Modal(document.getElementById('editTripModal'));
    modal.show();
}

function deleteTrip(tripId) {
    document.getElementById('delete_trip_id').value = tripId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteTripModal'));
    modal.show();
}
</script>

<?php  ?>
