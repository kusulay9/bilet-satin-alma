<?php
require_once '../includes/config.php';

$auth->requireRole('admin');
$db = new Database();

$pageTitle = 'Global Kupon Yönetimi';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && validateCSRF()) {
        $code = strtoupper($auth->sanitizeInput($_POST['code'] ?? ''));
        $discountPercent = (int)($_POST['discount_percent'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 1);
        $expiryDate = $_POST['expiry_date'] ?? '';
        
        if ($code && $discountPercent > 0 && $discountPercent <= 100 && $limit > 0 && $expiryDate) {

            $existingCoupon = $db->fetchOne("SELECT id FROM coupons WHERE code = ?", [$code]);
            
            if ($existingCoupon) {
                flashMessage('Bu kupon kodu zaten kullanılıyor.', 'error');
            } else {
                $couponId = $db->insert('coupons', [
                    'code' => $code,
                    'discount_percent' => $discountPercent,
                    'usage_limit' => $limit,
                    'expiry_date' => $expiryDate,
                    'firm_id' => null,
                    'is_global' => 1
                ]);
                
                if ($couponId) {
                    flashMessage('Global kupon başarıyla eklendi.', 'success');
                } else {
                    flashMessage('Kupon eklenirken bir hata oluştu.', 'error');
                }
            }
        } else {
            flashMessage('Lütfen tüm alanları doğru şekilde doldurun.', 'error');
        }
    } elseif ($action === 'edit' && validateCSRF()) {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        $code = strtoupper($auth->sanitizeInput($_POST['code'] ?? ''));
        $discountPercent = (int)($_POST['discount_percent'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 1);
        $expiryDate = $_POST['expiry_date'] ?? '';
        
        if ($couponId && $code && $discountPercent > 0 && $discountPercent <= 100 && $limit > 0 && $expiryDate) {

            $existingCoupon = $db->fetchOne("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $couponId]);
            
            if ($existingCoupon) {
                flashMessage('Bu kupon kodu zaten kullanılıyor.', 'error');
            } else {
                $result = $db->update('coupons', [
                    'code' => $code,
                    'discount_percent' => $discountPercent,
                    'usage_limit' => $limit,
                    'expiry_date' => $expiryDate
                ], 'id = ? AND is_global = 1', [$couponId]);
                
                if ($result) {
                    flashMessage('Global kupon başarıyla güncellendi.', 'success');
                } else {
                    flashMessage('Kupon güncellenirken bir hata oluştu.', 'error');
                }
            }
        } else {
            flashMessage('Lütfen tüm alanları doğru şekilde doldurun.', 'error');
        }
    } elseif ($action === 'delete' && validateCSRF()) {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        
        if ($couponId) {
            $result = $db->delete('coupons', 'id = ? AND is_global = 1', [$couponId]);
            
            if ($result) {
                flashMessage('Global kupon başarıyla silindi.', 'success');
            } else {
                flashMessage('Kupon silinirken bir hata oluştu.', 'error');
            }
        }
    }
}


$coupons = $db->fetchAll(
    "SELECT * FROM coupons WHERE is_global = 1 ORDER BY created_at DESC"
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
                    <a class="nav-link active" href="/admin/coupons.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-tags"></i> Global Kupon Yönetimi</h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCouponModal">
                <i class="fas fa-plus"></i> Yeni Global Kupon Ekle
            </button>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Global Kuponlar:</strong> Tüm firmaların seferlerinde kullanılabilen kuponlardır.
        </div>
        
        <?php if (empty($coupons)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                    <h5>Henüz global kupon eklenmemiş</h5>
                    <p class="text-muted">İlk global kuponunuzu eklemek için yukarıdaki butona tıklayın.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card coupon-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($coupon['code']); ?></h5>
                                    <span class="badge bg-primary">Global</span>
                                </div>
                                
                                <div class="mb-2">
                                    <strong>İndirim:</strong> %<?php echo $coupon['discount_percent']; ?>
                                </div>
                                
                                <div class="mb-2">
                                    <strong>Limit:</strong> <?php echo $coupon['usage_limit']; ?> kullanım
                                </div>
                                
                                <div class="mb-2">
                                    <strong>Durum:</strong>
                                    <span class="badge bg-<?php echo $coupon['expiry_date'] >= date('Y-m-d') ? 'success' : 'danger'; ?>">
                                        <?php echo $coupon['expiry_date'] >= date('Y-m-d') ? 'Aktif' : 'Süresi Dolmuş'; ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Son Tarih:</strong><br>
                                    <?php echo formatDate($coupon['expiry_date']); ?>
                                </div>
                                
                                <div class="btn-group w-100">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete" 
                                            onclick="deleteCoupon(<?php echo $coupon['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Global Kupon Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" id="code" name="code" 
                               placeholder="Örn: WELCOME10" required maxlength="20">
                        <div class="form-text">Büyük harflerle yazılacak</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount_percent" class="form-label">İndirim Yüzdesi</label>
                        <input type="number" class="form-control" id="discount_percent" name="discount_percent" 
                               min="1" max="100" required>
                        <div class="form-text">1-100 arası değer girin</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="limit" class="form-label">Kullanım Limiti</label>
                        <input type="number" class="form-control" id="limit" name="limit" 
                               min="1" value="1" required>
                        <div class="form-text">Kaç kez kullanılabileceği</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expiry_date" class="form-label">Son Kullanma Tarihi</label>
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Global Kupon Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="coupon_id" id="edit_coupon_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" id="edit_code" name="code" 
                               placeholder="Örn: WELCOME10" required maxlength="20">
                        <div class="form-text">Büyük harflerle yazılacak</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_discount_percent" class="form-label">İndirim Yüzdesi</label>
                        <input type="number" class="form-control" id="edit_discount_percent" name="discount_percent" 
                               min="1" max="100" required>
                        <div class="form-text">1-100 arası değer girin</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_limit" class="form-label">Kullanım Limiti</label>
                        <input type="number" class="form-control" id="edit_limit" name="limit" 
                               min="1" required>
                        <div class="form-text">Kaç kez kullanılabileceği</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_expiry_date" class="form-label">Son Kullanma Tarihi</label>
                        <input type="date" class="form-control" id="edit_expiry_date" name="expiry_date" required>
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

<!-- Delete Coupon Modal -->
<div class="modal fade" id="deleteCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Global Kupon Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu global kuponu silmek istediğinizden emin misiniz?</p>
                <p class="text-danger"><strong>Bu işlem geri alınamaz!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="coupon_id" id="delete_coupon_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function editCoupon(coupon) {
    document.getElementById('edit_coupon_id').value = coupon.id;
    document.getElementById('edit_code').value = coupon.code;
    document.getElementById('edit_discount_percent').value = coupon.discount_percent;
    document.getElementById('edit_limit').value = coupon.usage_limit;
    document.getElementById('edit_expiry_date').value = coupon.expiry_date;
    
    const modal = new bootstrap.Modal(document.getElementById('editCouponModal'));
    modal.show();
}

function deleteCoupon(couponId) {
    document.getElementById('delete_coupon_id').value = couponId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteCouponModal'));
    modal.show();
}
</script>

<?php  ?>
