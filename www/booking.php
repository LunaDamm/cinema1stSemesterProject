<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/lib/BookingService.php';
require_once __DIR__ . '/lib/customer_auth.php';
require_once __DIR__ . '/lib/BookingPageController.php';

$error = null;
$showtime = null;
$occupiedSeats = [];
try {
    $pdo = require __DIR__ . '/database.php';
    $page = (new BookingPageController($pdo, new BookingService($pdo)))->show([
        'method' => $_SERVER['REQUEST_METHOD'],
        'showtime_id' => $_SERVER['REQUEST_METHOD'] === 'POST'
            ? ($_POST['showtime_id'] ?? null)
            : ($_GET['showtime_id'] ?? null),
        'seats' => $_POST['seats'] ?? null,
        'name' => $_POST['name'] ?? null,
        'email' => $_POST['email'] ?? null,
    ]);
    $showtime = $page['showtime'];
    $occupiedSeats = $page['occupiedSeats'];
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
} catch (PDOException $exception) {
    $error = 'The booking desk is temporarily unavailable. Please try again shortly.';
}

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book a seat — The Marquee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marquee.css">
</head>
<body>
<header class="border-b border-stone-900/15 bg-[#f5f0e8]/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="/" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#b43a2d] text-xl text-[#f8e8bd]">✦</span>
            <span><strong class="serif block text-xl tracking-tight">The Marquee</strong><small class="text-[10px] uppercase tracking-[.3em] text-stone-500">Est. 1927 · Moving pictures</small></span>
        </a>
        <div class="flex items-center gap-3">
            <?php if (customer_user_id() !== null): ?><a href="bookings.php" class="text-xs uppercase tracking-widest hover:text-[#b43a2d]">My bookings</a><?php endif; ?>
            <a href="/" class="rounded-full border border-stone-900 px-4 py-2 text-xs uppercase tracking-widest hover:bg-stone-900 hover:text-[#f5f0e8]">← Programme</a>
        </div>
    </div>
</header>
<main class="mx-auto max-w-7xl px-6 py-20">
    <p class="mb-2 text-xs uppercase tracking-[.3em] text-[#b43a2d]">The booking desk</p>
    <h1 class="serif text-5xl font-bold">Choose your seats.</h1>

    <?php if ($error): ?><div class="mt-7 border-l-2 border-[#b43a2d] bg-[#eadfcb] px-5 py-4 text-sm"><?= $e($error) ?></div><?php endif; ?>

    <?php if ($showtime): ?>
        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_340px]">
            <section>
                <div class="mb-8 border-b border-stone-300 pb-6">
                    <p class="text-xs uppercase tracking-widest text-stone-500"><?= $e($showtime['theater']) ?> · <?= date('D, M j · g:i A', strtotime($showtime['starts_at'])) ?></p>
                    <h2 class="serif mt-2 text-3xl font-bold"><?= $e($showtime['title']) ?></h2>
                    <p class="mt-2 text-sm text-stone-600">$<?= $e($showtime['price']) ?> per seat</p>
                </div>
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-10 max-w-lg border-t-4 border-[#b43a2d] pt-3 text-[10px] uppercase tracking-[.4em] text-stone-500">Screen</div>
                    <form id="booking-form" method="post" action="booking.php?showtime_id=<?= (int) $showtime['showtimeID'] ?>">
                        <input type="hidden" name="showtime_id" value="<?= (int) $showtime['showtimeID'] ?>">
                        <div id="selected-seats"></div>
                        <div class="mx-auto grid max-w-lg gap-3" style="grid-template-columns: repeat(<?= (int) $showtime['seats_per_row'] ?>, minmax(0, 1fr));">
                            <?php for ($row = 1; $row <= (int) $showtime['seat_rows']; $row++): ?>
                                <?php for ($number = 1; $number <= (int) $showtime['seats_per_row']; $number++):
                                    $key = "{$row}-{$number}";
                                    $taken = isset($occupiedSeats[$key]);
                                ?>
                                    <button type="button" class="seat rounded-sm text-xs" data-row="<?= $row ?>" data-number="<?= $number ?>" <?= $taken ? 'disabled aria-label="Seat taken"' : '' ?>><?= $row ?><?= chr(64 + $number) ?></button>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="mt-7 flex justify-center gap-5 text-[10px] uppercase tracking-widest text-stone-500"><span>□ Available</span><span class="text-[#b43a2d]">■ Selected</span><span class="text-stone-400">■ Taken</span></div>
                    </form>
                </div>
            </section>
            <aside class="h-fit bg-[#211b1a] p-7 text-[#f8e8bd]">
                <p class="text-xs uppercase tracking-[.25em] text-[#d5a447]">Your details</p>
                <p id="selection" class="mt-4 border-b border-[#f8e8bd]/20 pb-5 text-sm text-[#f8e8bd]/70">Select one or more seats to continue.</p>
                <div class="mt-6 space-y-4">
                    <label class="block text-xs uppercase tracking-widest">Name<input required name="name" form="booking-form" maxlength="100" value="<?= $e($_SESSION['user_username'] ?? '') ?>" <?= customer_user_id() !== null ? 'readonly' : '' ?> class="mt-2 w-full border-b border-[#f8e8bd]/30 bg-transparent py-2 text-sm outline-none focus:border-[#d5a447]" autocomplete="name"></label>
                    <label class="block text-xs uppercase tracking-widest">Email<input required type="email" name="email" form="booking-form" value="<?= $e($_SESSION['user_email'] ?? '') ?>" <?= customer_user_id() !== null ? 'readonly' : '' ?> class="mt-2 w-full border-b border-[#f8e8bd]/30 bg-transparent py-2 text-sm outline-none focus:border-[#d5a447]" autocomplete="email"></label>
                </div>
                <button id="submit-booking" form="booking-form" disabled class="mt-8 w-full bg-[#d5a447] px-5 py-3 text-xs font-bold uppercase tracking-widest text-[#211b1a] disabled:cursor-not-allowed disabled:opacity-40">Reserve selected seats</button>
                <p class="mt-5 text-[10px] leading-5 text-[#f8e8bd]/50">Your reservation is written directly to the cinema booking system.</p>
            </aside>
        </div>
    <?php endif; ?>
</main>
<script>
    const seats = document.querySelectorAll('.seat:not(:disabled)');
    const selectedSeats = document.querySelector('#selected-seats');
    const selection = document.querySelector('#selection');
    const submit = document.querySelector('#submit-booking');
    const chosen = new Map();
    const renderSelection = () => {
        selectedSeats.replaceChildren();
        chosen.forEach((seat) => {
            [['row', seat.row], ['number', seat.number]].forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `seats[${seat.row}-${seat.number}][${name}]`;
                input.value = value;
                selectedSeats.appendChild(input);
            });
        });
        const labels = [...chosen.values()].map((seat) => `${seat.row}-${seat.number}`);
        selection.textContent = labels.length ? `${labels.length} seat${labels.length === 1 ? '' : 's'} selected: ${labels.join(', ')}` : 'Select one or more seats to continue.';
        submit.disabled = labels.length === 0 || labels.length > 10;
    };
    seats.forEach((seat) => seat.addEventListener('click', () => {
        const key = `${seat.dataset.row}-${seat.dataset.number}`;
        if (chosen.has(key)) {
            chosen.delete(key);
            seat.classList.remove('selected');
        } else if (chosen.size < 10) {
            chosen.set(key, { row: seat.dataset.row, number: seat.dataset.number });
            seat.classList.add('selected');
        }
        renderSelection();
    }));
</script>
</body>
</html>
