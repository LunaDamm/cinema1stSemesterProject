<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$movies = $pdo->query('SELECT movieID, title FROM movies ORDER BY title')->fetchAll();
$theaters = $pdo->query('SELECT theaterID, name FROM theaters ORDER BY name')->fetchAll();
$showtimes = $pdo->query(
    'SELECT st.showtimeID, st.movieID, st.theaterID, st.starts_at, st.price, st.is_featured
     FROM showtimes st ORDER BY st.starts_at DESC LIMIT 40'
)->fetchAll();
$csrf = e($_SESSION['csrf_token']);

render_admin_header('Showings', 'showings');
?>
<section>
    <h2 class="mb-4 font-serif text-3xl font-bold">Showings & daily premieres</h2>
    <form method="post" action="actions.php" class="mb-5 grid gap-3 bg-white/60 p-6 md:grid-cols-5">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="showtime_save">
        <select name="movie_id" required class="border border-stone-300 bg-transparent p-3 text-sm">
            <option value="">Movie</option>
            <?php foreach ($movies as $movie): ?><option value="<?= (int) $movie['movieID'] ?>"><?= e($movie['title']) ?></option><?php endforeach; ?>
        </select>
        <select name="theater_id" required class="border border-stone-300 bg-transparent p-3 text-sm">
            <option value="">Theater</option>
            <?php foreach ($theaters as $theater): ?><option value="<?= (int) $theater['theaterID'] ?>"><?= e($theater['name']) ?></option><?php endforeach; ?>
        </select>
        <input name="starts_at" required type="datetime-local" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="price" required type="number" min="0" placeholder="Price" class="border border-stone-300 bg-transparent p-3 text-sm">
        <label class="flex items-center gap-2 text-xs uppercase tracking-widest"><input type="checkbox" name="is_featured"> Front page premiere</label>
        <button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white md:col-span-5">Add showing</button>
    </form>
    <div class="overflow-x-auto bg-white/60">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-300 text-xs uppercase tracking-widest"><tr><th class="p-4">Movie</th><th class="p-4">Theater</th><th class="p-4">Starts</th><th class="p-4">Price</th><th class="p-4">Premiere</th><th class="p-4"></th></tr></thead>
            <tbody>
            <?php foreach ($showtimes as $showtime): ?>
            <tr class="border-b border-stone-200">
                <td class="p-4" colspan="6">
                    <form method="post" action="actions.php" class="grid items-center gap-2 md:grid-cols-[2fr_2fr_2fr_1fr_1fr_auto]">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="showtime_save">
                        <input type="hidden" name="showtime_id" value="<?= (int) $showtime['showtimeID'] ?>">
                        <select name="movie_id" class="border-b border-stone-300 bg-transparent p-2">
                            <?php foreach ($movies as $movie): ?><option value="<?= (int) $movie['movieID'] ?>" <?= (int) $movie['movieID'] === (int) $showtime['movieID'] ? 'selected' : '' ?>><?= e($movie['title']) ?></option><?php endforeach; ?>
                        </select>
                        <select name="theater_id" class="border-b border-stone-300 bg-transparent p-2">
                            <?php foreach ($theaters as $theater): ?><option value="<?= (int) $theater['theaterID'] ?>" <?= (int) $theater['theaterID'] === (int) $showtime['theaterID'] ? 'selected' : '' ?>><?= e($theater['name']) ?></option><?php endforeach; ?>
                        </select>
                        <input name="starts_at" type="datetime-local" value="<?= e(date('Y-m-d\TH:i', strtotime($showtime['starts_at']))) ?>" class="border-b border-stone-300 bg-transparent p-2">
                        <input name="price" type="number" min="0" value="<?= e($showtime['price']) ?>" class="border-b border-stone-300 bg-transparent p-2">
                        <input type="checkbox" name="is_featured" <?= $showtime['is_featured'] ? 'checked' : '' ?>>
                        <span class="flex gap-3">
                            <button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
                            <button formnovalidate name="action" value="showtime_delete" class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
                        </span>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_admin_footer(); ?>
