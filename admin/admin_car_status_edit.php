<?php
// Set the page title for the base template.
$page_title = "Edit Car Status";

require_once 'admin_base.php';

$message = '';
$message_type = ''; 

$edit_car_status_data = null; 
$car_details = null; 

// --- Handle Update Car Status Submission ---
if (isset($_POST['update_car_status'])) {
    $car_code = $_POST['car_code'];
    $new_status = $_POST['new_status']; // 'available', 'booked', or 'maintenance'

    // Reset all status flags to FALSE
    $car_available = FALSE;
    $car_booked = FALSE;
    $car_maintenance = FALSE;

    // Set the selected status to TRUE
    switch ($new_status) {
        case 'available':
            $car_available = TRUE;
            break;
        case 'booked':
            $car_booked = TRUE;
            break;
        case 'maintenance':
            $car_maintenance = TRUE;
            break;
    }

    $sql = "UPDATE Car_status SET
                car_available = ?,
                car_booked = ?,
                car_maintenance = ?
            WHERE car_code = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters: 3 integers (booleans), 1 integer (car_code)
        $stmt->bind_param("iiii",
            $car_available,
            $car_booked,
            $car_maintenance,
            $car_code
        );

        if ($stmt->execute()) {
            $message = "Car status updated successfully to: " . htmlspecialchars(ucfirst($new_status)) . "!";
            $message_type = 'success';
            // Redirect back to admin_car_status.php with success message
            header("Location: admin_car_status.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error updating car status: " . $stmt->error;
            $message_type = 'error';
            error_log("Error updating car status: " . $stmt->error); // Log the error
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
        error_log("Error preparing statement: " . $conn->error); // Log the error
    }
}

// --- Fetch Car Status Data for Editing (on initial page load) ---
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_car_code = $_GET['edit'];

    // Fetch car details and its status
    $sql_edit = "SELECT c.car_code, c.car_brand, c.car_model, c.car_platenumber,
                        cs.car_available, cs.car_booked, cs.car_maintenance
                 FROM Cars c
                 JOIN Car_status cs ON c.car_code = cs.car_code
                 WHERE c.car_code = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $edit_car_code);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows > 0) {
            $edit_car_status_data = $result_edit->fetch_assoc();
            $car_details = $edit_car_status_data; // Store car details for display
        } else {
            $message = "Car or its status not found for editing.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    } else {
        $message = "Error preparing statement to fetch car status data: " . $conn->error;
        $message_type = 'error';
    }
} else if (!isset($_GET['edit']) && !isset($_POST['update_car_status'])) {
    $message = "No car selected for status editing. Please select a car from the <a href='admin_car_status.php' class='font-medium text-red-800 hover:underline'>Car Status List</a>.";
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Edit Car Status
                <?php echo ($car_details) ? '(ID: ' . htmlspecialchars($car_details['car_code']) . ' - ' . htmlspecialchars($car_details['car_brand'] . ' ' . $car_details['car_model']) . ')' : ''; ?>
            </h2>

            <!-- Edit Car Status Form -->
            <?php if ($edit_car_status_data): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                    <form action="admin_car_status_edit.php" method="POST" class="grid grid-cols-1 gap-4">
                        <input type="hidden" name="car_code" value="<?php echo htmlspecialchars($edit_car_status_data['car_code']); ?>">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select New Status</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center">
                                    <input id="status_available" name="new_status" type="radio" value="available" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" <?php echo ($edit_car_status_data['car_available'] == 1) ? 'checked' : ''; ?>>
                                    <label for="status_available" class="ml-3 block text-sm font-medium text-gray-700">Available</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="status_booked" name="new_status" type="radio" value="booked" class="focus:ring-yellow-500 h-4 w-4 text-yellow-600 border-gray-300" <?php echo ($edit_car_status_data['car_booked'] == 1) ? 'checked' : ''; ?>>
                                    <label for="status_booked" class="ml-3 block text-sm font-medium text-gray-700">Booked</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="status_maintenance" name="new_status" type="radio" value="maintenance" class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300" <?php echo ($edit_car_status_data['car_maintenance'] == 1) ? 'checked' : ''; ?>>
                                    <label for="status_maintenance" class="ml-3 block text-sm font-medium text-gray-700">Maintenance</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <a href="admin_car_status.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                Back to Car Status List
                            </a>
                            <button type="submit" name="update_car_status" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class='p-4 mb-4 rounded-md bg-red-100 text-red-700 mt-8'>
                    No car status data available for editing. Please go back to the <a href="admin_car_status.php" class="font-medium text-red-800 hover:underline">Car Status List</a> to select a car.
                </div>
            <?php endif; ?>

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
