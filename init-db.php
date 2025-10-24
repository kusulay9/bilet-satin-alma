<?php

require_once 'includes/config.php';

echo "Veritabanı başlatılıyor...\n";

try {
    $db = new Database();
    

    $sql = file_get_contents(__DIR__ . '/db/init.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read init.sql file');
    }
    

    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->getConnection()->exec($statement);
        }
    }
    
    echo "Veritabanı başarıyla kuruldu!\n";
    echo "Uygulamaya erişebilirsiniz.\n";
    echo "Giriş bilgileri:\n";
    echo "- Admin: admin@sbilet.com / admin123\n";
    echo "- Firma Admin: metro@metro.com.tr / admin123\n";
    echo "- Kullanıcı: ahmet@outlook.com / user123\n";
    
} catch (Exception $e) {
    echo "Veritabanı kurulumunda hata: " . $e->getMessage() . "\n";
    exit(1);
}
?>
