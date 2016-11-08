<?php

use Carbon\Carbon;

require_once './bootstrap.php';

guestOnly();

// dd($_SESSION['tmpOpenidUserData']);

// 正式 code
$openidUserData = $_SESSION['tmpOpenidUserData'];

/* mock code */
/*$user_data = [
    'openid_username' => 'openiduser',
    'id_code' => '5EE2EFCE20722348C2E27AA5E21F60FE69FA11651069288F6F6F264BAF4620FB',
    'real_name' => '王小明',
    'nick_name' => '王小明',
    'gender' => '男',
    'birthday' => '1973-08-14',
    'email' => 'xxxxxx@apps.ntpc.edu.tw',
    'schoolNameShort' => '中正國中',
    'grade' => '00',
    'class' => '00',
    'num' => '00',
    'auth_info' => [
        [
            'id' => '014568',
            'name' => '新北市立中正國民中學',
            'role' => '教師',
            'title' => '專任教師',
            'groups' => ['導師']
        ],
        [
            'id' => '014568',
            'name' => '新北市立中正國民中學',
            'role' => '家長',
            'title' => '專任教師',
            'groups' => ['教官']
        ],
        [
            'id' => '014569',
            'name' => '新北市立xx國民中學',
            'role' => '教師',
            'title' => '專任教師',
            'groups' => ['導師']
        ]
    ]
];
$openidUserData = $user_data;*/
/* mock code end */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 多筆授權資訊，選擇後
    $index = (int)$_POST['index'];
    $authInfo = $openidUserData['auth_info'][$index];
} else {
    if (count($openidUserData['auth_info']) > 1) {
        // 多筆授權資訊
        // 顯示選擇身份頁面
        showSelectUser($openidUserData['auth_info']);
        exit();
    } else {
        // 只有一筆授權資訊
        // $schoolId = key($openidUserData['auth_info']);
        // $authInfo = $openidUserData['auth_info'][$schoolId];
        $authInfo = $openidUserData['auth_info'][0];
    }
}
$schoolId = $authInfo['id'];

// dd(compact('schoolId', 'authInfo'));

/* 至此已取得 一筆 校代碼與相應之授權資訊 */
/**
 * example:
 * $authInfo = [
 *      'id' => '014569',
 *      'name' => '新北市立xx國民中學',
 *      'role' => '教師',
 *      'title' => '專任教師',
 *      'groups' => ['導師']
 *  ]
 */
// dd(compact('schoolId', 'authInfo'));
unset($_SESSION['tmpOpenidUserData']);

/* 檢查是否允許登入 */
if (!canLogin($schoolId, $authInfo)) {
    // 不允許登入，轉回登入頁
    $_SESSION['messages'] = [
        ['type' => 'danger', 'data' => '不允許登入']
    ];
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
    // 取得規則
    $rules = getRulesBySchoolId($schoolId);

    if (empty($rules)) {
        // 若為空陣列則直接回傳 false 拒絕登入
        return false;
    }

    // 檢查登入規則
    $result = check($rules, $authInfo);

    return $result;
}

/**
 * 以校代碼取得登入規則，包含不限制校代碼的規則（排在後面）
 *
 * @param $schoolId
 *
 * @return array
 */
function getRulesBySchoolId($schoolId)
{
    global $mysqli;

    $rules = [];
    // note: school_id DESC 是為了將 school_id 為 *（不限制）的紀錄排到後面
    $sql = sprintf("SELECT school_id, rule FROM openid_rules WHERE school_id IN ('*', %s) ORDER BY school_id DESC, priority DESC", $schoolId);
    $result = $mysqli->query($sql);

    while ($rule = $result->fetch_assoc()) {
        $rule['rule'] = json_decode($rule['rule'], true);
        $rules[] = $rule;
    }

    return $rules;
}

/**
 * 檢查登入條件，first match
 *
 * @param $rules
 * @param $authInfo
 *
 * @return bool
 */
function check($rules, $authInfo)
{
    $result = false;

    foreach ($rules as $rule) {
        $condition = $rule['rule'];
        if (empty($condition)) {
            // 條件為空，表示除了校代碼外不檢查其餘條件，回傳 true 允許登入
            return true;
        }

        // 條件不為空，進行檢查
        // 一旦檢查通過，$result=true，中止檢查
        foreach ($condition as $field => $limit) {
            if (is_string($limit)) {
                // 條件值為字串，則轉成陣列以利搜尋
                $limit = [$limit];
            }
            // 取得 user authInfo 某欄位值
            $search = $authInfo[$field];
            if (is_array($search)) {
                // 欄位值為陣列，取交集
                $result = count(array_intersect($limit, $search)) > 0;
            } else {
                // 欄位值為字串
                $result = in_array($search, $limit);
            }

            if ($result) {
                // 通過檢查，跳出 2 層
                break 2;
            }
        }
    }

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
    global $mysqli, $authInfo;

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
        updateUserOpenidData($user['id'], $authInfo);
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
    global $mysqli, $schoolId, $authInfo;

    $user = null;
    $sql = "INSERT INTO users (username, real_name, password, openid_data, created_at) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $birthday = password_hash(str_replace('-', '', $data['birthday']), PASSWORD_DEFAULT);
        // $openid_data = json_encode([$schoolId => $authInfo]);
        $openid_data = json_encode($authInfo);
        $created_at = Carbon::now();

        $stmt->bind_param(
            'sssss',
            $data['openid_username'],
            $data['real_name'],
            $birthday,
            $openid_data,
            $created_at
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
        $auth_info = json_encode($auth_info);
        $stmt->bind_param('sd', $auth_info, $id);
        $stmt->execute();
        $stmt->close();
    }
}
