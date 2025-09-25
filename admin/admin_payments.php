<?php
// Set the page title for the base template.
$page_title = "Manage Payments";

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

// --- Handle Delete Payment ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $payment_id_to_delete = $_GET['delete'];

    // Start a transaction for atomicity
    $conn->begin_transaction();

    try {
        // First, get the booking_id associated with this payment to update its status later
        $sql_get_booking_id = "SELECT booking_id FROM Payments WHERE payment_id = ?";
        if ($stmt_get_booking_id = $conn->prepare($sql_get_booking_id)) {
            $stmt_get_booking_id->bind_param("i", $payment_id_to_delete);
            $stmt_get_booking_id->execute();
            $result_booking_id = $stmt_get_booking_id->get_result();
            $booking_row = $result_booking_id->fetch_assoc();
            $booking_id = $booking_row['booking_id'] ?? null;
            $stmt_get_booking_id->close();
        } else {
            throw new Exception("Error preparing booking_id fetch: " . $conn->error);
        }

        $sql_payment_del = "DELETE FROM Payments WHERE payment_id = ?";
        if ($stmt_payment_del = $conn->prepare($sql_payment_del)) {
            $stmt_payment_del->bind_param("i", $payment_id_to_delete);
            if ($stmt_payment_del->execute()) {
                if ($stmt_payment_del->affected_rows > 0) {
                    $message = "Payment deleted successfully!";
                    $message_type = 'success';

                    // If a booking_id was found, update the booking status
                    if ($booking_id) {
                        $sql_update_booking = "UPDATE Bookings SET booking_status = 'pending_payment' WHERE booking_id = ?";
                        if ($stmt_update_booking = $conn->prepare($sql_update_booking)) {
                            $stmt_update_booking->bind_param("i", $booking_id);
                            $stmt_update_booking->execute();
                            $stmt_update_booking->close();
                        } else {
                            // Log error but don't prevent payment deletion
                            error_log("Error updating booking status after payment deletion: " . $conn->error);
                        }
                    }
                    $conn->commit(); // Commit transaction on success
                } else {
                    throw new Exception("No payment found with ID: " . htmlspecialchars($payment_id_to_delete));
                }
            } else {
                throw new Exception("Error deleting payment: " . $stmt_payment_del->error);
            }
            $stmt_payment_del->close();
        } else {
            throw new Exception("Error preparing payment deletion: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $message = "Error deleting payment: " . $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to self to clear GET parameters and display message
    header("Location: admin_payments.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Handle Payment Status Update (Approve/Deny) ---
if (isset($_GET['action']) && isset($_GET['payment_id']) && isset($_GET['booking_id'])) {
    $payment_id = $_GET['payment_id'];
    $booking_id = $_GET['booking_id'];
    $action = $_GET['action']; // 'approve' or 'deny'

    $new_payment_status = '';
    $new_booking_status = '';

    if ($action === 'approve') {
        $new_payment_status = 'approved';
        // When payment is approved, the booking status can be 'confirmed'
        // It will be 'ongoing' in bookings.php if start date has passed
        $new_booking_status = 'confirmed';
    } elseif ($action === 'deny') {
        $new_payment_status = 'denied';
        // If payment is denied, the booking reverts to 'pending_payment'
        $new_booking_status = 'pending_payment';
    } else {
        $message = "Invalid action.";
        $message_type = 'error';
        header("Location: admin_payments.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
        exit();
    }

    $conn->begin_transaction();
    try {
        // Update payment status
        $sql_update_payment = "UPDATE Payments SET payment_status = ? WHERE payment_id = ?";
        if ($stmt_update_payment = $conn->prepare($sql_update_payment)) {
            $stmt_update_payment->bind_param("si", $new_payment_status, $payment_id);
            if (!$stmt_update_payment->execute()) {
                throw new Exception("Error updating payment status: " . $stmt_update_payment->error);
            }
            $stmt_update_payment->close();
        } else {
            throw new Exception("Error preparing payment status update: " . $conn->error);
        }

        // Update corresponding booking status
        $sql_update_booking = "UPDATE Bookings SET booking_status = ? WHERE booking_id = ?";
        if ($stmt_update_booking = $conn->prepare($sql_update_booking)) {
            $stmt_update_booking->bind_param("si", $new_booking_status, $booking_id);
            if (!$stmt_update_booking->execute()) {
                throw new Exception("Error updating booking status: " . $stmt_update_booking->error);
            }
            $stmt_update_booking->close();
        } else {
            throw new Exception("Error preparing booking status update: " . $conn->error);
        }

        $conn->commit();
        $message = "Payment status updated to '" . ucfirst($new_payment_status) . "' and booking status updated successfully!";
        $message_type = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating status: " . $e->getMessage();
        $message_type = 'error';
    }

    header("Location: admin_payments.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}


// --- Fetch All Payments for Display ---
$payments = [];
$sql = "SELECT p.*,
               b.booking_id, b.start_date, b.return_date, b.booking_status,
               c.car_brand, c.car_model, c.car_platenumber,
               cl.client_first_name, cl.client_last_name
        FROM Payments p
        JOIN Bookings b ON p.booking_id = b.booking_id
        JOIN Cars c ON b.car_code = c.car_code
        JOIN Client cl ON b.customer_id = cl.customer_id
        ORDER BY p.payment_id DESC"; // Order by latest added payments first

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
} else {
    $message = "Error fetching payments: " . $conn->error;
    $message_type = 'error';
}

?>

            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Payment Management</h2>

            <div class="mb-6 flex justify-end">
                <a href="admin_payments_add.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Payment
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Payments</h3>
                <?php if (empty($payments)): ?>
                    <p class="text-gray-600">No payments found in the database. Add a new payment using the button above!</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref Number</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proof</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($payment['booking_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($payment['client_first_name'] . ' ' . $payment['client_last_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($payment['car_brand'] . ' ' . $payment['car_model'] . ' (' . $payment['car_platenumber'] . ')'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo htmlspecialchars(number_format($payment['total_amount'], 2)); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['payment_refnumber']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($payment['payment_method'] === 'cash') ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($payment['proof_of_payment_path'])): ?>
                                                <a href="../<?php echo htmlspecialchars($payment['proof_of_payment_path']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Proof</a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                if ($payment['payment_status'] === 'approved') echo 'bg-green-100 text-green-800';
                                                elseif ($payment['payment_status'] === 'denied') echo 'bg-red-100 text-red-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                                ?>">
                                                <?php echo htmlspecialchars(ucfirst($payment['payment_status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if ($payment['payment_status'] === 'pending'): ?>
                                                <a href="admin_payments.php?action=approve&payment_id=<?php echo htmlspecialchars($payment['payment_id']); ?>&booking_id=<?php echo htmlspecialchars($payment['booking_id']); ?>" class="text-green-600 hover:text-green-900 mr-4" onclick="return confirm('Are you sure you want to approve this payment?');">Approve</a>
                                                <a href="admin_payments.php?action=deny&payment_id=<?php echo htmlspecialchars($payment['payment_id']); ?>&booking_id=<?php echo htmlspecialchars($payment['booking_id']); ?>" class="text-orange-600 hover:text-orange-900 mr-4" onclick="return confirm('Are you sure you want to deny this payment? This will revert the booking status.');">Deny</a>
                                            <?php endif; ?>
                                            <a href="admin_payments_edit.php?edit=<?php echo htmlspecialchars($payment['payment_id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <a href="admin_payments.php?delete=<?php echo htmlspecialchars($payment['payment_id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this payment record? This action cannot be undone and will revert the booking status.');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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