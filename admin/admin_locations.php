<?php
// Set the page title for the base template.
$page_title = "Manage Locations";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages passed via GET (e.g., from add/edit pages or after form submission)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Handle Add New Location ---
if (isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name']);

    if (!empty($location_name)) {
        $sql_insert_loc = "INSERT INTO Locations (location_name) VALUES (?)";
        if ($stmt_insert_loc = $conn->prepare($sql_insert_loc)) {
            $stmt_insert_loc->bind_param("s", $location_name);
            if ($stmt_insert_loc->execute()) {
                $message = "Location '" . htmlspecialchars($location_name) . "' added successfully!";
                $message_type = 'success';
            } else {
                $message = "Error adding location: " . $stmt_insert_loc->error;
                $message_type = 'error';
            }
            $stmt_insert_loc->close();
        } else {
            $message = "Error preparing location insert statement: " . $conn->error;
            $message_type = 'error';
        }
    } else {
        $message = "Location name cannot be empty.";
        $message_type = 'error';
    }
    // Redirect to self to clear POST data and display message
    header("Location: admin_locations.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Handle Edit Location ---
if (isset($_POST['update_location'])) {
    $location_id = $_POST['location_id'];
    $location_name = trim($_POST['location_name']);

    if (!empty($location_name) && !empty($location_id)) {
        $sql_update_loc = "UPDATE Locations SET location_name = ? WHERE location_id = ?";
        if ($stmt_update_loc = $conn->prepare($sql_update_loc)) {
            $stmt_update_loc->bind_param("si", $location_name, $location_id);
            if ($stmt_update_loc->execute()) {
                if ($stmt_update_loc->affected_rows > 0) {
                    $message = "Location updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "No changes made or location not found.";
                    $message_type = 'info'; // Use 'info' for no changes
                }
            } else {
                $message = "Error updating location: " . $stmt_update_loc->error;
                $message_type = 'error';
            }
            $stmt_update_loc->close();
        } else {
            $message = "Error preparing location update statement: " . $conn->error;
            $message_type = 'error';
        }
    } else {
        $message = "Location ID or name cannot be empty for update.";
        $message_type = 'error';
    }
    header("Location: admin_locations.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}


// --- Handle Delete Location ---
if (isset($_GET['delete_location']) && !empty($_GET['delete_location'])) {
    $location_id_to_delete = $_GET['delete_location'];

    $conn->begin_transaction();
    try {
        $sql_del_loc = "DELETE FROM Locations WHERE location_id = ?";
        if ($stmt_del_loc = $conn->prepare($sql_del_loc)) {
            $stmt_del_loc->bind_param("i", $location_id_to_delete);
            if ($stmt_del_loc->execute()) {
                if ($stmt_del_loc->affected_rows > 0) {
                    $message = "Location deleted successfully!";
                    $message_type = 'success';
                    $conn->commit();
                } else {
                    throw new Exception("No location found with ID: " . htmlspecialchars($location_id_to_delete));
                }
            } else {
                throw new Exception("Error deleting location: " . $stmt_del_loc->error);
            }
            $stmt_del_loc->close();
        } else {
            throw new Exception("Error preparing location delete statement: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error deleting location: " . $e->getMessage();
        $message_type = 'error';
    }
    header("Location: admin_locations.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}


// --- Fetch All Locations for Display ---
$locations = [];
$sql_fetch_locations = "SELECT * FROM Locations ORDER BY location_name";
if ($result_locations = $conn->query($sql_fetch_locations)) {
    while ($row = $result_locations->fetch_assoc()) {
        $locations[] = $row;
    }
    $result_locations->free();
} else {
    $message = "Error fetching locations: " . $conn->error;
    $message_type = 'error';
}

// --- Fetch All Car_Location Associations for Display ---
$car_locations = [];
$sql_fetch_car_locations = "SELECT cl.car_location_id, c.car_brand, c.car_model, c.car_platenumber, l.location_name
                            FROM Car_Location cl
                            JOIN Cars c ON cl.car_code = c.car_code
                            JOIN Locations l ON cl.location_id = l.location_id
                            ORDER BY l.location_name, c.car_brand, c.car_model";
if ($result_car_locations = $conn->query($sql_fetch_car_locations)) {
    while ($row = $result_car_locations->fetch_assoc()) {
        $car_locations[] = $row;
    }
    $result_car_locations->free();
} else {
    $message = (empty($message) ? "" : $message . "<br>") . "Error fetching car-location associations: " . $conn->error;
    $message_type = (empty($message_type) || $message_type === 'success') ? 'error' : $message_type;
}

// --- Fetch Cars and Locations for Car_Location Add/Edit Dropdowns ---
$cars_for_car_loc_dropdown = [];
$locations_for_car_loc_dropdown = [];

$sql_cars_for_dropdown = "SELECT car_code, car_brand, car_model, car_platenumber FROM Cars ORDER BY car_brand, car_model";
if ($result = $conn->query($sql_cars_for_dropdown)) {
    while ($row = $result->fetch_assoc()) {
        $cars_for_car_loc_dropdown[] = $row;
    }
    $result->free();
}

$sql_locations_for_dropdown = "SELECT location_id, location_name FROM Locations ORDER BY location_name";
if ($result = $conn->query($sql_locations_for_dropdown)) {
    while ($row = $result->fetch_assoc()) {
        $locations_for_car_loc_dropdown[] = $row;
    }
    $result->free();
}


// --- Handle Add Car_Location Association ---
if (isset($_POST['add_car_location'])) {
    $car_code = $_POST['car_code_car_loc'];
    $location_id = $_POST['location_id_car_loc'];

    // Check for duplicate entry before inserting
    $sql_check_duplicate = "SELECT COUNT(*) FROM Car_Location WHERE car_code = ? AND location_id = ?";
    if ($stmt_check = $conn->prepare($sql_check_duplicate)) {
        $stmt_check->bind_param("ii", $car_code, $location_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $message = "This car is already associated with this location!";
            $message_type = 'error';
        } else {
            $sql_insert_car_loc = "INSERT INTO Car_Location (car_code, location_id) VALUES (?, ?)";
            if ($stmt_insert_car_loc = $conn->prepare($sql_insert_car_loc)) {
                $stmt_insert_car_loc->bind_param("ii", $car_code, $location_id);
                if ($stmt_insert_car_loc->execute()) {
                    $message = "Car-Location association added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error adding Car-Location association: " . $stmt_insert_car_loc->error;
                    $message_type = 'error';
                }
                $stmt_insert_car_loc->close();
            } else {
                $message = "Error preparing Car-Location insert statement: " . $conn->error;
                $message_type = 'error';
            }
        }
    } else {
        $message = "Error preparing duplicate check statement: " . $conn->error;
        $message_type = 'error';
    }
    header("Location: admin_locations.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Handle Delete Car_Location Association ---
if (isset($_GET['delete_car_location']) && !empty($_GET['delete_car_location'])) {
    $car_location_id_to_delete = $_GET['delete_car_location'];

    $sql_del_car_loc = "DELETE FROM Car_Location WHERE car_location_id = ?";
    if ($stmt_del_car_loc = $conn->prepare($sql_del_car_loc)) {
        $stmt_del_car_loc->bind_param("i", $car_location_id_to_delete);
        if ($stmt_del_car_loc->execute()) {
            if ($stmt_del_car_loc->affected_rows > 0) {
                $message = "Car-Location association deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "No Car-Location association found with ID: " . htmlspecialchars($car_location_id_to_delete);
                $message_type = 'error';
            }
        } else {
            $message = "Error deleting Car-Location association: " . $stmt_del_car_loc->error;
            $message_type = 'error';
        }
        $stmt_del_car_loc->close();
    } else {
        $message = "Error preparing Car-Location delete statement: " . $conn->error;
        $message_type = 'error';
    }
    header("Location: admin_locations.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Location Management</h2>

            <!-- Locations Management Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Manage Rental Locations</h3>

                <!-- Add New Location Form -->
                <h4 class="text-lg font-medium text-gray-800 mb-2">Add New Location</h4>
                <form action="admin_locations.php" method="POST" class="flex items-center gap-4 mb-6">
                    <input type="text" name="location_name" placeholder="New Location Name" required class="flex-grow rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    <button type="submit" name="add_location" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                        Add Location
                    </button>
                </form>

                <!-- Existing Locations Table -->
                <h4 class="text-lg font-medium text-gray-800 mb-2">Existing Locations</h4>
                <?php if (empty($locations)): ?>
                    <p class="text-gray-600">No locations found in the database.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($locations as $location): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($location['location_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($location['location_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button type="button" class="text-indigo-600 hover:text-indigo-900 mr-4 edit-location-btn" data-id="<?php echo htmlspecialchars($location['location_id']); ?>" data-name="<?php echo htmlspecialchars($location['location_name']); ?>">Edit</button>
                                            <a href="admin_locations.php?delete_location=<?php echo htmlspecialchars($location['location_id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this location? This action cannot be undone and will also delete associated Car-Location and Booking records.');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Edit Location Form (hidden by default, shown by JS) -->
                <div id="edit-location-form" class="mt-8 p-4 border border-gray-200 rounded-md bg-gray-50 hidden">
                    <h4 class="text-lg font-medium text-gray-800 mb-2">Edit Location</h4>
                    <form action="admin_locations.php" method="POST" class="flex items-center gap-4">
                        <input type="hidden" name="location_id" id="edit_location_id">
                        <input type="text" name="location_name" id="edit_location_name" required class="flex-grow rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <button type="submit" name="update_location" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                            Update Location
                        </button>
                        <button type="button" id="cancel-edit-location" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Cancel
                        </button>
                    </form>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const editLocationButtons = document.querySelectorAll('.edit-location-btn');
                        const editLocationForm = document.getElementById('edit-location-form');
                        const editLocationIdInput = document.getElementById('edit_location_id');
                        const editLocationNameInput = document.getElementById('edit_location_name');
                        const cancelEditButton = document.getElementById('cancel-edit-location');

                        editLocationButtons.forEach(button => {
                            button.addEventListener('click', function() {
                                const locationId = this.dataset.id;
                                const locationName = this.dataset.name;

                                editLocationIdInput.value = locationId;
                                editLocationNameInput.value = locationName;
                                editLocationForm.classList.remove('hidden');
                            });
                        });

                        cancelEditButton.addEventListener('click', function() {
                            editLocationForm.classList.add('hidden');
                            editLocationIdInput.value = '';
                            editLocationNameInput.value = '';
                        });
                    });
                </script>
            </div>

            <!-- Car_Location Associations Management Section -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Manage Car-Location Associations</h3>

                <!-- Add New Car_Location Association Form -->
                <h4 class="text-lg font-medium text-gray-800 mb-2">Add New Car-Location Association</h4>
                <form action="admin_locations.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="car_code_car_loc" class="block text-sm font-medium text-gray-700">Select Car</label>
                        <select name="car_code_car_loc" id="car_code_car_loc" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">-- Select Car --</option>
                            <?php foreach ($cars_for_car_loc_dropdown as $car): ?>
                                <option value="<?php echo htmlspecialchars($car['car_code']); ?>">
                                    <?php echo htmlspecialchars($car['car_brand'] . ' ' . $car['car_model'] . ' (' . $car['car_platenumber'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="location_id_car_loc" class="block text-sm font-medium text-gray-700">Select Location</label>
                        <select name="location_id_car_loc" id="location_id_car_loc" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">-- Select Location --</option>
                            <?php foreach ($locations_for_car_loc_dropdown as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" name="add_car_location" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Add Association
                        </button>
                    </div>
                </form>

                <!-- Existing Car_Location Associations Table -->
                <h4 class="text-lg font-medium text-gray-800 mb-2">Existing Car-Location Associations</h4>
                <?php if (empty($car_locations)): ?>
                    <p class="text-gray-600">No car-location associations found in the database.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($car_locations as $car_loc): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car_loc['car_location_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($car_locations['car_code']. $car_loc['car_brand'] . ' ' . $car_loc['car_model'] . ' (' . $car_loc['car_platenumber'] . ')'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($car_loc['location_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_locations.php?delete_car_location=<?php echo htmlspecialchars($car_loc['car_location_id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this car-location association?');">Delete</a>
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
