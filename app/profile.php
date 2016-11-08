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
        $op = isset($_POST['op']) ? $_POST['op'] : null;
        // 進行密碼變更程序
        process($op);
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

    // $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    // if ($queryString) {
    //     parse_str($queryString, $queryArray);
    //     unset($queryArray['id']);
    //     $_SERVER['QUERY_STRING'] = http_build_query($queryArray);
    // }
    $_SERVER['QUERY_STRING'] = cleanQueryString(['id']);

    $userData = getUserDataById($id);
    $smarty->assign('userData', $userData);
    $smarty->display('profile.html');
}

/**
 * 進行密碼變更程序
 */
function process($op)
{
    switch ($op) {
        case 'changePassword':
            processChangePassword();
            break;
        case 'changeUserData':
            processChangeUserData();
            break;
        default:
            die();
    }
}

/**
 * 進行更改密碼程序
 */
function processChangePassword()
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

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

/**
 * 進行更改 user data 程序
 */
function processChangeUserData()
{
    $data['real_name'] = $_POST['realName'];

    changeUserData($data, $_POST['id']);

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

/**
 * 執行 user data 變更
 *
 * @param array $data
 * @param       $id
 */
function changeUserData(array $data, $id)
{
    global $mysqli;

    $sql = "UPDATE users SET real_name = ?, updated_at = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param(
            'sss',
            $data['real_name'],
            Carbon::now(),
            $id
        );
        $stmt->execute();
        $stmt->close();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '資料已更新']
        ];
    }
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
