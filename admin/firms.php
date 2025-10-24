<?php
require_once '../includes/config.php';

$auth->requireRole('admin');
$db = new Database();

$pageTitle = 'Firma Yönetimi';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && validateCSRF()) {
        $firmName = $auth->sanitizeInput($_POST['firm_name'] ?? '');
        $adminName = $auth->sanitizeInput($_POST['admin_name'] ?? '');
        $adminEmail = $auth->sanitizeInput($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        
        if ($firmName && $adminName && $adminEmail && $adminPassword) {
            if (!$auth->validateEmail($adminEmail)) {
                flashMessage('Geçerli bir e-posta adresi giriniz.', 'error');
            } elseif (!$auth->validatePassword($adminPassword)) {
                flashMessage('Şifre en az 6 karakter olmalıdır.', 'error');
            } else {
                try {
                    $db->beginTransaction();
                    

                    $firmId = $db->insert('firms', ['name' => $firmName]);
                    

                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $adminId = $db->insert('users', [
                        'name' => $adminName,
                        'email' => $adminEmail,
                        'password' => $hashedPassword,
                        'role' => 'firma_admin',
                        'firm_id' => $firmId,
                        'credit' => 0.00
                    ]);
                    
                    $db->commit();
                    flashMessage('Firma ve admin kullanıcısı başarıyla oluşturuldu.', 'success');
                    
                } catch (Exception $e) {
                    $db->rollback();
                    error_log('Firm creation error: ' . $e->getMessage());
                    flashMessage('Firma oluşturulurken bir hata oluştu.', 'error');
                }
            }
        } else {
            flashMessage('Lütfen tüm alanları doldurun.', 'error');
        }
    } elseif ($action === 'edit' && validateCSRF()) {
        $firmId = (int)($_POST['firm_id'] ?? 0);
        $firmName = $auth->sanitizeInput($_POST['firm_name'] ?? '');
        
        if ($firmId && $firmName) {
            $result = $db->update('firms', ['name' => $firmName], 'id = ?', [$firmId]);
            
            if ($result) {
                flashMessage('Firma başarıyla güncellendi.', 'success');
            } else {
                flashMessage('Firma güncellenirken bir hata oluştu.', 'error');
            }
        } else {
            flashMessage('Lütfen firma adını girin.', 'error');
        }
    } elseif ($action === 'assign_admin' && validateCSRF()) {
        $firmId = (int)($_POST['firm_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($firmId && $userId) {

            $user = $db->fetchOne("SELECT * FROM users WHERE id = ? AND role = 'user'", [$userId]);
            
            if ($user) {

                $result = $db->update('users', [
                    'role' => 'firma_admin',
                    'firm_id' => $firmId
                ], 'id = ?', [$userId]);
                
                if ($result) {
                    flashMessage('Kullanıcı başarıyla firma admini olarak atandı.', 'success');
                } else {
                    flashMessage('Kullanıcı atanırken bir hata oluştu.', 'error');
                }
            } else {
                flashMessage('Kullanıcı bulunamadı veya zaten firma admini.', 'error');
            }
        } else {
            flashMessage('Geçersiz veri.', 'error');
        }
    } elseif ($action === 'remove_admin' && validateCSRF()) {
        $firmId = (int)($_POST['firm_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($firmId && $userId) {

            $user = $db->fetchOne("SELECT * FROM users WHERE id = ? AND role = 'firma_admin' AND firm_id = ?", [$userId, $firmId]);
            
            if ($user) {

                $result = $db->update('users', [
                    'role' => 'user',
                    'firm_id' => null
                ], 'id = ?', [$userId]);
                
                if ($result) {
                    flashMessage('Kullanıcının firma admin yetkisi kaldırıldı.', 'success');
                } else {
                    flashMessage('Yetki kaldırılırken bir hata oluştu.', 'error');
                }
            } else {
                flashMessage('Kullanıcı bu firmanın admini değil.', 'error');
            }
        } else {
            flashMessage('Geçersiz veri.', 'error');
        }
    } elseif ($action === 'delete' && validateCSRF()) {
        $firmId = (int)($_POST['firm_id'] ?? 0);
        
        if ($firmId) {

            $tripCount = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE firm_id = ?", [$firmId])['count'];
            
            if ($tripCount > 0) {
                flashMessage('Bu firmada seferler bulunduğu için silinemez.', 'error');
            } else {
                try {
                    $db->beginTransaction();
                    

                    $db->delete('users', 'firm_id = ?', [$firmId]);
                    

                    $db->delete('firms', 'id = ?', [$firmId]);
                    
                    $db->commit();
                    flashMessage('Firma başarıyla silindi.', 'success');
                    
                } catch (Exception $e) {
                    $db->rollback();
                    error_log('Firm deletion error: ' . $e->getMessage());
                    flashMessage('Firma silinirken bir hata oluştu.', 'error');
                }
            }
        } else {
            flashMessage('Geçersiz firma ID.', 'error');
        }
    }
}


$firms = $db->fetchAll(
    "SELECT f.*, u.name as admin_name, u.email as admin_email, u.id as admin_id,
     (SELECT COUNT(*) FROM trips WHERE firm_id = f.id) as trip_count,
     (SELECT COUNT(*) FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE tr.firm_id = f.id AND t.status = 'active') as ticket_count
     FROM firms f 
     LEFT JOIN users u ON f.id = u.firm_id AND u.role = 'firma_admin'
     ORDER BY f.created_at DESC"
);


$users = $db->fetchAll(
    "SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name"
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
                    <a class="nav-link active" href="/admin/firms.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-building"></i> Firma Yönetimi</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFirmModal">
                <i class="fas fa-plus"></i> Yeni Firma Ekle
            </button>
        </div>
        
        <?php if (empty($firms)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-building fa-4x text-muted mb-3"></i>
                    <h5>Henüz firma eklenmemiş</h5>
                    <p class="text-muted">İlk firmayı eklemek için yukarıdaki butona tıklayın.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Firma Adı</th>
                                    <th>Admin</th>
                                    <th>E-posta</th>
                                    <th>Sefer</th>
                                    <th>Bilet</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($firms as $firm): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($firm['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($firm['admin_name']): ?>
                                                <?php echo htmlspecialchars($firm['admin_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Atanmamış</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($firm['admin_email']): ?>
                                                <?php echo htmlspecialchars($firm['admin_email']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $firm['trip_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $firm['ticket_count']; ?></span>
                                        </td>
                                        <td><?php echo formatDate($firm['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editFirm(<?php echo htmlspecialchars(json_encode($firm)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($firm['admin_id']): ?>
                                                    <button class="btn btn-sm btn-outline-warning" 
                                                            onclick="removeAdmin(<?php echo $firm['id']; ?>, <?php echo $firm['admin_id']; ?>, '<?php echo htmlspecialchars($firm['admin_name']); ?>')">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="assignAdmin(<?php echo $firm['id']; ?>, '<?php echo htmlspecialchars($firm['name']); ?>')">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                        onclick="deleteFirm(<?php echo $firm['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

<!-- Add Firm Modal -->
<div class="modal fade" id="addFirmModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firm_name" class="form-label">Firma Adı</label>
                            <input type="text" class="form-control" id="firm_name" name="firm_name" required>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Firma Admin Bilgileri</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_name" class="form-label">Admin Ad Soyad</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_email" class="form-label">Admin E-posta</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_password" class="form-label">Admin Şifre</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                   minlength="6" required>
                            <div class="form-text">En az 6 karakter</div>
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

<!-- Edit Firm Modal -->
<div class="modal fade" id="editFirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="firm_id" id="edit_firm_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="edit_firm_name" class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" id="edit_firm_name" name="firm_name" required>
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

<!-- Assign Admin Modal -->
<div class="modal fade" id="assignAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Admini Ata</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_admin">
                    <input type="hidden" name="firm_id" id="assign_firm_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Firma</label>
                        <input type="text" class="form-control" id="assign_firm_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assign_user_id" class="form-label">Kullanıcı Seçin</label>
                        <select class="form-select" id="assign_user_id" name="user_id" required>
                            <option value="">Kullanıcı seçin...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Seçilen kullanıcı bu firmanın admini olacak ve firma paneli erişimi kazanacaktır.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Admin Ata</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Admin Modal -->
<div class="modal fade" id="removeAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Admin Yetkisini Kaldır</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="remove_admin">
                    <input type="hidden" name="firm_id" id="remove_firm_id">
                    <input type="hidden" name="user_id" id="remove_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <p>Bu kullanıcının firma admin yetkisini kaldırmak istediğinizden emin misiniz?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Kullanıcı normal kullanıcı statüsüne dönecek ve firma paneli erişimi kaybedecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">Yetkiyi Kaldır</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Firm Modal -->
<div class="modal fade" id="deleteFirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu firmayı silmek istediğinizden emin misiniz?</p>
                <p class="text-danger"><strong>Bu işlem geri alınamaz!</strong></p>
                <p class="text-warning">Firma admin kullanıcıları da silinecektir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="firm_id" id="delete_firm_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function editFirm(firm) {
    document.getElementById('edit_firm_id').value = firm.id;
    document.getElementById('edit_firm_name').value = firm.name;
    const modal = new bootstrap.Modal(document.getElementById('editFirmModal'));
    modal.show();
}

function assignAdmin(firmId, firmName) {
    document.getElementById('assign_firm_id').value = firmId;
    document.getElementById('assign_firm_name').value = firmName;
    const modal = new bootstrap.Modal(document.getElementById('assignAdminModal'));
    modal.show();
}

function removeAdmin(firmId, userId, userName) {
    document.getElementById('remove_firm_id').value = firmId;
    document.getElementById('remove_user_id').value = userId;
    const modal = new bootstrap.Modal(document.getElementById('removeAdminModal'));
    modal.show();
}

function deleteFirm(firmId) {
    document.getElementById('delete_firm_id').value = firmId;
    const modal = new bootstrap.Modal(document.getElementById('deleteFirmModal'));
    modal.show();
}
</script>

<?php  ?>
