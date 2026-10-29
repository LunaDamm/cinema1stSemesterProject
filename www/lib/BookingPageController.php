<?php
declare(strict_types=1);

final class BookingPageController
{
    public function __construct(
        private PDO $pdo,
        private BookingService $bookingService
    ) {
    }

    public function show(array $request): array
    {
        $showtimeId = filter_var(
            $request['showtime_id'] ?? null,
            FILTER_VALIDATE_INT
        );
        if (!$showtimeId) {
            throw new RuntimeException('Choose a showtime before selecting a seat.');
        }

        $showtime = $this->bookingService->findShowtime($showtimeId);
        if (!$showtime) {
            throw new RuntimeException('That showtime is no longer available.');
        }

        if (($request['method'] ?? 'GET') === 'POST') {
            $this->createBooking($request, $showtime, $showtimeId);
        }

        return [
            'showtime' => $showtime,
            'occupiedSeats' => $this->bookingService->occupiedSeats($showtimeId),
        ];
    }

    private function createBooking(array $request, array $showtime, int $showtimeId): void
    {
        $userId = customer_user_id();
        $name = trim((string) ($request['name'] ?? ''));
        $email = trim((string) ($request['email'] ?? ''));

        if ($userId !== null) {
            $userStatement = $this->pdo->prepare(
                "SELECT username, email FROM users WHERE userID = :id AND role = 'user' LIMIT 1"
            );
            $userStatement->execute(['id' => $userId]);
            $user = $userStatement->fetch();
            if (!$user) {
                unset($_SESSION['user_id'], $_SESSION['user_username'], $_SESSION['user_email']);
                throw new RuntimeException('Please sign in again before booking.');
            }
            $name = $user['username'];
            $email = $user['email'];
        }

        $seats = $this->validateSeats($request['seats'] ?? [], $showtime);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new RuntimeException('Please enter your name.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        $_SESSION['booking_confirmation'] = $this->bookingService->createBooking(
            $showtimeId,
            $showtime,
            $seats,
            $name,
            $email,
            $userId
        );
        header('Location: booking_complete.php');
        exit;
    }

    private function validateSeats(mixed $postedSeats, array $showtime): array
    {
        if (!is_array($postedSeats) || count($postedSeats) < 1) {
            throw new RuntimeException('Please choose at least one seat from the seating plan.');
        }

        $seats = [];
        foreach ($postedSeats as $postedSeat) {
            if (
                !is_array($postedSeat) ||
                !isset($postedSeat['row'], $postedSeat['number']) ||
                filter_var($postedSeat['row'], FILTER_VALIDATE_INT) === false ||
                filter_var($postedSeat['number'], FILTER_VALIDATE_INT) === false
            ) {
                throw new RuntimeException('Please choose valid seats from the seating plan.');
            }

            $row = (int) $postedSeat['row'];
            $number = (int) $postedSeat['number'];
            if (
                $row < 1 || $row > (int) $showtime['seat_rows'] ||
                $number < 1 || $number > (int) $showtime['seats_per_row']
            ) {
                throw new RuntimeException('Please choose valid seats from the seating plan.');
            }

            $key = "{$row}-{$number}";
            if (isset($seats[$key])) {
                throw new RuntimeException('A seat cannot be selected more than once.');
            }
            $seats[$key] = ['row' => $row, 'number' => $number];
        }

        if (count($seats) > 10) {
            throw new RuntimeException('You can book a maximum of 10 seats at a time.');
        }

        return array_values($seats);
    }
}
