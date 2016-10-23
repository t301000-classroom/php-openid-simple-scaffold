<?php

require_once './bootstrap.php';

// 已登入則導回首頁
if (isLogined()) {
    header('Location: /');
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $smarty->display('login.html');
        break;

    case 'POST';
        // 處理本機登入
        break;
}