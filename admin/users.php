<?php
require_once '../includes/config.php';

$auth->requireRole('admin');
$db = new Database();

$pageTitle = 'Kullanıcı Yönetimi';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_credit' && validateCSRF()) {
        $userId = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        
        if ($userId && $amount > 0) {
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if ($user) {
                $newCredit = $user['credit'] + $amount;
                $result = $db->update('users', ['credit' => $newCredit], 'id = ?', [$userId]);
                
                if ($result > 0) {
                    flashMessage('Kredi başarıyla eklendi. Yeni kredi: ' . formatPrice($newCredit), 'success');
                } else {
                    flashMessage('Kredi eklenirken bir hata oluştu.', 'error');
                }
            } else {
                flashMessage('Kullanıcı bulunamadı.', 'error');
            }
        } else {
            flashMessage('Geçersiz veri.', 'error');
        }
    }
}


$users = $db->fetchAll(
    "SELECT u.*, f.name as firm_name 
     FROM users u 
     LEFT JOIN firms f ON u.firm_id = f.id 
     ORDER BY u.created_at DESC"
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
                    <a class="nav-link active" href="/admin/users.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-users"></i> Kullanıcı Yönetimi</h4>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h5>Henüz kullanıcı yok</h5>
                    <p class="text-muted">Sistemde kayıtlı kullanıcı bulunmuyor.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>Firma</th>
                                    <th>Kredi</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                    ($user['role'] === 'firma_admin' ? 'warning' : 'success'); 
                                            ?>">
                                                <?php 
                                                echo $user['role'] === 'admin' ? 'Admin' : 
                                                    ($user['role'] === 'firma_admin' ? 'Firma Admin' : 'Kullanıcı'); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['firm_name']): ?>
                                                <?php echo htmlspecialchars($user['firm_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatPrice($user['credit']); ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'user'): ?>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="addCredit(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                    <i class="fas fa-plus"></i> Kredi Ekle
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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

<!-- Add Credit Modal -->
<div class="modal fade" id="addCreditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_credit">
                    <input type="hidden" name="user_id" id="add_credit_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı</label>
                        <input type="text" class="form-control" id="add_credit_user_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Eklenecek Kredi Miktarı (₺)</label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Kredi Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addCredit(userId, userName) {
    document.getElementById('add_credit_user_id').value = userId;
    document.getElementById('add_credit_user_name').value = userName;
    const modal = new bootstrap.Modal(document.getElementById('addCreditModal'));
    modal.show();
}
</script>

<?php  ?>
