<?php
require_once '../includes/config.php';

$auth->requireRole('user');
$db = new Database();
$user = $auth->getUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/dashboard.php');
}

if (!validateCSRF()) {
    flashMessage('Güvenlik hatası. Lütfen tekrar deneyin.', 'error');
    redirect('/user/dashboard.php');
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);

if (!$ticketId) {
    flashMessage('Geçersiz bilet ID.', 'error');
    redirect('/user/dashboard.php');
}


$ticket = $db->fetchOne(
    "SELECT t.*, tr.date, tr.time
     FROM tickets t
     JOIN trips tr ON t.trip_id = tr.id
     WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'",
    [$ticketId, $user['id']]
);

if (!$ticket) {
    flashMessage('Bilet bulunamadı veya zaten iptal edilmiş.', 'error');
    redirect('/user/dashboard.php');
}


$tripDateTime = $ticket['date'] . ' ' . $ticket['time'];
if (isTimeCloseToDeparture($tripDateTime)) {
    flashMessage('Kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez.', 'error');
    redirect('/user/dashboard.php');
}

try {
    $db->beginTransaction();
    

    $db->update('tickets', ['status' => 'cancelled'], 'id = ?', [$ticketId]);
    

    $newCredit = $user['credit'] + $ticket['price'];
    $db->update('users', ['credit' => $newCredit], 'id = ?', [$user['id']]);
    
    $db->commit();
    

    $_SESSION['user_credit'] = $newCredit;
    
    flashMessage('Bilet başarıyla iptal edildi. ' . formatPrice($ticket['price']) . ' kredinize iade edildi.', 'success');
    
} catch (Exception $e) {
    $db->rollback();
    error_log('Ticket cancellation error: ' . $e->getMessage());
    flashMessage('Bilet iptali sırasında bir hata oluştu.', 'error');
}

redirect('/user/dashboard.php');
?>
