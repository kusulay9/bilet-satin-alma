<?php
require_once 'includes/config.php';

$pageTitle = 'Kayıt Ol';

if ($auth->isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $auth->sanitizeInput($_POST['name'] ?? '');
    $email = $auth->sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Tüm alanlar zorunludur.';
    } elseif (!$auth->validateEmail($email)) {
        $error = 'Geçerli bir e-posta adresi giriniz.';
    } elseif (!$auth->validatePassword($password)) {
        $error = 'Şifre en az 8 karakter olmalı ve harf ile rakam içermelidir.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $userId = $auth->register($name, $email, $password);
        
        if ($userId) {
            setFlashMessage('Kayıt başarılı! Giriş yapabilirsiniz.', 'success');
            redirect('/login.php');
        } else {
            $error = 'Bu e-posta adresi zaten kullanılıyor.';
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-user-plus"></i> Kayıt Ol</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        <div class="invalid-feedback">
                            Ad soyad alanı zorunludur.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        <div class="invalid-feedback">
                            Geçerli bir e-posta adresi giriniz.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="8" required>
                        <div class="invalid-feedback">
                            Şifre en az 8 karakter olmalı ve harf ile rakam içermelidir.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="8" required>
                        <div class="invalid-feedback">
                            Şifre tekrar alanı zorunludur.
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Kayıt Ol
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Zaten hesabınız var mı? <a href="/login.php">Giriş yapın</a></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php  ?>
