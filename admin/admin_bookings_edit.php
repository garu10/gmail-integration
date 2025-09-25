<?php
// Set the page title for the base template.
$page_title = "Edit Booking";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

$edit_booking_data = null; // Initialize to null

// --- Fetch Cars, Clients, and Locations for Dropdowns ---
$cars_list = [];
$clients_list = [];
$locations_list = [];

// Fetch Cars
$sql_cars = "SELECT car_code, car_brand, car_model, car_platenumber FROM Cars ORDER BY car_brand, car_model";
if ($result_cars = $conn->query($sql_cars)) {
    while ($row = $result_cars->fetch_assoc()) {
        $cars_list[] = $row;
    }
    $result_cars->free();
} else {
    error_log("Error fetching cars for dropdown: " . $conn->error);
}

// Fetch Clients
$sql_clients = "SELECT customer_id, client_first_name, client_last_name, client_username FROM Client ORDER BY client_last_name, client_first_name";
if ($result_clients = $conn->query($sql_clients)) {
    while ($row = $result_clients->fetch_assoc()) {
        $clients_list[] = $row;
    }
    $result_clients->free();
} else {
    error_log("Error fetching clients for dropdown: " . $conn->error);
}

// Fetch Locations
$sql_locations = "SELECT location_id, location_name FROM Locations ORDER BY location_name";
if ($result_locations = $conn->query($sql_locations)) {
    while ($row = $result_locations->fetch_assoc()) {
        $locations_list[] = $row;
    }
    $result_locations->free();
} else {
    error_log("Error fetching locations for dropdown: " . $conn->error);
}


// --- Handle Update Booking Submission ---
if (isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $car_code = $_POST['car_code'];
    $customer_id = $_POST['customer_id'];
    $start_date = $_POST['start_date'];
    $pickup_time = $_POST['pickup_time'];
    $return_date = $_POST['return_date'];
    $dropoff_time = $_POST['dropoff_time'];
    $pickup_location_id = $_POST['pickup_location_id'];
    $dropoff_location_id = $_POST['dropoff_location_id'];
    $total_cost = $_POST['total_cost'];

    $sql_fields_to_update = [];
    $params_to_bind = [];

    $sql_fields_to_update[] = "car_code = ?";
    $params_to_bind[] = $car_code;

    $sql_fields_to_update[] = "customer_id = ?";
    $params_to_bind[] = $customer_id;

    $sql_fields_to_update[] = "start_date = ?";
    $params_to_bind[] = $start_date;

    $sql_fields_to_update[] = "pickup_time = ?";
    $params_to_bind[] = $pickup_time;

    $sql_fields_to_update[] = "return_date = ?";
    $params_to_bind[] = $return_date;

    $sql_fields_to_update[] = "dropoff_time = ?";
    $params_to_bind[] = $dropoff_time;

    $sql_fields_to_update[] = "pickup_location_id = ?";
    $params_to_bind[] = $pickup_location_id;

    $sql_fields_to_update[] = "dropoff_location_id = ?";
    $params_to_bind[] = $dropoff_location_id;

    $sql_fields_to_update[] = "total_cost = ?";
    $params_to_bind[] = $total_cost;

    // Append booking_id for WHERE clause
    $params_to_bind[] = $booking_id;

    $sql = "UPDATE Bookings SET " . implode(", ", $sql_fields_to_update) . " WHERE booking_id = ?";

    // Dynamically generate the types string based on the populated params_to_bind array
    $types_string = "";
    foreach ($params_to_bind as $param) {
        if (is_int($param)) {
            $types_string .= "i";
        } elseif (is_float($param)) {
            $types_string .= "d";
        } else {
            $types_string .= "s"; // Default to string for null, boolean, and other types
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
            $message = "Booking updated successfully!";
            $message_type = 'success';
            // Redirect back to admin_bookings.php with success message
            header("Location: admin_bookings.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error updating booking: " . $stmt->error;
            $message_type = 'error';
            error_log("Error updating booking: " . $stmt->error); // Log the error
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
        error_log("Error preparing statement: " . $conn->error); // Log the error
    }
}

// --- Fetch Booking Data for Editing (on initial page load) ---
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_booking_id = $_GET['edit'];

    $sql_edit = "SELECT * FROM Bookings WHERE booking_id = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $edit_booking_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows > 0) {
            $edit_booking_data = $result_edit->fetch_assoc();
        } else {
            $message = "Booking not found for editing.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    } else {
        $message = "Error preparing statement to fetch booking data: " . $conn->error;
        $message_type = 'error';
    }
} else if (!isset($_GET['edit']) && !isset($_POST['update_booking'])) {
    $message = "No booking selected for editing. Please select a booking from the list.";
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Edit Booking
                <?php echo ($edit_booking_data) ? '(ID: ' . htmlspecialchars($edit_booking_data['booking_id']) . ')' : ''; ?>
            </h2>

            <!-- Edit Booking Form -->
            <?php if ($edit_booking_data): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                    <form action="admin_bookings_edit.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($edit_booking_data['booking_id']); ?>">
                        <div>
                            <label for="car_code" class="block text-sm font-medium text-gray-700">Car</label>
                            <select name="car_code" id="car_code" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Select Car</option>
                                <?php foreach ($cars_list as $car): ?>
                                    <option value="<?php echo htmlspecialchars($car['car_code']); ?>" <?php echo ($edit_booking_data['car_code'] == $car['car_code']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($car['car_brand'] . ' ' . $car['car_model'] . ' (' . $car['car_platenumber'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select name="customer_id" id="customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Select Client</option>
                                <?php foreach ($clients_list as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['customer_id']); ?>" <?php echo ($edit_booking_data['customer_id'] == $client['customer_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['client_first_name'] . ' ' . $client['client_last_name'] . ' (' . $client['client_username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($edit_booking_data['start_date']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="pickup_time" class="block text-sm font-medium text-gray-700">Pickup Time</label>
                            <input type="time" name="pickup_time" id="pickup_time" value="<?php echo htmlspecialchars($edit_booking_data['pickup_time']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="return_date" class="block text-sm font-medium text-gray-700">Return Date</label>
                            <input type="date" name="return_date" id="return_date" value="<?php echo htmlspecialchars($edit_booking_data['return_date']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="dropoff_time" class="block text-sm font-medium text-gray-700">Dropoff Time</label>
                            <input type="time" name="dropoff_time" id="dropoff_time" value="<?php echo htmlspecialchars($edit_booking_data['dropoff_time']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="pickup_location_id" class="block text-sm font-medium text-gray-700">Pickup Location</label>
                            <select name="pickup_location_id" id="pickup_location_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Select Pickup Location</option>
                                <?php foreach ($locations_list as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location['location_id']); ?>" <?php echo ($edit_booking_data['pickup_location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="dropoff_location_id" class="block text-sm font-medium text-gray-700">Dropoff Location</label>
                            <select name="dropoff_location_id" id="dropoff_location_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Select Dropoff Location</option>
                                <?php foreach ($locations_list as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location['location_id']); ?>" <?php echo ($edit_booking_data['dropoff_location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="total_cost" class="block text-sm font-medium text-gray-700">Total Cost</label>
                            <input type="number" step="0.01" name="total_cost" id="total_cost" value="<?php echo htmlspecialchars($edit_booking_data['total_cost']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>

                        <div class="md:col-span-3 flex justify-between items-center mt-4">
                            <a href="admin_bookings.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                Back to Booking List
                            </a>
                            <button type="submit" name="update_booking" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                                Update Booking
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class='p-4 mb-4 rounded-md bg-red-100 text-red-700 mt-8'>
                    No booking data available for editing. Please go back to the <a href="admin_bookings.php" class="font-medium text-red-800 hover:underline">Booking List</a> to select a booking.
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
