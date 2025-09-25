<?php
include("includes/db.php");
include("includes/functions.php");

if (!isset($_GET['car_code'])) {
    echo "No car selected.";
    exit;
}

$car_code = $_GET['car_code'];
$sql = "SELECT c.*, cs.car_available, cs.car_booked, cs.car_maintenance
        FROM Cars c
        LEFT JOIN Car_status cs ON c.car_code = cs.car_code
        WHERE c.car_code = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $car_code);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();

if (!$car) {
    echo "Car not found.";
    exit;
}

// Get booking parameters
$pickup_id = $_GET['pickup_location_id'] ?? '';
$dropoff_id = $_GET['dropoff_location_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$pickup_time = $_GET['pickup_time'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$dropoff_time = $_GET['dropoff_time'] ?? '';

// Fetch location names
$pickup_location_name = '';
$dropoff_location_name = '';

if (!empty($pickup_id)) {
    $stmt = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt->bind_param("i", $pickup_id);
    $stmt->execute();
    $stmt->bind_result($pickup_location_name);
    $stmt->fetch();
    $stmt->close();
}

if (!empty($dropoff_id)) {
    $stmt = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt->bind_param("i", $dropoff_id);
    $stmt->execute();
    $stmt->bind_result($dropoff_location_name);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Car Details</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/view_car.css"> <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<?php include("includes/navigations.php"); ?>

<div class="search-summary">
    <h2>Booking Details</h2>
    <ul>
        <li><strong>Pickup Location:</strong> <?= htmlspecialchars($pickup_location_name) ?></li>
        <li><strong>Dropoff Location:</strong> <?= htmlspecialchars($dropoff_location_name) ?></li>
        <li><strong>Start Date:</strong> <?= htmlspecialchars($start_date) ?></li>
        <li><strong>Pickup Time:</strong> <?= htmlspecialchars($pickup_time) ?></li>
        <li><strong>Return Date:</strong> <?= htmlspecialchars($end_date) ?></li>
        <li><strong>Drop-off Time:</strong> <?= htmlspecialchars($dropoff_time) ?></li>
    </ul>
</div>

<div class="car-details-wrapper-view">
    <div class="car-container-view">
        <div class="car-left-view">
            <div class="car-title-view">
                <h2><?= $car['car_brand'] ?> <?= $car['car_model'] ?></h2>
            </div>
            <div class="car-image-view">
                <img src="images/<?= $car['car_image'] ?>" alt="<?= $car['car_brand'] ?> <?= $car['car_model'] ?>">
                <p class="car-price-view">
                    <strong>PHP <?= htmlspecialchars($car['car_price_per_day']) ?><span class="price-unit">/day</span></strong>
                </p>
                <a href="book.php?car_code=<?= urlencode($car['car_code']) ?>&start_date=<?= urlencode($start_date) ?>&pickup_time=<?= urlencode($pickup_time) ?>&end_date=<?= urlencode($end_date) ?>&dropoff_time=<?= urlencode($dropoff_time) ?>&pickup_location_id=<?= urlencode($pickup_id) ?>&dropoff_location_id=<?= urlencode($dropoff_id) ?>" class="view-car-btn">RESERVE NOW</a>
            </div>
        </div>
        <div class="car-right-view">
            <div class="information-overview">
                <h2>Overview</h2>
            </div>
            <div class="top-specs-view">
                <div><i class="fa fa-users" aria-hidden="true"></i> <?= htmlspecialchars($car['car_seater']) ?> Seater</div>
                <div><i class="fa fa-suitcase" aria-hidden="true"></i> <?= htmlspecialchars($car['car_luggage_capacity']) ?> Luggage</div>
                <div><i class="fa fa-cogs" aria-hidden="true"></i> <?= htmlspecialchars($car['car_transmission']) ?></div>
            </div>
            <div class="details-box-view">
                <p><strong>Transmission Type:</strong> <?= $car['car_transmission'] ?></p>
                <p><strong>Fuel Type:</strong> <?= $car['fuel_type'] ?></p>
                <p><strong>Drive Type:</strong> <?= $car['car_drivetype'] ?></p>
                <p><strong>Car Mileage:</strong> <?= $car['car_mileage'] ?></p>
                <p><strong>Car Features:</strong> <?= $car['car_features'] ?></p>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>