<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
    <?php include("includes/navigations.php"); ?>
    <h1>Booking Confirmation</h1>
    <div class="message">
        <?php
        if (isset($_SESSION['booking_success_message'])) {
            echo $_SESSION['booking_success_message'];
            unset($_SESSION['booking_success_message']); 
        } else {
            echo "Your booking has been processed. Thank you!";
        }
        ?>
        <p><a href="index.php">Go back to Home</a></p>
    </div>
    <?php include("includes/footer.php"); ?>
</body>
</html>