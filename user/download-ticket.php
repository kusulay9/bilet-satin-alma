<?php
require_once '../includes/config.php';
require_once '../includes/pdfgenerator.php';

$auth->requireRole('user');
$db = new Database();
$user = $auth->getUser();

$ticketId = (int)($_GET['id'] ?? 0);

if (!$ticketId) {
    die('Geçersiz bilet ID.');
}


$ticket = $db->fetchOne(
    "SELECT t.*, tr.from_city, tr.to_city, tr.date, tr.time, f.name as firm_name
     FROM tickets t
     JOIN trips tr ON t.trip_id = tr.id
     JOIN firms f ON tr.firm_id = f.id
     WHERE t.id = ? AND t.user_id = ?",
    [$ticketId, $user['id']]
);

if (!$ticket) {
    die('Bilet bulunamadı.');
}


$pdf = new PDFGenerator();


$pdf->addText('OTOBUS BILETI', 200, 750, 20)
    ->addText(SITE_NAME, 250, 720, 12)
    ->addLine(50, 700, 562, 700)
    ->newLine(20);


$pdf->addText($ticket['from_city'] . ' -> ' . $ticket['to_city'], 200, 650, 16)
    ->addRect(50, 630, 512, 40)
    ->newLine(50);


$pdf->addText('Bilet No: TKT' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT), 50, 600, 12)
    ->addText('Koltuk No: ' . $ticket['seat_no'], 50, 580, 12)
    ->addText('Tarih: ' . formatDate($ticket['date']), 50, 560, 12)
    ->addText('Saat: ' . date('H:i', strtotime($ticket['time'])), 50, 540, 12)
    ->addText('Firma: ' . $ticket['firm_name'], 50, 520, 12);

if ($ticket['discount_amount'] > 0) {
    $pdf->addText('Indirimsiz Fiyat: ' . formatPrice($ticket['original_price']), 50, 500, 12)
        ->addText('Indirim: -' . formatPrice($ticket['discount_amount']), 50, 480, 12)
        ->addText('Kupon: ' . $ticket['coupon_code'], 50, 460, 12)
        ->addText('Odenecek Toplm Tutar: ' . formatPrice($ticket['price']), 50, 440, 12)
        ->addText('Yolcu Adı: ' . $user['name'], 50, 400, 12)
        ->addText('E-posta: ' . $user['email'], 50, 380, 12);
} else {
    $pdf->addText('Fiyat: ' . formatPrice($ticket['price']), 50, 500, 12)
        ->addText('Yolcu Adı: ' . $user['name'], 50, 460, 12)
        ->addText('E-posta: ' . $user['email'], 50, 440, 12);
}


if ($ticket['discount_amount'] > 0) {
    $pdf->addText('Indirimsiz Fiyat: ' . formatPrice($ticket['original_price']), 50, 500, 12)
        ->addText('Indirim: -' . formatPrice($ticket['discount_amount']), 50, 480, 12)
        ->addText('Kupon: ' . $ticket['coupon_code'], 50, 460, 12)
        ->addText('Odenecek Toplm Tutar: ' . formatPrice($ticket['price']), 50, 440, 12)
        ->addText('Yolcu Adı: ' . $user['name'], 50, 400, 12)
        ->addText('E-posta: ' . $user['email'], 50, 380, 12);
} else {
    $pdf->addText('Fiyat: ' . formatPrice($ticket['price']), 50, 500, 12)
        ->addText('Yolcu Adı: ' . $user['name'], 50, 460, 12)
        ->addText('E-posta: ' . $user['email'], 50, 440, 12);
}

$pdf->addText('Bu bilet ' . formatDateTime($ticket['purchase_time']) . ' tarihinde satin alinmistir.', 50, 200, 10)
    ->addText('Biletinizi seyahat sirasinda yaninizda bulundurunuz.', 50, 180, 10)
    ->addText('Gecerli kimlik belgenizi yaninizda bulundurunuz.', 50, 160, 10);


$safeFilename = 'bilet_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $ticketId) . '.pdf';
$pdf->output($safeFilename);
?>
