<?php
session_start();
define('BASE_URL', 'https://hizlipazaresnaf.com//hizlipazarvip/dashboard');
define('APP_URL', 'https://hizlipazaresnaf.com//hizlipazarvip');
define('DASHBOARD_URL', BASE_URL . '/dashboard');
define('DB_HOST', 'localhost');
define('DB_USER', 'hizlivip');
define('DB_PASS', '181200@63Aa');
define('DB_NAME', 'hizlivip');
define('SITE_NAME', 'Hızlı Pazar Esnaf');
define('COOKIE_EXPIRE', 30 * 24 * 60 * 60); // 30 days for "Remember Me"


// Prevent redirect loop
$current_script = basename($_SERVER['PHP_SELF']);
if (
    $current_script !== 'login.php' &&
    $current_script !== 'auth.php' &&
    (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor']))
) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

?>
