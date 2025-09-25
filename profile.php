<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch user info
$user_stmt = $conn->prepare("SELECT * FROM Client WHERE customer_id = ?");
$user_stmt->bind_param("i", $customer_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch booking info
$booking_stmt = $conn->prepare("
    SELECT b.*, c.car_brand, c.car_model, c.car_image, 
           l1.location_name AS pickup_location, l2.location_name AS dropoff_location
    FROM Bookings b
    JOIN Cars c ON b.car_code = c.car_code
    JOIN Locations l1 ON b.pickup_location_id = l1.location_id
    JOIN Locations l2 ON b.dropoff_location_id = l2.location_id
    WHERE b.customer_id = ?
    ORDER BY b.start_date DESC
");
$booking_stmt->bind_param("i", $customer_id);
$booking_stmt->execute();
$bookings = $booking_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SintaDrive News - Summer Deals</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/navigations.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/profile.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include("includes/navigations.php"); ?>
  <div class="profile-container">
    <h1>Welcome, <?php echo htmlspecialchars($user['client_first_name'] . ' ' . $user['client_last_name']); ?>!</h1>

    <!-- User Info -->
    <section class="user-info">
      <h2>User Information</h2>
      <ul>
        <li><strong>Email:</strong> <?php echo htmlspecialchars($user['client_email_address']); ?></li>
        <li><strong>Contact:</strong> <?php echo htmlspecialchars($user['client_contact_number']); ?></li>
        <li><strong>Address:</strong> <?php echo htmlspecialchars($user['client_address']); ?></li>
        <li><strong>License No:</strong> <?php echo htmlspecialchars($user['client_driver_license_number']); ?></li>
      </ul>
    </section>

    <!-- Bookings -->
    <section class="booking-history">
      <h2>Your Bookings</h2>
      <?php if ($bookings->num_rows > 0): ?>
        <?php while ($booking = $bookings->fetch_assoc()): ?>
          <div class="booking-card">
            <img src="images/<?php echo htmlspecialchars($booking['car_image']); ?>" alt="<?php echo htmlspecialchars($booking['car_model']); ?>">
            <div class="booking-details">
              <h3><?php echo htmlspecialchars($booking['car_brand'] . ' ' . $booking['car_model']); ?></h3>
              <p><strong>From:</strong> <?php echo $booking['start_date']; ?> at <?php echo $booking['pickup_time']; ?></p>
              <p><strong>To:</strong> <?php echo $booking['return_date']; ?> at <?php echo $booking['dropoff_time']; ?></p>
              <p><strong>Pickup:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
              <p><strong>Dropoff:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
              <p><strong>Total Cost:</strong> PHP <?php echo number_format($booking['total_cost'], 2); ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No bookings found.</p>
      <?php endif; ?>
    </section>
  </div>
    <?php include("includes/footer.php"); ?>
</body>
</html>
