<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$invoices = $pdo->query('SELECT * FROM invoices ORDER BY issued_at DESC')->fetchAll();
$csrf = e($_SESSION['csrf_token']);

render_admin_header('Invoices', 'invoices');
?>
<section>
    <h2 class="mb-4 font-serif text-3xl font-bold">Invoice creator</h2>
    <form method="post" action="actions.php" class="grid gap-3 bg-white/60 p-6 md:grid-cols-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="invoice_create">
        <input name="invoice_number" required placeholder="Invoice number" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="customer_name" required placeholder="Customer name" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="customer_email" required type="email" placeholder="Customer email" class="border border-stone-300 bg-transparent p-3 text-sm">
        <input name="amount" required type="number" min="0" step=".01" placeholder="Amount" class="border border-stone-300 bg-transparent p-3 text-sm">
        <select name="status" class="border border-stone-300 bg-transparent p-3 text-sm"><option>draft</option><option>sent</option><option>paid</option><option>void</option></select>
        <textarea name="notes" placeholder="Notes" class="border border-stone-300 bg-transparent p-3 text-sm"></textarea>
        <button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white md:col-span-3">Create invoice</button>
    </form>
    <div class="mt-4 overflow-x-auto bg-white/60">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-300 text-xs uppercase tracking-widest"><tr><th class="p-4">Number</th><th class="p-4">Customer</th><th class="p-4">Amount</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
            <tr class="border-b border-stone-200">
                <td class="p-4"><?= e($invoice['invoice_number']) ?></td>
                <td class="p-4"><?= e($invoice['customer_name']) ?><br><span class="text-xs text-stone-500"><?= e($invoice['customer_email']) ?></span></td>
                <td class="p-4">$<?= e($invoice['amount']) ?></td>
                <td class="p-4">
                    <form method="post" action="actions.php" class="flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="invoice_update">
                        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoiceID'] ?>">
                        <select name="status" class="border-b border-stone-300 bg-transparent text-sm">
                            <?php foreach (['draft', 'sent', 'paid', 'void'] as $status): ?><option <?= $invoice['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                        </select>
                        <button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
                    </form>
                </td>
                <td class="p-4">
                    <form method="post" action="actions.php">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="invoice_delete">
                        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoiceID'] ?>">
                        <button class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_admin_footer(); ?>
