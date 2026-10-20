<?php
declare(strict_types=1);
session_start();

$confirmation = $_SESSION['booking_confirmation'] ?? null;
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

if (!is_array($confirmation) || empty($confirmation['booking_id'])) {
    header('Location: /');
    exit;
}

$seats = is_array($confirmation['seats'] ?? null) ? $confirmation['seats'] : [];
$seatLabels = array_map(
    static fn (array $seat): string => $seat['row'] . chr(64 + (int) $seat['number']),
    $seats
);
$seatCount = count($seats);
$total = (float) ($confirmation['price'] ?? 0) * $seatCount;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking confirmed — The Marquee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f0e8; color: #191716; font-family: 'DM Mono', monospace; background-image: radial-gradient(rgba(25,23,22,.09) .7px, transparent .7px); background-size: 5px 5px; }
        .serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="min-h-screen">
<header class="border-b border-stone-900/15 bg-[#f5f0e8]/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="/" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#b43a2d] text-xl text-[#f8e8bd]">✦</span>
            <span><strong class="serif block text-xl tracking-tight">The Marquee</strong><small class="text-[10px] uppercase tracking-[.3em] text-stone-500">Est. 1927 · Moving pictures</small></span>
        </a>
        <a href="/" class="rounded-full border border-stone-900 px-4 py-2 text-xs uppercase tracking-widest hover:bg-stone-900 hover:text-[#f5f0e8]">← Programme</a>
    </div>
</header>
<main class="mx-auto max-w-3xl px-6 py-16 md:py-24">
    <div class="text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#b43a2d] text-4xl text-[#f8e8bd]" aria-hidden="true">✓</div>
        <p class="mt-8 text-xs uppercase tracking-[.35em] text-[#b43a2d]">Your seats are waiting</p>
        <h1 class="serif mt-3 text-5xl font-bold md:text-6xl">Booking complete.</h1>
        <p class="mx-auto mt-5 max-w-xl text-sm leading-7 text-stone-600">Thanks, <?= $e($confirmation['name'] ?? 'film lover') ?>. Your confirmation has been saved. Show your booking reference when you arrive at the cinema.</p>
    </div>

    <section class="mt-12 overflow-hidden bg-[#211b1a] text-[#f8e8bd] shadow-xl">
        <div class="border-b border-dashed border-[#f8e8bd]/30 px-7 py-6 md:px-10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] uppercase tracking-[.3em] text-[#d5a447]">Booking reference</p>
                    <p class="serif mt-2 text-3xl font-bold">#<?= (int) $confirmation['booking_id'] ?></p>
                </div>
                <p class="text-right text-[10px] uppercase tracking-widest text-[#f8e8bd]/60">The Marquee<br>Admission ticket</p>
            </div>
        </div>
        <div class="grid gap-8 px-7 py-8 md:grid-cols-[1fr_auto] md:px-10">
            <div>
                <p class="text-[10px] uppercase tracking-[.25em] text-[#d5a447]">Feature presentation</p>
                <h2 class="serif mt-2 text-3xl font-bold"><?= $e($confirmation['movie_title'] ?? 'Your film') ?></h2>
                <p class="mt-5 text-sm"><?= $e(date('l, F j, Y · g:i A', strtotime((string) $confirmation['starts_at']))) ?></p>
                <p class="mt-2 text-sm text-[#f8e8bd]/65"><?= $e($confirmation['theater'] ?? 'The Marquee') ?></p>
            </div>
            <div class="md:text-right">
                <p class="text-[10px] uppercase tracking-[.25em] text-[#d5a447]">Your seats</p>
                <p class="serif mt-2 text-3xl font-bold"><?= $e(implode(' · ', $seatLabels)) ?></p>
                <p class="mt-2 text-xs text-[#f8e8bd]/65"><?= $seatCount ?> <?= $seatCount === 1 ? 'seat' : 'seats' ?> · $<?= number_format($total, 2) ?></p>
            </div>
        </div>
        <div class="border-t border-[#f8e8bd]/15 px-7 py-5 text-xs text-[#f8e8bd]/60 md:px-10">Confirmation sent to <?= $e($confirmation['email'] ?? '') ?></div>
    </section>

    <div class="mt-10 flex flex-col justify-center gap-3 text-center sm:flex-row">
        <a href="/" class="rounded-full bg-[#b43a2d] px-7 py-3 text-xs font-bold uppercase tracking-widest text-[#f8e8bd] hover:bg-[#922e24]">Back to the programme</a>
        <button type="button" onclick="window.print()" class="rounded-full border border-stone-900 px-7 py-3 text-xs uppercase tracking-widest hover:bg-stone-900 hover:text-[#f5f0e8]">Print confirmation</button>
    </div>
</main>
</body>
</html>
