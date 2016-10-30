<?php

require_once '../bootstrap.php';

use JasonGrimes\Paginator;

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
    global $smarty, $mysqli;

    // 取得資料總筆數
    $result = $mysqli->query("SELECT count(id) FROM users");
    $totalItems = ($result->fetch_row())[0];
    $result->close();
    // 每頁幾筆
    $itemsPerPage = 10;
    // 目前頁數
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 1;
    // 搜尋關鍵字
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    // paginator 的 url pattern
    $urlPattern = generatePaginatorUrlPattern($search);
    // 取得 paginator
    $paginator = generatePaginator($totalItems, $itemsPerPage, $currentPage, $urlPattern, 7);

    // 取得當頁資料
    $offset = ($currentPage-1) * $itemsPerPage;
    $users = getUsers($offset, $itemsPerPage);

    $smarty->assign('users', $users);
    $smarty->assign('paginator', $paginator);
    $smarty->display('admin/users.html');
}

/**
 * 產生分頁物件所需之 url pattern
 *
 * @param $search
 *
 * @return string
 */
function generatePaginatorUrlPattern($search = '')
{
    $urlPattern = $_SERVER['PHP_SELF'];
    $urlPattern .= ($search) ? "?search={$search}&" : '?';
    $urlPattern .= 'page=(:num)';

    return $urlPattern;
}

/**
 * 產生分頁 Paginator
 *
 * @param        $totalItems
 * @param        $itemsPerPage
 * @param        $currentPage
 * @param        $urlPattern
 *
 * @param int    $maxPagesToShow
 * @param string $previousText
 * @param string $nextText
 *
 * @return Paginator
 */
function generatePaginator(
    $totalItems,
    $itemsPerPage,
    $currentPage,
    $urlPattern,
    $maxPagesToShow = 10,
    $previousText = '',
    $nextText = ''
) {
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $paginator->setMaxPagesToShow($maxPagesToShow);
    $paginator->setPreviousText($previousText);
    $paginator->setNextText($nextText);

    return $paginator;
}

/**
 * 取得帳號陣列
 *
 * @param int $offset
 * @param int $limit
 *
 * @return array
 */
function getUsers($offset = 0, $limit = 10)
{
    global $mysqli;
    $users=[];

    $search = (isset($_GET['search'])) ? trim($_GET['search']) : '';

    $sql = "SELECT 
              id, username, real_name, is_admin, openid_data
            FROM users ";
    $sql .= ($search) ? "WHERE username LIKE ? OR real_name LIKE ? " : '';
    $sql .= "ORDER BY id LIMIT ? OFFSET ?";
    if ($stmt = $mysqli->prepare($sql)) {
        if ($search) {
            $search = '%' . $search . '%';
            $stmt->bind_param('ssdd', $search, $search, $limit, $offset);
        } else {
            $stmt->bind_param('dd', $limit, $offset);
        }

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

    header('Location: ' . generateRedirectUrl($_SERVER['PHP_SELF'], cleanQueryString(['op', 'id'])));
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

    header('Location: ' . generateRedirectUrl($_SERVER['PHP_SELF'], cleanQueryString(['op', 'id'])));
    exit();
}
