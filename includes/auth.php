<?php
/**
 * MarvelStore v1.0 — Authentication & Session Management
 */

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

start_session();

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user('role'), $roles, true)) {
        http_response_code(403);
        $base = defined('BASE_URL') ? BASE_URL : '/';
        $otika = defined('OTIKA_ASSETS') ? OTIKA_ASSETS : $base . 'assets/';
        die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>403 — Access Denied</title>
        <link rel="stylesheet" href="' . $otika . 'css/app.min.css">
        <link rel="stylesheet" href="' . $otika . 'css/style.css">
        <link rel="stylesheet" href="' . $otika . 'css/components.css">
        </head><body><div class="container mt-5"><div class="page-error"><div class="page-inner">
        <h1>403</h1><div class="page-description">Access Denied</div>
        <div class="page-search"><p class="text-muted">You do not have permission to view this page.<br>Your role (<strong>' . htmlspecialchars(current_user('role') ?? 'unknown') . '</strong>) is not authorized for this action.</p>
        <div class="mt-3"><a href="' . $base . 'index.php" class="btn btn-primary">Back to Dashboard</a></div>
        </div></div></div></div></body></html>');
    }
}

function login_user(array $user): void {
    // Regenerate session ID on production for security (session fixation protection)
    // Skipped on Windows XAMPP localhost due to file-locking bug causing 0-byte sessions
    if (!str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_user(?string $key = null): mixed {
    if ($key) {
        // Map 'id' to 'user_id' since session stores it as 'user_id'
        if ($key === 'id') $key = 'user_id';
        return $_SESSION[$key] ?? null;
    }
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role'      => $_SESSION['role']      ?? null,
    ];
}
