<?php

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && starts_with($value, '"') && ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (! function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}

/********** 自定義輔助函數 **********/

if (! function_exists('isLogined')) {
    /**
     * 檢查是否已登入
     *
     * @return bool
     */
    function isLogined()
    {
        return isset($_SESSION['user']);
    }
}

if (! function_exists('dd')) {
    /**
     * 輸出變數內容
     *
     * @param      $data
     * @param bool $die 是否停止程式執行
     */
    function dd($data, $die = true)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';

        if ($die) {
            die();
        }
    }
}

if (! function_exists('authGuard')) {
    /**
     * 認證防護
     *
     * 未登入則導向登入
     *
     * @param $redirectTo 登入後導向至
     */
    function authGuard($redirectTo = null)
    {
        if (!isLogined()) {
            $_SESSION['target_url'] = $redirectTo;
            $_SESSION['messages'] = [
                ['type' => 'danger', 'data' => '請先登入']
            ];
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
}

if (! function_exists('guestOnly')) {
    /**
     * 只允許 guest
     *
     * 已登入則導回首頁
     */
    function guestOnly()
    {
        if (isLogined()) {
            header('Location: ' . SITE_URL);
            exit();
        }
    }
}

if (! function_exists('adminOnly')) {
    /**
     * 只允許 admin
     *
     * @param $redirectTo 登入後導向至
     */
    function adminOnly($redirectTo)
    {
        authGuard($redirectTo);
        // 權限不足，導回首頁
        if (!$_SESSION['user']['is_admin']) {
            $_SESSION['messages'] = [
                ['type' => 'danger', 'data' => '權限不足']
            ];
            header('Location: ' . SITE_URL);
            exit();
        }
    }
}

/**
 * 移除 query string 中不需要的參數，回傳新的 query string
 *
 * @param array $removeKeys
 *
 * @return string|null
 */
function cleanQueryString(array $removeKeys)
{
    $queryString = null;
    if (isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $queryArray);

        foreach ($removeKeys as $key) {
            unset($queryArray[$key]);
        }

        $queryString = http_build_query($queryArray);
    }

    return $queryString ? $queryString : null;
}

/**
 * 產生重導向之 url or uri
 *
 * @param string $baseUrl
 * @param string $queryString
 *
 * @return string
 */
function generateRedirectUrl($baseUrl = '', $queryString = '')
{
    $redirectTo = $baseUrl ? $baseUrl : $_SERVER['PHP_SELF'];
    if ($queryString) {
        $redirectTo .= '?' . $queryString;
    }

    return $redirectTo;
}
