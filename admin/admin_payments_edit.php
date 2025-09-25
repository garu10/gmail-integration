<?php
// Set the page title for the base template.
$page_title = "Edit Payment";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

$edit_payment_data = null; // Initialize to null

// --- Fetch Bookings for Dropdown ---
$bookings_list = [];
$sql_bookings = "SELECT b.booking_id, b.start_date, b.return_date,
                        c.car_brand, c.car_model, c.car_platenumber,
                        cl.client_first_name, cl.client_last_name
                 FROM Bookings b
                 JOIN Cars c ON b.car_code = c.car_code
                 JOIN Client cl ON b.customer_id = cl.customer_id
                 ORDER BY b.booking_id DESC";
if ($result_bookings = $conn->query($sql_bookings)) {
    while ($row = $result_bookings->fetch_assoc()) {
        $bookings_list[] = $row;
    }
    $result_bookings->free();
} else {
    error_log("Error fetching bookings for dropdown: " . $conn->error);
}


// --- Handle Update Payment Submission ---
if (isset($_POST['update_payment'])) {
    $payment_id = $_POST['payment_id'];
    $booking_id = $_POST['booking_id'];
    $payment_date = $_POST['payment_date'];
    $total_amount = $_POST['total_amount'];
    $payment_refnumber = $_POST['payment_refnumber'];
    $payment_method = $_POST['payment_method'];

    // Handle optional proof_of_payment_path
    $proof_of_payment_path = null;
    if (isset($_POST['proof_of_payment_path']) && !empty(trim($_POST['proof_of_payment_path']))) {
        $proof_of_payment_path = trim($_POST['proof_of_payment_path']);
    }


    $sql_fields_to_update = [];
    $params_to_bind = [];

    $sql_fields_to_update[] = "booking_id = ?";
    $params_to_bind[] = $booking_id;

    $sql_fields_to_update[] = "payment_date = ?";
    $params_to_bind[] = $payment_date;

    $sql_fields_to_update[] = "total_amount = ?";
    $params_to_bind[] = $total_amount;

    $sql_fields_to_update[] = "payment_refnumber = ?";
    $params_to_bind[] = $payment_refnumber;

    $sql_fields_to_update[] = "payment_method = ?";
    $params_to_bind[] = $payment_method;

    $sql_fields_to_update[] = "proof_of_payment_path = ?";
    $params_to_bind[] = $proof_of_payment_path;

    // Append payment_id for WHERE clause
    $params_to_bind[] = $payment_id;

    $sql = "UPDATE Payments SET " . implode(", ", $sql_fields_to_update) . " WHERE payment_id = ?";

    // Dynamically generate the types string based on the populated params_to_bind array
    $types_string = "";
    foreach ($params_to_bind as $param) {
        if (is_int($param)) {
            $types_string .= "i";
        } elseif (is_float($param) || is_double($param)) {
            $types_string .= "d";
        } elseif (is_bool($param)) { 
            $types_string .= "i";
        } else {
            $types_string .= "s";
        }
    }


    if ($stmt = $conn->prepare($sql)) {
        // Create an array of references for bind_param
        $refs = [];
        foreach ($params_to_bind as $key => $value) {
            $refs[$key] = &$params_to_bind[$key];
        }

        // Dynamically bind parameters using call_user_func_array
        call_user_func_array(array($stmt, 'bind_param'), array_merge([$types_string], $refs));

        if ($stmt->execute()) {
            $message = "Payment updated successfully!";
            $message_type = 'success';
            // Redirect back to admin_payments.php with success message
            header("Location: admin_payments.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error updating payment: " . $stmt->error;
            $message_type = 'error';
            error_log("Error updating payment: " . $stmt->error); // Log the error
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
        error_log("Error preparing statement: " . $conn->error); // Log the error
    }
}

// --- Fetch Payment Data for Editing (on initial page load) ---
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_payment_id = $_GET['edit'];

    $sql_edit = "SELECT * FROM Payments WHERE payment_id = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $edit_payment_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows > 0) {
            $edit_payment_data = $result_edit->fetch_assoc();
        } else {
            $message = "Payment not found for editing.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    } else {
        $message = "Error preparing statement to fetch payment data: " . $conn->error;
        $message_type = 'error';
    }
} else if (!isset($_GET['edit']) && !isset($_POST['update_payment'])) {
    $message = "No payment selected for editing. Please select a payment from the <a href='admin_payments.php' class='font-medium text-red-800 hover:underline'>Payment List</a>.";
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Edit Payment
                <?php echo ($edit_payment_data) ? '(ID: ' . htmlspecialchars($edit_payment_data['payment_id']) . ')' : ''; ?>
            </h2>

            <!-- Edit Payment Form -->
            <?php if ($edit_payment_data): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                    <form action="admin_payments_edit.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($edit_payment_data['payment_id']); ?>">
                        <div>
                            <label for="booking_id" class="block text-sm font-medium text-gray-700">Booking</label>
                            <select name="booking_id" id="booking_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Select Booking</option>
                                <?php foreach ($bookings_list as $booking): ?>
                                    <option value="<?php echo htmlspecialchars($booking['booking_id']); ?>" <?php echo ($edit_payment_data['booking_id'] == $booking['booking_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars("ID: " . $booking['booking_id'] . " | Car: " . $booking['car_brand'] . " " . $booking['car_model'] . " | Client: " . $booking['client_first_name'] . " " . $booking['client_last_name'] . " | Dates: " . $booking['start_date'] . " to " . $booking['return_date']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date</label>
                            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($edit_payment_data['payment_date']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="total_amount" class="block text-sm font-medium text-gray-700">Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" id="total_amount" value="<?php echo htmlspecialchars($edit_payment_data['total_amount']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="payment_refnumber" class="block text-sm font-medium text-gray-700">Reference Number</label>
                            <input type="text" name="payment_refnumber" id="payment_refnumber" value="<?php echo htmlspecialchars($edit_payment_data['payment_refnumber']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select name="payment_method" id="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="cash" <?php echo ($edit_payment_data['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="online" <?php echo ($edit_payment_data['payment_method'] == 'online') ? 'selected' : ''; ?>>Online</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="proof_of_payment_path" class="block text-sm font-medium text-gray-700">Proof of Payment Path (Optional)</label>
                            <input type="text" name="proof_of_payment_path" id="proof_of_payment_path" value="<?php echo htmlspecialchars($edit_payment_data['proof_of_payment_path'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., uploads/proof_123.jpg">
                        </div>
                        <div class="md:col-span-3 flex justify-between items-center mt-4">
                            <a href="admin_payments.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                Back to Payment List
                            </a>
                            <button type="submit" name="update_payment" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                                Update Payment
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class='p-4 mb-4 rounded-md bg-red-100 text-red-700 mt-8'>
                    No payment data available for editing. Please go back to the <a href="admin_payments.php" class="font-medium text-red-800 hover:underline">Payment List</a> to select a payment.
                </div>
            <?php endif; ?>

<?php
// End of main content area, close the main tag and the flex container div.
// This part is crucial to close the HTML structure started in admin_base.php.
?>
        </main>
    </div>

</body>
</html>
<?php
// Close the database connection when the page processing is complete.
$conn->close();
?>
