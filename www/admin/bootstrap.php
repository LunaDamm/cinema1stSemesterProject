<?php
declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/../database.php';

if (empty($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function verify_csrf(): void
{
    if (
        !hash_equals(
            $_SESSION['csrf_token'] ?? '',
            (string) ($_POST['csrf_token'] ?? '')
        )
    ) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}

function redirect_admin(string $message = '', string $section = 'index'): never
{
    if ($message !== '') {
        $_SESSION['flash'] = $message;
    }
    header('Location: ' . ($section === 'index' ? 'index.php' : $section . '.php'));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function process_news_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('The image must be no larger than 2MB.');
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('The uploaded image could not be verified.');
    }
    if (!extension_loaded('gd')) {
        throw new RuntimeException('Image processing is unavailable on the server.');
    }

    $imageInfo = getimagesize((string) $file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    $source = match ($imageInfo['mime']) {
        'image/jpeg' => imagecreatefromjpeg((string) $file['tmp_name']),
        'image/png' => imagecreatefrompng((string) $file['tmp_name']),
        'image/gif' => imagecreatefromgif((string) $file['tmp_name']),
        'image/webp' => function_exists('imagecreatefromwebp')
            ? imagecreatefromwebp((string) $file['tmp_name'])
            : false,
        default => false,
    };
    if (!$source) {
        throw new RuntimeException('Only JPEG, PNG, GIF, and WebP images are supported.');
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $scale = max(800 / $sourceWidth, 600 / $sourceHeight);
    $resizedWidth = (int) round($sourceWidth * $scale);
    $resizedHeight = (int) round($sourceHeight * $scale);
    $offsetX = (int) (($resizedWidth - 800) / 2);
    $offsetY = (int) (($resizedHeight - 600) / 2);

    $resized = imagecreatetruecolor($resizedWidth, $resizedHeight);
    $output = imagecreatetruecolor(800, 600);
    $background = imagecolorallocate($output, 255, 255, 255);
    imagefill($output, 0, 0, $background);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $resizedWidth, $resizedHeight, $sourceWidth, $sourceHeight);
    imagecopy($output, $resized, 0, 0, $offsetX, $offsetY, 800, 600);

    $directory = dirname(__DIR__) . '/uploads/news';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        imagedestroy($source);
        imagedestroy($resized);
        imagedestroy($output);
        throw new RuntimeException('Could not create the news upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $destination = $directory . '/' . $filename;
    $saved = imagejpeg($output, $destination, 88);

    imagedestroy($source);
    imagedestroy($resized);
    imagedestroy($output);

    if (!$saved) {
        throw new RuntimeException('Could not process the uploaded image.');
    }
    return 'uploads/news/' . $filename;
}

function delete_news_image(?string $imagePath): void
{
    if ($imagePath === null || $imagePath === '') {
        return;
    }
    $absolutePath = dirname(__DIR__) . '/' . ltrim($imagePath, '/');
    $uploadPrefix = dirname(__DIR__) . '/uploads/news/';
    if (str_starts_with(realpath($absolutePath) ?: $absolutePath, $uploadPrefix) && is_file($absolutePath)) {
        unlink($absolutePath);
    }
}
