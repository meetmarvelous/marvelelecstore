<?php
/**
 * MarvelStore v1.0 — Global Helper Functions
 */

/** Escape output for safe HTML rendering (XSS prevention) */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect to a page relative to BASE_URL */
function redirect(string $path): never {
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Set a flash message in the session */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Render and clear flash message */
function render_flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = e($f['type']);
    $msg  = e($f['message']);
    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">{$msg}<button type=\"button\" class=\"close\" data-dismiss=\"alert\"><span>&times;</span></button></div>";
}

/** Safely get a string from POST/GET */
function input_str(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $_GET[$key] ?? $default);
}

/** Safely get an integer from POST/GET */
function input_int(string $key, int $default = 0): int {
    return (int)($_POST[$key] ?? $_GET[$key] ?? $default);
}

/** Format a number as Naira currency */
function format_naira(float|int|string|null $amount): string {
    return '₦' . number_format((float)($amount ?? 0), 2);
}

/** Get a human-readable time-ago string */
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}

/** Generate a status badge */
function status_badge(string $status): string {
    $map = [
        'pending'   => 'warning',
        'repairing' => 'info',
        'ready'     => 'success',
        'collected' => 'secondary',
    ];
    $color = $map[strtolower($status)] ?? 'dark';
    $label = ucfirst($status);
    return "<span class=\"badge badge-{$color}\">{$label}</span>";
}
