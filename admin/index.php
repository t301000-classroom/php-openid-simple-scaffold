<?php

require_once '../bootstrap.php';

if (!isLogined()) {
    $_SESSION['target_url'] = $_SERVER['PHP_SELF'];
    $_SESSION['messages'] = [
        ['type' => 'danger', 'data' => '請先登入']
    ];
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

if (!$_SESSION['user']['is_admin']) {
    $_SESSION['messages'] = [
        ['type' => 'danger', 'data' => '權限不足']
    ];
    header('Location: ' . SITE_URL);
    exit();
}

$smarty->display('admin/index.html');
