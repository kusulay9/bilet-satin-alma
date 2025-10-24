<?php
$turkishCities = [
    'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Amasya', 'Ankara', 'Antalya', 'Artvin',
    'Aydın', 'Balıkesir', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa',
    'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Edirne', 'Elazığ', 'Erzincan',
    'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkâri', 'Hatay', 'Isparta',
    'Mersin', 'İstanbul', 'İzmir', 'Kars', 'Kastamonu', 'Kayseri', 'Kırklareli', 'Kırşehir',
    'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Kahramanmaraş', 'Mardin', 'Muğla',
    'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Rize', 'Sakarya', 'Samsun', 'Siirt',
    'Sinop', 'Sivas', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Şanlıurfa', 'Uşak',
    'Van', 'Yozgat', 'Zonguldak', 'Aksaray', 'Bayburt', 'Karaman', 'Kırıkkale', 'Batman',
    'Şırnak', 'Bartın', 'Ardahan', 'Iğdır', 'Yalova', 'Karabük', 'Kilis', 'Osmaniye',
    'Düzce'
];

setlocale(LC_COLLATE, 'tr_TR.UTF-8', 'tr_TR', 'turkish');
usort($turkishCities, function($a, $b) {
    return strcoll($a, $b);
});

if (!setlocale(LC_COLLATE, 'tr_TR.UTF-8')) {
    $turkishOrder = [
        'A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'Ğ', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 
        'N', 'O', 'Ö', 'P', 'Q', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'V', 'W', 'X', 'Y', 'Z'
    ];
    
    usort($turkishCities, function($a, $b) use ($turkishOrder) {
        $aFirst = mb_strtoupper(mb_substr($a, 0, 1, 'UTF-8'), 'UTF-8');
        $bFirst = mb_strtoupper(mb_substr($b, 0, 1, 'UTF-8'), 'UTF-8');
        
        $aIndex = array_search($aFirst, $turkishOrder);
        $bIndex = array_search($bFirst, $turkishOrder);
        
        if ($aIndex === false) $aIndex = 999;
        if ($bIndex === false) $bIndex = 999;
        
        if ($aIndex === $bIndex) {
            return strcmp($a, $b);
        }
        
        return $aIndex - $bIndex;
    });
}
?>
