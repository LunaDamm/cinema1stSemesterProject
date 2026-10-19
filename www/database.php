<?php
declare(strict_types=1);

return new PDO(
    'mysql:host=database;port=3306;dbname=' . ($_ENV['MOVIE_DATABASE'] ?? 'movieDB') . ';charset=utf8mb4',
    $_ENV['MYSQL_USER'] ?? 'root',
    $_ENV['MYSQL_PASSWORD'] ?? ($_ENV['MYSQL_ROOT_PASSWORD'] ?? ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
