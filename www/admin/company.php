<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$settings = array_fill_keys(['company_description', 'opening_hours', 'contact_information'], '');
foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings') as $row) {
    if (array_key_exists($row['setting_key'], $settings)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$csrf = e($_SESSION['csrf_token']);

render_admin_header('Company information', 'company');
?>
<section>
    <h2 class="mb-4 font-serif text-3xl font-bold">Company information</h2>
    <form method="post" action="actions.php" class="grid gap-4 bg-white/60 p-6 md:grid-cols-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="settings">
        <label class="text-xs uppercase tracking-widest">Description
            <textarea name="company_description" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['company_description']) ?></textarea>
        </label>
        <label class="text-xs uppercase tracking-widest">Opening hours
            <textarea name="opening_hours" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['opening_hours']) ?></textarea>
        </label>
        <label class="text-xs uppercase tracking-widest">Contact information
            <textarea name="contact_information" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['contact_information']) ?></textarea>
        </label>
        <button class="bg-[#211b1a] px-4 py-3 text-xs uppercase tracking-widest text-[#f8e8bd] md:col-span-3">Save details</button>
    </form>
</section>
<?php render_admin_footer(); ?>
