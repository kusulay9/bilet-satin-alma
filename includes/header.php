<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #dc3545;">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-bus"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <?php if ($auth->isLoggedIn() && $auth->getUser()['role'] === 'user'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/user/dashboard.php">Biletlerim</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <?php $user = $auth->getUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                                <?php if ($user['role'] === 'user'): ?>
                                    <span class="badge bg-success"><?php echo formatPrice($user['credit']); ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="/admin/dashboard.php">Admin Panel</a></li>
                                <?php elseif ($user['role'] === 'firma_admin'): ?>
                                    <li><a class="dropdown-item" href="/firma_admin/dashboard.php">Firma Yönetimi</a></li>
                                <?php elseif ($user['role'] === 'user'): ?>
                                    <li><a class="dropdown-item" href="/user/dashboard.php">Müşteri Paneli</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
