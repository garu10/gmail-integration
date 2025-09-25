<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include("includes/db.php");
include("includes/functions.php");

// Check if user is logged in (assuming 'customer_id' is stored in session)
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer/login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$bookings_data = [];
$error_message = '';
$flash_message = null;

// --- Handle Car Return Request (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_car') {
    if (isset($_POST['booking_id'])) {
        $booking_id_to_return = $_POST['booking_id'];

        // Start a transaction for atomicity
        $conn->begin_transaction();

        try {
            // Step 1: Fetch booking details including car_code and current booking status
            $stmt = $conn->prepare("SELECT customer_id, car_code, booking_status FROM Bookings WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id_to_return);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking_to_check = $result->fetch_assoc();
            $stmt->close();

            if ($booking_to_check && $booking_to_check['customer_id'] == $customer_id) {
                if ($booking_to_check['booking_status'] === 'ongoing' || $booking_to_check['booking_status'] === 'overdue') {
                    // Step 2: Mark the booking as returned in the Bookings table
                    $update_booking_stmt = $conn->prepare("UPDATE Bookings SET booking_status = 'returned' WHERE booking_id = ?");
                    $update_booking_stmt->bind_param("i", $booking_id_to_return);
                    $update_booking_stmt->execute();
                    $update_booking_stmt->close();

                    // Step 3: Update the Car_status table for the returned car
                    $car_code_to_update = $booking_to_check['car_code'];
                    $update_car_status_stmt = $conn->prepare("UPDATE Car_status SET car_booked = FALSE, car_available = TRUE WHERE car_code = ?");
                    $update_car_status_stmt->bind_param("i", $car_code_to_update);
                    $update_car_status_stmt->execute();
                    $update_car_status_stmt->close();

                    $conn->commit(); // Commit transaction on success
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Car has been successfully marked as returned and is now available.'];
                } else {
                    $conn->rollback(); // Rollback if not ongoing/overdue
                    $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'This booking cannot be marked as returned from its current status (' . ucfirst($booking_to_check['booking_status']) . ').'];
                }
            } else {
                $conn->rollback(); // Rollback if not authorized or booking not found
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'You are not authorized to mark this booking as returned or booking not found.'];
            }
        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Rollback on error
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error processing return request: ' . $e->getMessage()];
            error_log("Return car error: " . $e->getMessage());
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid return request.'];
    }
    header("Location: bookings.php");
    exit();
}

// --- Fetch Bookings Data (GET) ---
try {
    $stmt = $conn->prepare("
        SELECT
            b.booking_id,
            b.start_date,
            b.pickup_time,
            b.return_date,
            b.dropoff_time,
            b.total_cost,
            b.booking_status, -- Fetch the new booking_status
            c.car_model AS car_name,
            c.car_image,
            cs.car_available,
            cs.car_booked
        FROM
            Bookings b
        JOIN
            Cars c ON b.car_code = c.car_code
        LEFT JOIN
            Car_status cs ON c.car_code = cs.car_code
        WHERE
            b.customer_id = ?
        ORDER BY
            b.start_date DESC, b.pickup_time DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $raw_bookings_result = $stmt->get_result();
    $raw_bookings = $raw_bookings_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Set default timezone to avoid warnings and ensure correct time comparison
    date_default_timezone_set('Asia/Manila');
    $now = new DateTime();

    foreach ($raw_bookings as $booking) {
        $start_datetime_str = $booking['start_date'] . ' ' . $booking['pickup_time'];
        $end_datetime_str = $booking['return_date'] . ' ' . $booking['dropoff_time'];

        $booking_start = new DateTime($start_datetime_str);
        $booking_end = new DateTime($end_datetime_str);

        $status = $booking['booking_status']; // Use the status from the database
        $time_info = null;

        // Refine status based on current time *if* payment is approved/confirmed
        if ($status === 'confirmed') {
            if ($now >= $booking_start && $now <= $booking_end) {
                $status = 'ongoing';
                // Update booking status in DB to ongoing for real-time reflection
                $update_status_stmt = $conn->prepare("UPDATE Bookings SET booking_status = 'ongoing' WHERE booking_id = ?");
                $update_status_stmt->bind_param("i", $booking['booking_id']);
                $update_status_stmt->execute();
                $update_status_stmt->close();
            } elseif ($now > $booking_end) {
                // If the confirmed booking's end date has passed, it should be overdue
                $status = 'overdue';
                // Update booking status in DB to overdue
                $update_status_stmt = $conn->prepare("UPDATE Bookings SET booking_status = 'overdue' WHERE booking_id = ?");
                $update_status_stmt->bind_param("i", $booking['booking_id']);
                $update_status_stmt->execute();
                $update_status_stmt->close();
            } else { // $now < $booking_start
                $status = 'upcoming'; // Already 'confirmed', but if it's in the future
            }
        }

        // Calculate time info for ongoing and overdue bookings
        if ($status === 'ongoing') {
            $interval = $booking_end->diff($now);
            $time_info = "";
            if ($interval->days > 0) $time_info .= $interval->days . " days, ";
            $time_info .= $interval->h . " hours, " . $interval->i . " minutes left";
        } elseif ($status === 'overdue') {
            $interval = $now->diff($booking_end);
            $time_info = "Overdue by ";
            if ($interval->days > 0) $time_info .= $interval->days . " days, ";
            $time_info .= $interval->h . " hours, " . $interval->i . " minutes";
        }

        $bookings_data[] = [
            'id' => $booking['booking_id'],
            'car_name' => htmlspecialchars($booking['car_name']),
            'car_image' => htmlspecialchars($booking['car_image']),
            'total_amount' => number_format($booking['total_cost'], 2),
            'start_date' => $booking_start->format('Y-m-d H:i'),
            'end_date' => $booking_end->format('Y-m-d H:i'),
            'status' => $status,
            'time_info' => $time_info,
            'is_pending_payment' => ($status === 'pending_payment'),
            'is_confirmed' => ($status === 'confirmed'),
            'is_ongoing' => ($status === 'ongoing'),
            'is_upcoming' => ($status === 'upcoming'), // This will be 'confirmed' if not yet started
            'is_overdue' => ($status === 'overdue'),
            'is_returned' => ($status === 'returned'),
            'car_currently_available' => $booking['car_available'] ?? null,
            'car_currently_booked' => $booking['car_booked'] ?? null
        ];
    }

} catch (mysqli_sql_exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Fetch bookings error: " . $e->getMessage());
}

// Flash message display (if any)
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Car Bookings - Car Rental System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/available_cars.css">
    <link rel="stylesheet" href="css/book.css">
    <link rel="stylesheet" href="css/bookings.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
</head>
<body>
    <?php include("includes/navigations.php"); ?>

    <div class="main-content"> <div class="bookings-page">
        <h1 class="page-title">My Car Bookings</h1>

        <?php if ($flash_message): ?>
            <div class="flash-message flash-<?= htmlspecialchars($flash_message['type']) ?>">
                <?= htmlspecialchars($flash_message['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php elseif (!empty($bookings_data)): ?>
            <div class="bookings-grid">
                <?php foreach ($bookings_data as $booking): ?>
                    <div class="car-container booking-card <?= strtolower($booking['status']) ?>">
                        <div class="car-left">
                            <h3 class="car-name"><?= $booking['car_name'] ?></h3>
                            <img src="images/<?= $booking['car_image'] ?>" alt="<?= $booking['car_name'] ?>" class="car-image">
                        </div>
                        <div class="car-right">
                            <div class="car-info">
                                <h3><strong>Booking ID:</strong> #<?= $booking['id'] ?></h3>
                                <h3><strong>Total Amount:</strong> ₱<?= $booking['total_amount'] ?></h3>
                                <h3><strong>Start Date:</strong> <?= $booking['start_date'] ?></h3>
                                <h3><strong>End Date:</strong> <?= $booking['end_date'] ?></h3>
                                <h3><strong>Status:</strong> <span class="status-badge"><?= htmlspecialchars(ucfirst($booking['status'])) ?></span></h3>

                                <?php if ($booking['is_ongoing'] || $booking['is_overdue']): ?>
                                    <h3><strong>Time Info:</strong> <span class="countdown"><?= $booking['time_info'] ?></span></h3>
                                    <?php if ($booking['is_ongoing'] || ($booking['is_overdue'] && !$booking['is_returned'])): ?>
                                        <form action="bookings.php" method="POST" onsubmit="return confirm('Are you sure you want to mark this car as returned?');">
                                            <input type="hidden" name="action" value="return_car">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" class="btn return-btn">Mark as Returned</button>
                                        </form>
                                    <?php endif; ?>
                                <?php elseif ($booking['is_pending_payment']): ?>
                                    <h3 class="text-orange-600">Awaiting payment approval from administrator.</h3>
                                <?php elseif ($booking['is_upcoming']): ?>
                                    <h3 class="text-blue-600">Booking confirmed! Starts soon.</h3>
                                <?php elseif ($booking['is_returned']): ?>
                                    <h3 class="returned-info">This booking is completed.</h3>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-bookings-message">You don't have any car bookings yet.</p>
            <p><a href="index.php" class="btn">Browse Cars to Rent</a></p>
        <?php endif; ?>
    </div>

    </div> <?php include("includes/footer.php"); ?>
</body>
</html>