<?php

require_once './bootstrap.php';

if (!isLogined()) {
    $_SESSION['target_url'] = $_SERVER['PHP_SELF'];
    $_SESSION['messages'] = [
        ['type' => 'danger', 'data' => '請先登入']
    ];
    header('Location: ./login.php');
    exit();
}

$smarty->display('profile.html');
