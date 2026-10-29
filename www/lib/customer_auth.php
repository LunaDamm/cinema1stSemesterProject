<?php
declare(strict_types=1);

function customer_user_id(): ?int
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    return $userId ? (int) $userId : null;
}

function require_customer_login(): int
{
    $userId = customer_user_id();
    if (!$userId) {
        header('Location: login.php');
        exit;
    }

    return $userId;
}
