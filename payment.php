<?php
// Start session at the very beginning
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include("includes/db.php"); // Database connection
include("includes/functions.php");


// Redirect if user is not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer/login.php");
    exit();
}
$user_id = $_SESSION['customer_id']; // Get user ID from session (using 'customer_id')


// DATA RETRIEVAL LOGIC FOR PAYMENT.PHP 
// this block captures data from POST (if coming from book.php form submission),
// or from GET (if direct URL access with params), or falls back to existing session data.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_code'])) {
    // Data came from book.php via POST submission
    $_SESSION['booking_data'] = [
        'car_code'           => filter_var($_POST['car_code'], FILTER_SANITIZE_NUMBER_INT),
        'start_date'         => $_POST['start_date'],
        'pickup_time'        => $_POST['pickup_time'],
        'end_date'           => $_POST['end_date'],
        'dropoff_time'       => $_POST['dropoff_time'],
        'pickup_location_id' => filter_var($_POST['pickup_location_id'], FILTER_SANITIZE_NUMBER_INT),
        'dropoff_location_id'=> filter_var($_POST['dropoff_location_id'], FILTER_SANITIZE_NUMBER_INT)
    ];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['car_code'])) {
    // Data came from a direct GET request (e.g., refreshing page with URL params)
    $_SESSION['booking_data'] = [
        'car_code'           => filter_var($_GET['car_code'], FILTER_SANITIZE_NUMBER_INT),
        'start_date'         => $_GET['start_date'],
        'pickup_time'        => $_GET['pickup_time'],
        'end_date'           => $_GET['end_date'],
        'dropoff_time'       => $_GET['dropoff_time'],
        'pickup_location_id' => filter_var($_GET['pickup_location_id'], FILTER_SANITIZE_NUMBER_INT),
        'dropoff_location_id'=> filter_var($_GET['dropoff_location_id'], FILTER_SANITIZE_NUMBER_INT)
    ];
}
// Retrieve variables from session (which was populated above, or from a prior request within payment.php)
$car_code = $_SESSION['booking_data']['car_code'] ?? null;
$start_date = $_SESSION['booking_data']['start_date'] ?? '';
$pickup_time = $_SESSION['booking_data']['pickup_time'] ?? '';
$end_date = $_SESSION['booking_data']['end_date'] ?? '';
$dropoff_time = $_SESSION['booking_data']['dropoff_time'] ?? '';
$pickup_location_id = $_SESSION['booking_data']['pickup_location_id'] ?? 0;
$dropoff_location_id = $_SESSION['booking_data']['dropoff_location_id'] ?? 0;
// === END DATA RETRIEVAL LOGIC ===

// Basic validation for essential booking parameters (now also checking session data)
if (!$car_code || empty($start_date) || empty($end_date) || $pickup_location_id == 0 || $dropoff_location_id == 0) {
    // Changed die() to a redirect to car listing for better user experience
    header("Location: car_listing.php?error=booking_details_missing");
    exit("Error: Essential booking details missing or invalid. Redirecting to car listing.");
}


// Fetch car details using prepared statement
$car_stmt = $conn->prepare("SELECT car_code, car_model, car_image, car_price_per_day, car_seater, car_luggage_capacity, car_transmission, car_drivetype, car_mileage, car_features FROM Cars WHERE car_code = ?");
$car_stmt->bind_param("i", $car_code);
$car_stmt->execute();
$car_result = $car_stmt->get_result();
$car = $car_result->fetch_assoc();
$car_stmt->close();


if (!$car) {
    // If car not found, redirect to car listing with an error
    header("Location: car_listing.php?error=car_not_found&car_code=" . urlencode($car_code));
    exit("Error: Car not found for the given code.");
}


// Fetch client details for initial display.
// This fetch always gets the *current* data from the database.
$client_stmt = $conn->prepare("SELECT * FROM Client WHERE customer_id = ?");
$client_stmt->bind_param("i", $user_id);
$client_stmt->execute();
$client = $client_stmt->get_result()->fetch_assoc();
$client_stmt->close();


if (!$client) {
    // This should ideally not happen if user_id is from a logged-in session, but good to check.
    header("Location: customer/login.php?error=client_data_missing");
    exit();
}


// Calculate days and total cost
$datetime1 = new DateTime($start_date . ' ' . $pickup_time);
$datetime2 = new DateTime($end_date . ' ' . $dropoff_time);
$interval = $datetime1->diff($datetime2);
$days = $interval->days;


// Logic for charging at least one day
if ($days == 0 && ($datetime2 > $datetime1)) {
    $days = 1;
} elseif ($days == 0 && $datetime1->format('Y-m-d H:i') == $datetime2->format('Y-m-d H:i')) {
    $days = 1; // Exactly the same datetime, charge for 1 day
} elseif ($datetime2 < $datetime1) {
    die("Error: Return date/time cannot be before pickup date/time."); // Critical error, should prevent booking
}


$total_cost = $days * $car['car_price_per_day'];


// Fetch pickup location name (for display only)
$pickup_location_name = '';
if ($pickup_location_id > 0) {
    $stmt = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt->bind_param("i", $pickup_location_id);
    $stmt->execute();
    $stmt->bind_result($pickup_location_name);
    $stmt->fetch();
    $stmt->close();
}


// Fetch dropoff location name (for display only)
$dropoff_location_name = '';
if ($dropoff_location_id > 0) {
    $stmt = $conn->prepare("SELECT location_name FROM Locations WHERE location_id = ?");
    $stmt->bind_param("i", $dropoff_location_id);
    $stmt->execute();
    $stmt->bind_result($dropoff_location_name);
    $stmt->fetch();
    $stmt->close();
}


// --- Initialize success/error messages for both sections ---
$update_success = false;
$update_error = '';
$payment_error_message = '';

// handle Form Submissions (Unified Logic) 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common variables from POST (for both update and proceed)
    $submitted_client_first_name = trim($_POST['client_first_name'] ?? '');
    $submitted_client_last_name = trim($_POST['client_last_name'] ?? '');
    $submitted_client_address = trim($_POST['client_address'] ?? '');
    $submitted_client_contact_number = trim($_POST['client_contact_number'] ?? '');
    $submitted_client_driver_license_number = trim($_POST['client_driver_license_number'] ?? '');
    $submitted_client_email_address = filter_var($_POST['client_email_address'] ?? '', FILTER_SANITIZE_EMAIL);


    // If 'Update Info' button was clicked
    if (isset($_POST['update_personal_info'])) {
        // Validation for "Update Info" button
        if (empty($submitted_client_first_name) || empty($submitted_client_last_name) || empty($submitted_client_email_address) || empty($submitted_client_contact_number) || empty($submitted_client_driver_license_number) || empty($submitted_client_address)) {
            $update_error = "All personal information fields are required to update your profile.";
        } else {
            // Update query
            $update_stmt = $conn->prepare("UPDATE Client SET
                client_username = ?,
                client_first_name = ?,
                client_last_name = ?,
                client_address = ?,
                client_contact_number = ?,
                client_driver_license_number = ?,
                client_email_address = ?
                WHERE customer_id = ?");
            $update_stmt->bind_param("sssssssi",
                $submitted_client_first_name, // Using first name for username
                $submitted_client_first_name,
                $submitted_client_last_name,
                $submitted_client_address,
                $submitted_client_contact_number,
                $submitted_client_driver_license_number,
                $submitted_client_email_address,
                $user_id);


            if ($update_stmt->execute()) {
                $update_success = true;
                // Re-fetch client data from DB to display updated info immediately on the form
                $re_fetch_client_stmt = $conn->prepare("SELECT * FROM Client WHERE customer_id = ?");
                $re_fetch_client_stmt->bind_param("i", $user_id);
                $re_fetch_client_stmt->execute();
                $client = $re_fetch_client_stmt->get_result()->fetch_assoc();
                $re_fetch_client_stmt->close();
            } else {
                $update_error = "Error updating personal information: " . htmlspecialchars($update_stmt->error);
            }
            $update_stmt->close();
        }
    }


    // If 'Proceed' button was clicked
    if (isset($_POST['process_payment'])) {
        // --- Server-Side Validation for Client Profile Completeness (using submitted data) ---
        $required_profile_fields = [
            'client_first_name'         => 'First Name',
            'client_last_name'          => 'Last Name',
            'client_email_address'      => 'Email Address',
            'client_contact_number'     => 'Contact Number',
            'client_driver_license_number' => 'Driver\'s License Number',
            'client_address'            => 'Address'
        ];


        $missing_profile_fields_display = [];
        foreach ($required_profile_fields as $post_field => $display_name) {
            // Check if the field from the current $_POST submission is empty.
            // This ensures we validate the data the user just submitted, not stale DB data.
            if (empty($_POST[$post_field])) {
                $missing_profile_fields_display[] = $display_name;
            }
        }


        if (!empty($missing_profile_fields_display)) {
            $payment_error_message = "Please complete your personal information before proceeding with payment. Missing fields: " . implode(', ', $missing_profile_fields_display) . ".";
        } else { // Only proceed with booking/payment if profile is complete (from current submission)


            $update_stmt = $conn->prepare("UPDATE Client SET
                client_username = ?,
                client_first_name = ?,
                client_last_name = ?,
                client_address = ?,
                client_contact_number = ?,
                client_driver_license_number = ?,
                client_email_address = ?
                WHERE customer_id = ?");
            $update_stmt->bind_param("sssssssi",
                $submitted_client_first_name,
                $submitted_client_first_name,
                $submitted_client_last_name,
                $submitted_client_address,
                $submitted_client_contact_number,
                $submitted_client_driver_license_number,
                $submitted_client_email_address,
                $user_id);


            if (!$update_stmt->execute()) {
                $payment_error_message = "ERROR: Failed to update client profile before booking: " . htmlspecialchars($update_stmt->error);
            }
            $update_stmt->close();

            // Only proceed with booking/payment if client profile update was successful
            if (empty($payment_error_message)) {
                $payment_method = $_POST['payment_method'] ?? '';
                $booking_id = $_SESSION['current_booking_id'] ?? null; // Try to get existing booking ID


                // Only insert new booking if no current_booking_id in session
                if (!$booking_id) {

                    // MODIFIED: Added booking_status to the INSERT statement and 'pending_payment' as its value
                    $stmt_booking = $conn->prepare("INSERT INTO Bookings
                        (car_code, customer_id, start_date, pickup_time, return_date, dropoff_time, pickup_location_id, dropoff_location_id, total_cost, booking_status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment')"); // 'pending_payment' is hardcoded, not a '?'

                    if ($stmt_booking === false) {
                        $payment_error_message = "ERROR: Booking prepare failed: " . htmlspecialchars($conn->error);
                    } else {
                        $stmt_booking->bind_param("iissssiid", 
                            $car_code,
                            $user_id,
                            $start_date,
                            $pickup_time,
                            $end_date, 
                            $dropoff_time,
                            $pickup_location_id,
                            $dropoff_location_id,
                            $total_cost
                        );

                        if ($stmt_booking->execute()) {
                            $booking_id = $conn->insert_id; // Get the ID of the newly inserted booking
                            $_SESSION['current_booking_id'] = $booking_id; // Store for current session
                        } else {
                            $payment_error_message = "ERROR: Booking insertion failed: " . htmlspecialchars($stmt_booking->error);
                        }
                        $stmt_booking->close();
                    }


                    // Only attempt car status update if booking was successful and no errors
                    if (empty($payment_error_message)) {
                        $update_car_status_stmt = $conn->prepare("UPDATE Car_status SET car_booked = 1, car_available = 0 WHERE car_code = ?");
                        if ($update_car_status_stmt === false) {
                            $payment_error_message = "ERROR: Car status prepare failed: " . htmlspecialchars($conn->error);
                        } else {
                            $update_car_status_stmt->bind_param("i", $car_code);
                            if ($update_car_status_stmt->execute()) {
                            } else {
                                $payment_error_message = "ERROR: Car status update failed: " . htmlspecialchars($update_car_status_stmt->error);
                            }
                            $update_car_status_stmt->close();
                        }
                    }
                } else {
                }


                // --- Process Payment Information ONLY if no prior errors ---
                if (empty($payment_error_message)) {
                    if ($payment_method == 'cash') {
                        $payment_refnumber = 'CASH-' . uniqid();
                        $payment_date = date('Y-m-d');


                        $stmt_payment = $conn->prepare("INSERT INTO Payments
                            (booking_id, payment_date, total_amount, payment_refnumber, payment_method)
                            VALUES (?, ?, ?, ?, ?)");
                        if ($stmt_payment === false) {
                            $payment_error_message = "ERROR: Payment prepare failed (cash): " . htmlspecialchars($conn->error);
                        } else {
                            $stmt_payment->bind_param("isdss", $booking_id, $payment_date, $total_cost, $payment_refnumber, $payment_method);


                            if ($stmt_payment->execute()) {
                                unset($_SESSION['current_booking_id']); 
                                // clear booking_data session after successful booking
                                unset($_SESSION['booking_data']);
                                echo "<script>alert('Booking confirmed! Payment will be made on pickup.'); window.location.href='booking_success.php';</script>";
                                exit();
                            } else {
                                $payment_error_message = "ERROR: Cash payment insertion failed: " . htmlspecialchars($stmt_payment->error);
                            }
                            $stmt_payment->close();
                        }


                    } elseif ($payment_method == 'online') {
                        $proof_path = null;


                        if (!isset($_FILES['proof_of_payment']) || $_FILES['proof_of_payment']['error'] != UPLOAD_ERR_OK) {
                            $payment_error_message = "Please upload a proof of payment image. Error code: " . ($_FILES['proof_of_payment']['error'] ?? 'N/A');
                        } else {
                            $file_tmp_name = $_FILES['proof_of_payment']['tmp_name'];
                            $file_name = basename($_FILES['proof_of_payment']['name']);
                            $file_size = $_FILES['proof_of_payment']['size'];
                            $file_type = $_FILES['proof_of_payment']['type'];
                            $target_dir = "uploads/proofs/"; 
                            $unique_file_name = uniqid('proof_') . "_" . $file_name; // Add original name for better identification
                            $target_file = $target_dir . $unique_file_name;


                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                            $max_file_size = 5 * 1024 * 1024; // 5MB


                            if (!in_array($file_type, $allowed_types)) {
                                $payment_error_message = "Invalid file type. Only JPG, PNG, GIF, and PDF are allowed for proof of payment.";
                            } elseif ($file_size > $max_file_size) {
                                $payment_error_message = "File size too large. Max 5MB allowed.";
                            } else {
                                // Ensure target directory exists and is writable
                                if (!is_dir($target_dir)) {
                                    if (!mkdir($target_dir, 0777, true)) { 
                                        $payment_error_message = 'Failed to create upload directory. Check permissions.';
                                    }
                                } elseif (!is_writable($target_dir)) {
                                    $payment_error_message = 'Upload directory is not writable. Check permissions.';
                                }


                                if (empty($payment_error_message)) { 
                                    if (move_uploaded_file($file_tmp_name, $target_file)) {
                                        $proof_path = $target_file;


                                        $payment_refnumber = 'ONLINE-' . uniqid();
                                        $payment_date = date('Y-m-d');

                                        $stmt_payment = $conn->prepare("INSERT INTO Payments
                                            (booking_id, payment_date, total_amount, payment_refnumber, payment_method, proof_of_payment_path)
                                            VALUES (?, ?, ?, ?, ?, ?)");
                                        if ($stmt_payment === false) {
                                            $payment_error_message = "ERROR: Payment prepare failed (online): " . htmlspecialchars($conn->error);
                                        } else {
                                            $stmt_payment->bind_param("isdsss", $booking_id, $payment_date, $total_cost, $payment_refnumber, $payment_method, $proof_path);


                                            if ($stmt_payment->execute()) {
                                                unset($_SESSION['current_booking_id']); // Clear session booking ID
                                                // clear booking_data session after successful booking
                                                unset($_SESSION['booking_data']);
                                                echo "<script>alert('Online payment proof uploaded and awaiting verification. Booking confirmed.'); window.location.href='booking_success.php';</script>";
                                                exit(); 
                                            } else {
                                                $payment_error_message = "Error processing online payment: " . htmlspecialchars($stmt_payment->error);
                                                // If payment insertion fails, consider deleting the uploaded file to clean up
                                                if (file_exists($proof_path)) {
                                                    unlink($proof_path);
                                                }
                                            }
                                            $stmt_payment->close();
                                        }
                                    } else {
                                        $payment_error_message = "Error moving uploaded proof of payment. Check server permissions.";
                                    }
                                }
                            }
                        }
                    } else { // No valid payment method selected
                        $payment_error_message = 'Please select a payment method.';
                    }
                } 
            } 
        } 
    } 
} 


// Re-fetch client data *after* any potential POST (update_personal_info or process_payment)
// to ensure the form fields display the most current data.
$client_stmt_re_fetch = $conn->prepare("SELECT * FROM Client WHERE customer_id = ?");
$client_stmt_re_fetch->bind_param("i", $user_id);
$client_stmt_re_fetch->execute();
$client = $client_stmt_re_fetch->get_result()->fetch_assoc();
$client_stmt_re_fetch->close();


// Display error messages using JavaScript alert (only if set)
if (!empty($update_error)) {
    echo "<script>alert('" . addslashes($update_error) . "');</script>";
}
if (!empty($payment_error_message)) {
    echo "<script>alert('" . addslashes($payment_error_message) . "');</script>";
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Payment & Personal Information</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigations.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/payment.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<?php include("includes/navigations.php"); ?>
    <h1>Payment & Personal Information</h1>


    <div class="car-container">
        <div class="car-left">
            <h3 class="car-name"><?php echo htmlspecialchars($car['car_model']); ?></h3>
            <img src="images/<?php echo htmlspecialchars($car['car_image']); ?>" alt="<?php echo htmlspecialchars($car['car_model']); ?>" class="car-image">
            <p class="car-price-view">
                <strong>PHP <?= htmlspecialchars($car['car_price_per_day']) ?><span class="price-unit">/day</span></strong>
            </p>
        </div>
        <div class="car-right">
            <div class="car-top-row">
                <div><i class="fa fa-users" aria-hidden="true"></i> <?= htmlspecialchars($car['car_seater']) ?> Seater</div>
                <div><i class="fa fa-suitcase" aria-hidden="true"></i> <?= htmlspecialchars($car['car_luggage_capacity']) ?> Luggage</div>
                <div><i class="fa fa-cogs" aria-hidden="true"></i> <?= htmlspecialchars($car['car_transmission']) ?></div>
            </div>
            <div class="car-info">
                <h3><strong>Transmission Type:</strong> <?php echo htmlspecialchars($car['car_transmission']); ?></h3>
                <h3><strong>Fuel Type:</strong> Not available in DB</h3>
                <h3><strong>Drive Type:</strong> <?php echo htmlspecialchars($car['car_drivetype']); ?></h3>
                <h3><strong>Car Mileage:</strong> <?php echo htmlspecialchars($car['car_mileage']); ?> km</h3>
                <h3><strong>Car Features:</strong> <?php echo htmlspecialchars($car['car_features']); ?></h3>
            </div>
        </div>
    </div>


    <div class="booking-details-container">
        <h3>Booking Information</h3>
        <div class="booking-dates">
            <div>
                <p><strong>Pickup Date:</strong> <?= htmlspecialchars($start_date) ?></p>
                <p><strong>Pickup Time:</strong> <?= htmlspecialchars($pickup_time) ?></p>
            </div>
            <div>
                <p><strong>Return Date:</strong> <?= htmlspecialchars($end_date) ?></p>
                <p><strong>Return Time:</strong> <?= htmlspecialchars($dropoff_time) ?></p>
            </div>
        </div>
        <div class="locations">
            <p><strong>Pickup/Dropoff Location:</strong> <?= htmlspecialchars($pickup_location_name) ?> / <?= htmlspecialchars($dropoff_location_name) ?></p>
        </div>
    </div>


    <div class="container">
        <form method="POST" enctype="multipart/form-data" id="paymentForm">
            <div class="section-container">
                <h3>Fill up Personal Information</h3>
                <?php if ($update_success): ?>
                    <p class="success-message">Personal information updated successfully!</p>
                <?php elseif (!empty($update_error)): ?>
                    <p class="error-message"><?= htmlspecialchars($update_error) ?></p>
                <?php endif; ?>
                <input type="hidden" name="car_code" value="<?= htmlspecialchars($car_code) ?>">
                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <input type="hidden" name="pickup_time" value="<?= htmlspecialchars($pickup_time) ?>">
                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                <input type="hidden" name="dropoff_time" value="<?= htmlspecialchars($dropoff_time) ?>">
                <input type="hidden" name="pickup_location_id" value="<?= htmlspecialchars($pickup_location_id) ?>">
                <input type="hidden" name="dropoff_location_id" value="<?= htmlspecialchars($dropoff_location_id) ?>">




                <div class="form-group">
                    <label for="client_first_name">First Name:</label>
                    <input type="text" id="client_first_name" name="client_first_name" value="<?= htmlspecialchars($client['client_first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="client_last_name">Last Name:</label>
                    <input type="text" id="client_last_name" name="client_last_name" value="<?= htmlspecialchars($client['client_last_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="client_email_address">Email Address:</label>
                    <input type="email" id="client_email_address" name="client_email_address" value="<?= htmlspecialchars($client['client_email_address'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="client_contact_number">Contact Number:</label>
                    <input type="tel" id="client_contact_number" name="client_contact_number" value="<?= htmlspecialchars($client['client_contact_number'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="client_driver_license_number">Driver's License Number:</label>
                    <input type="text" id="client_driver_license_number" name="client_driver_license_number" value="<?= htmlspecialchars($client['client_driver_license_number'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="client_address">Address:</label>
                    <textarea id="client_address" name="client_address" rows="3" required><?= htmlspecialchars($client['client_address'] ?? '') ?></textarea>
                </div>
                <div class="button-group">
                    <button type="submit" name="update_personal_info">Update Info</button>
                </div>
            </div>


            <div class="section-container">
                <h3>Payment Process</h3>
                <div class="section-container">
                    <h4>Price Breakdown</h4>
                    <table class="price-breakdown-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Unit Price</th>
                                <th>Days</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($car['car_model']) ?> (Code: <?= htmlspecialchars($car['car_code']) ?>)</td>
                                <td>PHP <?= htmlspecialchars($car['car_price_per_day']) ?></td>
                                <td><?= htmlspecialchars($days) ?></td>
                                <td>PHP <?= number_format($total_cost, 2) ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3">Total Amount</td>
                                <td>PHP <?= number_format($total_cost, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <div class="section-container">
                    <h4>Please Select Payment Method</h4>
                    <div class="payment-method-options">
                        <label>
                            <input type="radio" name="payment_method" value="cash" id="cash_on_pickup" checked> Cash on Pickup
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="online" id="online_payment"> Online Payment(GCash)
                        </label>
                    </div>


                    <div id="proof_of_payment_div" style="display: none;">
                        <div class="form-group qr-code-display">
                            <p>Scan this QR code to make your payment:</p>
                            <img src="images/qrcode_placeholder.jpg" alt="QR Code for Payment" style="max-width: 200px; height: auto; display: block; margin: 10px auto;">
                            <small>Please ensure the payment amount matches the total.</small>
                        </div>
                        <div class="form-group">
                            <label for="proof_of_payment">Proof of Payment (Image/PDF):</label>
                            <input type="file" name="proof_of_payment" id="proof_of_payment" accept="image/*,.pdf"> <small>Please upload an image or PDF showing the total amount (PHP <?= number_format($total_cost, 2) ?>).</small>
                        </div>
                    </div>
                </div>


                <div class="button-group">
                    <button type="submit" name="process_payment" id="proceedButton">Proceed</button>
                </div>
            </div>
        </form>
    </div>


<?php include("includes/footer.php"); ?>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cashOnPickupRadio = document.getElementById('cash_on_pickup');
        const onlinePaymentRadio = document.getElementById('online_payment');
        const proofOfPaymentDiv = document.getElementById('proof_of_payment_div');
        const proofOfPaymentInput = document.getElementById('proof_of_payment');
        const paymentForm = document.getElementById('paymentForm');
        const proceedButton = document.getElementById('proceedButton');


        function toggleProofOfPayment() {
            if (onlinePaymentRadio.checked) {
                proofOfPaymentDiv.style.display = 'block';
                proofOfPaymentInput.setAttribute('required', 'required');
            } else {
                proofOfPaymentDiv.style.display = 'none';
                proofOfPaymentInput.removeAttribute('required');
            }
        }


        cashOnPickupRadio.addEventListener('change', toggleProofOfPayment);
        onlinePaymentRadio.addEventListener('change', toggleProofOfPayment);


        // Initial check on page load
        toggleProofOfPayment();


        // Client-side validation before submitting the form via "Proceed" button
        proceedButton.addEventListener('click', function(event) {
            // Get all required personal information fields
            const requiredFields = document.querySelectorAll(
                '#client_first_name, #client_last_name, #client_email_address, ' +
                '#client_contact_number, #client_driver_license_number, #client_address'
            );


            let allFieldsFilled = true;
            let missingFieldNames = [];


            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    allFieldsFilled = false;
                    let label = document.querySelector(`label[for="${field.id}"]`);
                    missingFieldNames.push(label ? label.textContent.replace(':', '').trim() : field.name);
                    field.classList.add('error-border'); 
                } else {
                    field.classList.remove('error-border'); 
                }
            });


            if (!allFieldsFilled) {
                event.preventDefault();
                alert('Please fill in all required personal information fields before proceeding with payment. Missing: ' + missingFieldNames.join(', ') + '.');
                return false;
            }


            // If online payment is selected, check proof of payment
            if (onlinePaymentRadio.checked && proofOfPaymentInput.value.trim() === '') {
                event.preventDefault();
                alert('Please upload a proof of payment image for online payment.');
                proofOfPaymentInput.classList.add('error-border');
                return false;
            } else {
                proofOfPaymentInput.classList.remove('error-border');
            }


            // If all checks pass, allow form submission (the server will re-validate)
            return true;
        });
    });
</script>
<style>
    .error-border {
        border: 2px solid red !important;
    }
</style>
</body>
</html>