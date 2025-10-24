<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Istanbul');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

define('SITE_NAME', 'SBilet');
define('SITE_URL', 'http://localhost');
define('DB_PATH', '/var/www/html/db/database.sqlite');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

if (!file_exists(DB_PATH)) {
    touch(DB_PATH);
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
$stmt->execute(['trips']);
$tableExists = $stmt->fetchColumn();

if (!$tableExists) {
    $initPath = __DIR__ . '/../db/init.sql';
    if (file_exists($initPath)) {
        $initSql = file_get_contents($initPath);
        try {
            $conn->exec($initSql);
            error_log("✅ Database initialized successfully.");
        } catch (PDOException $e) {
            error_log("❌ Database initialization failed: " . $e->getMessage());
        }
    } else {
        error_log("⚠️ init.sql not found at: $initPath");
    }
}

$auth = new Auth();

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); 
        return $flash;
    }
    return null;
}

function redirect($url){ header("Location: $url"); exit; }
function formatPrice($p){ return number_format($p,2).' ₺'; }
function formatDate($date){ 
    if (!$date) return 'Tarih belirtilmemiş';
    return date('d.m.Y', strtotime($date)); 
}
function formatDateTime($datetime){ 
    if (!$datetime) return 'Tarih belirtilmemiş';
    return date('d.m.Y H:i', strtotime($datetime)); 
}
function isTimeCloseToDeparture($tripDateTime) {
    $departureTime = strtotime($tripDateTime);
    $currentTime = time();
    $timeDifference = $departureTime - $currentTime;
    return $timeDifference < 3600;
}
function flashMessage($message, $type = 'info') { setFlashMessage($message, $type); }
function generateCSRFToken(){ if(!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function validateCSRF(){ return isset($_POST['csrf_token']) && $_POST['csrf_token']===$_SESSION['csrf_token']; }
?>
