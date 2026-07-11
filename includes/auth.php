<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-login from remember me cookie
function autoLoginFromCookie($conn)
{
    if (isLoggedIn()) return;

    $token = $_COOKIE['remember_token'] ?? '';
    if (empty($token)) return;

    $token = mysqli_real_escape_string($conn, $token);
    $result = mysqli_query($conn, "SELECT user_id FROM remember_tokens WHERE token = '$token' AND expires_at > NOW()");
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $uid = $row['user_id'];
        $userResult = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid AND is_banned = 0");
        $user = mysqli_fetch_assoc($userResult);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        }
    }
}

// Only run auto-login if db.php has been loaded (connection exists)
if (isset($conn) && $conn) {
    autoLoginFromCookie($conn);
}

/**
 * Check if any user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is owner/admin
 */
function isOwner()
{
    return (
        isset($_SESSION['role']) &&
        $_SESSION['role'] === 'owner'
    );
}

/**
 * Backward compatibility
 */
function isAdminLoggedIn()
{
    return isOwner();
}

/**
 * Require normal login
 */
function requireLogin()
{
    if (!isLoggedIn()) {

        setFlash('error', 'Please log in first.');

        header('Location: /login.php');
        exit;
    }
}

/**
 * Require owner/admin access
 */
function requireOwner()
{
    if (!isOwner()) {

        setFlash('error', 'Access denied.');

        header('Location: /index.php');
        exit;
    }
}

/**
 * Backward compatibility
 */
function requireAdmin()
{
    requireOwner();
}

/**
 * Flash message setter
 */
function setFlash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Flash message getter
 */
function getFlash($type)
{
    if (isset($_SESSION['flash'][$type])) {

        $message = $_SESSION['flash'][$type];

        unset($_SESSION['flash'][$type]);

        return $message;
    }

    return null;
}

/**
 * Get current logged-in user
 */
function getCurrentUser($conn)
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            id,
            username,
            email,
            role,
            is_banned,
            created_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $_SESSION['user_id']
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);

    // Auto logout banned users
    if ($user && $user['is_banned']) {

        session_destroy();

        header('Location: /login.php');
        exit;
    }

    return $user;
}

/**
 * Logout helper
 */
function logout()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}