<?php
/**
 * MarvelStore v1.0 — Application Configuration Template
 * Rename this file to config.php and fill in your credentials.
 */

// ── Error Reporting (Set to 1 for Dev, 0 for Production) ────────────────
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ── Application ────────────────────────────────────────────────────────
define('APP_NAME', 'MarvelStore');
define('APP_VERSION', '1.0');

// Base URL — auto-detects localhost subdirectory vs production domain root
$_base = (
    isset($_SERVER['HTTP_HOST']) &&
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)
) ? '/Store_Management/' : '/';
define('BASE_URL', $_base);

// ── Database ───────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// ── Paths ──────────────────────────────────────────────────────────────
define('ROOT_PATH', __DIR__ . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('OTIKA_ASSETS', BASE_URL . 'assets/');

// ── Security ───────────────────────────────────────────────────────────
define('SESSION_NAME', 'MARVELSTORE_SID');
define('CSRF_TOKEN_NAME', 'csrf_token');
