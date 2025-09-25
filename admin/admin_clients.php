<?php
// Set the page title for the base template.
$page_title = "Manage Clients";

// Include the base admin template.
// This will handle session start, authentication, and render the common header/navigation.
require_once 'admin_base.php';

// Initialize feedback messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages passed via GET (e.g., from add/edit pages)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Handle Delete Client ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $customer_id_to_delete = $_GET['delete'];

    // Start a transaction for atomicity (optional but good practice for related data)
    $conn->begin_transaction();

    try {

        $sql_client_del = "DELETE FROM Client WHERE customer_id = ?";
        if ($stmt_client_del = $conn->prepare($sql_client_del)) {
            $stmt_client_del->bind_param("i", $customer_id_to_delete);
            if ($stmt_client_del->execute()) {
                if ($stmt_client_del->affected_rows > 0) {
                    $message = "Client deleted successfully!";
                    $message_type = 'success';
                    $conn->commit(); // Commit transaction on success
                } else {
                    throw new Exception("No client found with ID: " . htmlspecialchars($customer_id_to_delete));
                }
            } else {
                throw new Exception("Error deleting client: " . $stmt_client_del->error);
            }
            $stmt_client_del->close();
        } else {
            throw new Exception("Error preparing client deletion: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $message = "Error deleting client: " . $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to self to clear GET parameters and display message
    header("Location: admin_clients.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// --- Fetch All Clients for Display ---
$clients = [];
$sql = "SELECT * FROM Client ORDER BY customer_id DESC"; // Order by latest added clients first

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }
} else {
    $message = "Error fetching clients: " . $conn->error;
    $message_type = 'error';
}

?>

            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Client Management</h2>

            <!-- Navigation Buttons -->
            <div class="mb-6 flex justify-end">
                <a href="admin_clients_add.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Client
                </a>
            </div>

            <!-- Clients List Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Clients</h3>
                <?php if (empty($clients)): ?>
                    <p class="text-gray-600">No clients found in the database. Add a new client using the button above!</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact No.</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver License</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Is Returned</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($client['customer_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($client['client_username']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($client['client_first_name'] . ' ' . $client['client_last_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($client['client_email_address']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($client['client_contact_number']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($client['client_address']); ?>">
                                            <?php echo htmlspecialchars($client['client_address'] ?: 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($client['client_driver_license_number'] ?: 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($client['client_role'] === 'admin') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($client['client_role'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                                if ($client['is_returned'] === 1) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>';
                                                } elseif ($client['is_returned'] === 0) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>';
                                                } else {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">N/A</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="admin_clients_edit.php?edit=<?php echo htmlspecialchars($client['customer_id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <a href="admin_clients.php?delete=<?php echo htmlspecialchars($client['customer_id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone and may affect associated bookings.');">Delete</a>
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
