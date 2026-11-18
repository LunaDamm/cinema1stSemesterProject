<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$movies = $pdo->query('SELECT movieID, title, release_date, rating FROM movies ORDER BY title')->fetchAll();
$csrf = e($_SESSION['csrf_token']);

render_admin_header('Movies', 'movies');
?>
<section>
    <div class="mb-4 flex items-end justify-between">
        <h2 class="font-serif text-3xl font-bold">Movies</h2>
        <span class="text-xs uppercase tracking-widest text-stone-500"><?= count($movies) ?> titles</span>
    </div>
    <form method="post" action="actions.php" class="mb-5 grid gap-3 bg-white/60 p-6 md:grid-cols-4">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="movie_save">
        <input name="title" required placeholder="Title" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="release_date" required type="date" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="rating" type="number" min="0" max="10" step=".1" placeholder="Rating" class="border border-stone-300 bg-transparent p-3 text-sm">
        <button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Add movie</button>
    </form>
    <div class="grid gap-3 md:grid-cols-2">
        <?php foreach ($movies as $movie): ?>
        <form method="post" action="actions.php" class="flex flex-wrap items-center gap-2 bg-white/60 p-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="movie_save">
            <input type="hidden" name="movie_id" value="<?= (int) $movie['movieID'] ?>">
            <input name="title" value="<?= e($movie['title']) ?>" class="min-w-40 flex-1 border-b border-stone-300 bg-transparent p-2 text-sm">
            <input name="release_date" type="date" value="<?= e($movie['release_date']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
            <input name="rating" type="number" min="0" max="10" step=".1" value="<?= e($movie['rating']) ?>" class="w-20 border-b border-stone-300 bg-transparent p-2 text-sm">
            <button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
            <button formnovalidate name="action" value="movie_delete" class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
        </form>
        <?php endforeach; ?>
    </div>
</section>
<?php render_admin_footer(); ?>
