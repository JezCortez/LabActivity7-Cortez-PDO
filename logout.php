<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';

// Logging out is a state change → POST + CSRF only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    session_start(); // fresh session just to carry the flash message
    flash('success', 'You have been logged out.');
}

redirect('login.php');
