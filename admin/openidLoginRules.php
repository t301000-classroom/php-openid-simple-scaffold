<?php
require_once '../bootstrap.php';

// authGuard($_SERVER['PHP_SELF']);
// 只允許 admin 進入
adminOnly($_SERVER['PHP_SELF']);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

switch ($op) {
    case 'addRule':
        showAddRuleForm();
        break;

    case 'createRule':
        $rule = collectData($_POST['rule']);
        createRule($rule);
        break;

    case 'deleteRule':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        deleteRule($id);
        break;

    default:
        showAllRules();
}


/********** 函數區 **********/

/**
 * 顯示規則列表
 */
function showAllRules()
{
    global $smarty;

    $rules = getAllOpenidLoginRules();

    $smarty->assign('rules', $rules);
    $smarty->display('admin/openid-login-rules.html');
}

/**
 * 取得所有規則
 *
 * @return array|mixed
 */
function getAllOpenidLoginRules()
{
    global $mysqli;

    $rules = [];
    $sql = "SELECT id, school_id, rule, priority FROM openid_rules ORDER BY school_id, priority DESC, id";
    if ($result = $mysqli->query($sql)) {
        while ($rule = $result->fetch_assoc()) {
            $rule['rule'] = json_decode($rule['rule'], true);
            $rules[] = $rule;
        }
    }

    return $rules;
}

/**
 * 顯示新增規則表單
 */
function showAddRuleForm()
{
    global $smarty;

    $smarty->display('admin/openid-login-rule-form.html');
}

/**
 * 收集規則資料，回傳陣列
 *
 * @param array $data
 *
 * @return array
 */
function collectData(array $data)
{
    $fields = ['schoolId', 'schoolName', 'role', 'title', 'groups', 'priority'];
    $rule = [];

    foreach ($fields as $field) {
        if (empty($data[$field])) {
            continue;
        }

        $value = trim($data[$field]);
        if (substr_count($value, ',') > 0) {
            // 若有 2 個以上的值，則拆成陣列
            $rule[$field] = explode(',', $value);
        } else {
            $rule[$field] = $value;
        }
    }

    return $rule;
}

/**
 * 執行新增規則
 *
 * @param array $rule
 */
function createRule(array $rule)
{
    global $mysqli;

    $schoolId = isset($rule['schoolId']) ? $rule['schoolId'] : '*';
    $priority = isset($rule['priority']) ? (int)$rule['priority'] : 1;
    $rule = json_encode($rule);

    $sql = "INSERT INTO openid_rules (school_id, rule, priority) VALUES (?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssd', $schoolId, $rule, $priority);
        $stmt->execute();
        $stmt->close();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '新增規則完成']
        ];
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

/**
 * 執行刪除規則
 *
 * @param $id
 */
function deleteRule($id)
{
    global $mysqli;

    $sql = "DELETE FROM openid_rules WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '規則已刪除']
        ];
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
