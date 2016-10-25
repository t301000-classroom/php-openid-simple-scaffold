<?php

date_default_timezone_set('Asia/Taipei');

require_once __DIR__ . '/vendor/autoload.php';

session_start();

use Dotenv\Dotenv;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();

/* 網站首頁 url */
define('SITE_URL', env('SITE_URL', $_SERVER['HTTP_HOST']));

/* 定義資料庫連線參數 */
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', 3306));
define('DB_DATABASE', env('DB_DATABASE', 'mydb'));
define('DB_CHARSET', env('DB_CHARSET', 'utf-8'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', 'root'));

/* 建立資料庫連線 */
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($mysqli->connect_error) {
    die('無法連上資料庫(' . $mysqli->connect_error . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset(DB_CHARSET);

/* Smarty 設定 */
// 定義 smarty 相關目錄之根路徑
define('SMARTY_ROOT', __DIR__ . '/smarty');
$smarty = new Smarty();
// 設定各 smarty 目錄之路徑
$smarty->setTemplateDir(SMARTY_ROOT . '/templates');
$smarty->setCompileDir(SMARTY_ROOT . '/templates_c');
$smarty->setConfigDir(SMARTY_ROOT . '/configs');
$smarty->setCacheDir(SMARTY_ROOT . '/cache');
// 設定樣板共用變數
// 網站 root url
$smarty->assign('home', SITE_URL);
// 目前登入之使用者資料
$smarty->assign('currUser', isLogined() ? $_SESSION['user'] : null);
// 訊息
$smarty->assign('messages', isset($_SESSION['messages']) ? $_SESSION['messages'] : null);
// if (isset($_SESSION['messages'])) {
//     die(var_dump($_SESSION['messages']));
// }
unset($_SESSION['messages']);
