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
        <h1 class="headline">Summer Deals Are Here!</h1>
        <p class="meta">
            <i class="fas fa-calendar-alt"></i>
            <time datetime="2025-06-22">June 22, 2025</time> · <span class="author">SintaDrive News Team</span>
        </p>
        </header>

        <figure class="news-media">
        <img src="images/news1.jpg" alt="Summer Car Rental Deals" class="news-banner">
        <figcaption class="caption">Get ready to hit the road with exclusive summer savings from SintaDrive!</figcaption>
        </figure>


      <section class="news-content">
        <h2>Your Key to an Unforgettable Summer</h2>
        <p>As the days grow longer and the promise of summer beckons, many of us dream of road trips, spontaneous getaways, and exploring new horizons.
        However, the logistics of travel, especially securing reliable and affordable transportation, can often dampen the excitement.</p>

        <p>This is where <strong>SintaDrive</strong>, the premier car rental service, steps in—transforming those summer dreams into reality with an array of irresistible deals designed to make your journey as enjoyable and effortless as the destination itself. SintaDrive understands that summer travel is synonymous with freedom and flexibility. Their meticulously crafted summer deals cater to a diverse range of needs, ensuring that whether you're planning a romantic escape, a family adventure, or a solo exploration, there's a perfect vehicle and a deal to match. One of the most appealing aspects of SintaDrive's summer offerings is their tiered discount system. Early bird bookings are handsomely rewarded, encouraging travelers to plan ahead and secure significant savings. This not only lightens the financial load but also provides peace of mind, knowing your ride is ready when you are.</p>

        <p>SintaDrive understands that summer travel is synonymous with freedom and flexibility. Their meticulously crafted summer deals cater to a diverse range of needs, ensuring that whether you're planning a romantic escape, a family adventure, or a solo exploration, there's a perfect vehicle and a deal to match. Beyond just discounts, SintaDrive’s summer deals often include complimentary upgrades, transforming a standard rental into a luxurious experience. Imagine booking an economy car and being surprised with a spacious sedan or a comfortable SUV, perfect for accommodating extra luggage or providing more room for the kids. This commitment to enhancing the customer experience is a hallmark of SintaDrive’s service, making every trip feel like an exclusive adventure. Furthermore, their flexible cancellation policies during the summer months alleviate the stress of unforeseen changes, a vital consideration in today's dynamic travel landscape. This customer-centric approach demonstrates SintaDrive's dedication to making your summer plans adaptable and worry-free.</p>

        <p>One of the most appealing aspects of SintaDrive's summer offerings is their tiered discount system. <span class="highlight">Early bird bookings are handsomely rewarded</span>, encouraging travelers to plan ahead and secure significant savings. This not only lightens the financial load but also provides peace of mind, knowing your ride is ready when you are. The true value of SintaDrive's summer deals lies not just in the monetary savings, but in the enhanced travel experience they facilitate. With a well-maintained, comfortable vehicle at your disposal, the journey becomes an integral part of the vacation. The ease of booking through their user-friendly platform, combined with transparent pricing and exceptional customer service, ensures that the entire process, from reservation to return, is seamless.</p>
      </section>
    </article>
  </main>

  <?php include("includes/footer.php"); ?>
</body>
</html>
