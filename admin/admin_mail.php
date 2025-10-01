
<?php 
// Change the require path to be relative to the current file's directory
require __DIR__ . '/mail_bootstrap/bootstrap.config.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin_mail.css">
  <title>Sinta Drive - PHP MAILER</title>
</head>
<body>
  <h1 class="text-center">
    <div class="logo">Admin Panel</div>

    <ul class="nav-links">
      <li><a href="admin_dashboard.php">Dashboard</a></li>
      <li><a href="admin_cars.php">Manage Cars</a></li>
      <li><a href="admin_bookings.php">Manage Bookings</a></li>
      <li><a href="admin_clients.php">Manage Clients</a></li>
      <li><a href="admin_locations.php">Manage Locations</a></li>
      <li><a href="admin_car_status.php">Manage Car Status</a></li>
      <li><a href="admin_payments.php">Payments</a></li>
    </ul>

    <a href="logout.php" class="btn-logout">Logout</a>
  </h1>

  <div class="title-container"> Client Email Verification </div> 

  <div class="container">
    <form  action="admin_main_mail.php" method="POST" enctype="multipart/form-data">
      
      <label for="email">Email</label>
      <input type="email" name="email" id="email" class="form-control mb-3" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>" required>

      <label for="subject">Subject</label>
      <input type="text" name="subject" id="subject" class="form-control mb-3" required>

      <label for="message">Message</label>
      <textarea name="message" id="message" class="form-control mb-3" cols="30" rows="10" required></textarea>

      <label for="attachment">Attachment</label>
      <input type="file" name="attachment" id="attachment" class="form-control mb-3">

      <input type="submit" class="btn btn-primary" value="Send Email">
    </form>
  </div>
  
</body>
</html>