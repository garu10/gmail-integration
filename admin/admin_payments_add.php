<?php
// Set the page title for the base template.
$page_title = "Add New Payment";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

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

// --- Handle Add New Payment Submission ---
if (isset($_POST['add_payment'])) {
    // Collect form data
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

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO Payments (booking_id, payment_date, total_amount, payment_refnumber, payment_method, proof_of_payment_path)
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters: 1 integer, 1 string (date), 1 double, 2 strings, 1 string (nullable path)
        $stmt->bind_param("isdsss",
            $booking_id,
            $payment_date,
            $total_amount,
            $payment_refnumber,
            $payment_method,
            $proof_of_payment_path
        );

        // Execute the statement
        if ($stmt->execute()) {
            $message = "Payment added successfully!";
            $message_type = 'success';
            // Redirect back to admin_payments.php with success message
            header("Location: admin_payments.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error adding payment: " . $stmt->error;
            $message_type = 'error';
            error_log("Error adding payment: " . $stmt->error); // Log the error
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
        error_log("Error preparing statement: " . $conn->error); // Log the error
    }
}
?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Payment</h2>

            <!-- Add New Payment Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <form action="admin_payments_add.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="booking_id" class="block text-sm font-medium text-gray-700">Booking</label>
                        <select name="booking_id" id="booking_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">Select Booking</option>
                            <?php foreach ($bookings_list as $booking): ?>
                                <option value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                                    <?php echo htmlspecialchars("ID: " . $booking['booking_id'] . " | Car: " . $booking['car_brand'] . " " . $booking['car_model'] . " | Client: " . $booking['client_first_name'] . " " . $booking['client_last_name'] . " | Dates: " . $booking['start_date'] . " to " . $booking['return_date']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="total_amount" class="block text-sm font-medium text-gray-700">Total Amount</label>
                        <input type="number" step="0.01" name="total_amount" id="total_amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="payment_refnumber" class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" name="payment_refnumber" id="payment_refnumber" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select name="payment_method" id="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="proof_of_payment_path" class="block text-sm font-medium text-gray-700">Proof of Payment Path (Optional)</label>
                        <input type="text" name="proof_of_payment_path" id="proof_of_payment_path" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., uploads/proof_123.jpg">
                    </div>
                    <div class="md:col-span-3 flex justify-between items-center mt-4">
                        <a href="admin_payments.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Back to Payment List
                        </a>
                        <button type="submit" name="add_payment" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Add Payment
                        </button>
                    </div>
                </form>
            </div>

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
