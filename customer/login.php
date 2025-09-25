<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../includes/db.php");
include("../includes/functions.php");

$message = '';
$message_type = '';

// Redirect if already logged in as a client
if (isset($_SESSION['customer_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
    header("Location: ../index.php"); // Redirects to the main index page
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = "Please enter both username and password.";
        $message_type = 'error';
    } else {
        // Ensure you're only trying to log in clients here
        $stmt = $conn->prepare("SELECT customer_id, client_username, client_password, client_role, client_email_address FROM Client WHERE client_username = ? AND client_role = 'client'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['client_password'])) {
                $_SESSION['customer_id'] = $user['customer_id']; 
                $_SESSION['username'] = $user['client_username'];
                $_SESSION['role'] = $user['client_role'];
                $_SESSION['email'] = $user['client_email_address'];

                header("Location: ../index.php"); 
                exit();
            } else {
                $message = "Invalid username or password.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid username or password.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Close connection at the end of the script
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SINTADrive - Customer Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer_login.css">
</head>

<body class="customer-login-body">
    <main class="login-page-container">
        <div class="marketing-text">
            <h1>Drive your Adventure!</h1>
            <p>
                Booking your perfect vehicle is effortless with SintaDrive's diverse fleet and flexible rental options, tailored to fit your unique journey.
                Each SintaDrive car is meticulously maintained for safety and peak performance, ensuring a smooth and worry-free experience on every road.
                From city escapades to scenic routes, gain the freedom to explore at your own pace with a dependable ride from SintaDrive.
            </p>
            <h2>SINTA<span class="drive-word">Drive</span></h2>
        </div>

        <div class="form-card">
            <h2>Customer Login</h2>

            <form action="login.php" method="POST" class="login-form">
                <div class="input-group">
                    <input type="text" id="customer-username" name="username" placeholder="Username" class="input-field" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="input-group">
                    <input type="password" id="customer-password" name="password" placeholder="Password" class="input-field" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-3-9V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/></svg>
                </div>

                <button type="submit" class="btn btn-primary">Log In</button>

                <?php
                if (!empty($message)) {
                    $class = ($message_type === 'error') ? 'error-message' : 'success-message';
                    echo "<p class='{$class}'>{$message}</p>";
                }
                ?>
            </form>

            <div class="separator">Or</div>

            <a href="register.php" class="btn btn-secondary">Register</a>
        </div>
    </main>
</body>
</html>