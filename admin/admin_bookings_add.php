<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the page title for the base template.
$page_title = "Add New Booking";

require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

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
    // Log error for debugging purposes
    error_log("Error fetching cars for dropdown: " . $conn->error);
    $message .= "Failed to load car data. ";
    $message_type = 'error';
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
    $message .= "Failed to load client data. ";
    $message_type = 'error';
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
    $message .= "Failed to load location data. ";
    $message_type = 'error';
}


// --- Handle Add New Booking Submission ---
if (isset($_POST['add_booking'])) {
    // Collect form data
    $car_code = $_POST['car_code'];
    $customer_id = $_POST['customer_id'];
    $start_date = $_POST['start_date'];
    $pickup_time = $_POST['pickup_time'];
    $return_date = $_POST['return_date'];
    $dropoff_time = $_POST['dropoff_time'];
    $pickup_location_id = $_POST['pickup_location_id'];
    $dropoff_location_id = $_POST['dropoff_location_id'];
    $total_cost = $_POST['total_cost'];

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO Bookings (car_code, customer_id, start_date, pickup_time, return_date, dropoff_time, pickup_location_id, dropoff_location_id, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iissssidd",
            $car_code,
            $customer_id,
            $start_date,
            $pickup_time,
            $return_date,
            $dropoff_time,
            $pickup_location_id,
            $dropoff_location_id,
            $total_cost
        );

        // Execute the statement
        if ($stmt->execute()) {
            $message = "Booking added successfully!";
            $message_type = 'success';
            // Redirect back to admin_bookings.php with success message
            header("Location: admin_bookings.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error adding booking: " . $stmt->error;
            $message_type = 'error';
            error_log("Error adding booking: " . $stmt->error); // Log the error
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

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Booking</h2>

            <!-- Add New Booking Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <form action="admin_bookings_add.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="car_code" class="block text-sm font-medium text-gray-700">Car</label>
                        <select name="car_code" id="car_code" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">Select Car</option>
                            <?php foreach ($cars_list as $car): ?>
                                <option value="<?php echo htmlspecialchars($car['car_code']); ?>">
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
                                <option value="<?php echo htmlspecialchars($client['customer_id']); ?>">
                                    <?php echo htmlspecialchars($client['client_first_name'] . ' ' . $client['client_last_name'] . ' (' . $client['client_username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="pickup_time" class="block text-sm font-medium text-gray-700">Pickup Time</label>
                        <input type="time" name="pickup_time" id="pickup_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="return_date" class="block text-sm font-medium text-gray-700">Return Date</label>
                        <input type="date" name="return_date" id="return_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="dropoff_time" class="block text-sm font-medium text-gray-700">Dropoff Time</label>
                        <input type="time" name="dropoff_time" id="dropoff_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="pickup_location_id" class="block text-sm font-medium text-gray-700">Pickup Location</label>
                        <select name="pickup_location_id" id="pickup_location_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">Select Pickup Location</option>
                            <?php foreach ($locations_list as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
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
                                <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="total_cost" class="block text-sm font-medium text-gray-700">Total Cost</label>
                        <input type="number" step="0.01" name="total_cost" id="total_cost" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>

                    <div class="md:col-span-3 flex justify-between items-center mt-4">
                        <a href="admin_bookings.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Back to Booking List
                        </a>
                        <button type="submit" name="add_booking" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Add Booking
                        </button>
                    </div>
                </form>
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
