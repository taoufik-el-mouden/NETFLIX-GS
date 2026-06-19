<?php
// ============================================================
// logout.php — Destroy session and redirect to login
// ============================================================
require_once 'config/auth.php';

// Destroy all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect to login with a goodbye message
header('Location: login.php?logged_out=1');
exit;
