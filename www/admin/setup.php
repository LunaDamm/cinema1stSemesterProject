<?php
declare(strict_types=1);

session_start();
$pdo = require __DIR__ . '/../database.php';

if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0) {
    header('Location: login.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
        $error = 'Use a 3-20 character username, a valid email, and a password of at least 12 characters.';
    } else {
        try {
            $statement = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'admin')"
            );
            $statement->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php');
            exit;
        } catch (PDOException $exception) {
            $error = 'That username or email is already in use.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Create owner — The Marquee</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-[#f5f0e8] text-[#191716]">
<main class="mx-auto flex min-h-screen max-w-md items-center px-6">
    <form method="post" class="w-full bg-white/70 p-8 shadow-xl">
        <p class="text-xs uppercase tracking-[.3em] text-[#b43a2d]">First-time setup</p><h1 class="mt-2 font-serif text-4xl font-bold">Create owner</h1>
        <?php if ($error): ?><p class="mt-5 border-l-2 border-[#b43a2d] bg-red-50 p-3 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <label class="mt-6 block text-xs uppercase tracking-widest">Username<input required name="username" maxlength="20" class="mt-2 w-full border-b border-stone-400 bg-transparent p-2 outline-none"></label>
        <label class="mt-5 block text-xs uppercase tracking-widest">Email<input required type="email" name="email" class="mt-2 w-full border-b border-stone-400 bg-transparent p-2 outline-none"></label>
        <label class="mt-5 block text-xs uppercase tracking-widest">Password<input required minlength="12" type="password" name="password" class="mt-2 w-full border-b border-stone-400 bg-transparent p-2 outline-none"></label>
        <button class="mt-8 w-full bg-[#211b1a] px-5 py-3 text-xs uppercase tracking-widest text-[#f8e8bd]">Create account</button>
    </form>
</main>
</body>
</html>
