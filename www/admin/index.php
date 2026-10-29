<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'settings':
                $statement = $pdo->prepare(
                    'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );
                foreach (['company_description', 'opening_hours', 'contact_information'] as $key) {
                    $statement->execute(['key' => $key, 'value' => trim((string) ($_POST[$key] ?? ''))]);
                }
                $message = 'Company information updated.';
                break;
            case 'movie_save':
                $movieId = filter_var($_POST['movie_id'] ?? 0, FILTER_VALIDATE_INT);
                $title = trim((string) ($_POST['title'] ?? ''));
                $releaseDate = (string) ($_POST['release_date'] ?? '');
                $rating = (float) ($_POST['rating'] ?? 0);
                if ($title === '' || $releaseDate === '') {
                    throw new RuntimeException('Movie title and release date are required.');
                }
                if ($movieId) {
                    $statement = $pdo->prepare('UPDATE movies SET title = :title, release_date = :release_date, rating = :rating WHERE movieID = :id');
                } else {
                    $statement = $pdo->prepare('INSERT INTO movies (title, release_date, rating) VALUES (:title, :release_date, :rating)');
                }
                $statement->execute(['title' => $title, 'release_date' => $releaseDate, 'rating' => $rating, 'id' => $movieId]);
                $message = 'Movie saved.';
                break;
            case 'movie_delete':
                $statement = $pdo->prepare('DELETE FROM movies WHERE movieID = :id');
                $statement->execute(['id' => (int) $_POST['movie_id']]);
                $message = 'Movie deleted.';
                break;
            case 'showtime_save':
                $showtimeId = filter_var($_POST['showtime_id'] ?? 0, FILTER_VALIDATE_INT);
                $values = [
                    'movie_id' => (int) $_POST['movie_id'],
                    'theater_id' => (int) $_POST['theater_id'],
                    'starts_at' => str_replace('T', ' ', (string) $_POST['starts_at']) . ':00',
                    'price' => (int) $_POST['price'],
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                ];
                if ($showtimeId) {
                    $statement = $pdo->prepare('UPDATE showtimes SET movieID = :movie_id, theaterID = :theater_id, starts_at = :starts_at, price = :price, is_featured = :is_featured WHERE showtimeID = :id');
                    $values['id'] = $showtimeId;
                } else {
                    $statement = $pdo->prepare('INSERT INTO showtimes (movieID, theaterID, starts_at, price, is_featured) VALUES (:movie_id, :theater_id, :starts_at, :price, :is_featured)');
                }
                $statement->execute($values);
                $message = 'Showing saved.';
                break;
            case 'showtime_delete':
                $statement = $pdo->prepare('DELETE FROM showtimes WHERE showtimeID = :id');
                $statement->execute(['id' => (int) $_POST['showtime_id']]);
                $message = 'Showing deleted.';
                break;
            case 'invoice_update':
                $statement = $pdo->prepare('UPDATE invoices SET status = :status WHERE invoiceID = :id');
                $statement->execute(['status' => (string) $_POST['status'], 'id' => (int) $_POST['invoice_id']]);
                $message = 'Invoice updated.';
                break;
            case 'invoice_delete':
                $statement = $pdo->prepare('DELETE FROM invoices WHERE invoiceID = :id');
                $statement->execute(['id' => (int) $_POST['invoice_id']]);
                $message = 'Invoice deleted.';
                break;
            case 'news_save':
                $newsId = filter_var($_POST['news_id'] ?? 0, FILTER_VALIDATE_INT);
                $values = [
                    'title' => trim((string) $_POST['news_title']),
                    'body' => trim((string) $_POST['news_body']),
                    'published' => isset($_POST['is_published']) ? 1 : 0,
                ];
                if ($newsId) {
                    $statement = $pdo->prepare('UPDATE news SET title = :title, body = :body, is_published = :published WHERE newsID = :id');
                    $values['id'] = $newsId;
                } else {
                    $statement = $pdo->prepare('INSERT INTO news (title, body, is_published) VALUES (:title, :body, :published)');
                }
                $statement->execute($values);
                $message = 'News item saved.';
                break;
            case 'news_delete':
                $statement = $pdo->prepare('DELETE FROM news WHERE newsID = :id');
                $statement->execute(['id' => (int) $_POST['news_id']]);
                $message = 'News item deleted.';
                break;
            case 'invoice_create':
                $statement = $pdo->prepare(
                    'INSERT INTO invoices (invoice_number, customer_name, customer_email, amount, status, notes)
                     VALUES (:number, :name, :email, :amount, :status, :notes)'
                );
                $statement->execute([
                    'number' => trim((string) $_POST['invoice_number']),
                    'name' => trim((string) $_POST['customer_name']),
                    'email' => trim((string) $_POST['customer_email']),
                    'amount' => (float) $_POST['amount'],
                    'status' => (string) $_POST['status'],
                    'notes' => trim((string) $_POST['notes']),
                ]);
                $message = 'Invoice created.';
                break;
            case 'user_create':
                $username = trim((string) ($_POST['username'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
                    throw new RuntimeException('Use a 3-20 character username, a valid email, and a password of at least 12 characters.');
                }
                $statement = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'user')"
                );
                $statement->execute([
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $message = 'User account created.';
                break;
            case 'user_update':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $username = trim((string) ($_POST['username'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                if ($userId < 1 || !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Use a 3-20 character username and a valid email.');
                }
                $statement = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE userID = :id AND role = 'user'");
                $statement->execute(['username' => $username, 'email' => $email, 'id' => $userId]);
                $newPassword = (string) ($_POST['password'] ?? '');
                if ($newPassword !== '') {
                    if (strlen($newPassword) < 12) {
                        throw new RuntimeException('A new password must be at least 12 characters.');
                    }
                    $statement = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE userID = :id AND role = 'user'");
                    $statement->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
                }
                $message = 'User account updated.';
                break;
            case 'user_delete':
                $statement = $pdo->prepare("DELETE FROM users WHERE userID = :id AND role = 'user'");
                $statement->execute(['id' => (int) ($_POST['user_id'] ?? 0)]);
                $message = 'User account deleted.';
                break;
            default:
                throw new RuntimeException('Unknown dashboard action.');
        }
    } catch (Throwable $exception) {
        $message = 'Could not save that change: ' . $exception->getMessage();
    }
}

$settings = array_fill_keys(['company_description', 'opening_hours', 'contact_information'], '');
foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings') as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$movies = $pdo->query('SELECT movieID, title, release_date, rating FROM movies ORDER BY title')->fetchAll();
$theaters = $pdo->query('SELECT theaterID, name FROM theaters ORDER BY name')->fetchAll();
$showtimes = $pdo->query(
    'SELECT st.showtimeID, st.movieID, st.theaterID, st.starts_at, st.price, st.is_featured, m.title, t.name AS theater
     FROM showtimes st JOIN movies m ON m.movieID = st.movieID JOIN theaters t ON t.theaterID = st.theaterID
     ORDER BY st.starts_at DESC LIMIT 40'
)->fetchAll();
$news = $pdo->query('SELECT newsID, title, body, is_published, published_at FROM news ORDER BY published_at DESC')->fetchAll();
$bookings = $pdo->query(
    'SELECT b.bookingID, b.created_at, b.seat_row, b.seat_number, b.price_paid, u.username, u.email, m.title, st.starts_at
     FROM bookings b JOIN users u ON u.userID = b.userID JOIN showtimes st ON st.showtimeID = b.showtimeID
     JOIN movies m ON m.movieID = st.movieID ORDER BY b.created_at DESC LIMIT 100'
)->fetchAll();
$invoices = $pdo->query('SELECT * FROM invoices ORDER BY issued_at DESC')->fetchAll();
$users = $pdo->query("SELECT userID, username, email FROM users WHERE role = 'user' ORDER BY username")->fetchAll();
$csrf = e($_SESSION['csrf_token']);
$sections = [
    'company' => 'Company information',
    'movies' => 'Movies',
    'showings' => 'Showings',
    'news' => 'News',
    'bookings' => 'Bookings',
    'invoices' => 'Invoices',
    'users' => 'Users',
];
$section = (string) ($_GET['section'] ?? '');
if ($section !== '' && !array_key_exists($section, $sections)) {
    $section = '';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner dashboard — The Marquee</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#f5f0e8] text-[#191716]">
<header class="bg-[#211b1a] px-6 py-5 text-[#f8e8bd]">
<div class="mx-auto flex max-w-7xl items-center justify-between">
<div>
<p class="text-xs uppercase tracking-[.3em] text-[#d5a447]">The Marquee</p>
<h1 class="font-serif text-3xl font-bold"><?= $section === '' ? 'Owner dashboard' : e($sections[$section]) ?></h1>
</div>
<a href="logout.php" class="text-xs uppercase tracking-widest hover:text-[#d5a447]">Sign out</a>
</div>
</header>
<main class="mx-auto max-w-7xl space-y-10 px-6 py-10">
<?php if ($message): ?>
<p class="border-l-2 border-[#b43a2d] bg-white/70 p-4 text-sm">
<?= e($message) ?>
</p>
<?php endif; ?>
<nav class="flex flex-wrap gap-2 border-b border-stone-300 pb-5" aria-label="Dashboard sections">
<a href="index.php" class="<?= $section === '' ? 'bg-[#211b1a] text-[#f8e8bd]' : 'bg-white/60' ?> px-4 py-3 text-xs uppercase tracking-widest">Overview</a>
<?php foreach ($sections as $key => $label): ?>
<a href="index.php?section=<?= e($key) ?>" class="<?= $section === $key ? 'bg-[#211b1a] text-[#f8e8bd]' : 'bg-white/60' ?> px-4 py-3 text-xs uppercase tracking-widest"><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<?php if ($section === '' || $section === 'company'): ?>
<section>
<h2 class="mb-4 font-serif text-3xl font-bold">Company information</h2>
<form method="post" class="grid gap-4 bg-white/60 p-6 md:grid-cols-3">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="settings">
<label class="text-xs uppercase tracking-widest">Description<textarea name="company_description" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['company_description']) ?></textarea></label>
<label class="text-xs uppercase tracking-widest">Opening hours<textarea name="opening_hours" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['opening_hours']) ?></textarea></label>
<label class="text-xs uppercase tracking-widest">Contact information<textarea name="contact_information" rows="4" class="mt-2 w-full border border-stone-300 bg-transparent p-2 text-sm"><?= e($settings['contact_information']) ?></textarea><button class="mt-3 bg-[#211b1a] px-4 py-3 text-xs uppercase tracking-widest text-[#f8e8bd]">Save details</button>
</label>
</form>
</section>
<?php endif; ?>
<?php if ($section === '' || $section === 'movies'): ?>
<section>
<div class="mb-4 flex items-end justify-between">
<h2 class="font-serif text-3xl font-bold">Movies</h2>
<span class="text-xs uppercase tracking-widest text-stone-500">
<?= count($movies) ?> titles</span>
</div>
<form method="post" class="mb-5 grid gap-3 bg-white/60 p-6 md:grid-cols-4">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="movie_save">
<input name="title" required placeholder="Title" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="release_date" required type="date" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="rating" type="number" min="0" max="10" step=".1" placeholder="Rating" class="border border-stone-300 bg-transparent p-3 text-sm">
<button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Add movie</button>
</form>
<div class="grid gap-3 md:grid-cols-2">
<?php foreach ($movies as $movie): ?>
<form method="post" class="flex flex-wrap items-center gap-2 bg-white/60 p-4">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="movie_save">
<input type="hidden" name="movie_id" value="<?= (int) $movie['movieID'] ?>">
<input name="title" value="<?= e($movie['title']) ?>" class="min-w-40 flex-1 border-b border-stone-300 bg-transparent p-2 text-sm">
<input name="release_date" type="date" value="<?= e($movie['release_date']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
<input name="rating" type="number" min="0" max="10" step=".1" value="<?= e($movie['rating']) ?>" class="w-20 border-b border-stone-300 bg-transparent p-2 text-sm">
<button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
<button name="action" value="movie_delete" class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
</form>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
<?php if ($section === '' || $section === 'showings'): ?>
<section>
<h2 class="mb-4 font-serif text-3xl font-bold">Showings & daily premieres</h2>
<form method="post" class="mb-5 grid gap-3 bg-white/60 p-6 md:grid-cols-5">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="showtime_save">
<select name="movie_id" required class="border border-stone-300 bg-transparent p-3 text-sm">
<option value="">Movie</option>
<?php foreach ($movies as $movie): ?>
<option value="<?= (int) $movie['movieID'] ?>">
<?= e($movie['title']) ?>
</option>
<?php endforeach; ?>
</select>
<select name="theater_id" required class="border border-stone-300 bg-transparent p-3 text-sm">
<option value="">Theater</option>
<?php foreach ($theaters as $theater): ?>
<option value="<?= (int) $theater['theaterID'] ?>">
<?= e($theater['name']) ?>
</option>
<?php endforeach; ?>
</select>
<input name="starts_at" required type="datetime-local" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="price" required type="number" min="0" placeholder="Price" class="border border-stone-300 bg-transparent p-3 text-sm">
<label class="flex items-center gap-2 text-xs uppercase tracking-widest">
<input type="checkbox" name="is_featured"> Front page premiere</label>
<button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white md:col-span-5">Add showing</button>
</form>
<div class="overflow-x-auto bg-white/60">
<table class="w-full text-left text-sm">
<thead class="border-b border-stone-300 text-xs uppercase tracking-widest">
<tr>
<th class="p-4">Movie</th>
<th class="p-4">Theater</th>
<th class="p-4">Starts</th>
<th class="p-4">Price</th>
<th class="p-4">Premiere</th>
<th class="p-4">
</th>
</tr>
</thead>
<tbody>
<?php foreach ($showtimes as $showtime): ?>
<tr class="border-b border-stone-200">
<form method="post">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="showtime_save">
<input type="hidden" name="showtime_id" value="<?= (int) $showtime['showtimeID'] ?>">
<td class="p-4">
<select name="movie_id" class="border-b border-stone-300 bg-transparent p-2">
<?php foreach ($movies as $movie): ?>
<option value="<?= (int) $movie['movieID'] ?>" <?= (int) $movie['movieID'] === (int) $showtime['movieID'] ? 'selected' : '' ?>>
<?= e($movie['title']) ?>
</option>
<?php endforeach; ?>
</select>
</td>
<td class="p-4">
<select name="theater_id" class="border-b border-stone-300 bg-transparent p-2">
<?php foreach ($theaters as $theater): ?>
<option value="<?= (int) $theater['theaterID'] ?>" <?= (int) $theater['theaterID'] === (int) $showtime['theaterID'] ? 'selected' : '' ?>>
<?= e($theater['name']) ?>
</option>
<?php endforeach; ?>
</select>
</td>
<td class="p-4">
<input name="starts_at" type="datetime-local" value="<?= e(date('Y-m-d\TH:i', strtotime($showtime['starts_at']))) ?>" class="border-b border-stone-300 bg-transparent p-2">
</td>
<td class="p-4">
<input name="price" type="number" min="0" value="<?= e($showtime['price']) ?>" class="w-20 border-b border-stone-300 bg-transparent p-2">
</td>
<td class="p-4">
<input type="checkbox" name="is_featured" <?= $showtime['is_featured'] ? 'checked' : '' ?>>
</td>
<td class="p-4">
<button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
</form>
<form method="post" class="mt-2">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="showtime_delete">
<input type="hidden" name="showtime_id" value="<?= (int) $showtime['showtimeID'] ?>">
<button class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
<?php endif; ?>
<?php if ($section === '' || $section === 'news'): ?>
<section>
<h2 class="mb-4 font-serif text-3xl font-bold">News</h2>
<form method="post" class="grid gap-3 bg-white/60 p-6 md:grid-cols-3">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="news_save">
<input name="news_title" required placeholder="Headline" class="border border-stone-300 bg-transparent p-3 text-sm"><textarea name="news_body" required placeholder="News text" class="border border-stone-300 bg-transparent p-3 text-sm"></textarea><label class="text-xs uppercase tracking-widest">
<span class="flex items-center gap-2">
<input type="checkbox" name="is_published" checked> Published</span>
<button class="mt-4 bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Add news</button>
</label>
</form>
<div class="mt-4 space-y-3">
<?php foreach ($news as $item): ?>
<form method="post" class="grid gap-3 bg-white/60 p-4 md:grid-cols-[1fr_2fr_auto_auto]">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="news_save">
<input type="hidden" name="news_id" value="<?= (int) $item['newsID'] ?>">
<input name="news_title" required value="<?= e($item['title']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm"><textarea name="news_body" required class="border-b border-stone-300 bg-transparent p-2 text-sm"><?= e($item['body']) ?></textarea><label class="flex items-center gap-2 text-xs uppercase tracking-widest">
<input type="checkbox" name="is_published" <?= $item['is_published'] ? 'checked' : '' ?>> Published</label>
<span>
<button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
<button name="action" value="news_delete" class="ml-3 text-xs uppercase tracking-widest text-stone-500">Delete</button>
</span>
</form>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
<?php if ($section === '' || $section === 'bookings'): ?>
<section>
<h2 class="mb-4 font-serif text-3xl font-bold">Booking overview</h2>
<div class="overflow-x-auto bg-white/60">
<table class="w-full text-left text-sm">
<thead class="border-b border-stone-300 text-xs uppercase tracking-widest">
<tr>
<th class="p-4">Customer</th>
<th class="p-4">Movie / showing</th>
<th class="p-4">Seat</th>
<th class="p-4">Paid</th>
<th class="p-4">Booked</th>
</tr>
</thead>
<tbody>
<?php foreach ($bookings as $booking): ?>
<tr class="border-b border-stone-200">
<td class="p-4">
<?= e($booking['username']) ?>
<br>
<span class="text-xs text-stone-500">
<?= e($booking['email']) ?>
</span>
</td>
<td class="p-4">
<?= e($booking['title']) ?>
<br>
<span class="text-xs text-stone-500">
<?= e($booking['starts_at']) ?>
</span>
</td>
<td class="p-4">Row <?= (int) $booking['seat_row'] ?> / <?= (int) $booking['seat_number'] ?>
</td>
<td class="p-4">$<?= e($booking['price_paid']) ?>
</td>
<td class="p-4">
<?= e($booking['created_at']) ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
<?php endif; ?>
<?php if ($section === '' || $section === 'invoices'): ?>
<section>
<h2 class="mb-4 font-serif text-3xl font-bold">Invoice creator</h2>
<form method="post" class="grid gap-3 bg-white/60 p-6 md:grid-cols-3">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="invoice_create">
<input name="invoice_number" required placeholder="Invoice number" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="customer_name" required placeholder="Customer name" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="customer_email" required type="email" placeholder="Customer email" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="amount" required type="number" min="0" step=".01" placeholder="Amount" class="border border-stone-300 bg-transparent p-3 text-sm">
<select name="status" class="border border-stone-300 bg-transparent p-3 text-sm">
<option>draft</option>
<option>sent</option>
<option>paid</option>
<option>void</option>
</select><textarea name="notes" placeholder="Notes" class="border border-stone-300 bg-transparent p-3 text-sm"></textarea><button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white md:col-span-3">Create invoice</button>
</form>
<div class="mt-4 overflow-x-auto bg-white/60">
<table class="w-full text-left text-sm">
<thead class="border-b border-stone-300 text-xs uppercase tracking-widest">
<tr>
<th class="p-4">Number</th>
<th class="p-4">Customer</th>
<th class="p-4">Amount</th>
<th class="p-4">Status</th>
<th class="p-4">
</th>
</tr>
</thead>
<tbody>
<?php foreach ($invoices as $invoice): ?>
<tr class="border-b border-stone-200">
<td class="p-4">
<?= e($invoice['invoice_number']) ?>
</td>
<td class="p-4">
<?= e($invoice['customer_name']) ?>
<br>
<span class="text-xs text-stone-500">
<?= e($invoice['customer_email']) ?>
</span>
</td>
<td class="p-4">$<?= e($invoice['amount']) ?>
</td>
<td class="p-4">
<form method="post" class="flex gap-2">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="invoice_update">
<input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoiceID'] ?>">
<select name="status" class="border-b border-stone-300 bg-transparent text-sm">
<option <?= $invoice['status'] === 'draft' ? 'selected' : '' ?>>draft</option>
<option <?= $invoice['status'] === 'sent' ? 'selected' : '' ?>>sent</option>
<option <?= $invoice['status'] === 'paid' ? 'selected' : '' ?>>paid</option>
<option <?= $invoice['status'] === 'void' ? 'selected' : '' ?>>void</option>
</select>
<button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
</form>
</td>
<td class="p-4">
<form method="post">
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
<?php endif; ?>
<?php if ($section === '' || $section === 'users'): ?>
<section>
<div class="mb-4 flex items-end justify-between">
<h2 class="font-serif text-3xl font-bold">User accounts</h2>
<span class="text-xs uppercase tracking-widest text-stone-500"><?= count($users) ?> users</span>
</div>
<form method="post" class="grid gap-3 bg-white/60 p-6 md:grid-cols-4">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="user_create">
<input name="username" required maxlength="20" pattern="[A-Za-z0-9_]{3,20}" placeholder="Username" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="email" required type="email" placeholder="Email" class="border border-stone-300 bg-transparent p-3 text-sm">
<input name="password" required minlength="12" type="password" placeholder="Password (12+ characters)" class="border border-stone-300 bg-transparent p-3 text-sm">
<button class="bg-[#b43a2d] px-4 py-3 text-xs uppercase tracking-widest text-white">Create user</button>
</form>
<div class="mt-4 space-y-3">
<?php foreach ($users as $user): ?>
<form method="post" class="grid gap-3 bg-white/60 p-4 md:grid-cols-[1fr_2fr_1fr_auto_auto]">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
<input type="hidden" name="action" value="user_update">
<input type="hidden" name="user_id" value="<?= (int) $user['userID'] ?>">
<input name="username" required maxlength="20" pattern="[A-Za-z0-9_]{3,20}" value="<?= e($user['username']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
<input name="email" required type="email" value="<?= e($user['email']) ?>" class="border-b border-stone-300 bg-transparent p-2 text-sm">
<input name="password" minlength="12" type="password" placeholder="New password" class="border-b border-stone-300 bg-transparent p-2 text-sm">
<button class="text-xs uppercase tracking-widest text-[#b43a2d]">Update</button>
<button name="action" value="user_delete" class="text-xs uppercase tracking-widest text-stone-500">Delete</button>
</form>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</main>
</body>
</html>
