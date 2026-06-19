<?php
// ============================================================
// config/auth.php — Session & Authentication Guard
// Include this file at the TOP of every protected page
// ============================================================

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if the user is NOT logged in.
 * Call this on every protected page.
 */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        // Save the page the user tried to visit so we can redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Returns the currently logged-in user's data from the session.
 */
function current_user(): array
{
    return [
        'id'        => $_SESSION['user_id']        ?? 0,
        'username'  => $_SESSION['user_username']  ?? '',
        'full_name' => $_SESSION['user_full_name'] ?? 'مستخدم',
        'role'      => $_SESSION['user_role']      ?? 'admin',
    ];
}

/**
 * Returns true if a user is currently logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}
