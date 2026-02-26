<?php
/**
 * MarvelStore v1.0 — Logout Handler
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';

log_activity('logout', 'user', current_user('id'), "User '" . current_user('username') . "' logged out");
logout_user();
redirect('login.php');
