<?php

require_once '../bootstrap.php';

// authGuard($_SERVER['PHP_SELF']);
// 只允許 admin 進入
adminOnly($_SERVER['PHP_SELF']);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : null;

switch ($op) {
    case 'deleteUser':
        deleteUser((int)$_GET['id']);
        break;

    case 'toggleAdmin':
        toggleAdmin((int)$_GET['id']);
        break;

    default:
        listUsers();
}


/**
 * 顯示帳號列表
 */
function listUsers()
{
    global $smarty;
    $users = getUsers();

    $smarty->assign('users', $users);
    $smarty->display('admin/users.html');
}

/**
 * 取得帳號陣列
 *
 * @return array
 */
function getUsers()
{
    global $mysqli;
    $users=[];

    $sql = "SELECT id, username, real_name, is_admin, openid_data FROM users ORDER BY id";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->execute();
        $stmt->bind_result($id, $username, $realName, $isAdmin, $openid_data);

        while ($stmt->fetch()) {
            $isOpenid = ($openid_data) ? true : false;
            $users[] = compact('id', 'username', 'realName', 'isAdmin', 'isOpenid');
        }

        $stmt->close();
    }

    return $users;
}

/**
 * 切換管理員權限
 *
 * @param $id user id
 */
function toggleAdmin($id)
{
    global $mysqli;

    $user = getUserDataById($_GET['id']);
    $is_admin = !$user['is_admin'];

    $sql = "UPDATE users SET is_admin = ? WHERE id = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('dd', $is_admin, $id);
        $stmt->execute();
        $stmt->close();

        $msg = ($is_admin) ? ' 已成為管理員' : ' 已取消管理權限';

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => $user['username'] . $msg]
        ];
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

/**
 * 刪除帳號
 *
 * @param $id user id
 */
function deleteUser($id)
{
    global $mysqli;

    $user = getUserDataById($_GET['id']);

    $sql = "DELETE FROM users WHERE id = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('d', $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '帳號已刪除： ' . $user['username']]
        ];
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}