<?php
// Set the page title for the base template.
$page_title = "Manage Cars";

// Include the base admin template.
// This will handle session start, authentication, and render the common header/sidebar.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages passed via GET (e.g., from add/edit pages)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}


// --- Handle Delete Car ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $car_code_to_delete = $_GET['delete'];

    // Start a transaction to ensure both Car and Car_status are handled correctly
    $conn->begin_transaction();

    try {
        // Delete from Cars
        // Due to FOREIGN KEY (car_code) REFERENCES Cars(car_code) ON DELETE CASCADE in Car_status,
        // deleting from Cars will automatically delete related records in Car_status.
        $sql_car_del = "DELETE FROM Cars WHERE car_code = ?";
        if ($stmt_car_del = $conn->prepare($sql_car_del)) {
            $stmt_car_del->bind_param("i", $car_code_to_delete);
            if ($stmt_car_del->execute()) {
                if ($stmt_car_del->affected_rows > 0) {
                    $message = "Car deleted successfully!";
                    $message_type = 'success';
                    $conn->commit(); // Commit transaction on success
                } else {
                    throw new Exception("No car found with ID: " . htmlspecialchars($car_code_to_delete));
                }
            } else {
                throw new Exception("Error deleting car: " . $stmt_car_del->error);
            }
            $stmt_car_del->close();
        } else {
            throw new Exception("Error preparing car deletion: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $message = "Error deleting car: " . $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to self to clear GET parameters and display message
    header("Location: admin_cars.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}


// --- Fetch All Cars for Display ---
$cars = [];
$sql = "SELECT c.*, cs.car_available, cs.car_booked, cs.car_maintenance
        FROM Cars c
        LEFT JOIN Car_status cs ON c.car_code = cs.car_code
        ORDER BY c.car_code DESC"; // Order by latest added cars first

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
} else {
    $message = "Error fetching cars: " . $conn->error;
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Car Management</h2>

            <!-- Navigation Buttons -->
            <div class="mb-6 flex justify-end">
                <a href="admin_cars_add.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Car
                </a>
            </div>

            <!-- Cars List Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Cars</h3>
                <?php if (empty($cars)): ?>
                    <p class="text-gray-600">No cars found in the database. Add a new car using the button above!</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seater</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transmission</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mileage</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Luggage</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drive Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plate No.</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price/Day</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($cars as $car): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car['car_code']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($car['car_image'])): ?>
                                                <img src="../images/<?php echo htmlspecialchars($car['car_image']); ?>" alt="<?php echo htmlspecialchars($car['car_brand'] . ' ' . $car['car_model']); ?>" class="h-12 w-16 object-cover rounded-md" onerror="this.onerror=null;this.src='https://placehold.co/100x80/e2e8f0/000000?text=No%20Image';">
                                            <?php else: ?>
                                                <img src="https://placehold.co/100x80/e2e8f0/000000?text=No%20Image" alt="No Image" class="h-12 w-16 object-cover rounded-md">
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($car['car_brand']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_model']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_type']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_category']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_seater']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_transmission']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_mileage']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_luggage_capacity']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_drivetype']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($car['car_platenumber']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo htmlspecialchars(number_format($car['car_price_per_day'], 2)); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                                if ($car['car_booked']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Booked</span>';
                                                } elseif ($car['car_maintenance']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Maintenance</span>';
                                                } elseif ($car['car_available']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>';
                                                } else {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_cars_edit.php?edit=<?php echo htmlspecialchars($car['car_code']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <a href="admin_cars.php?delete=<?php echo htmlspecialchars($car['car_code']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this car? This action cannot be undone and will also delete associated status records.');">Delete</a>
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
