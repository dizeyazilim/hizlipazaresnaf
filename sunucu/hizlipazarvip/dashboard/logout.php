<?php
require_once 'config.php';

session_unset();
session_destroy();
setcookie('login_email', '', time() - 3600, '/', '', true, true);

header('Location: ' . BASE_URL . '/login.php');
exit;
?>