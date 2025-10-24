<?php
require_once 'includes/config.php';

$pageTitle = 'Sefer Detayları';
$db = new Database();

$tripId = (int)($_GET['id'] ?? 0);

if (!$tripId) {
    flashMessage('Geçersiz sefer ID.', 'error');
    redirect('/');
}


$trip = $db->fetchOne(
    "SELECT t.*, f.name as firm_name 
     FROM trips t 
     JOIN firms f ON t.firm_id = f.id 
     WHERE t.id = ?",
    [$tripId]
);

if (!$trip) {
    flashMessage('Sefer bulunamadı.', 'error');
    redirect('/');
}


$occupiedSeats = $db->fetchAll(
    "SELECT seat_no FROM tickets WHERE trip_id = ? AND status = 'active'",
    [$tripId]
);

$occupiedSeatNumbers = array_column($occupiedSeats, 'seat_no');


$user = $auth->getUser();
$canPurchase = $auth->isLoggedIn() && $user['role'] === 'user';

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bus"></i> Sefer Detayları</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Güzergah</h5>
                        <div class="trip-route">
                            <div class="from-to"><?php echo htmlspecialchars($trip['from_city']); ?></div>
                            <div class="arrow">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="from-to"><?php echo htmlspecialchars($trip['to_city']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Fiyat</h5>
                        <div class="price"><?php echo formatPrice($trip['price']); ?></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-3">
                        <strong>Tarih:</strong><br>
                        <?php echo formatDate($trip['date']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Saat:</strong><br>
                        <?php echo date('H:i', strtotime($trip['time'])); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Firma:</strong><br>
                        <?php echo htmlspecialchars($trip['firm_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Toplam Koltuk:</strong><br>
                        <?php echo $trip['seat_count']; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($canPurchase): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-chair"></i> Koltuk Seçimi</h5>
                </div>
                <div class="card-body">
                    <div class="seat-grid">
                        <?php for ($i = 1; $i <= $trip['seat_count']; $i++): ?>
                            <div class="seat <?php echo in_array($i, $occupiedSeatNumbers) ? 'occupied' : 'available'; ?>" 
                                 data-seat="<?php echo $i; ?>"
                                 <?php echo in_array($i, $occupiedSeatNumbers) ? 'title="Dolu"' : 'title="Boş"'; ?>>
                                <?php echo $i; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div id="selected-seat-info" class="mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span id="seat-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <?php if ($canPurchase): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-shopping-cart"></i> Bilet Satın Al</h5>
                </div>
                <div class="card-body">
                    <form id="purchase-form" method="POST" action="/purchase-ticket.php">
                        <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                        <input type="hidden" name="seat_no" id="seat_no" value="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Seçilen Koltuk:</label>
                            <div id="selected-seat-display" class="form-control-plaintext">Koltuk seçiniz</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fiyat:</label>
                            <div class="form-control-plaintext">
                                <span id="base-price"><?php echo formatPrice($trip['price']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="coupon-code" class="form-label">Kupon Kodu (Opsiyonel)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="coupon-code" name="coupon_code" 
                                       placeholder="Kupon kodu girin">
                                <button type="button" class="btn btn-outline-secondary" id="validate-coupon-btn">
                                    Doğrula
                                </button>
                            </div>
                            <div id="coupon-result" class="mt-2" style="display: none;"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Son Fiyat:</label>
                            <div class="form-control-plaintext">
                                <strong id="final-price"><?php echo formatPrice($trip['price']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mevcut Kredi:</label>
                            <div class="form-control-plaintext">
                                <span class="text-success"><?php echo formatPrice($user['credit']); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success" id="purchase-btn" disabled>
                                <i class="fas fa-credit-card"></i> Bilet Satın Al
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <?php if (!$auth->isLoggedIn()): ?>
                        <h5>Giriş Gerekli</h5>
                        <p>Bilet satın almak için giriş yapmanız gerekmektedir.</p>
                        <a href="/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </a>
                    <?php else: ?>
                        <h5>Yetki Yok</h5>
                        <p>Bu işlem için uygun yetkiniz bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seats = document.querySelectorAll('.seat.available');
    const seatNoInput = document.getElementById('seat_no');
    const selectedSeatDisplay = document.getElementById('selected-seat-display');
    const selectedSeatInfo = document.getElementById('selected-seat-info');
    const seatText = document.getElementById('seat-text');
    const purchaseBtn = document.getElementById('purchase-btn');
    const validateCouponBtn = document.getElementById('validate-coupon-btn');
    const couponCodeInput = document.getElementById('coupon-code');
    const couponResult = document.getElementById('coupon-result');
    const finalPriceElement = document.getElementById('final-price');
    
    let originalPrice = <?php echo json_encode($trip['price']); ?>;
    let currentPrice = originalPrice;
    
    seats.forEach(seat => {
        seat.addEventListener('click', function() {
            // Önceki seçimi temizle
            seats.forEach(s => s.classList.remove('selected'));
            
            // Yeni seçimi yap
            this.classList.add('selected');
            
            const seatNumber = this.dataset.seat;
            seatNoInput.value = seatNumber;
            selectedSeatDisplay.textContent = `Koltuk ${seatNumber}`;
            seatText.textContent = `Seçilen koltuk: ${seatNumber}`;
            
            selectedSeatInfo.style.display = 'block';
            purchaseBtn.disabled = false;
        });
    });
    
    validateCouponBtn.addEventListener('click', function() {
        const couponCode = couponCodeInput.value.trim();
        
        if (!couponCode) {
            couponResult.innerHTML = '<div class="alert alert-warning">Kupon kodu giriniz</div>';
            couponResult.style.display = 'block';
            return;
        }
        
        validateCouponBtn.disabled = true;
        validateCouponBtn.textContent = 'Doğrulanıyor...';
        
        fetch('/api/validate-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `code=${encodeURIComponent(couponCode)}&trip_id=<?php echo $tripId; ?>`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentPrice = data.new_price;
                finalPriceElement.innerHTML = `<strong>${data.new_price.toFixed(2)} ₺</strong> <small class="text-muted">(${data.discount_percent}% indirim)</small>`;
                couponResult.innerHTML = `<div class="alert alert-success">Kupon geçerli! ${data.discount_percent}% indirim uygulandı.</div>`;
            } else {
                couponResult.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
            couponResult.style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            couponResult.innerHTML = `<div class="alert alert-danger">Kupon doğrulanırken bir hata oluştu: ${error.message}</div>`;
            couponResult.style.display = 'block';
        })
        .finally(() => {
            validateCouponBtn.disabled = false;
            validateCouponBtn.textContent = 'Doğrula';
        });
    });
});
</script>

<?php  ?>
