<?php
session_start();
require 'config/database.php';
require 'includes/auth.php';

$services_stmt = $pdo->query("SELECT * FROM services ORDER BY name ASC");
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

require 'includes/header.php';
?>

<h1>Available Services</h1>

<div class="service-grid">
    <?php foreach ($services as $service): ?>
        <div class="service-card">
            <h2><?= htmlspecialchars($service['name']) ?></h2>
            <p><?= $service['duration_minutes'] ?> minutes</p>
            <p class="price">$<?= number_format($service['price'], 2) ?></p>

            <?php if (is_logged_in()): ?>
                <a href="book.php?service_id=<?= $service['id'] ?>" class="book-btn">Book Now</a>
            <?php else: ?>
                <a href="login.php" class="book-btn">Login to Book</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<h2>Calendar</h2>
<div id="calendar"></div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            events: 'calendar_events.php',
            height: 'auto',
        });
        calendar.render();
    });
</script>

<?php require 'includes/footer.php'; ?>