<?php
// ============================================================
// MIS Shop - Logout Handler
// ============================================================

require_once __DIR__ . '/includes/auth.php';

// Unset all sessions
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a new session just to show a flash message
session_start();
setFlash('success', 'Logged out successfully.');
header('Location: index.php');
exit;
