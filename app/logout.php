<?php

require_once './bootstrap.php';

session_destroy();
// unset($_SESSION['user']);

// 登出後轉回首頁
header('Location: ' . SITE_URL);
exit();
