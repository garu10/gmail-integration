<?php include("includes/db.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <title>Cars in Category</title>
</head>
<body>
<?php include("includes/navigations.php"); ?>

<div class="main-container">
    <?php
    $category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
    echo "<h1>" . htmlspecialchars($category) . "</h1>";
    ?>

    <div class="car-list">
        <?php
        if ($category) {
            // Join Cars with Car_status using car_code (NOT car_statusid anymore)
            $sql = "SELECT c.* 
                    FROM Cars c
                    JOIN Car_status s ON c.car_code = s.car_code
                    WHERE c.car_category = '$category' 
                      AND s.car_available = TRUE 
                      AND s.car_booked = FALSE 
                      AND s.car_maintenance = FALSE";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($car = $result->fetch_assoc()) {
                    echo "<div class='car'>";
                    if (!empty($car['car_image'])) {
            echo "<img src='images/" . htmlspecialchars($car['car_image']) . "' alt='Car image' style='width: 200px; height: auto;'><br>";
        }

                    echo "<h3>" . htmlspecialchars($car['car_brand']) . " " . htmlspecialchars($car['car_model']) . "</h3>";
                    echo "<p>Category: " . htmlspecialchars($car['car_category']) . "</p>";
                    echo "<p>Price per day: $" . htmlspecialchars($car['car_price_per_day']) . "</p>";
                    echo "<a href='book.php?car_code=" . urlencode($car['car_code']) . "'>Book Now</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>No available cars in this category.</p>";
            }
        } else {
            echo "<p>No category selected.</p>";
        }
        ?>
    </div>
</div>
</body>
</html>
