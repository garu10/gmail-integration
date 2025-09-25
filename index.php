<?php
session_start();
include("includes/db.php"); // Ensure db.php is included
include("includes/functions.php"); // Ensure functions.php is included
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js"></script>
    <title>Car Rental Home</title>
</head>
<body>
<?php include("includes/navigations.php"); ?>
<video autoplay muted loop playsinline class="banner-video">
    <source src="images/home_bg.mp4" type="video/mp4">
</video>

<div class="home-wrapper">
    <div class="home-content">
        <div class="home-text">
            <h1><span>Rent the Car, </span><span class="otr">Own the Road!</span></h1>
            <p>Need a ride for your next adventure or business trip? We’ve got the perfect car waiting for you.</p>
            <a href="#search-form">RENT NOW</a>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.style.right = sidebar.style.right === "0px" ? "-250px" : "0px";
}
</script>

<div class="car-categories-text">
    <h1><span>More cars. More choices.</span> <span class="only">Only</span> <span>at Sintadrive.</span></h1>
    <div class="form-container" id="search-form"> 
        <form action="available_cars.php" method="get" class="search-form">
            <div class="form-section">
                <div class="form-group">
                    <label>Pickup Date/Time:</label>
                    <input type="text" id="pickup_date" name="start_date" placeholder="Select Pickup Date" required>
                </div>

                <div class="form-group">
                    <select name="pickup_time" required>
                        <option value="">Pickup Time</option>
                        <?php
                        for ($h = 0; $h < 24; $h++) {
                            foreach (['00', '30'] as $m) {
                                $time = sprintf('%02d:%s', $h, $m);
                                echo "<option value=\"$time\">$time</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <div class="form-group">
                    <label>Return Date/Time:</label>
                    <input type="text" id="return_date" name="end_date" placeholder="Select Return Date" required>
                </div>
                <div class="form-group">
                    <select name="dropoff_time" required>
                        <option value="">Dropoff Time</option>
                        <?php
                        for ($h = 0; $h < 24; $h++) {
                            foreach (['00', '30'] as $m) {
                                $time = sprintf('%02d:%s', $h, $m);
                                echo "<option value=\"$time\">$time</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <div class="form-group">
                    <label>Pickup Location:</label>
                    <select name="pickup_location_id" required>
                        <option value="" disabled selected>Select Pickup Location</option>
                        <?php
                        $loc_result = $conn->query("SELECT * FROM Locations");
                        while ($loc = $loc_result->fetch_assoc()) {
                            echo "<option value='" . $loc['location_id'] . "'>" . htmlspecialchars($loc['location_name']) . "</option>";
                        }
                        $loc_result->data_seek(0);
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dropoff Location:</label>
                    <select name="dropoff_location_id" required>
                        <option value="" disabled selected>Select Dropoff Location</option>
                        <?php
                        while ($loc = $loc_result->fetch_assoc()) {
                            echo "<option value='" . $loc['location_id'] . "'>" . htmlspecialchars($loc['location_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

</div>

<script>
    const pickupPicker = new Litepicker({
        element: document.getElementById('pickup_date'),
        singleMode: true,
        format: 'YYYY-MM-DD',
        minDate: new Date()
    });

    const returnPicker = new Litepicker({
        element: document.getElementById('return_date'),
        singleMode: true,
        format: 'YYYY-MM-DD',
        minDate: new Date()
    });
</script>

<div class="car-categories-home">
    <?php
    $categories = ['Luxury Cars', 'Sedans', 'Mid-size MPVs', 'SUVs', 'Vans', 'Family Cars'];
    foreach ($categories as $cat) {
        echo "<div class='category-box'>
                <img src='images/" . strtolower(str_replace(' ', '_', $cat)) . ".webp' alt='$cat'>
                <p>$cat</p>
            </div>";
    }
    ?>
</div>
<div class="home-banners">
    <h1><span class="banner-texts">Start Your Engines: Sintadrive Updates Are Here!</span></h1>
    
    <a href="news.php" class="banner-link">
        <div class="banner">
            <img src="images/summerdeals.jpg" alt="Summer Deals">
            <div class="banner-text">
                <h2>Summer Deals</h2>
                <p>Get up to 30% off on SUV rentals!</p>
            </div>
        </div>
    </a>
    
    <a href="news2.php" class="banner-link">
        <div class="banner">
            <img src="images/luxurydrive.jpg" alt="Business Rides">
            <div class="banner-text">
                <h2>Business Rides</h2>
                <p>Lux sedans now available for your executive needs.</p>
            </div>
        </div>
    </a>
    
    <a href="news3.php" class="banner-link">
        <div class="banner">
            <img src="images/familytrip.jpg" alt="Family Trips">
            <div class="banner-text">
                <h2>Family Trips</h2>
                <p>Spacious vans perfect for weekend getaways.</p>
            </div>
        </div>
    </a>
</div>
<br><br><br><br>

<?php include("includes/footer.php"); ?>
</body>
</html>
