<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$news = $pdo->query('SELECT newsID, title, body, is_published, image_path FROM news ORDER BY published_at DESC')->fetchAll();
$csrf = e($_SESSION['csrf_token']);

render_admin_header('News', 'news');
?>
<section>
    <h2 class="mb-4 font-serif text-3xl font-bold">News</h2>
    <form method="post" action="actions.php" enctype="multipart/form-data" class="grid gap-3 bg-white/60 p-6 md:grid-cols-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="news_save">
        <input name="news_title" required placeholder="Headline" class="border border-stone-300 bg-transparent p-3 text-sm">
        <textarea name="news_body" required placeholder="News text" class="border border-stone-300 bg-transparent p-3 text-sm"></textarea>
        <label class="text-xs uppercase tracking-widest">
            Image (JPEG, PNG, GIF or WebP; max 2MB)
            <input type="file" name="news_image" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-2 block w-full text-xs">
        </label>
        <label class="text-xs uppercase tracking-widest">
            <span class="flex items-center gap-2"><input type="checkbox" name="is_published" checked> Published</span>
            <button class="mt-4 bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Add news</button>
        </label>
    </form>
    <div class="mt-4 space-y-3">
        <?php foreach ($news as $item): ?>
        <form method="post" action="actions.php" enctype="multipart/form-data" class="grid gap-3 bg-white/60 p-4 md:grid-cols-[1fr_2fr_auto_auto]">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="news_save">
            <input type="hidden" name="news_id" value="<?= (int) $item['newsID'] ?>">
            <input name="news_title" required value="<?= e($item['title']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
            <textarea name="news_body" required class="border-b border-stone-300 bg-transparent p-2 text-sm"><?= e($item['body']) ?></textarea>
            <span>
                <?php if (!empty($item['image_path'])): ?>
                <img src="/<?= e($item['image_path']) ?>" alt="" class="mb-2 h-20 w-28 object-cover">
                <?php endif; ?>
                <input type="file" name="news_image" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full text-xs">
            </span>
            <label class="flex items-center gap-2 text-xs uppercase tracking-widest"><input type="checkbox" name="is_published" <?= $item['is_published'] ? 'checked' : '' ?>> Published</label>
            <span>
                <button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
                <button formnovalidate name="action" value="news_delete" class="ml-3 text-xs uppercase tracking-widest text-stone-500">Delete</button>
            </span>
        </form>
        <?php endforeach; ?>
    </div>
</section>
<?php render_admin_footer(); ?>
