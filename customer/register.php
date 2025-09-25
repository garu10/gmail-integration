<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../includes/db.php");
include("../includes/functions.php"); 

$message = '';
$message_type = ''; 

// Initialize variables for form values to retain them on error
$first_name = '';
$last_name = '';
$email = '';
$username = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input for first and last names
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password']; // Password will be hashed, no direct sanitization for input

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'error';
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check_user_stmt = $conn->prepare("SELECT customer_id FROM Client WHERE client_username = ? LIMIT 1");
        $check_user_stmt->bind_param("s", $username);
        $check_user_stmt->execute();
        $check_user_result = $check_user_stmt->get_result();

        if ($check_user_result->num_rows > 0) {
            $message = "Username '" . htmlspecialchars($username) . "' is already taken. Please choose another.";
            $message_type = 'error';
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO Client (client_first_name, client_last_name, client_email_address, client_username, client_password, client_role) VALUES (?, ?, ?, ?, ?, 'client')");
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $username, $hashed_password); // "sssss" for five string parameters

            if ($insert_stmt->execute()) {
                $message = "Registration successful! You can now <a href='login.php' class='text-link'>Login here</a>.";
                $message_type = 'success';
            } else {
                $message = "Error during registration: " . htmlspecialchars($insert_stmt->error);
                $message_type = 'error';
            }
            $insert_stmt->close();
        }
        $check_user_stmt->close();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>SINTADrive - Customer Registration</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer_login.css">
</head>
<body class="customer-login-body customer-registration-page">
    <main class="login-page-container">
        <div class="marketing-text">
            <h1>Your Journey, Our Passion</h1>
            <p>
                At SintaDrive, we believe that every journey should be smooth, safe, and memorable. Our passion lies in providing reliable, top-quality vehicles that match your lifestyle and travel needs.
                Whether you're exploring the city or heading out on a long road trip, SintaDrive is here to drive you forward with confidence.
            </p>
            <h2>SINTA<span class="drive-word">Drive</span></h2>
        </div>

        <div class="form-card">
            <h2>Customer Registration</h2>

            <form action="register.php" method="POST" class="login-form">
               <div class="input-group">
                    <input type="text" id="reg-first-name" name="first_name" placeholder="First Name" class="input-field" value="<?php echo htmlspecialchars($first_name); ?>" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="input-group">
                    <input type="text" id="reg-last-name" name="last_name" placeholder="Last Name" class="input-field" value="<?php echo htmlspecialchars($last_name); ?>" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="input-group">
                    <input type="email" id="reg-email" name="email" placeholder="Email Address" class="input-field" value="<?php echo htmlspecialchars($email); ?>" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10z"/></svg>
                </div>
                <div class="input-group">
                    <input type="text" id="reg-username" name="username" placeholder="Username" class="input-field" value="<?php echo htmlspecialchars($username); ?>" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="input-group">
                    <input type="password" id="reg-password" name="password" placeholder="Password" class="input-field" required />
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-3-9V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/></svg>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>

                <?php
                // Display registration feedback messages dynamically
                if (!empty($message)) {
                    $class = ($message_type === 'error') ? 'error-message' : 'success-message';
                    echo "<p class='{$class}'>{$message}</p>";
                }
                ?>
            </form>

            <div class="separator">Or</div>
            <div class="text-center mt-4">
                <a href="login.php" class="text-link">Already have an account? Log In</a>
            </div>
        </div>
    </main>
</body>
</html>