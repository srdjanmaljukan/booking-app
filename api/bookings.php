<?php
require_once __DIR__ . '/bootstrap.php';

// Svaka akcija ispod zahtijeva validan token
$auth_user = authenticate_request();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Vraća samo rezervacije ulogovanog korisnika (iz tokena, ne iz URL-a —
    // korisnik ne može da vidi tuđe rezervacije mijenjajući parametar)
    $stmt = $pdo->prepare(
        "SELECT bookings.*, services.name AS service_name, services.duration_minutes, services.price
         FROM bookings
         JOIN services ON bookings.service_id = services.id
         WHERE bookings.user_id = :user_id
         ORDER BY bookings.booking_datetime DESC"
    );
    $stmt->execute(['user_id' => $auth_user['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($bookings);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $service_id = $input['service_id'] ?? null;
    $booking_datetime = $input['booking_datetime'] ?? null;

    if ($service_id === null || $booking_datetime === null) {
        http_response_code(400);
        echo json_encode(['error' => 'service_id and booking_datetime are required.']);
        exit;
    }

    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
    $service_stmt->execute(['id' => $service_id]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);

    if ($service === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found.']);
        exit;
    }

    try {
        $requested_start = new DateTime($booking_datetime);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD HH:MM:SS.']);
        exit;
    }

    if ($requested_start < new DateTime()) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot book a time in the past.']);
        exit;
    }

    $requested_end = clone $requested_start;
    $requested_end->modify('+' . $service['duration_minutes'] . ' minutes');

    $date_only = $requested_start->format('Y-m-d');
    $existing_stmt = $pdo->prepare(
        "SELECT bookings.booking_datetime, services.duration_minutes
         FROM bookings
         JOIN services ON bookings.service_id = services.id
         WHERE bookings.status = 'confirmed'
         AND DATE(bookings.booking_datetime) = :date"
    );
    $existing_stmt->execute(['date' => $date_only]);
    $existing_bookings = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existing_bookings as $existing) {
        $existing_start = new DateTime($existing['booking_datetime']);
        $existing_end = clone $existing_start;
        $existing_end->modify('+' . $existing['duration_minutes'] . ' minutes');

        if ($requested_start < $existing_end && $requested_end > $existing_start) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'This time slot overlaps with an existing booking.']);
            exit;
        }
    }

    $insert_stmt = $pdo->prepare(
        "INSERT INTO bookings (user_id, service_id, booking_datetime) VALUES (:user_id, :service_id, :booking_datetime)"
    );
    $insert_stmt->execute([
        'user_id' => $auth_user['user_id'],
        'service_id' => $service_id,
        'booking_datetime' => $requested_start->format('Y-m-d H:i:s'),
    ]);

    http_response_code(201); // Created
    echo json_encode([
        'message' => 'Booking created successfully.',
        'booking_id' => $pdo->lastInsertId(),
    ]);
} elseif ($method === 'DELETE') {
    // id rezervacije koju otkazujemo dolazi kroz query string: ?id=5
    $booking_id = $_GET['id'] ?? null;

    if ($booking_id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking id is required.']);
        exit;
    }

    $cancel_stmt = $pdo->prepare(
        "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND user_id = :user_id"
    );
    $cancel_stmt->execute([
        'id' => $booking_id,
        'user_id' => $auth_user['user_id'],
    ]);

    if ($cancel_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found or does not belong to you.']);
        exit;
    }

    echo json_encode(['message' => 'Booking cancelled successfully.']);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}