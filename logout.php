<?php

require_once './bootstrap.php';

unset($_SESSION['user']);

header('Location: /');
exit();
