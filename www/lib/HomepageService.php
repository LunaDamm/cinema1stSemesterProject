<?php
declare(strict_types=1);

final class HomepageService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function load(): array
    {
        $movies = $this->pdo->query(
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

        $showtimes = $this->pdo->query(
            "SELECT st.showtimeID, st.movieID, st.starts_at, st.price, t.name AS theater
             FROM showtimes st
             JOIN theaters t ON t.theaterID = st.theaterID
             WHERE st.starts_at >= NOW()
             ORDER BY st.starts_at ASC
             LIMIT 12"
        )->fetchAll();

        $theaters = $this->pdo->query(
            "SELECT name, seat_rows * seats_per_row AS seats
             FROM theaters
             ORDER BY theaterID"
        )->fetchAll();

        $settings = [];
        foreach ($this->pdo->query('SELECT setting_key, setting_value FROM site_settings') as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }

        $news = $this->pdo->query(
            'SELECT title, body, published_at FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3'
        )->fetchAll();

        $featuredStatement = $this->pdo->query(
            "SELECT st.showtimeID, st.starts_at, m.title, t.name AS theater
             FROM showtimes st
             JOIN movies m ON m.movieID = st.movieID
             JOIN theaters t ON t.theaterID = st.theaterID
             WHERE st.is_featured = 1 AND st.starts_at >= NOW()
             ORDER BY st.starts_at ASC
             LIMIT 1"
        );

        return [
            'movies' => $movies,
            'showtimes' => $showtimes,
            'theaters' => $theaters,
            'settings' => $settings,
            'news' => $news,
            'featured_showtime' => $featuredStatement->fetch() ?: null,
        ];
    }
}
