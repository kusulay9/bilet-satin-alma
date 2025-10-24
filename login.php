<?php
require_once 'includes/config.php';

$pageTitle = 'Giriş Yap';

if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    $redirectUrl = $_GET['redirect'] ?? '';
    
    if ($redirectUrl && strpos($redirectUrl, '/') === 0) {
        redirect($redirectUrl);
    } elseif ($user['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    } elseif ($user['role'] === 'firma_admin') {
        redirect('/firma_admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $auth->sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre alanları zorunludur.';
    } elseif (!$auth->validateEmail($email)) {
        $error = 'Geçerli bir e-posta adresi giriniz.';
    } else {
        if ($auth->login($email, $password)) {
            $user = $auth->getUser();
            setFlashMessage('Başarıyla giriş yaptınız!', 'success');
            
            $redirectUrl = $_GET['redirect'] ?? '';
            
            if ($redirectUrl && strpos($redirectUrl, '/') === 0) {
                redirect($redirectUrl);
            } elseif ($user['role'] === 'admin') {
                redirect('/admin/dashboard.php');
            } elseif ($user['role'] === 'firma_admin') {
                redirect('/firma_admin/dashboard.php');
            } else {
                redirect('/user/dashboard.php');
            }
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-sign-in-alt"></i> Giriş Yap</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
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
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Şifre alanı zorunludur.
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Hesabınız yok mu? <a href="/register.php">Kayıt olun</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php  ?>
