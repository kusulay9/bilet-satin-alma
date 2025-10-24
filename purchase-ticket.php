<?php
require_once 'includes/config.php';

$auth->requireRole('user');
$db = new Database();
$user = $auth->getUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}


if (!validateCSRF()) {
    flashMessage('Güvenlik hatası. Lütfen tekrar deneyin.', 'error');
    redirect('/');
}

$tripId = (int)($_POST['trip_id'] ?? 0);
$seatNo = (int)($_POST['seat_no'] ?? 0);
$couponCode = $auth->sanitizeInput($_POST['coupon_code'] ?? '');

if (!$tripId || !$seatNo) {
    flashMessage('Geçersiz sefer veya koltuk bilgisi.', 'error');
    redirect('/');
}


$trip = $db->fetchOne(
    "SELECT * FROM trips WHERE id = ?",
    [$tripId]
);

if (!$trip) {
    flashMessage('Sefer bulunamadı.', 'error');
    redirect('/');
}


$existingTicket = $db->fetchOne(
    "SELECT id FROM tickets WHERE trip_id = ? AND seat_no = ? AND status = 'active'",
    [$tripId, $seatNo]
);

if ($existingTicket) {
    flashMessage('Bu koltuk zaten satılmış.', 'error');
    redirect("/trip-details.php?id=$tripId");
}


$finalPrice = $trip['price'];
$couponId = null;
$discountAmount = 0;
$couponCodeUsed = '';

if ($couponCode) {
    $coupon = $db->fetchOne(
        "SELECT * FROM coupons WHERE code = ? AND expiry_date >= date('now') AND (usage_limit IS NULL OR usage_limit > 0) AND (firm_id = ? OR is_global = 1)",
        [$couponCode, $trip['firm_id']]
    );
    
    if ($coupon) {
        $discountAmount = ($trip['price'] * $coupon['discount_percent']) / 100;
        $finalPrice = $trip['price'] - $discountAmount;
        $couponId = $coupon['id'];
        $couponCodeUsed = $couponCode;
    }
}


if ($user['credit'] < $finalPrice) {
    flashMessage('Yetersiz kredi. Mevcut krediniz: ' . formatPrice($user['credit']), 'error');
    redirect("/trip-details.php?id=$tripId");
}

try {
    $db->beginTransaction();
    

    $ticketId = $db->insert('tickets', [
        'user_id' => $user['id'],
        'trip_id' => $tripId,
        'seat_no' => $seatNo,
        'price' => $finalPrice,
        'original_price' => $trip['price'],
        'discount_amount' => $discountAmount,
        'coupon_code' => $couponCodeUsed,
        'status' => 'active'
    ]);
    

    $newCredit = $user['credit'] - $finalPrice;
    $db->update('users', ['credit' => $newCredit], 'id = ?', [$user['id']]);
    

    if ($couponId) {
        $db->query(
            "UPDATE coupons SET usage_limit = usage_limit - 1 WHERE id = ? AND usage_limit > 0",
            [$couponId]
        );
    }
    

    $db->insert('logs', [
        'user_id' => $user['id'],
        'action' => 'ticket_purchase',
        'details' => "Trip ID: $tripId, Seat: $seatNo, Price: $finalPrice"
    ]);
    
    $db->commit();
    

    $_SESSION['user_credit'] = $newCredit;
    
    flashMessage('Bilet başarıyla satın alındı!', 'success');
    redirect("/user/ticket.php?id=$ticketId");
    
} catch (Exception $e) {
    $db->rollback();
    error_log('Ticket purchase error: ' . $e->getMessage());
    flashMessage('Bilet satın alma sırasında bir hata oluştu.', 'error');
    redirect("/trip-details.php?id=$tripId");
}
?>
