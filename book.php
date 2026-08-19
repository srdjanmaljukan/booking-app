<?php
session_start();
require 'config/database.php';
require 'includes/auth.php';
require_login();

$service_id = $_GET['service_id'] ?? null;

if ($service_id === null) {
    header('Location: index.php');
    exit;
}

$service_stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
$service_stmt->execute(['id' => $service_id]);
$service = $service_stmt->fetch(PDO::FETCH_ASSOC);

if ($service === false) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if ($date === '' || $time === '') {
        $error_message = 'Please select a date and time.';
    } else {
        $booking_datetime = $date . ' ' . $time . ':00';
        $requested_start = new DateTime($booking_datetime);
        $requested_end = clone $requested_start;
        $requested_end->modify('+' . $service['duration_minutes'] . ' minutes');

        // Ne dozvoljavamo rezervaciju u prošlosti
        if ($requested_start < new DateTime()) {
            $error_message = 'You cannot book a time in the past.';
        } else {
            // Provjeravamo preklapanje sa postojećim terminima
            // Uzimamo sve rezervacije istog dana da ih provjerimo u PHP-u
            $existing_stmt = $pdo->prepare(
                "SELECT bookings.booking_datetime, services.duration_minutes
                 FROM bookings
                 JOIN services ON bookings.service_id = services.id
                 WHERE bookings.status = 'confirmed'
                 AND DATE(bookings.booking_datetime) = :date"
            );
            $existing_stmt->execute(['date' => $date]);
            $existing_bookings = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);

            $has_conflict = false;
            foreach ($existing_bookings as $existing) {
                $existing_start = new DateTime($existing['booking_datetime']);
                $existing_end = clone $existing_start;
                $existing_end->modify('+' . $existing['duration_minutes'] . ' minutes');

                // Dva termina se preklapaju ako jedan počinje prije nego što se drugi završi, i obrnuto
                if ($requested_start < $existing_end && $requested_end > $existing_start) {
                    $has_conflict = true;
                    break;
                }
            }

            if ($has_conflict) {
                $error_message = 'This time slot overlaps with an existing booking. Please choose another time.';
            } else {
                $insert_stmt = $pdo->prepare(
                    "INSERT INTO bookings (user_id, service_id, booking_datetime) VALUES (:user_id, :service_id, :booking_datetime)"
                );
                $insert_stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'service_id' => $service_id,
                    'booking_datetime' => $booking_datetime,
                ]);

                header('Location: my_bookings.php');
                exit;
            }
        }
    }
}

require 'includes/header.php';
?>

<h1>Book: <?= htmlspecialchars($service['name']) ?></h1>
<p><?= $service['duration_minutes'] ?> minutes — $<?= number_format($service['price'], 2) ?></p>

<?php if ($error_message !== ''): ?>
    <p class="error"><?= htmlspecialchars($error_message) ?></p>
<?php endif; ?>

<form method="POST" action="book.php?service_id=<?= $service['id'] ?>">
    <label>
        Date
        <input type="date" name="date" required>
    </label>

    <label>
        Time
        <input type="time" name="time" required>
    </label>

    <button type="submit">Confirm Booking</button>
</form>

<?php require 'includes/footer.php'; ?>