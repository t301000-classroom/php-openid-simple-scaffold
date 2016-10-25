<?php

require_once './bootstrap.php';

// 已登入則導回首頁
if (isLogined()) {
    header('Location: ' . SITE_URL);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $credential = isset($_SESSION['credential'])
            ? $_SESSION['credential'] : ['username' => '', 'password' => ''];
        unset($_SESSION['credential']);
        $smarty->assign('credential', $credential);
        $smarty->display('login.html');
        break;

    case 'POST':
        // 處理本機登入
        // dd($_POST, true);
        $credential = [
            'username' => $_POST['username'],
            'password' => $_POST['password']
        ];
        $user = login($credential);
        if (empty($user)) {
            $_SESSION['messages'] = [
                ['type' => 'danger', 'data' => '帳號或密碼錯誤']
            ];
            $_SESSION['credential'] = $credential;
            $redirectTo = $_SERVER['PHP_SELF'];
        } else {
            $_SESSION['user'] = $user;
            $redirectTo = (isset($_SESSION['target_url']))
                ? $_SESSION['target_url'] : $_SERVER['PHP_SELF'];

            unset($_SESSION['target_url']);
        }

        header('Location: ' . $redirectTo);
        exit();

        break;
}

/**
 * 本機登入
 *
 * @param array $credential
 *
 * @return array
 */
function login(array $credential)
{
    global $mysqli;
    $user = [];

    $sql = "SELECT id, real_name, is_admin, password FROM users WHERE username = ? LIMIT 1";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('s', $credential['username']);
        $stmt->execute();
        $stmt->bind_result($user['id'], $user['real_name'], $user['is_admin'], $hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($credential['password'], $hashedPassword)) {
            $user = [];
        }
    }

    return $user;
}
