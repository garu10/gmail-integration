<?php
// Set the page title for the base template.
$page_title = "Add New Client";

// Include the base admin template.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// --- Handle Add New Client Submission ---
if (isset($_POST['add_client'])) {
    // Collect form data
    $client_username = $_POST['client_username'];
    $client_password = $_POST['client_password']; 
    $client_address = $_POST['client_address'];
    $client_contact_number = $_POST['client_contact_number'];
    $client_driver_license_number = $_POST['client_driver_license_number'];
    $client_email_address = $_POST['client_email_address'];
    $client_role = $_POST['client_role'];
    $client_first_name = $_POST['client_first_name'];
    $client_last_name = $_POST['client_last_name'];
    $is_returned = $_POST['is_returned']; 

    // Convert 'N/A' to null for database
    if ($is_returned === '') {
        $is_returned_db = null;
    } else {
        $is_returned_db = (int)$is_returned; // Convert to 0 or 1
    }

    // Hash the password for security before storing
    $hashed_password = password_hash($client_password, PASSWORD_DEFAULT);

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO Client (client_username, client_password, client_address, client_contact_number, client_driver_license_number, client_email_address, client_role, client_first_name, client_last_name, is_returned)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("sssssssssi", 
            $client_username,
            $hashed_password,
            $client_address,
            $client_contact_number,
            $client_driver_license_number,
            $client_email_address,
            $client_role,
            $client_first_name,
            $client_last_name,
            $is_returned_db 
        );

        if ($stmt->execute()) {
            $message = "Client added successfully!";
            $message_type = 'success';

            header("Location: admin_clients.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit();

        } else {
            $message = "Error adding client: " . $stmt->error;
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

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Client</h2>

            <!-- Add New Client Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <form action="admin_clients_add.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="client_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="client_first_name" id="client_first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="client_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="client_last_name" id="client_last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="client_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="client_username" id="client_username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="client_password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="client_password" id="client_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="md:col-span-2">
                        <label for="client_email_address" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="client_email_address" id="client_email_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="client_contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" name="client_contact_number" id="client_contact_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="md:col-span-2">
                        <label for="client_address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="client_address" id="client_address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                    </div>
                    <div>
                        <label for="client_driver_license_number" class="block text-sm font-medium text-gray-700">Driver License Number</label>
                        <input type="text" name="client_driver_license_number" id="client_driver_license_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="client_role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="client_role" id="client_role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="client">Client</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_returned" class="block text-sm font-medium text-gray-700">Is Returned</label>
                        <select name="is_returned" id="is_returned" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="">N/A</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex justify-between items-center mt-4">
                        <a href="admin_clients.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Back to Client List
                        </a>
                        <button type="submit" name="add_client" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Add Client
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
