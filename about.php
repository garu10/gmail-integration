<?php
// about.php
include("includes/db.php");
include("includes/functions.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - SintaDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/available_cars.css"> <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/about.css"> 

</head>
<body>
    <?php include("includes/navigations.php"); ?> <!-- Optional navigation/header -->

        <!-- About -->
        <section class="about-description">
        <div class="video-wrapper">
            <video class="headline-video" autoplay muted loop playsinline>
            <source src="images/aboutsinta.mp4" type="video/mp4">
            Your browser does not support the video tag.
            </video>
        </div>

        <div class="name">
            <h1> Discover SintaDrive</h1>
        </div>

        <p>
           More than just providing a vehicle, Sintadrive is about empowering you to create your own narrative on the open road. 
           Your Journey, Your Way isn't just a tagline; it's our promise to deliver the freedom, flexibility, and reliability you need to make every mile memorable.
            Discover the ease of travel with Sintadrive, and let us be the starting point of your next great adventure. We pride ourselves on a hassle-free rental experience. Our streamlined booking process, transparent pricing, and dedicated customer support team are all designed to put you in the driver's seat with confidence. 
            We understand that convenience is key, which is why we offer flexible pick-up and drop-off options to fit your schedule.
        </p>
        </section>

        <!-- Mission -->
        <section id="about-mission" class="mission-section">
        <div class="mission-images">
            <img src="images/mission1.jpg"alt="Mission Image 1">
            <img src="images/mission2.jpg" alt="Mission Image 2">
        </div>
        <div class="mission-vision-text-content">
            <h2>Our Mission</h2>
            <p>To empower every traveler beyond with seamless, reliable, and personalized car rental solutions, enabling them to explore, connect, and create their unique journeys with ultimate freedom and convenience.</p>
        </div>
        </section>

        <!-- Vision -->
        <section id="about-vision" class="vision-section">
        <div class="mission-images">
            <img src="images/vision1.jpg" alt="Vision Image 1">
            <img src="images/vision2.jpg" alt="Vision Image 2">
        </div>
        <div class="mission-vision-text-content">
            <h2>Our Vision</h2>
            <p>To be the leading and most trusted car rental partner in Calabarzon, recognized for our exceptional service, diverse fleet, and unwavering commitment to making every customer's journey exactly the way they envision it.</p>
        </div>
        </section>


        <div class="terms-book-wrapper">
        <section id="why-choose-us">
        <h2>Why Pick SintaDrive?</h2>
        <div class="features-icons-row">
            <div class="feature-item">
            <div class="icon"><i class="fas fa-car-side"></i></div>
            <p><strong>Diverse Fleet:</strong><br> More Fleet. More Choices!  Choose from economy to luxury vehicles</p>
            </div>
            <div class="feature-item">
            <div class="icon"><i class="fas fa-headset"></i></div>
            <p><strong>Customer-Centric:</strong><br>We listen and tailor solutions to your needs. 24/7 Customer Support for inquries and issues.</p>
            </div>
            <div class="feature-item">
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <p><strong>Flexible Schedules:</strong><br> Flexible rental durations – daily, weekly, or monthly.</p>
            </div>
            <div class="feature-item">
            <div class="icon"><i class="fas fa-award"></i></div>
            <p><strong>Community Trust:</strong><br>Known for honesty, reliability, and transparency. Also,  Well-maintained, road-safe vehicles</p>
            </div>
        </div>
        </section>
        </div>

        <div class="terms-book-wrapper">
        <section class="terms-conditions">
            <h2>Terms & Conditions</h2>
            <ul>
            <li><i class="fas fa-id-card"></i> All drivers must present a valid driver’s license.</li>
            <li><i class="fas fa-credit-card"></i> Payment must be completed before vehicle release.</li>
            <li><i class="fas fa-clock"></i> Late returns may be subject to additional fees.</li>
            <li><i class="fas fa-gas-pump"></i> Fuel policy: Return the vehicle with the same fuel level.</li>
            <li><i class="fas fa-shield-alt"></i> Renter is responsible for any damage unless covered by insurance.</li>
            </ul>
        </section>

        <section class="how-to-book">
            <h2>How to Book a Drive</h2>
            <ul>
            <li><i class="fas fa-map-marker-alt"></i> Choose Your Pickup & Return Location</li>
            <li><i class="fas fa-calendar-alt"></i> Select your rental date and duration.</li>
            <li><i class="fas fa-car"></i> Browse our fleet and choose your preferred vehicle.</li>
            <li><i class="fas fa-user-check"></i> Sign in or register your account.</li>
            <li><i class="fas fa-file-contract"></i> Fill out the booking form and agree to the terms.</li>
            <li><i class="fas fa-credit-card"></i> Complete your payment online or choose to pay on pickup.</li>
            <li><i class="fas fa-key"></i> Pick up your car and start driving!</li>
            </ul>
        </section>
        </div>

    <?php include("includes/footer.php"); ?> <!-- Optional footer -->
</body>
</html>
