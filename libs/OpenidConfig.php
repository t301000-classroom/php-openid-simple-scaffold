<?php

// require_once './bootstrap.php';

class OpenidConfig
{
    public static $redirectTo = SITE_URL;

    // OpenID 要求資料欄位
    public static $required = array(
        'namePerson/friendly', // 暱稱
        'contact/email', // email
        'namePerson', // 姓名
        'birthDate', // 生日，1985-06-12
        'person/gender', // 性別，M 男
        'contact/postalCode/home', // 識別碼
        'contact/country/home', // 單位簡稱，xx國中
        'pref/language', // 年級班級座號，6 碼
        'pref/timezone' // 授權資訊，含單位代碼、單位全銜、職務別、職稱別、身份別等資料，可能有多筆
    );
}
