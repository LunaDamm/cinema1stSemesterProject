<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_post();
verify_csrf();

$action = (string) ($_POST['action'] ?? '');
$sections = [
    'settings' => 'company',
    'movie_save' => 'movies',
    'movie_delete' => 'movies',
    'showtime_save' => 'showings',
    'showtime_delete' => 'showings',
    'news_save' => 'news',
    'news_delete' => 'news',
    'invoice_create' => 'invoices',
    'invoice_update' => 'invoices',
    'invoice_delete' => 'invoices',
    'user_create' => 'users',
    'user_update' => 'users',
    'user_delete' => 'users',
];

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
            redirect_admin('Company information updated.', 'company');

        case 'movie_save':
            $movieId = filter_var($_POST['movie_id'] ?? 0, FILTER_VALIDATE_INT);
            $title = trim((string) ($_POST['title'] ?? ''));
            $releaseDate = (string) ($_POST['release_date'] ?? '');
            $rating = (float) ($_POST['rating'] ?? 0);
            if ($title === '' || $releaseDate === '') {
                throw new RuntimeException('Movie title and release date are required.');
            }
            $values = ['title' => $title, 'release_date' => $releaseDate, 'rating' => $rating];
            $statement = $movieId
                ? $pdo->prepare('UPDATE movies SET title = :title, release_date = :release_date, rating = :rating WHERE movieID = :id')
                : $pdo->prepare('INSERT INTO movies (title, release_date, rating) VALUES (:title, :release_date, :rating)');
            if ($movieId) {
                $values['id'] = $movieId;
            }
            $statement->execute($values);
            redirect_admin('Movie saved.', 'movies');

        case 'movie_delete':
            $statement = $pdo->prepare('DELETE FROM movies WHERE movieID = :id');
            $statement->execute(['id' => (int) $_POST['movie_id']]);
            redirect_admin('Movie deleted.', 'movies');

        case 'showtime_save':
            $showtimeId = filter_var($_POST['showtime_id'] ?? 0, FILTER_VALIDATE_INT);
            $values = [
                'movie_id' => (int) $_POST['movie_id'],
                'theater_id' => (int) $_POST['theater_id'],
                'starts_at' => str_replace('T', ' ', (string) $_POST['starts_at']) . ':00',
                'price' => (int) $_POST['price'],
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            ];
            $statement = $showtimeId
                ? $pdo->prepare('UPDATE showtimes SET movieID = :movie_id, theaterID = :theater_id, starts_at = :starts_at, price = :price, is_featured = :is_featured WHERE showtimeID = :id')
                : $pdo->prepare('INSERT INTO showtimes (movieID, theaterID, starts_at, price, is_featured) VALUES (:movie_id, :theater_id, :starts_at, :price, :is_featured)');
            if ($showtimeId) {
                $values['id'] = $showtimeId;
            }
            $statement->execute($values);
            redirect_admin('Showing saved.', 'showings');

        case 'showtime_delete':
            $statement = $pdo->prepare('DELETE FROM showtimes WHERE showtimeID = :id');
            $statement->execute(['id' => (int) $_POST['showtime_id']]);
            redirect_admin('Showing deleted.', 'showings');

        case 'news_save':
            $newsId = filter_var($_POST['news_id'] ?? 0, FILTER_VALIDATE_INT);
            $values = [
                'title' => trim((string) ($_POST['news_title'] ?? '')),
                'body' => trim((string) ($_POST['news_body'] ?? '')),
                'published' => isset($_POST['is_published']) ? 1 : 0,
            ];
            if ($values['title'] === '' || $values['body'] === '') {
                throw new RuntimeException('News headline and text are required.');
            }

            $oldImagePath = null;
            if ($newsId) {
                $oldStatement = $pdo->prepare('SELECT image_path FROM news WHERE newsID = :id');
                $oldStatement->execute(['id' => $newsId]);
                $oldImagePath = $oldStatement->fetchColumn() ?: null;
            }
            $uploadedImage = process_news_image($_FILES['news_image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($uploadedImage !== null) {
                $values['image_path'] = $uploadedImage;
            }

            $statement = $newsId
                ? $pdo->prepare($uploadedImage !== null
                    ? 'UPDATE news SET title = :title, body = :body, is_published = :published, image_path = :image_path WHERE newsID = :id'
                    : 'UPDATE news SET title = :title, body = :body, is_published = :published WHERE newsID = :id')
                : $pdo->prepare($uploadedImage !== null
                    ? 'INSERT INTO news (title, body, is_published, image_path) VALUES (:title, :body, :published, :image_path)'
                    : 'INSERT INTO news (title, body, is_published) VALUES (:title, :body, :published)');
            if ($newsId) {
                $values['id'] = $newsId;
            }
            $statement->execute($values);
            if ($uploadedImage !== null) {
                delete_news_image($oldImagePath);
            }
            redirect_admin('News item saved.', 'news');

        case 'news_delete':
            $newsId = (int) $_POST['news_id'];
            $imageStatement = $pdo->prepare('SELECT image_path FROM news WHERE newsID = :id');
            $imageStatement->execute(['id' => $newsId]);
            $imagePath = $imageStatement->fetchColumn() ?: null;
            $statement = $pdo->prepare('DELETE FROM news WHERE newsID = :id');
            $statement->execute(['id' => $newsId]);
            delete_news_image($imagePath);
            redirect_admin('News item deleted.', 'news');

        case 'invoice_create':
            $statement = $pdo->prepare(
                'INSERT INTO invoices (invoice_number, customer_name, customer_email, amount, status, notes)
                 VALUES (:number, :name, :email, :amount, :status, :notes)'
            );
            $statement->execute([
                'number' => trim((string) ($_POST['invoice_number'] ?? '')),
                'name' => trim((string) ($_POST['customer_name'] ?? '')),
                'email' => trim((string) ($_POST['customer_email'] ?? '')),
                'amount' => (float) ($_POST['amount'] ?? 0),
                'status' => (string) ($_POST['status'] ?? 'draft'),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ]);
            redirect_admin('Invoice created.', 'invoices');

        case 'invoice_update':
            $statement = $pdo->prepare('UPDATE invoices SET status = :status WHERE invoiceID = :id');
            $statement->execute(['status' => (string) $_POST['status'], 'id' => (int) $_POST['invoice_id']]);
            redirect_admin('Invoice updated.', 'invoices');

        case 'invoice_delete':
            $statement = $pdo->prepare('DELETE FROM invoices WHERE invoiceID = :id');
            $statement->execute(['id' => (int) $_POST['invoice_id']]);
            redirect_admin('Invoice deleted.', 'invoices');

        case 'user_create':
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
                throw new RuntimeException('Use a 3-20 character username, a valid email, and a password of at least 12 characters.');
            }
            $statement = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'user')");
            $statement->execute(['username' => $username, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            redirect_admin('User account created.', 'users');

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
            redirect_admin('User account updated.', 'users');

        case 'user_delete':
            $statement = $pdo->prepare("DELETE FROM users WHERE userID = :id AND role = 'user'");
            $statement->execute(['id' => (int) ($_POST['user_id'] ?? 0)]);
            redirect_admin('User account deleted.', 'users');

        default:
            throw new RuntimeException('Unknown dashboard action.');
    }
} catch (Throwable $exception) {
    $section = $sections[$action] ?? 'index';
    redirect_admin('Could not save that change: ' . $exception->getMessage(), $section);
}
