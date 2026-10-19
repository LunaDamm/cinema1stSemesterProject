<?php
declare(strict_types=1);

$movie = null;
$showtimes = [];
$cast = [];
$error = null;
$movieId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$movieId) {
        throw new RuntimeException('Choose a picture from the programme.');
    }

    $pdo = require __DIR__ . '/database.php';

    $movieStatement = $pdo->prepare(
        "SELECT m.movieID, m.title, YEAR(m.release_date) AS release_year, m.rating,
                GROUP_CONCAT(DISTINCT g.genreName ORDER BY g.genreName SEPARATOR ', ') AS genres
         FROM movies m
         LEFT JOIN movieGenreRelation mgr ON mgr.movieID = m.movieID
         LEFT JOIN genre g ON g.genreID = mgr.genreID
         WHERE m.movieID = :movie_id
         GROUP BY m.movieID, m.title, m.release_date, m.rating"
    );
    $movieStatement->execute(['movie_id' => $movieId]);
    $movie = $movieStatement->fetch();

    if (!$movie) {
        throw new RuntimeException('That picture is not in our programme.');
    }

    $showtimeStatement = $pdo->prepare(
        "SELECT st.showtimeID, st.starts_at, st.price, t.name AS theater
         FROM showtimes st
         JOIN theaters t ON t.theaterID = st.theaterID
         WHERE st.movieID = :movie_id AND st.starts_at >= NOW()
         ORDER BY st.starts_at ASC"
    );
    $showtimeStatement->execute(['movie_id' => $movieId]);
    $showtimes = $showtimeStatement->fetchAll();

    $castStatement = $pdo->prepare(
        "SELECT c.firstName, c.lastName, cmr.relationToMovie
         FROM castMovieRelation cmr
         JOIN `cast` c ON c.castID = cmr.castID
         WHERE cmr.movieID = :movie_id
         ORDER BY cmr.relationToMovie = 'Director' DESC, c.lastName ASC, c.firstName ASC"
    );
    $castStatement->execute(['movie_id' => $movieId]);
    $cast = $castStatement->fetchAll();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
} catch (PDOException $exception) {
    $error = 'The programme desk is temporarily unavailable. Please try again shortly.';
}

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = $movie['title'] ?? 'Picture details';
$posterClasses = ['poster-amber', 'poster-red', 'poster-blue', 'poster-green', 'poster-violet'];
$posterClass = $posterClasses[((int) ($movie['movieID'] ?? 1) - 1) % count($posterClasses)];
$words = preg_split('/\s+/', trim($title));
$initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[count($words) - 1] ?? '', 0, 1));
$showtimesByDate = [];
foreach ($showtimes as $showtime) {
    $dateKey = date('Y-m-d', strtotime($showtime['starts_at']));
    $showtimesByDate[$dateKey][] = $showtime;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($title) ?> — The Marquee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #191716; --paper: #f5f0e8; --red: #b43a2d; --gold: #d5a447; }
        body { background: var(--paper); color: var(--ink); font-family: 'DM Mono', monospace; background-image: radial-gradient(rgba(25,23,22,.09) .7px, transparent .7px); background-size: 5px 5px; }
        .serif { font-family: 'Playfair Display', serif; }
        .poster { min-height: 430px; position: relative; overflow: hidden; }
        .poster:after { content: ''; position: absolute; inset: 10px; border: 1px solid rgba(255,244,206,.6); pointer-events: none; }
        .poster-amber { background: linear-gradient(145deg, #4f2619, #bd7931 54%, #201817); }
        .poster-red { background: linear-gradient(145deg, #21171a, #9f302d 48%, #e0a54a); }
        .poster-blue { background: linear-gradient(145deg, #131e2f, #466c76 48%, #c18b4b); }
        .poster-green { background: linear-gradient(145deg, #192522, #4e6d57 50%, #b67a3e); }
        .poster-violet { background: linear-gradient(145deg, #211c30, #734c75 50%, #c38b4b); }
        .ticket { border: 1px solid rgba(248,232,189,.25); background: #211b1a; }
    </style>
</head>
<body>
<header class="border-b border-stone-900/15 bg-[#f5f0e8]/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="/" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#b43a2d] text-xl text-[#f8e8bd]">✦</span>
            <span><strong class="serif block text-xl tracking-tight">The Marquee</strong><small class="text-[10px] uppercase tracking-[.3em] text-stone-500">Est. 1927 · Moving pictures</small></span>
        </a>
        <a href="/" class="rounded-full border border-stone-900 px-4 py-2 text-xs uppercase tracking-widest hover:bg-stone-900 hover:text-[#f5f0e8]">← Programme</a>
    </div>
</header>
<main class="mx-auto max-w-7xl px-6 py-20">
    <?php if ($error): ?>
        <div class="border-l-2 border-[#b43a2d] bg-[#eadfcb] px-5 py-4 text-sm"><?= $e($error) ?></div>
    <?php elseif ($movie): ?>
        <div class="grid items-start gap-12 md:grid-cols-[320px_1fr] lg:gap-20">
            <div class="poster <?= $e($posterClass) ?> flex items-end justify-center p-8 text-center text-[#f8e8bd] shadow-xl">
                <div class="relative z-10"><p class="text-[10px] uppercase tracking-[.4em]">A Marquee presentation</p><p class="serif my-8 text-7xl font-bold"><?= $e($initials) ?></p><h1 class="serif text-4xl font-bold uppercase leading-none"><?= $e($movie['title']) ?></h1><p class="mt-7 border-y border-[#f8e8bd]/50 py-3 text-xs uppercase tracking-widest"><?= $e($movie['release_year']) ?></p></div>
            </div>
            <section>
                <p class="mb-3 text-xs uppercase tracking-[.3em] text-[#b43a2d]">Now showing · <?= $e($movie['genres'] ?: 'Feature presentation') ?></p>
                <h1 class="serif text-6xl font-bold leading-none"><?= $e($movie['title']) ?></h1>
                <p class="mt-5 text-sm text-stone-600">Released <?= $e($movie['release_year']) ?> · Audience rating <?= $e($movie['rating']) ?>/10</p>
                <?php if ($cast): ?>
                    <div class="mt-10 border-y border-stone-300 py-5">
                        <p class="mb-3 text-xs uppercase tracking-[.25em] text-[#b43a2d]">Credits</p>
                        <p class="text-sm leading-7"><?= $e(implode(', ', array_map(static fn ($person): string => $person['firstName'] . ' ' . $person['lastName'] . ' (' . $person['relationToMovie'] . ')', $cast))) ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-10">
                    <p class="mb-4 text-xs uppercase tracking-[.25em] text-[#b43a2d]">Screen times</p>
                    <?php if ($showtimes): ?>
                        <div class="space-y-8">
                            <?php foreach ($showtimesByDate as $date => $dateShowtimes): ?>
                                <div>
                                    <h2 class="mb-3 border-b border-stone-300 pb-2 text-xs font-bold uppercase tracking-[.25em] text-stone-600"><?= $e(date('l, F j, Y', strtotime($date))) ?></h2>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <?php foreach ($dateShowtimes as $showtime): ?>
                                            <a href="booking.php?showtime_id=<?= (int) $showtime['showtimeID'] ?>" class="ticket flex items-center justify-between p-5 text-[#f8e8bd] transition hover:-translate-y-1 hover:border-[#d5a447]">
                                                <span><strong class="serif block text-lg"><?= $e(date('g:i A', strtotime($showtime['starts_at']))) ?></strong><small class="mt-1 block text-[10px] uppercase tracking-widest text-[#f8e8bd]/60"><?= $e($showtime['theater']) ?></small></span>
                                                <span class="text-sm text-[#d5a447]">$<?= $e($showtime['price']) ?> →</span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="border border-stone-300 bg-white/40 p-5 text-sm">No future screenings are listed for this picture.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
