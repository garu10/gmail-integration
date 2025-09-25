<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<div class="nav-container">
    <div class="logo">
        <h2>SINTADRIVE</h2>
    </div>

    <div class="nav">
        <ul>
            <li><a href="/car_rental_system/index.php">Home</a></li>
            <li><a href="/car_rental_system/bookings.php">Bookings</a></li>
            <li><a href="/car_rental_system/about.php">About</a></li>
        </ul>
    </div>

    <!-- User Icon with Dropdown -->
    <div class="dropdown">
        <a href="#" class="sidebar-toggle dropbtn">
            <i class="fa <?php echo isset($_SESSION['customer_id']) ? 'fa-user-circle' : 'fa-user'; ?>" aria-hidden="true"></i>
        </a>
        <div class="dropdown-content">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <a href="/car_rental_system/profile.php">
                    <?php echo htmlspecialchars($_SESSION['email'] ?? 'Profile'); ?>
                </a>
                <a href="/car_rental_system/profile.php">Profile</a>
                <a href="/car_rental_system/logout.php">Logout</a>
            <?php else: ?>
                <a href="/car_rental_system/choose_login.php">Login</a>
                <a href="/car_rental_system/customer/register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
