<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

// Vraćamo samo osnovne podatke o zauzetim terminima (bez imena korisnika,
// iz privatnosti — drugi korisnici ne treba da vide ko je rezervisao šta)
$stmt = $pdo->query(
    "SELECT bookings.booking_datetime, services.name, services.duration_minutes
     FROM bookings
     JOIN services ON bookings.service_id = services.id
     WHERE bookings.status = 'confirmed'"
);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FullCalendar očekuje niz objekata sa "title", "start", "end" poljima
$events = [];
foreach ($bookings as $booking) {
    $start = new DateTime($booking['booking_datetime']);
    $end = clone $start;
    $end->modify('+' . $booking['duration_minutes'] . ' minutes');

    $events[] = [
        'title' => 'Booked: ' . $booking['name'],
        'start' => $start->format('Y-m-d\TH:i:s'),
        'end' => $end->format('Y-m-d\TH:i:s'),
    ];
}

echo json_encode($events);