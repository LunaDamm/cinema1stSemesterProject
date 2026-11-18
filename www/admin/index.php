<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$counts = [
    'movies' => (int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn(),
    'showings' => (int) $pdo->query('SELECT COUNT(*) FROM showtimes')->fetchColumn(),
    'news' => (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    'bookings' => (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'invoices' => (int) $pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
];

render_admin_header('Owner dashboard', 'index');
?>
<section>
    <h2 class="mb-5 font-serif text-3xl font-bold">Overview</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($counts as $section => $count): ?>
        <a href="<?= e($section) ?>.php" class="block bg-white/60 p-6">
            <p class="text-xs uppercase tracking-widest text-stone-500"><?= e(ucfirst($section)) ?></p>
            <p class="mt-2 font-serif text-4xl font-bold"><?= $count ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php render_admin_footer(); ?>
