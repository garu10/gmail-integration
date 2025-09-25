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
        <h1 class="headline">SintaDrive: Redefining Luxury Travel</h1>
        <p class="meta">
            <i class="fas fa-calendar-alt"></i>
            <time datetime="2025-03-22">March 22, 2025</time> · <span class="author">SintaDrive News Team</span>
        </p>
        </header>

        <figure class="news-media">
        <img src="images/news2.jpg" alt="Summer Car Rental Deals" class="news-banner">
        <figcaption class="caption">Fun, Rentals in Business Ways.</figcaption>
        </figure>


      <section class="news-content">
        <h2>SintaDrive: Powering Your Business Journeys</h2>
        <p> In the dynamic landscape of modern business, efficiency, reliability, and cost-effectiveness are paramount. From client meetings and conferences to project site visits and executive travel, seamless transportation is often a critical component of success. SintaDrive, renowned for its commitment to customer satisfaction and diverse fleet, extends its exceptional service to the corporate world, offering tailored solutions that make business drives not just productive, but also remarkably convenient and economical.</p>

        <p> SintaDrive understands that business travel demands a different set of priorities than leisure trips. For companies, time is money, and every minute saved on logistics translates into enhanced productivity. SintaDrive's corporate rental programs are meticulously designed to streamline the entire process. Dedicated account managers ensure personalized service, facilitating quick bookings, managing complex itineraries, and providing comprehensive invoicing. This efficiency minimizes administrative burdens, allowing employees to focus on their core responsibilities rather than travel arrangements. Furthermore, SintaDrive offers a wide array of vehicles, from professional sedans perfect for executive transport to spacious SUVs ideal for team travel or hauling equipment, ensuring that businesses always have the right vehicle for the task at hand.</p>

        <p> Beyond mere convenience, SintaDrive provides significant financial advantages for businesses. Corporate rates, often structured with volume discounts and long-term rental options, translate into substantial cost savings compared to ad-hoc rentals or maintaining a company fleet. These programs frequently include benefits such as unlimited mileage, reducing concerns about unexpected surcharges on extensive business trips. Moreover, the transparent pricing model eliminates hidden fees, allowing companies to accurately budget for their transportation needs without surprises. The flexibility to scale vehicle usage up or down based on project demands further optimizes expenditure, making SintaDrive an agile and adaptable partner for businesses of all sizes</p>

        <p> SintaDrive's commitment to reliability and safety is another cornerstone of its appeal for business use. All vehicles undergo rigorous maintenance checks and are equipped with modern safety features, providing peace of mind for both the company and its employees. In the event of unforeseen circumstances, SintaDrive's robust roadside assistance ensures prompt support, minimizing disruptions to critical business schedules. This focus on dependable service underscores SintaDrive's role as a trusted partner in facilitating smooth and uninterrupted business operations.</p>
      </section>
    </article>
  </main>

  <?php include("includes/footer.php"); ?>
</body>
</html>
