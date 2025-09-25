<?php
// admin_car_status.php
// This page allows administrators to view and manage the availability status of cars.

// Set the page title for the base template.
$page_title = "Manage Car Status";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages passed via GET (e.g., from edit page)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Fetch Cars and their Status for Display ---
$cars_status = [];
$sql = "SELECT c.car_code, c.car_brand, c.car_model, c.car_platenumber,
               cs.car_available, cs.car_booked, cs.car_maintenance
        FROM Cars c
        LEFT JOIN Car_status cs ON c.car_code = cs.car_code
        ORDER BY c.car_brand, c.car_model";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cars_status[] = $row;
        }
    }
    $result->free();
} else {
    $message = "Error fetching car status data: " . $conn->error;
    $message_type = 'error';
    error_log("Error fetching car status data: " . $conn->error);
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Car Status Management</h2>

            <!-- Car Status List Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Current Car Status</h3>
                <?php if (empty($cars_status)): ?>
                    <p class="text-gray-600">No car status records found in the database. Add cars to view their status.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car Details</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($cars_status as $car): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car['car_code']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($car['car_brand'] . ' ' . $car['car_model']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($car['car_platenumber']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                                if ($car['car_booked']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Booked</span>';
                                                } elseif ($car['car_maintenance']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Maintenance</span>';
                                                } elseif ($car['car_available']) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>';
                                                } else {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown / No Status</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_car_status_edit.php?edit=<?php echo htmlspecialchars($car['car_code']); ?>" class="text-indigo-600 hover:text-indigo-900">Edit Status</a>
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
