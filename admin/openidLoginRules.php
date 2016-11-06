<?php
require_once '../bootstrap.php';

// authGuard($_SERVER['PHP_SELF']);
// 只允許 admin 進入
adminOnly($_SERVER['PHP_SELF']);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

switch ($op) {
    case 'addRule':
        showRuleForm();
        break;

    case 'createRule':
        $rule = collectData($_POST['rule']);
        createRule($rule);
        break;

    case 'editRule':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $rule = getSingleRuleById($id);
        showRuleForm($rule);
        break;

    case 'updateRule':
        $rule = collectData($_POST['rule']);
        updateRule($rule);
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
 * 以 id 取得一條規則
 *
 * @param $id
 *
 * @return null|array
 */
function getSingleRuleById($id)
{
    global $mysqli;

    $rule = null;

    $sql = "SELECT school_id, rule, priority FROM openid_rules WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($school_id, $rule_db, $priority);
        $stmt->fetch();

        $rule = json_decode($rule_db, true);

        // 將值為陣列的轉為逗點分隔字串
        foreach ($rule as $key => $value) {
            if (is_array($value)) {
                $rule[$key] = implode(',', $value);
            }
        }

        // 取得缺少的元素 key 名
        $fields = ['name', 'role', 'title', 'groups'];
        $fieldsToFill = array_diff($fields, array_keys($rule));
        // 將缺少的欄位補為空字串
        foreach ($fieldsToFill as $field) {
            $rule[$field] = '';
        }
        // 補上其他欄位
        $rule['id'] = $id;
        $rule['schoolId'] = $school_id;
        $rule['priority'] = $priority;
    }

    return $rule;
}

/**
 * 顯示新增 / 編輯規則表單
 *
 * @param null|array $rule
 */
function showRuleForm($rule = null)
{
    global $smarty;

    if (is_null($rule)) {
        $rule = [
            // 'id' => 1,
            'schoolId' => '',
            'name' => '',
            'role' => '',
            'title' => '',
            'groups' => '',
            'priority' => 1
        ];
    }

    $smarty->assign('rule', $rule);
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
    $fields = ['id', 'schoolId', 'name', 'role', 'title', 'groups', 'priority'];
    // 允許多個值的欄位
    $multiValuesFields = ['role', 'title', 'groups'];
    $rule = [];

    foreach ($fields as $field) {
        if (empty($data[$field])) {
            continue;
        }

        $value = trim($data[$field]);
        if (in_array($field, $multiValuesFields) and substr_count($value, ',') > 0) {
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
    unset($rule['schoolId']);
    unset($rule['priority']);
    $rule = json_encode($rule);

    $sql = "INSERT INTO openid_rules (school_id, rule, priority) VALUES (?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssi', $schoolId, $rule, $priority);
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
 * 執行更新規則
 *
 * @param $rule
 */
function updateRule($rule)
{
    global $mysqli;

    $id = (int)$rule['id'];
    $schoolId = isset($rule['schoolId']) ? $rule['schoolId'] : '*';
    $priority = isset($rule['priority']) ? (int)$rule['priority'] : 1;
    unset($rule['id']);
    unset($rule['schoolId']);
    unset($rule['priority']);
    $rule = json_encode($rule);

    $sql = "UPDATE openid_rules SET school_id = ?, rule = ?, priority = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssii', $schoolId, $rule, $priority, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['messages'] = [
            ['type' => 'success', 'data' => '規則已更新']
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
