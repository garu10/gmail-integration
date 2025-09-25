<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("includes/db.php");
include("includes/functions.php");

// Redirect if the user is not logged in (using the correct session variable: customer_id)
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer/login.php"); // Corrected path to login.php
    exit();
}

$car_code = $_GET['car_code'] ?? null;
if (!$car_code) {
    die("Error: Car not specified. Please go back to <a href='car_listing.php'>Car Listing</a> to select a car.");
}

// Fetch car details
$car_stmt = $conn->prepare("SELECT * FROM Cars WHERE car_code = ?");
$car_stmt->bind_param("i", $car_code);
$car_stmt->execute();
$car_result = $car_stmt->get_result();
$car = $car_result->fetch_assoc();
$car_stmt->close();

if (!$car) {
    die("Error: Car not found. Please go back to <a href='car_listing.php'>Car Listing</a> to select a valid car.");
}

// Retrieve booking data from GET parameters (for initial display)
$start_date = $_GET['start_date'] ?? '';
$pickup_time = $_GET['pickup_time'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$dropoff_time = $_GET['dropoff_time'] ?? '';
$pickup_location_id = isset($_GET['pickup_location_id']) ? (int)$_GET['pickup_location_id'] : 0;
$dropoff_location_id = isset($_GET['dropoff_location_id']) ? (int)$_GET['dropoff_location_id'] : 0;

// Fetch pickup location name
$pickup_location_name = '';
if ($pickup_location_id > 0) {
    $stmt_pickup = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt_pickup->bind_param("i", $pickup_location_id);
    $stmt_pickup->execute();
    $stmt_pickup->bind_result($pickup_location_name);
    $stmt_pickup->fetch();
    $stmt_pickup->close();
}

// Fetch dropoff location name
$dropoff_location_name = '';
if ($dropoff_location_id > 0) {
    $stmt_dropoff = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt_dropoff->bind_param("i", $dropoff_location_id);
    $stmt_dropoff->execute();
    $stmt_dropoff->bind_result($dropoff_location_name);
    $stmt_dropoff->fetch();
    $stmt_dropoff->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Booking | SintaDrive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include your existing CSS plus new book.css -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/book_reserve.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
</head>
<body>
<?php include("includes/navigations.php"); ?>

<main class="confirm-booking-page">
    <h1 class="page-title">Confirm Booking</h1>

    <div class="car-details-container">
    <div class="car-image-section">
        <h2 class="car-name"><?= htmlspecialchars($car['car_brand'] . ' ' . $car['car_model']) ?></h2>
        <div class="car-image-wrapper">
        <img src="images/<?= htmlspecialchars($car['car_image']) ?>" alt="<?= htmlspecialchars($car['car_model']) ?>" class="car-image">
        </div>
        <p class="car-price-view">
        <strong>PHP <?= htmlspecialchars($car['car_price_per_day']) ?> <span class="price-unit">/day</span></strong>
        </p>
    </div>
    <div class="car-features-section">
        <div class="car-top-row">
        <div class="attribute"><i class="fa fa-users"></i> <?= htmlspecialchars($car['car_seater']) ?> Seater</div>
        <div class="attribute"><i class="fa fa-suitcase"></i> <?= htmlspecialchars($car['car_luggage_capacity']) ?> Luggage</div>
        <div class="attribute"><i class="fa fa-cogs"></i> <?= htmlspecialchars($car['car_transmission']) ?></div>
        </div>
        <div class="car-info-details">
        <h3><strong>Transmission:</strong> <?= htmlspecialchars($car['car_transmission']) ?></h3>
        <h3><strong>Drive Type:</strong> <?= htmlspecialchars($car['car_drivetype']) ?></h3>
        <h3><strong>Mileage:</strong> <?= htmlspecialchars($car['car_mileage']) ?> km</h3>
        <h3><strong>Features:</strong> <?= htmlspecialchars($car['car_features']) ?></h3>
        </div>
    </div>
    </div>


    <!-- Booking form: submits directly to payment.php -->
    <form method="POST" action="payment.php" class="booking-form">
        <div class="booking-details-container">
            <h2>Booking Information</h2>
            <div class="booking-dates">
                <div class="date-group">
                    <label for="start_date">Pickup Date</label>
                    <input type="text" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>" required autocomplete="off">

                    <label for="pickup_time">Pickup Time</label>
                    <input type="time" name="pickup_time" id="pickup_time" value="<?= htmlspecialchars($pickup_time) ?>" required>
                </div>
                <div class="date-group">
                    <label for="end_date">Return Date</label>
                    <input type="text" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>" required autocomplete="off">

                    <label for="dropoff_time">Return Time</label>
                    <input type="time" name="dropoff_time" id="dropoff_time" value="<?= htmlspecialchars($dropoff_time) ?>" required>
                </div>
            </div>

            <div class="locations-info">
                <div><strong>Pickup Location:</strong> <?= htmlspecialchars($pickup_location_name) ?></div>
                <div><strong>Drop-off Location:</strong> <?= htmlspecialchars($dropoff_location_name) ?></div>
            </div>

            <!-- Hidden fields for processing -->
            <input type="hidden" name="car_code" value="<?= htmlspecialchars($car_code) ?>">
            <input type="hidden" name="pickup_location_id" value="<?= htmlspecialchars($pickup_location_id) ?>">
            <input type="hidden" name="dropoff_location_id" value="<?= htmlspecialchars($dropoff_location_id) ?>">
        </div>

        <div class="submit-container">
            <button type="submit" class="confirm-booking-btn">Confirm Booking</button>
        </div>
    </form>
</main>

<?php include("includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js"></script>
<script>
    // Litepicker date pickers
    const pickupPicker = new Litepicker({
        element: document.getElementById('start_date'),
        singleMode: true,
        format: 'YYYY-MM-DD',
        minDate: new Date()
    });
    const returnPicker = new Litepicker({
        element: document.getElementById('end_date'),
        singleMode: true,
        format: 'YYYY-MM-DD',
        minDate: new Date()
    });
</script>
</body>
</html>
