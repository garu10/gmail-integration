<?php
// admin_base.php
// This file serves as the base template for all admin-side pages.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file.
require_once '../includes/db.php';

// --- Authentication Check ---
// Ensure the user is logged in and has an 'admin' role.
// If not authenticated or not an admin, redirect them to the login page within the admin directory.
if (!isset($_SESSION['client_username']) || $_SESSION['client_role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login.php within the same admin directory
    exit(); // Always exit after a header redirect
}

$admin_username = $_SESSION['client_username'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Rental</title>
    <!-- Favicon (optional) -->
    <link rel="icon" href="https://placehold.co/32x32/cccccc/000000?text=AD" type="image/x-icon">

    <!-- Tailwind CSS CDN for quick styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Custom styles for 'Inter' font */
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Remove sidebar specific styles */
        .main-content {
            flex-grow: 1; /* Allow main content to take remaining space */
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Top Navigation Bar -->
    <nav class="bg-gray-800 text-white p-4 shadow-lg flex justify-between items-center">
        <div class="text-2xl font-bold">Admin Panel</div>
        <div class="flex items-center space-x-4">
            <!-- Left-aligned Navigation Links -->
            <ul class="flex space-x-4">
                <li>
                    <a href="admin_dashboard.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_cars.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Manage Cars
                    </a>
                </li>
                <li>
                    <a href="admin_bookings.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Manage Bookings
                    </a>
                </li>
                <li>
                    <a href="admin_clients.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Manage Clients
                    </a>
                </li>
                <li>
                    <a href="admin_locations.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Manage Locations
                    </a>
                </li>
                <li>
                    <a href="admin_car_status.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Manage Car Status
                    </a>
                </li>
                <li>
                    <a href="admin_payments.php" class="py-2 px-3 rounded-md hover:bg-gray-700 transition duration-200">
                        Payments
                    </a>
                </li>
            </ul>
        </div>
        <!-- Right-aligned Logout Button -->
        <div>
            <a href="logout.php" class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded-md transition duration-200">
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content flex flex-col flex-1 p-8">
        <!-- Header for main content (can be removed if redundant with top nav) -->
        <header class="bg-white p-6 rounded-lg shadow-md mb-8 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-gray-800">
                <?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?>
            </h1>
            <div class="text-gray-600">
                Welcome, <span class="font-medium text-gray-800"><?php echo htmlspecialchars($admin_username); ?></span>!
            </div>
        </header>

        <!-- The content of individual admin pages will be inserted here -->
        <main class="flex-1 bg-white p-6 rounded-lg shadow-md">
            <!-- This is where the specific page content will be included -->
            <?php
            // This is a placeholder. Child pages will output their content here.
            // Example: include 'dashboard_content.php';
            ?>
