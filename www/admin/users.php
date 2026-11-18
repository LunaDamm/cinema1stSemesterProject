<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$users = $pdo->query("SELECT userID, username, email FROM users WHERE role = 'user' ORDER BY username")->fetchAll();
$csrf = e($_SESSION['csrf_token']);

render_admin_header('Users', 'users');
?>
<section>
    <div class="mb-4 flex items-end justify-between">
        <h2 class="font-serif text-3xl font-bold">User accounts</h2>
        <span class="text-xs uppercase tracking-widest text-stone-500"><?= count($users) ?> users</span>
    </div>
    <form method="post" action="actions.php" class="grid gap-3 bg-white/60 p-6 md:grid-cols-4">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="user_create">
        <input name="username" required maxlength="20" pattern="[A-Za-z0-9_]{3,20}" placeholder="Username" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="email" required type="email" placeholder="Email" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="password" required minlength="12" type="password" placeholder="Password (12+ characters)" class="border border-stone-300 bg-transparent p-3 text-sm">
        <button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Create user</button>
    </form>
    <div class="mt-4 space-y-3">
        <?php foreach ($users as $user): ?>
        <form method="post" action="actions.php" class="grid gap-3 bg-white/60 p-4 md:grid-cols-[1fr_2fr_1fr_auto_auto]">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="user_update">
            <input type="hidden" name="user_id" value="<?= (int) $user['userID'] ?>">
            <input name="username" required maxlength="20" pattern="[A-Za-z0-9_]{3,20}" value="<?= e($user['username']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
            <input name="email" required type="email" value="<?= e($user['email']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
            <input name="password" minlength="12" type="password" placeholder="New password" class="border-b border-stone-300 bg-transparent p-2 text-sm">
            <button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
            <button formnovalidate name="action" value="user_delete" class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
        </form>
        <?php endforeach; ?>
    </div>
</section>
<?php render_admin_footer(); ?>
