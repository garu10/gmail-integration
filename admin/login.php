<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include db.php first to establish connection
include("../includes/db.php");
// Then include functions.php which should contain session_start() and helper functions
include("../includes/functions.php");

// Error variable for displaying messages
$error = '';
$message_type = ''; // To control styling of the message

// --- START STATIC ADMIN CREDENTIALS ---
$static_admin_username = "admin_user"; 
$static_admin_password = "admin_password"; 
// --- END STATIC ADMIN CREDENTIALS ---

// If admin is already logged in, redirect them
// Using 'client_username' and 'client_role' to match admin_base.php's expectations
if (isset($_SESSION['client_username']) && isset($_SESSION['client_role']) && $_SESSION['client_role'] === 'admin') {
    header("Location: admin_dashboard.php"); // Redirect to the correct admin dashboard file
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_username = $_POST['username'];
    $entered_password = $_POST['password'];

    // Compare entered credentials with static credentials
    if ($entered_username === $static_admin_username && $entered_password === $static_admin_password) {
        // Login successful - create a dummy user_id for the session as it's typically an INT
        $_SESSION['user_id'] = 99999;   // A dummy ID, since we're not fetching from DB
        $_SESSION['client_username'] = $static_admin_username; // Set 'client_username'
        $_SESSION['client_role'] = 'admin'; // Set 'client_role'

        header("Location: admin_dashboard.php"); // Redirect to admin dashboard page
        exit(); // Always exit after a header redirect
    } else {
        $error = "Incorrect username or password.";
        $message_type = 'error'; // Set message type for styling
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin_login.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SINTADrive - Admin Login</title>
</head>
<body class="admin-login-body">
    <main class="login-page-container">
        <div class="marketing-text">
            <h1>Don't Rent a Car. Rent the Car.</h1>
            <p>
                Experience seamless travel and reliable performance when you rent from SINTADrive, where our diverse fleet of meticulously maintained vehicles is ready
                for your next adventure. Unlock the ultimate freedom to explore with confidence, ensuring every journey is
                smooth, comfortable, and tailored precisely to your needs.
            </p>
            <h2>SINTA<span class="drive-word">Drive</span></h2>
        </div>

        <div class="form-card">
            <h2>Admin Login</h2>

            <form action="login.php" method="POST" class="login-form">
                <div class="input-group">
                    <input type="text" id="admin-username" name="username" placeholder="Admin Username" class="input-field" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="input-group">
                    <input type="password" id="admin-password" name="password" placeholder="Admin Password" class="input-field" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-3-9V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/></svg>
                </div>

                <button type="submit" class="btn btn-primary">Log In as Admin</button>

                <?php
                // Display login feedback messages dynamically based on type
                if (!empty($error)) {
                    // Use a more specific class for error/success messages
                    $class = ($message_type === 'error') ? 'error-message' : 'success-message';
                    echo "<p class='{$class}'>{$error}</p>";
                }
                ?>
            </form>

            <div class="separator">Or</div>

            <a href="../customer/login.php" class="btn btn-secondary">Sign Up as Customer</a>
        </div>
    </main>
</body>
</html>
