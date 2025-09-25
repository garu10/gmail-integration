<?php


// Set the page title for the base template.
$page_title = "Manage Bookings";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages passed via GET (e.g., from add/edit pages)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Handle Delete Booking ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $booking_id_to_delete = $_GET['delete'];

    // Start a transaction for atomicity
    $conn->begin_transaction();

    try {
        $sql_payments_del = "DELETE FROM Payments WHERE booking_id = ?";
        if ($stmt_payments_del = $conn->prepare($sql_payments_del)) {
            $stmt_payments_del->bind_param("i", $booking_id_to_delete);
            $stmt_payments_del->execute(); 
            $stmt_payments_del->close();
        } else {

            error_log("Error preparing Payments deletion: " . $conn->error);
        }


        $sql_booking_del = "DELETE FROM Bookings WHERE booking_id = ?";
        if ($stmt_booking_del = $conn->prepare($sql_booking_del)) {
            $stmt_booking_del->bind_param("i", $booking_id_to_delete);
            if ($stmt_booking_del->execute()) {
                if ($stmt_booking_del->affected_rows > 0) {
                    $message = "Booking deleted successfully!";
                    $message_type = 'success';
                    $conn->commit(); // Commit transaction on success
                } else {
                    throw new Exception("No booking found with ID: " . htmlspecialchars($booking_id_to_delete));
                }
            } else {
                throw new Exception("Error deleting booking: " . $stmt_booking_del->error);
            }
            $stmt_booking_del->close();
        } else {
            throw new Exception("Error preparing booking deletion: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $message = "Error deleting booking: " . $e->getMessage();
        $message_type = 'error';
        error_log("Transaction rolled back for booking delete. Error: " . $e->getMessage());
    }

    header("Location: admin_bookings.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Fetch All Bookings for Display ---
$bookings = [];
$sql = "SELECT b.*,
               c.car_brand, c.car_model, c.car_platenumber,
               cl.client_first_name, cl.client_last_name, cl.client_username,
               pl.location_name AS pickup_location_name,
               dl.location_name AS dropoff_location_name
        FROM Bookings b
        JOIN Cars c ON b.car_code = c.car_code
        JOIN Client cl ON b.customer_id = cl.customer_id
        JOIN Locations pl ON b.pickup_location_id = pl.location_id
        JOIN Locations dl ON b.dropoff_location_id = dl.location_id
        ORDER BY b.booking_id DESC"; // Order by latest added bookings first

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
} else {
    $message = "Error fetching bookings: " . $conn->error;
    $message_type = 'error';
    error_log("Error fetching bookings: " . $conn->error);
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Booking Management</h2>

            <!-- Navigation Buttons -->
            <div class="mb-6 flex justify-end">
                <a href="admin_bookings_add.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Booking
                </a>
            </div>

            <!-- Bookings List Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Bookings</h3>
                <?php if (empty($bookings)): ?>
                    <p class="text-gray-600">No bookings found in the database. Add a new booking using the button above!</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dropoff Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dropoff Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($booking['car_brand'] . ' ' . $booking['car_model'] . ' (' . $booking['car_platenumber'] . ')'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($booking['client_first_name'] . ' ' . $booking['client_last_name'] . ' (' . $booking['client_username'] . ')'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['start_date']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['pickup_time']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['return_date']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['dropoff_time']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['pickup_location_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['dropoff_location_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo htmlspecialchars(number_format($booking['total_cost'], 2)); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_bookings_edit.php?edit=<?php echo htmlspecialchars($booking['booking_id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <a href="admin_bookings.php?delete=<?php echo htmlspecialchars($booking['booking_id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

<?php


?>
        </main>
    </div>

</body>
</html>
<?php
// Close the database connection when the page processing is complete.
$conn->close();
?>
