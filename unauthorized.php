<?php
require_once 'includes/config.php';

$pageTitle = 'Yetkisiz Erişim';

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                <h3>Yetkisiz Erişim</h3>
                <p class="text-muted">Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ana Sayfaya Dön
                </a>
            </div>
        </div>
    </div>
</div>

<?php  ?>
