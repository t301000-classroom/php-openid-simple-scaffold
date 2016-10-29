<?php

require_once '../bootstrap.php';

// authGuard($_SERVER['PHP_SELF']);
//
// // 權限不足，導回首頁
// if (!$_SESSION['user']['is_admin']) {
//     $_SESSION['messages'] = [
//         ['type' => 'danger', 'data' => '權限不足']
//     ];
//     header('Location: ' . SITE_URL);
//     exit();
// }

// 只允許 admin 進入
adminOnly($_SERVER['PHP_SELF']);

$smarty->display('admin/index.html');
