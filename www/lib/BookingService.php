<?php
declare(strict_types=1);

final class BookingService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findShowtime(int $showtimeId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT st.showtimeID, st.starts_at, st.price, m.title,
                    t.theaterID, t.name AS theater, t.seat_rows, t.seats_per_row
             FROM showtimes st
             JOIN movies m ON m.movieID = st.movieID
             JOIN theaters t ON t.theaterID = st.theaterID
             WHERE st.showtimeID = :showtime_id AND st.starts_at >= NOW()"
        );
        $statement->execute(['showtime_id' => $showtimeId]);

        $showtime = $statement->fetch();
        return $showtime ?: null;
    }

    public function occupiedSeats(int $showtimeId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT seat_row, seat_number FROM bookings WHERE showtimeID = :showtime_id'
        );
        $statement->execute(['showtime_id' => $showtimeId]);

        $occupiedSeats = [];
        foreach ($statement as $seat) {
            $occupiedSeats[(int) $seat['seat_row'] . '-' . (int) $seat['seat_number']] = true;
        }

        return $occupiedSeats;
    }

    public function createBooking(
        int $showtimeId,
        array $showtime,
        array $seats,
        string $name,
        string $email,
        ?int $userId = null
    ): array {
        try {
            $this->pdo->beginTransaction();

            if ($userId === null) {
                $userStatement = $this->pdo->prepare(
                    'SELECT userID FROM users WHERE email = :email LIMIT 1'
                );
                $userStatement->execute(['email' => $email]);
                $userId = (int) $userStatement->fetchColumn();
            }

            if (!$userId) {
                $createUser = $this->pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role)
                     VALUES (:username, :email, :password_hash, 'user')"
                );
                $createUser->execute([
                    'username' => 'guest_' . substr(hash('sha256', strtolower($email)), 0, 14),
                    'email' => $email,
                    'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ]);
                $userId = (int) $this->pdo->lastInsertId();
            }

            $createBooking = $this->pdo->prepare(
                "INSERT INTO bookings (userID, showtimeID, seat_row, seat_number, price_paid)
                 VALUES (:user_id, :showtime_id, :seat_row, :seat_number, :price)"
            );
            $bookingId = null;
            foreach ($seats as $seat) {
                $createBooking->execute([
                    'user_id' => $userId,
                    'showtime_id' => $showtimeId,
                    'seat_row' => $seat['row'],
                    'seat_number' => $seat['number'],
                    'price' => $showtime['price'],
                ]);
                $bookingId ??= (int) $this->pdo->lastInsertId();
            }

            $this->pdo->commit();

            return [
                'booking_id' => $bookingId,
                'movie_title' => $showtime['title'],
                'theater' => $showtime['theater'],
                'starts_at' => $showtime['starts_at'],
                'price' => $showtime['price'],
                'seats' => $seats,
                'name' => $name,
                'email' => $email,
            ];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new RuntimeException(
                    'One of those seats was just taken. Please choose another selection.',
                    0,
                    $exception
                );
            }
            throw $exception;
        }
    }
}
