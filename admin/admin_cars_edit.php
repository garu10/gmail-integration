<?php
// admin_clients_edit.php
// This page allows administrators to edit existing client records.

// --- DEBUGGING: Enable PHP Error Reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING SETTINGS ---


// Set the page title for the base template.
$page_title = "Edit Client";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

$edit_client_data = null; // Initialize to null

// --- Handle Update Client Submission ---
if (isset($_POST['update_client'])) {
    $customer_id = $_POST['customer_id'];
    $client_username = $_POST['client_username'];
    $client_password_new = $_POST['client_password_new'];
    $client_address = $_POST['client_address'];
    $client_contact_number = $_POST['client_contact_number'];
    $client_driver_license_number = $_POST['client_driver_license_number'];
    $client_email_address = $_POST['client_email_address'];
    $client_role = $_POST['client_role'];
    $client_first_name = $_POST['client_first_name'];
    $client_last_name = $_POST['client_last_name'];
    $is_returned = $_POST['is_returned'];

    // Convert 'N/A' to null for database
    $is_returned_db = null;
    if ($is_returned === '1') {
        $is_returned_db = 1;
    } elseif ($is_returned === '0') {
        $is_returned_db = 0;
    }

    $sql_parts = [];
    $params = [];
    $types_string = "";

    // Always include these fields first
    $sql_parts[] = "client_username = ?";
    $params[] = $client_username;
    $types_string .= "s";

    // Conditionally add password field
    if (!empty($client_password_new)) {
        $hashed_password = password_hash($client_password_new, PASSWORD_DEFAULT);
        $sql_parts[] = "client_password = ?";
        $params[] = $hashed_password;
        $types_string .= "s";
    }

    // Add remaining fields
    $sql_parts[] = "client_address = ?";
    $params[] = $client_address;
    $types_string .= "s";

    $sql_parts[] = "client_contact_number = ?";
    $params[] = $client_contact_number;
    $types_string .= "s";

    $sql_parts[] = "client_driver_license_number = ?";
    $params[] = $client_driver_license_number;
    $types_string .= "s";

    $sql_parts[] = "client_email_address = ?";
    $params[] = $client_email_address;
    $types_string .= "s";

    $sql_parts[] = "client_role = ?";
    $params[] = $client_role;
    $types_string .= "s";

    $sql_parts[] = "client_first_name = ?";
    $params[] = $client_first_name;
    $types_string .= "s";

    $sql_parts[] = "client_last_name = ?";
    $params[] = $client_last_name;
    $types_string .= "s";

    $sql_parts[] = "is_returned = ?";
    $params[] = $is_returned_db;
    $types_string .= "i";

    // Add WHERE clause parameter
    $sql = "UPDATE Client SET " . implode(", ", $sql_parts) . " WHERE customer_id = ?";
    $params[] = $customer_id;
    $types_string .= "i"; // for customer_id

    if ($stmt = $conn->prepare($sql)) {
        // Create an array of references for bind_param
        // This is necessary because bind_param requires arguments to be passed by reference
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }

        // Dynamically bind parameters using call_user_func_array
        // The first argument is the types string, followed by the actual parameters (passed by reference)
        call_user_func_array(array($stmt, 'bind_param'), array_merge([$types_string], $refs));

        if ($stmt->execute()) {
            $message = "Client updated successfully!";
            $message_type = 'success';
            // Redirect back to admin_clients.php with success message
            header("Location: admin_clients.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();
        } else {
            $message = "Error updating client: " . $stmt->error;
            $message_type = 'error';
            error_log("Error updating client: " . $stmt->error); // Log the error
        }
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = 'error';
        error_log("Error preparing statement: " . $conn->error); // Log the error
    }
}

// --- Fetch Client Data for Editing (on initial page load) ---
// This block ensures $edit_client_data is populated even if there was an update attempt that failed
// (so the form can pre-fill with the data that was *intended* to be updated).
// This must be done AFTER the update logic.
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_customer_id = $_GET['edit'];

    $sql_edit = "SELECT * FROM Client WHERE customer_id = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $edit_customer_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows > 0) {
            $edit_client_data = $result_edit->fetch_assoc();
        } else {
            $message = "Client not found for editing.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    } else {
        $message = "Error preparing statement to fetch client data: " . $conn->error;
        $message_type = 'error';
    }
} else if (!isset($_GET['edit']) && !isset($_POST['update_client'])) {
    // If no 'edit' parameter is present on initial load and no update attempt
    $message = "No client selected for editing. Please select a client from the list.";
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Edit Client
                <?php echo ($edit_client_data) ? '(ID: ' . htmlspecialchars($edit_client_data['customer_id']) . ')' : ''; ?>
            </h2>

            <!-- Edit Client Form -->
            <?php if ($edit_client_data): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                    <form action="admin_clients_edit.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($edit_client_data['customer_id']); ?>">
                        <div>
                            <label for="edit_client_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="client_first_name" id="edit_client_first_name" value="<?php echo htmlspecialchars($edit_client_data['client_first_name']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="edit_client_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="client_last_name" id="edit_client_last_name" value="<?php echo htmlspecialchars($edit_client_data['client_last_name']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="edit_client_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="client_username" id="edit_client_username" value="<?php echo htmlspecialchars($edit_client_data['client_username']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="client_password_new" class="block text-sm font-medium text-gray-700">New Password (leave blank to keep current)</label>
                            <input type="password" name="client_password_new" id="client_password_new" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div class="md:col-span-2">
                            <label for="edit_client_email_address" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="client_email_address" id="edit_client_email_address" value="<?php echo htmlspecialchars($edit_client_data['client_email_address']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="edit_client_contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text" name="client_contact_number" id="edit_client_contact_number" value="<?php echo htmlspecialchars($edit_client_data['client_contact_number']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div class="md:col-span-2">
                            <label for="edit_client_address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="client_address" id="edit_client_address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"><?php echo htmlspecialchars($edit_client_data['client_address']); ?></textarea>
                        </div>
                        <div>
                            <label for="edit_client_driver_license_number" class="block text-sm font-medium text-gray-700">Driver License Number</label>
                            <input type="text" name="client_driver_license_number" id="edit_client_driver_license_number" value="<?php echo htmlspecialchars($edit_client_data['client_driver_license_number']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        </div>
                        <div>
                            <label for="edit_client_role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="client_role" id="edit_client_role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="client" <?php echo ($edit_client_data['client_role'] == 'client') ? 'selected' : ''; ?>>Client</option>
                                <option value="admin" <?php echo ($edit_client_data['client_role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_is_returned" class="block text-sm font-medium text-gray-700">Is Returned</label>
                            <select name="is_returned" id="edit_is_returned" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">N/A</option>
                                <option value="1" <?php echo ($edit_client_data['is_returned'] === 1) ? 'selected' : ''; ?>>Yes</option>
                                <option value="0" <?php echo ($edit_client_data['is_returned'] === 0) ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        <div class="md:col-span-3 flex justify-between items-center mt-4">
                            <a href="admin_clients.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                Back to Client List
                            </a>
                            <button type="submit" name="update_client" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                                Update Client
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class='p-4 mb-4 rounded-md bg-red-100 text-red-700 mt-8'>
                    No client data available for editing. Please go back to the <a href="admin_clients.php" class="font-medium text-red-800 hover:underline">Client List</a> to select a client.
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