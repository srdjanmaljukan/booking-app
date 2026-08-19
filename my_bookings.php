<?php
session_start();
require 'config/database.php';
require 'includes/auth.php';
require_login();

// Otkazivanje rezervacije
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;

    if ($booking_id !== null) {
        // Provjeravamo da rezervacija pripada baš ovom ulogovanom korisniku,
        // da neko ne bi mogao da otkaže tuđu rezervaciju mijenjajući booking_id ručno
        $cancel_stmt = $pdo->prepare(
            "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND user_id = :user_id"
        );
        $cancel_stmt->execute([
            'id' => $booking_id,
            'user_id' => $_SESSION['user_id'],
        ]);
    }

    header('Location: my_bookings.php');
    exit;
}

$bookings_stmt = $pdo->prepare(
    "SELECT bookings.*, services.name, services.duration_minutes, services.price
     FROM bookings
     JOIN services ON bookings.service_id = services.id
     WHERE bookings.user_id = :user_id
     ORDER BY bookings.booking_datetime DESC"
);
$bookings_stmt->execute(['user_id' => $_SESSION['user_id']]);
$bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

require 'includes/header.php';
?>

<h1>My Bookings</h1>

<?php if (count($bookings) === 0): ?>
    <p>You have no bookings yet. <a href="index.php">Browse services</a>.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Date & Time</th>
                <th>Price</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['name']) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($booking['booking_datetime'])) ?></td>
                    <td>$<?= number_format($booking['price'], 2) ?></td>
                    <td>
                        <span class="status <?= $booking['status'] ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <form method="POST" action="my_bookings.php" class="inline-form">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <button type="submit" class="reject" onclick="return confirm('Cancel this booking?')">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>