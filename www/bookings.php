<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/lib/customer_auth.php';
require_once __DIR__ . '/lib/BookingHistoryService.php';

$userId = require_customer_login();
$pdo = require __DIR__ . '/database.php';
$bookings = (new BookingHistoryService($pdo))->forUser($userId);

$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$formatShowtime = static fn (string $startsAt): string => date(
    'D, M j, Y · g:i A',
    strtotime($startsAt)
);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>My bookings — The Marquee</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-[#f5f0e8] text-[#191716]">
<main class="mx-auto max-w-5xl px-6 py-12">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-300 pb-6">
        <div>
            <p class="text-xs uppercase tracking-[.3em] text-[#b43a2d]">The Marquee</p>
            <h1 class="mt-2 font-serif text-4xl font-bold">My bookings</h1>
        </div>
        <nav class="flex gap-4 text-xs uppercase tracking-widest">
            <a href="/" class="hover:text-[#b43a2d]">Programme</a>
            <a href="logout.php" class="hover:text-[#b43a2d]">Sign out</a>
        </nav>
    </header>

    <?php if (!$bookings): ?>
        <p class="mt-10 bg-white/70 p-6 text-sm">
            You have no bookings yet. Choose a film from the programme to reserve your seats.
        </p>
    <?php endif; ?>

    <div class="mt-8 space-y-3">
        <?php foreach ($bookings as $booking): ?>
            <article class="bg-white/70 p-6">
                <div class="flex flex-wrap justify-between gap-4">
                    <h2 class="font-serif text-2xl font-bold"><?= $escape($booking['title']) ?></h2>
                    <strong>#<?= (int) $booking['bookingID'] ?></strong>
                </div>
                <p class="mt-2 text-sm">
                    <?= $escape($formatShowtime((string) $booking['starts_at'])) ?>
                    · <?= $escape($booking['theater']) ?>
                </p>
                <p class="mt-3 text-xs uppercase tracking-widest text-stone-500">
                    Row <?= (int) $booking['seat_row'] ?>
                    / Seat <?= (int) $booking['seat_number'] ?>
                    · $<?= $escape($booking['price_paid']) ?>
                </p>
            </article>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
