<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
        exit;
    }
    
    $user = $auth->getUser();
    if ($user['role'] !== 'user') {
        echo json_encode(['success' => false, 'message' => 'Bu işlem için uygun yetkiniz yok']);
        exit;
    }
    
    $db = new Database();

    $couponCode = trim(strip_tags($_POST['code'] ?? ''));

    if (!$couponCode) {
        echo json_encode(['success' => false, 'message' => 'Kupon kodu gerekli']);
        exit;
    }

    $tripId = (int)($_POST['trip_id'] ?? 0);

    if (!$tripId) {
        echo json_encode(['success' => false, 'message' => 'Sefer ID gerekli']);
        exit;
    }

    $trip = $db->fetchOne("SELECT * FROM trips WHERE id = ?", [$tripId]);

    if (!$trip) {
        echo json_encode(['success' => false, 'message' => 'Sefer bulunamadı']);
        exit;
    }

    $coupon = $db->fetchOne(
        "SELECT * FROM coupons WHERE code = ? AND expiry_date >= date('now') AND (usage_limit IS NULL OR usage_limit > 0) AND (firm_id = ? OR is_global = 1)",
        [$couponCode, $trip['firm_id']]
    );

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon']);
        exit;
    }

    $discountAmount = ($trip['price'] * $coupon['discount_percent']) / 100;
    $newPrice = $trip['price'] - $discountAmount;

    echo json_encode([
        'success' => true,
        'discount_percent' => $coupon['discount_percent'],
        'discount_amount' => $discountAmount,
        'new_price' => $newPrice,
        'original_price' => $trip['price']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
