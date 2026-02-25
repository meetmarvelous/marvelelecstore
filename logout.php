<?php
/**
 * MarvelStore v1.0 — Logout Handler
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'helpers.php';

logout_user();
redirect('login.php');
