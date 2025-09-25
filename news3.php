<?php
// about.php — now styled like a news article page
include("includes/db.php");
include("includes/functions.php");
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
  <link rel="stylesheet" href="css/news.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>


<body>
  <?php include("includes/navigations.php"); ?>


    <main class="news-layout">
    <!-- Main News Article -->
    <article class="news-article">
        <header class="news-header">
        <h1 class="headline">SintaDrive: Creating Memorable Family Journeys, One Trip at a Time</h1>
        <p class="meta">
            <i class="fas fa-calendar-alt"></i>
            <time datetime="2025-01-22">January 22, 2025</time> · <span class="author">SintaDrive News Team</span>
        </p>
        </header>


        <figure class="news-media">
        <img src="images/news3.jpg" alt="Summer Car Rental Deals" class="news-banner">
        <figcaption class="caption">Family in SintaDrive.</figcaption>
        </figure>




      <section class="news-content">
        <h2>Turning Family Travels Into Treasured Memories</h2>
        <p>In a fast-paced world where family moments can sometimes feel fleeting, SintaDrive offers a way to bring loved ones together through the joy of travel. As a trusted car rental service, SintaDrive isn’t just about providing vehicles—it’s about enabling meaningful connections, unforgettable road trips, and the creation of lasting memories for families of all sizes.


SintaDrive understands that family trips are more than just vacations—they’re opportunities to bond, to laugh, and to share experiences away from the noise of everyday life. Whether it’s a weekend getaway to the countryside, a long-awaited reunion, or a spontaneous adventure to a scenic destination, SintaDrive ensures families travel comfortably, safely, and with confidence.


With a wide selection of well-maintained vehicles—from spacious SUVs for big families to fuel-efficient sedans for quick out-of-town drives—SintaDrive tailors each rental experience to fit the unique needs of every customer. Features like roomy interiors, GPS navigation, child safety seats, and 24/7 roadside support give families peace of mind while on the road.


Beyond convenience, SintaDrive prides itself on accessibility and affordability. Flexible booking, easy pickup and return locations, and transparent pricing make the entire process stress-free. More importantly, their friendly customer service team treats each renter like family—ready to assist, advise, and support before, during, and after the trip.


For many families, the journey matters just as much as the destination. With SintaDrive as a travel partner, road trips are transformed into joyful shared experiences, filled with laughter, music, and moments that strengthen family bonds.


At its core, SintaDrive is more than a car rental service—it’s a companion on the road to togetherness.</p>
      </section>
    </article>
  </main>


  <?php include("includes/footer.php"); ?>
</body>
</html>



