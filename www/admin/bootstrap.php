<?php
declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/../database.php';

if (empty($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function verify_csrf(): void
{
    if (
        !hash_equals(
            $_SESSION['csrf_token'] ?? '',
            (string) ($_POST['csrf_token'] ?? '')
        )
    ) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}

function redirect_admin(string $message = ''): never
{
    if ($message !== '') {
        $_SESSION['flash'] = $message;
    }
    header('Location: index.php');
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
