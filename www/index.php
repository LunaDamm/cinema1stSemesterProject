<?php
declare(strict_types=1);

$movies = [];
$showtimes = [];
$theaters = [];
$databaseError = null;

try {
    $pdo = new PDO(
        'mysql:host=database;port=3306;dbname=' . ($_ENV['MOVIE_DATABASE'] ?? 'movieDB') . ';charset=utf8mb4',
        $_ENV['MYSQL_USER'] ?? 'root',
        $_ENV['MYSQL_PASSWORD'] ?? ($_ENV['MYSQL_ROOT_PASSWORD'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $movies = $pdo->query(
        "SELECT m.movieID, m.title, YEAR(m.release_date) AS release_year, m.rating,
                GROUP_CONCAT(DISTINCT g.genreName ORDER BY g.genreName SEPARATOR ', ') AS genres,
                COUNT(DISTINCT st.showtimeID) AS screenings
         FROM movies m
         LEFT JOIN movieGenreRelation mgr ON mgr.movieID = m.movieID
         LEFT JOIN genre g ON g.genreID = mgr.genreID
         LEFT JOIN showtimes st ON st.movieID = m.movieID
         GROUP BY m.movieID, m.title, m.release_date, m.rating
         ORDER BY m.rating DESC, m.title ASC"
    )->fetchAll();

    $showtimes = $pdo->query(
        "SELECT st.showtimeID, st.movieID, st.starts_at, st.price, t.name AS theater
         FROM showtimes st
         JOIN theaters t ON t.theaterID = st.theaterID
         WHERE st.starts_at >= NOW()
         ORDER BY st.starts_at ASC
         LIMIT 12"
    )->fetchAll();

    $theaters = $pdo->query(
        "SELECT name, seat_rows * seats_per_row AS seats
         FROM theaters
         ORDER BY theaterID"
    )->fetchAll();
} catch (PDOException $exception) {
    $databaseError = 'The projector is warming up. Showing the house programme while we reconnect.';
}

$featured = $movies[0];
$movieTitles = [];
foreach ($movies as $movie) {
    $movieTitles[(int) $movie['movieID']] = $movie['title'];
}
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$posterClasses = ['poster-amber', 'poster-red', 'poster-blue', 'poster-green', 'poster-violet'];
$initials = static function (string $title): string {
    $words = preg_split('/\s+/', trim($title));
    return strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[count($words) - 1] ?? '', 0, 1));
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Marquee — Independent Cinema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #191716; --paper: #f5f0e8; --red: #b43a2d; --gold: #d5a447; }
        body { background: var(--paper); color: var(--ink); font-family: 'DM Mono', monospace; }
        .serif { font-family: 'Playfair Display', serif; }
        .grain { background-image: radial-gradient(rgba(25,23,22,.09) .7px, transparent .7px); background-size: 5px 5px; }
        .hero-glow { background: radial-gradient(circle at 70% 45%, #d6a749 0, #bd5a35 22%, #721e24 54%, #211719 100%); }
        .poster { min-height: 280px; position: relative; overflow: hidden; }
        .poster:after { content: ''; position: absolute; inset: 10px; border: 1px solid rgba(255,244,206,.55); pointer-events: none; }
        .poster-amber { background: linear-gradient(145deg, #4f2619, #bd7931 54%, #201817); }
        .poster-red { background: linear-gradient(145deg, #21171a, #9f302d 48%, #e0a54a); }
        .poster-blue { background: linear-gradient(145deg, #131e2f, #466c76 48%, #c18b4b); }
        .poster-green { background: linear-gradient(145deg, #192522, #4e6d57 50%, #b67a3e); }
        .poster-violet { background: linear-gradient(145deg, #211c30, #734c75 50%, #c38b4b); }
        .filmstrip { background: repeating-linear-gradient(90deg, #1c1917 0, #1c1917 14px, transparent 14px, transparent 31px); }
        .lift { transition: transform .2s ease, box-shadow .2s ease; }
        .lift:hover { transform: translateY(-5px); box-shadow: 0 18px 30px rgba(63,34,24,.13); }
    </style>
</head>
<body class="grain">
<header class="border-b border-stone-900/15 bg-[#f5f0e8]/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="#" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#b43a2d] text-xl text-[#f8e8bd]">✦</span>
            <span><strong class="serif block text-xl tracking-tight">The Marquee</strong><small class="text-[10px] uppercase tracking-[.3em] text-stone-500">Est. 1927 · Moving pictures</small></span>
        </a>
        <nav class="hidden items-center gap-8 text-xs uppercase tracking-[.18em] md:flex">
            <a href="#programme" class="hover:text-[#b43a2d]">Programme</a>
            <a href="#about" class="hover:text-[#b43a2d]">Our house</a>
            <a href="#visit" class="hover:text-[#b43a2d]">Visit us</a>
        </nav>
        <a href="#programme" class="rounded-full border border-stone-900 px-4 py-2 text-xs uppercase tracking-widest hover:bg-stone-900 hover:text-[#f5f0e8]">Book a seat</a>
    </div>
</header>

<main>
    <section class="hero-glow relative overflow-hidden text-[#f8e8bd]">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 md:grid-cols-[1.1fr_.9fr] md:py-28">
            <div class="relative z-10">
                <p class="mb-5 text-xs uppercase tracking-[.35em] text-[#f3c76d]">Tonight at The Marquee</p>
                <h1 class="serif max-w-2xl text-6xl font-bold leading-[.93] md:text-8xl">The art of the moving picture.</h1>
                <p class="mt-7 max-w-lg text-sm leading-7 text-[#f8e8bd]/75">A modern cinema with an old soul. Find your next great picture, settle into velvet, and let the house lights go down.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#programme" class="rounded-full bg-[#f3c76d] px-6 py-3 text-xs font-bold uppercase tracking-widest text-[#381b18]">See the programme ↘</a>
                    <span class="rounded-full border border-[#f8e8bd]/30 px-6 py-3 text-xs uppercase tracking-widest">35mm spirit · Digital comfort</span>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-sm rotate-2">
                <div class="poster poster-blue flex items-center justify-center shadow-2xl">
                    <div class="relative z-10 px-8 text-center text-[#f8e8bd]">
                        <p class="text-[10px] uppercase tracking-[.4em]">A presentation by</p>
                        <p class="serif my-7 text-6xl font-bold leading-none">THE<br>MARQUEE</p>
                        <p class="border-y border-[#f8e8bd]/50 py-3 text-xs uppercase tracking-[.25em]"><?= $e($featured['title']) ?></p>
                    </div>
                </div>
                <span class="absolute -right-5 -top-5 rounded-full bg-[#d5a447] px-3 py-5 text-center text-[10px] font-bold uppercase leading-3 text-stone-900">Now<br>showing</span>
            </div>
        </div>
        <div class="filmstrip absolute bottom-0 left-0 h-3 w-full opacity-70"></div>
    </section>

    <section id="programme" class="mx-auto max-w-7xl px-6 py-20">
        <div class="mb-10 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div><p class="mb-2 text-xs uppercase tracking-[.3em] text-[#b43a2d]">The programme</p><h2 class="serif text-5xl font-bold">Now playing</h2></div>
            <label class="relative"><span class="sr-only">Search films</span><input id="search" type="search" placeholder="Search the programme..." class="w-full rounded-none border-0 border-b border-stone-400 bg-transparent py-2 pl-1 pr-8 text-xs outline-none focus:border-[#b43a2d] sm:w-64"></label>
        </div>
        <?php if ($databaseError): ?><p class="mb-6 border-l-2 border-[#d5a447] bg-[#eadfcb] px-4 py-3 text-xs"><?= $e($databaseError) ?></p><?php endif; ?>
        <div id="movie-grid" class="grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($movies as $index => $movie): ?>
                <article class="movie-card lift overflow-hidden bg-white/60" data-title="<?= $e(strtolower($movie['title'])) ?>" data-genre="<?= $e(strtolower($movie['genres'] ?? '')) ?>">
                    <div class="poster <?= $posterClasses[$index % count($posterClasses)] ?> flex items-end justify-between p-6 text-[#f8e8bd]">
                        <span class="relative z-10 serif text-7xl font-bold opacity-90"><?= $e($initials($movie['title'])) ?></span>
                        <span class="relative z-10 rounded-full bg-[#191716]/70 px-2 py-1 text-[10px]"><?= $e($movie['release_year']) ?></span>
                    </div>
                    <div class="p-5">
                        <div class="mb-2 flex items-start justify-between gap-3"><h3 class="serif text-xl font-bold"><?= $e($movie['title']) ?></h3><span class="text-xs text-[#b43a2d]">★ <?= $e($movie['rating']) ?></span></div>
                        <p class="mb-5 text-[10px] uppercase tracking-widest text-stone-500"><?= $e($movie['genres'] ?: 'Feature presentation') ?></p>
                        <a href="#showtimes" class="block border-t border-stone-300 pt-3 text-[10px] font-bold uppercase tracking-widest hover:text-[#b43a2d]">Find showtimes <span class="float-right">→</span></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="showtimes" class="bg-[#211b1a] text-[#f8e8bd]">
        <div class="mx-auto max-w-7xl px-6 py-20">
            <div class="mb-10"><p class="mb-2 text-xs uppercase tracking-[.3em] text-[#d5a447]">Choose your curtain call</p><h2 class="serif text-5xl font-bold">Coming up</h2></div>
            <?php if ($showtimes): ?>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($showtimes as $showtime): ?>
                        <a href="#" class="group flex items-center justify-between border border-[#f8e8bd]/20 p-5 hover:border-[#d5a447]">
                            <span><strong class="serif block text-lg"><?= $e($movieTitles[(int) $showtime['movieID']] ?? 'Feature presentation') ?></strong><small class="mt-1 block text-[10px] uppercase tracking-widest text-[#f8e8bd]/55"><?= $e($showtime['theater']) ?> · <?= date('D, M j · g:i A', strtotime($showtime['starts_at'])) ?></small></span>
                            <span class="text-sm text-[#d5a447]">$<?= $e($showtime['price']) ?> <span class="ml-2 transition group-hover:ml-3">→</span></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="border border-[#f8e8bd]/20 p-6 text-sm text-[#f8e8bd]/70">Showtimes are being threaded into the projector. Check back shortly.</p>
            <?php endif; ?>
        </div>
    </section>

    <section id="about" class="mx-auto grid max-w-7xl gap-12 px-6 py-20 md:grid-cols-2">
        <div><p class="mb-2 text-xs uppercase tracking-[.3em] text-[#b43a2d]">The house</p><h2 class="serif text-5xl font-bold">Small seats.<br>Big pictures.</h2></div>
        <div class="grid grid-cols-2 gap-6 text-sm leading-6 text-stone-600">
            <div><strong class="serif mb-2 block text-2xl text-stone-900">01</strong>Curated films, comfortable rooms, and the kind of service that makes an evening of it.</div>
            <div><strong class="serif mb-2 block text-2xl text-stone-900">02</strong>Our screens are showing <?= count($movies) ?> pictures from the SQL programme tonight.</div>
            <?php foreach (array_slice($theaters, 0, 2) as $theater): ?><div><strong class="serif mb-2 block text-2xl text-stone-900"><?= $e($theater['seats']) ?></strong><?= $e($theater['name']) ?> seats.</div><?php endforeach; ?>
        </div>
    </section>
</main>
<footer id="visit" class="border-t border-stone-900/15 px-6 py-8 text-xs uppercase tracking-widest text-stone-500">
    <div class="mx-auto flex max-w-7xl flex-col justify-between gap-3 sm:flex-row"><span>The Marquee Cinema · 14 Picturehouse Lane</span><span>Doors open 30 minutes before showtime · © <?= date('Y') ?></span></div>
</footer>
<script>
    const search = document.querySelector('#search');
    const cards = document.querySelectorAll('.movie-card');
    search.addEventListener('input', (event) => {
        const term = event.target.value.toLowerCase().trim();
        cards.forEach((card) => {
            card.hidden = term && !(`${card.dataset.title} ${card.dataset.genre}`).includes(term);
        });
    });
</script>
</body>
</html>
