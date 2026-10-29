<?php
declare(strict_types=1);

final class BookingHistoryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT b.bookingID, b.seat_row, b.seat_number, b.price_paid, b.created_at,
                    st.starts_at, t.name AS theater, m.title
             FROM bookings b
             INNER JOIN showtimes st ON st.showtimeID = b.showtimeID
             INNER JOIN theaters t ON t.theaterID = st.theaterID
             INNER JOIN movies m ON m.movieID = st.movieID
             WHERE b.userID = :user_id
             ORDER BY st.starts_at DESC, b.bookingID DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }
}
