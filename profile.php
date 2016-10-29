<?php

use Carbon\Carbon;

require_once './bootstrap.php';

// 需先登入，登入後導回
authGuard($_SERVER['PHP_SELF']);

// 只允許使用本機帳號登入者使用
if ($_SESSION['user']['type'] !== 'local') {
    header('Location: ' . SITE_URL);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // 顯示頁面
        showPage();
        break;

    case 'POST':
        // 進行密碼變更程序
        process();
        break;
}


/********** 函數區 **********/

/**
 * 顯示頁面
 */
function showPage()
{
    global $smarty;

    $id = ($_SESSION['user']['is_admin'] && isset($_GET['id'])) ? $_GET['id'] : $_SESSION['user']['id'];

    $userData = getUserDataById($id);
    $smarty->assign('userData', $userData);
    $smarty->display('profile.html');
}

/**
 * 進行密碼變更程序
 */
function process()
{
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if ($newPassword !== $confirmPassword) {
        $_SESSION['messages'] = [
            ['type' => 'danger', 'data' => '兩次密碼不一致']
        ];
    } else {
        // 兩次密碼符合，執行密碼變更
        changePassword($newPassword, $_POST['id']);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}


/**
 * 執行密碼變更
 *
 * @param $password  new password
 * @param $id               user id
 *
 * @internal param $mysqli
 */
function changePassword($password, $id)
{
    global $mysqli;
    $sql = "UPDATE users SET password = ?, updated_at = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param(
            'sss',
            password_hash($password, PASSWORD_DEFAULT),
            Carbon::now(),
            $id
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '密碼已更新']
        ];
    }
}
