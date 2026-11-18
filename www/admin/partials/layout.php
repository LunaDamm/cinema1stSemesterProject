<?php
declare(strict_types=1);

function admin_sections(): array
{
    return [
        'index' => 'Overview',
        'company' => 'Company information',
        'movies' => 'Movies',
        'showings' => 'Showings',
        'news' => 'News',
        'bookings' => 'Bookings',
        'invoices' => 'Invoices',
        'users' => 'Users',
    ];
}

function render_admin_header(string $title, string $active): void
{
    $sections = admin_sections();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - The Marquee</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f5f0e8] text-[#191716]">
<header class="bg-[#211b1a] px-6 py-5 text-[#f8e8bd]">
    <div class="mx-auto flex max-w-7xl items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-[.3em] text-[#d5a447]">The Marquee</p>
            <h1 class="font-serif text-3xl font-bold"><?= e($title) ?></h1>
        </div>
        <a href="logout.php" class="text-xs uppercase tracking-widest hover:text-[#d5a447]">Sign out</a>
    </div>
</header>
<main class="mx-auto max-w-7xl space-y-10 px-6 py-10">
<?php if ($message): ?>
    <p class="border-l-2 border-[#b43a2d] bg-white/70 p-4 text-sm"><?= e($message) ?></p>
<?php endif; ?>
<nav class="flex flex-wrap gap-2 border-b border-stone-300 pb-5" aria-label="Dashboard sections">
<?php foreach ($sections as $key => $label): ?>
    <a href="<?= $key === 'index' ? 'index.php' : $key . '.php' ?>"
       class="<?= $active === $key ? 'bg-[#211b1a] text-[#f8e8bd]' : 'bg-white/60' ?> px-4 py-3 text-xs uppercase tracking-widest">
        <?= e($label) ?>
    </a>
<?php endforeach; ?>
</nav>
    <?php
}

function render_admin_footer(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
