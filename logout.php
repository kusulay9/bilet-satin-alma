<?php
require_once 'includes/config.php';

$auth->logout();
flashMessage('Başarıyla çıkış yaptınız.', 'success');
redirect('/');
?>
