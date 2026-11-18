<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials/layout.php';

$bookings = $pdo->query(
    'SELECT b.bookingID, b.created_at, b.seat_row, b.seat_number, b.price_paid, u.username, u.email, m.title, st.starts_at
     FROM bookings b JOIN users u ON u.userID = b.userID JOIN showtimes st ON st.showtimeID = b.showtimeID
     JOIN movies m ON m.movieID = st.movieID ORDER BY b.created_at DESC LIMIT 100'
)->fetchAll();

render_admin_header('Bookings', 'bookings');
?>
<section>
    <h2 class="mb-4 font-serif text-3xl font-bold">Booking overview</h2>
    <div class="overflow-x-auto bg-white/60">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-300 text-xs uppercase tracking-widest"><tr><th class="p-4">Customer</th><th class="p-4">Movie / showing</th><th class="p-4">Seat</th><th class="p-4">Paid</th><th class="p-4">Booked</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr class="border-b border-stone-200">
                <td class="p-4"><?= e($booking['username']) ?><br><span class="text-xs text-stone-500"><?= e($booking['email']) ?></span></td>
                <td class="p-4"><?= e($booking['title']) ?><br><span class="text-xs text-stone-500"><?= e($booking['starts_at']) ?></span></td>
                <td class="p-4">Row <?= (int) $booking['seat_row'] ?> / <?= (int) $booking['seat_number'] ?></td>
                <td class="p-4">$<?= e($booking['price_paid']) ?></td>
                <td class="p-4"><?= e($booking['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_admin_footer(); ?>
