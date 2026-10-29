<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/lib/customer_auth.php';
$pdo = require __DIR__ . '/database.php';

if (customer_user_id() !== null) {
    header('Location: bookings.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare(
        "SELECT userID, username, email, password_hash FROM users WHERE username = :username AND role = 'user' LIMIT 1"
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['userID'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        header('Location: bookings.php');
        exit;
    }
    $error = 'Those customer credentials are not recognised.';
}

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Customer login — The Marquee</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-[#f5f0e8] text-[#191716]">
<main class="mx-auto flex min-h-screen max-w-md items-center px-6">
    <form method="post" class="w-full bg-white/70 p-8 shadow-xl">
        <p class="text-xs uppercase tracking-[.3em] text-[#b43a2d]">The Marquee</p><h1 class="mt-2 font-serif text-4xl font-bold">Customer login</h1>
        <?php if ($error): ?><p class="mt-5 border-l-2 border-[#b43a2d] bg-red-50 p-3 text-sm"><?= $e($error) ?></p><?php endif; ?>
        <label class="mt-6 block text-xs uppercase tracking-widest">Username<input required name="username" class="mt-2 w-full border-b border-stone-400 bg-transparent p-2 outline-none"></label>
        <label class="mt-5 block text-xs uppercase tracking-widest">Password<input required type="password" name="password" class="mt-2 w-full border-b border-stone-400 bg-transparent p-2 outline-none"></label>
        <button class="mt-8 w-full bg-[#211b1a] px-5 py-3 text-xs uppercase tracking-widest text-[#f8e8bd]">Sign in</button>
        <a href="admin/setup.php?role=user" class="mt-5 block text-center text-xs uppercase tracking-widest text-[#b43a2d]">Create a customer account</a>
    </form>
</main>
</body>
</html>
