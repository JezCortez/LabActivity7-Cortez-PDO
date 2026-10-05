<?php
declare(strict_types=1);

/**
 * includes/helpers.php — session, auth guards, CSRF, flash messages, escaping.
 * Required by every page (before any output is sent).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/** Escape output (use on EVERY echo of user data to prevent XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/* ---------- Authentication ---------- */

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION['user_id'] : null;
}

/** GUEST pages (login / register): logged-in users are sent to the feed. */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect('index.php');
    }
}

/** AUTHENTICATED pages: guests are sent to the login page. */
function require_auth(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/* ---------- CSRF protection ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/* ---------- Flash messages (one-time notices after a redirect) ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Misc ---------- */

function format_date(?string $datetime): string
{
    return $datetime ? date('M j, Y · g:i A', strtotime($datetime)) : '';
}

/** Read a positive integer id from $_GET, or null if missing/invalid. */
function get_id(string $key = 'id'): ?int
{
    $id = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return ($id === false || $id === null) ? null : $id;
}
