<?php
// admin_cars_add.php
// This page allows administrators to add new car records.

// Set the page title for the base template.
$page_title = "Add New Car";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// --- Handle Add New Car Submission ---
if (isset($_POST['add_car'])) {
    // Collect form data
    $car_brand = $_POST['car_brand'];
    $car_type = $_POST['car_type'];
    $car_image = $_POST['car_image']; // This will now accept any text string
    $car_model = $_POST['car_model'];
    $car_price_per_day = $_POST['car_price_per_day'];
    $car_platenumber = $_POST['car_platenumber'];
    $car_category = $_POST['car_category'];
    $car_seater = $_POST['car_seater'];
    $car_transmission = $_POST['car_transmission'];
    $car_mileage = $_POST['car_mileage'];
    $car_luggage_capacity = $_POST['car_luggage_capacity'];
    $car_features = $_POST['car_features'];
    $car_drivetype = $_POST['car_drivetype'];

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO Cars (car_brand, car_type, car_image, car_model, car_price_per_day, car_platenumber, car_category, car_seater, car_transmission, car_mileage, car_luggage_capacity, car_features, car_drivetype)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("ssssdssssiiss",
            $car_brand,
            $car_type,
            $car_image, // Bind as string
            $car_model,
            $car_price_per_day,
            $car_platenumber,
            $car_category,
            $car_seater,
            $car_transmission,
            $car_mileage,
            $car_luggage_capacity,
            $car_features,
            $car_drivetype
        );

        // Execute the statement
        if ($stmt->execute()) {
            $message = "Car added successfully!";
            $message_type = 'success';

            // Also insert into Car_status as available
            $new_car_code = $stmt->insert_id; // Get the ID of the newly inserted car
            $sql_status = "INSERT INTO Car_status (car_code, car_available, car_booked, car_maintenance) VALUES (?, TRUE, FALSE, FALSE)";
            if ($stmt_status = $conn->prepare($sql_status)) {
                $stmt_status->bind_param("i", $new_car_code);
                $stmt_status->execute();
                $stmt_status->close();
            } else {
                error_log("Error inserting into Car_status: " . $conn->error);
            }
            // Redirect back to admin_cars.php with success message
            header("Location: admin_cars.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();

        } else {
            $message = "Error adding car: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
    }
}
?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Car</h2>

            <!-- Add New Car Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <form action="admin_cars_add.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="car_brand" class="block text-sm font-medium text-gray-700">Brand</label>
                        <input type="text" name="car_brand" id="car_brand" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_model" class="block text-sm font-medium text-gray-700">Model</label>
                        <input type="text" name="car_model" id="car_model" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_type" class="block text-sm font-medium text-gray-700">Type (e.g., Sedan, SUV)</label>
                        <input type="text" name="car_type" id="car_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_platenumber" class="block text-sm font-medium text-gray-700">Plate Number</label>
                        <input type="text" name="car_platenumber" id="car_platenumber" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_price_per_day" class="block text-sm font-medium text-gray-700">Price Per Day</label>
                        <input type="number" step="0.01" name="car_price_per_day" id="car_price_per_day" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_category" class="block text-sm font-medium text-gray-700">Category</label>
                        <input type="text" name="car_category" id="car_category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_seater" class="block text-sm font-medium text-gray-700">Seater Capacity</label>
                        <input type="number" name="car_seater" id="car_seater" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_transmission" class="block text-sm font-medium text-gray-700">Transmission</label>
                        <select name="car_transmission" id="car_transmission" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">Select...</option>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div>
                        <label for="car_mileage" class="block text-sm font-medium text-gray-700">Mileage (km)</label>
                        <input type="number" name="car_mileage" id="car_mileage" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_luggage_capacity" class="block text-sm font-medium text-gray-700">Luggage Capacity</label>
                        <input type="number" name="car_luggage_capacity" id="car_luggage_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="car_drivetype" class="block text-sm font-medium text-gray-700">Drive Type</label>
                        <input type="text" name="car_drivetype" id="car_drivetype" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="car_features" class="block text-sm font-medium text-gray-700">Features (comma separated)</label>
                        <textarea name="car_features" id="car_features" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="car_image" class="block text-sm font-medium text-gray-700">Image Filename</label>
                        <input type="text" name="car_image" id="car_image" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="md:col-span-2 lg:col-span-3 flex justify-between items-center">
                        <a href="admin_cars.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Back to Car List
                        </a>
                        <button type="submit" name="add_car" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Add Car
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
