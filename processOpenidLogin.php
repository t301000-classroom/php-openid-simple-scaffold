<?php

require_once './bootstrap.php';

// 已登入則導回首頁
if (isLogined()) {
    header('Location: /');
    exit();
}

$openid = new LightOpenID(SITE_URL . $_SERVER['REQUEST_URI']);

switch ($openid->mode) {
    // 使用者同意
    case 'id_res':
        $_SESSION['user'] = getUserData($openid);

        $redirectTo = (isset($_SESSION['target_url']))
            ? $_SESSION['target_url'] : OpenidConfig::$redirectTo;

        unset($_SESSION['target_url']);

        header('Location: ' . $redirectTo);
        exit();
        break;

    // 使用者取消認證
    case 'cancel':
        header('Location: ./login.php');
        exit();
        break;

    // 啟動 OpenID 認證流程
    default:
        startOpenidAuth($openid);
}


/**
 * 取得 OpenID 資料
 *
 * 取得之原始資料範例：
 *
 * $openid->identity
 * string(36) "https://openid.ntpc.edu.tw/user/xxxxx"
 *
 * $openid->getAttributes()
 * array(9) {
 *     ["namePerson/friendly"]=>
 *     string(9) "王小明"
 *     ["contact/email"]=>
 *     string(21) "xxxx@apps.ntpc.edu.tw"
 *     ["namePerson"]=>
 *     string(9) "王小明"
 *     ["birthDate"]=>
 *     string(10) "1983-06-25"
 *     ["person/gender"]=>
 *     string(1) "M"
 *     ["contact/postalCode/home"]=>
 *     string(64) "5EE2EFCE20722348C2E27AA5E21F60FE69F811651068288F6F7F264BAF4620FB"
 *     ["contact/country/home"]=>
 *     string(12) "xx國中"
 *     ["pref/language"]=>
 *     string(6) "000000"
 *     ["pref/timezone"]=>
 *     string(116) "[{"id":"014579","name":"新北市立xx國民中學","role":"教師","title":"專任教師","groups":["導師"]}]"
 * }
 *
 * @param LightOpenID $openid
 *
 * @return null
 */
function getUserData(LightOpenID $openid)
{
    $user_data = null;

    if ($openid->validate()) {
        // Notice: Only variables should be passed by reference
        // http://stackoverflow.com/questions/4636166/only-variables-should-be-passed-by-reference
        // $user_data['openid_username'] = end(array_values(explode('/', $openid->identity)));

        $identity_array = array_values(explode('/', $openid->identity));
        $user_data['openid_username'] = end($identity_array);

        $attr = $openid->getAttributes();

        $user_data['id_code'] = $attr['contact/postalCode/home'];
        $user_data['real_name'] = $attr['namePerson'];
        $user_data['nick_name'] = $attr['namePerson/friendly'];
        $user_data['gender'] = ($attr['person/gender'] == 'M') ? '男' : '女';
        $user_data['birthday'] = $attr['birthDate'];
        $user_data['email'] = $attr['contact/email'];
        $user_data['org_name_short'] = $attr['contact/country/home'];
        $user_data['grade'] = substr($attr['pref/language'], 0, 2);
        $user_data['class'] = substr($attr['pref/language'], 2, 2);
        $user_data['num'] = substr($attr['pref/language'], 4, 2);
        foreach (json_decode($attr['pref/timezone']) as $item) {
            $user_data['auth_info'][$item->id] = [
                'org_name' => $item->name,
                'role' => $item->role,
                'title' => $item->title,
                'groups' => $item->groups
            ];
        }
        // var_dump($user_data);
    }

    return $user_data;
}

/**
 * 啟動 OpenID 認證流程
 *
 * @param LightOpenID $openid
 */
function startOpenidAuth(LightOpenID $openid)
{
    $openid->identity = 'http://openid.ntpc.edu.tw/';
    $openid->required = OpenidConfig::$required;

    header('Location: ' . $openid->authUrl());
    exit();
}
