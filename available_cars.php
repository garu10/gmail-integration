<?php
session_start();
include("includes/db.php");
include("includes/functions.php");

// Default empty variables to prevent undefined errors
$pickup_location_name = '';
$start_date = $_GET['start_date'] ?? '';
$pickup_time = $_GET['pickup_time'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$dropoff_time = $_GET['dropoff_time'] ?? '';
$pickup_location_id = $_GET['pickup_location_id'] ?? '';
$dropoff_location_id = $_GET['dropoff_location_id'] ?? '';

// Prepare SQL query
$sql = "SELECT DISTINCT c.* FROM Cars c
        JOIN Car_status cs ON c.car_code = cs.car_code
        JOIN Car_Location cl ON c.car_code = cl.car_code
        JOIN Locations l ON cl.location_id = l.location_id
        WHERE cs.car_available = 1";

$params = [];
$types = "";

// Filters
if (!empty($pickup_location_id)) {
    $sql .= " AND l.location_id = ?";
    $types .= "i";
    $params[] = $pickup_location_id;

    $stmt_location = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt_location->bind_param("i", $pickup_location_id);
    $stmt_location->execute();
    $stmt_location->bind_result($pickup_location_name);
    $stmt_location->fetch();
    $stmt_location->close();
}

if (!empty($_GET['car_brand'])) {
    $sql .= " AND c.car_brand = ?";
    $types .= "s";
    $params[] = $_GET['car_brand'];
}

if (!empty($_GET['car_type'])) {
    $sql .= " AND c.car_type = ?";
    $types .= "s";
    $params[] = $_GET['car_type'];
}

if (!empty($_GET['car_seater'])) {
    $sql .= " AND c.car_seater = ?";
    $types .= "i";
    $params[] = (int)$_GET['car_seater'];
}

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Cars | SintaDrive</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/navigations.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/available_car.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include("includes/navigations.php"); ?>

<main class="car-results-wrapper">
<section class="search-summary card">
  <h2>Booking Details</h2>
  <div class="booking-grid">
    <div class="booking-item">
      <strong>Pickup Location:</strong>
      <span><?= htmlspecialchars($pickup_location_name) ?></span>
    </div>
    <div class="booking-item">
      <strong>Start Date:</strong>
      <span><?= htmlspecialchars($start_date) ?></span>
    </div>
    <div class="booking-item">
      <strong>Pickup Time:</strong>
      <span><?= htmlspecialchars($pickup_time) ?></span>
    </div>
    <div class="booking-item">
      <strong>Return Date:</strong>
      <span><?= htmlspecialchars($end_date) ?></span>
    </div>
    <div class="booking-item">
      <strong>Drop-off Time:</strong>
      <span><?= htmlspecialchars($dropoff_time) ?></span>
    </div>
  </div>
</section>


  <section class="cars-section">
    <h1 class="section-title">Available Cars</h1>

    <?php if (count($cars) > 0): ?>
      <div class="car-grid">
        <?php foreach ($cars as $car): ?>
          <div class="car card">
            <div class="car-image-container">
              <?php
              $imageFile = 'images/' . $car['car_image'];
              if (!file_exists($imageFile)) {
                  echo "<p style='color:red;'>Image not found: $imageFile</p>";
              }
            ?>

            <img src="images/<?= htmlspecialchars($car['car_image']) ?>" alt="<?= htmlspecialchars($car['car_model']) ?>">
            </div>

            <div class="car-info">
              <h2 class="car-title"><?= htmlspecialchars($car['car_brand'] . ' ' . $car['car_model']) ?></h2>
              <div class="car-attributes">
                <span><i class="fa fa-users"></i> <?= htmlspecialchars($car['car_seater']) ?> Seater</span>
                <span><i class="fa fa-suitcase"></i> <?= htmlspecialchars($car['car_luggage_capacity']) ?> Luggage</span>
                <span><i class="fa fa-cogs"></i> <?= htmlspecialchars($car['car_transmission']) ?></span>
              </div>
              <p class="car-price">₱ <?= number_format($car['car_price_per_day'], 2) ?>/day</p>

              <div class="car-buttons">
                <a href="view_car.php?car_code=<?= urlencode($car['car_code']) ?>&start_date=<?= urlencode($start_date) ?>&pickup_time=<?= urlencode($pickup_time) ?>&end_date=<?= urlencode($end_date) ?>&dropoff_time=<?= urlencode($dropoff_time) ?>&pickup_location_id=<?= urlencode($pickup_location_id) ?>&dropoff_location_id=<?= urlencode($dropoff_location_id) ?>" class="btn btn-view">View</a>

                <a href="book.php?car_code=<?= urlencode($car['car_code']) ?>&start_date=<?= urlencode($start_date) ?>&pickup_time=<?= urlencode($pickup_time) ?>&end_date=<?= urlencode($end_date) ?>&dropoff_time=<?= urlencode($dropoff_time) ?>&pickup_location_id=<?= urlencode($pickup_location_id) ?>&dropoff_location_id=<?= urlencode($dropoff_location_id) ?>" class="btn btn-book">Book Now</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="no-results">No cars found matching your criteria.</p>
    <?php endif; ?>
  </section>
</main>

<?php include("includes/footer.php"); ?>
