<?php

use Carbon\Carbon;

require_once './bootstrap.php';

guestOnly();

// dd($_SESSION['tmpOpenidUserData']);

// 正式 code
$openidUserData = $_SESSION['tmpOpenidUserData'];

/* mock code */
// $user_data = [
//     'openid_username' => 'openiduser',
//     'id_code' => '5EE2EFCE20722348C2E27AA5E21F60FE69FA11651069288F6F6F264BAF4620FB',
//     'real_name' => '王小明',
//     'nick_name' => '王小明',
//     'gender' => '男',
//     'birthday' => '1973-08-14',
//     'email' => 'xxxxxx@apps.ntpc.edu.tw',
//     'org_name_short' => '中正國中',
//     'grade' => '00',
//     'class' => '00',
//     'num' => '00',
//     'auth_info' => [
//         // '014569' => [
//         //     'org_name' => '新北市立中正國民中學',
//         //     'role' => '教師',
//         //     'title' => '專任教師',
//         //     'groups' => ['導師']
//         // ],
//         '014568' => [
//             'org_name' => '新北市立xx國民中學',
//             'role' => '教師',
//             'title' => '專任教師',
//             'groups' => ['導師']
//         ]
//     ]
// ];
// $openidUserData = $user_data;
/* mock code end */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 多筆授權資訊，選擇後
    $schoolId = $_POST['schoolId'];
    $authInfo = $openidUserData['auth_info'][$schoolId];
} else {
    if (count($openidUserData['auth_info']) > 1) {
        // 多筆授權資訊
        // 顯示選擇身份頁面
        showSelectUser($openidUserData['auth_info']);
        exit();
    } else {
        // 只有一筆授權資訊
        $schoolId = key($openidUserData['auth_info']);
        $authInfo = $openidUserData['auth_info'][$schoolId];
    }
}

/* 至此已取得 一筆 校代碼與相應之授權資訊 */
// dd(compact('schoolId', 'authInfo'));
unset($_SESSION['tmpOpenidUserData']);

/* 檢查是否允許登入 */
if (!canLogin($schoolId)) {
    // 不允許登入，轉回登入頁
    header('Location: ./login.php');
    exit();
}

loginOpenidUser($openidUserData);


/********** 函數區 **********/

/**
 * 登入 openid user 進系統
 *
 * 登入後導向至預設頁面或重導回登入前頁面
 *
 * @param $openidUserData
 */
function loginOpenidUser($openidUserData)
{
    // 允許登入，存入 session
    // 自資料庫取得 user 資料或新建 user
    $_SESSION['user'] = getOrCreateUser($openidUserData);

    // 轉回登入前頁面欲進入之頁面或預設登入後頁面
    $redirectTo = (isset($_SESSION['target_url']))
        ? $_SESSION['target_url'] : OpenidConfig::$redirectTo;

    unset($_SESSION['target_url']);

    header('Location: ' . $redirectTo);
    exit();
}

/**
 * 顯示選擇身份頁面
 *
 * @param $authInfos user's auth info array
 */
function showSelectUser($authInfos)
{
    global $smarty;

    $smarty->assign('authInfos', $authInfos);
    $smarty->display("select-openid-user.html");
}

/**
 * 檢查是否允許登入
 *
 * @param $schoolId 校代碼
 * @param $authInfo 授權資訊
 *
 * @return bool
 */
function canLogin($schoolId, $authInfo)
{
    // 檢查校代碼
    $result = checkSchoolId($schoolId);

    return $result;
}

/**
 * 檢查校代碼
 *
 * @param $schoolId
 *
 * @return bool
 */
function checkSchoolId($schoolId)
{
    // 允許登入之校代碼
    $allowSchoolId = ['014568', '014569'];

    return in_array($schoolId, $allowSchoolId);
}

/**
 * 取得或建立 user
 *
 * @param $data
 *
 * @return array|null
 */
function getOrCreateUser($data)
{
    if (is_null($data)) {
        return null;
    }

    return ($user = getExistOpenidUser($data)) ? $user : createUser($data);
}

/**
 * 取得已存在之 user
 *
 * @param $data
 *
 * @return null|array
 */
function getExistOpenidUser($data)
{
    global $mysqli;

    $user = null;
    $sql = "SELECT id, real_name, is_admin from users where username = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('s', $data['openid_username']);
        $stmt->execute();
        $stmt->bind_result($user['id'], $user['real_name'], $user['is_admin']);
        $stmt->fetch();
        $stmt->close();
    }

    if ($user['id']) {
        updateUserOpenidData($user['id'], $data['auth_info']);
    }

    return $user['id'] ? $user : null;
}

/**
 * 建立 user
 *
 * @param $data
 *
 * @return array|null
 */
function createUser($data)
{
    global $mysqli;

    $user = null;
    $sql = "INSERT INTO users (username, real_name, password, openid_data, created_at) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param(
            'sssss',
            $data['openid_username'],
            $data['real_name'],
            password_hash(str_replace('-', '', $data['birthday']), PASSWORD_DEFAULT),
            json_encode($data['auth_info']),
            Carbon::now()
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        $user = [
            'id' => $id,
            'real_name' => $data['real_name'],
            'is_admin' => 0
        ];
    }

    return $user;
}

/**
 * 更新 user openid_data 欄位
 *
 * @param       $id         user id
 * @param array $auth_info  user's openid auth_info
 */
function updateUserOpenidData($id, array $auth_info)
{
    global $mysqli;

    $sql = "UPDATE users SET openid_data = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('sd', json_encode($auth_info), $id);
        $stmt->execute();
        $stmt->close();
    }
}
