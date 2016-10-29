<?php

/**
 * 以 id 取得 user data
 *
 * @param $id
 *
 * @return array
 */
function getUserDataById($id)
{
    global $mysqli;

    $data = [];
    $sql = "SELECT id, username, real_name, openid_data, is_admin FROM users WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('d', $id);
        $stmt->bind_result($data['id'], $data['username'], $data['real_name'], $openidData, $data['is_admin']);
        $stmt->execute();
        $stmt->fetch();

        // convert json to associative array
        $data['openid_data'] = json_decode($openidData, true);
    }

    return $data;
}
