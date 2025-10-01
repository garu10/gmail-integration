<?php
$page_title = "Dashboard Overview";

// Include the base admin template.
// This will handle session start, authentication, and render the common header/sidebar.
require_once 'admin_base.php';

// --- Dashboard Content (Specific to admin_dashboard.php) ---

// Example: Fetch some summary data from the database
$total_cars = 0;
$total_bookings = 0;
$total_clients = 0;

// Fetch total number of cars
$stmt_cars = $conn->prepare("SELECT COUNT(*) AS total_cars FROM Cars");
if ($stmt_cars) {
    $stmt_cars->execute();
    $result_cars = $stmt_cars->get_result();
    $data_cars = $result_cars->fetch_assoc();
    $total_cars = $data_cars['total_cars'];
    $stmt_cars->close();
} else {
    echo "<p class='text-red-500'>Error fetching car count: " . $conn->error . "</p>";
}

// Fetch total number of bookings
$stmt_bookings = $conn->prepare("SELECT COUNT(*) AS total_bookings FROM Bookings");
if ($stmt_bookings) {
    $stmt_bookings->execute();
    $result_bookings = $stmt_bookings->get_result();
    $data_bookings = $result_bookings->fetch_assoc();
    $total_bookings = $data_bookings['total_bookings'];
    $stmt_bookings->close();
} else {
    echo "<p class='text-red-500'>Error fetching booking count: " . $conn->error . "</p>";
}

// Fetch total number of clients
$stmt_clients = $conn->prepare("SELECT COUNT(*) AS total_clients FROM Client");
if ($stmt_clients) {
    $stmt_clients->execute();
    $result_clients = $stmt_clients->get_result();
    $data_clients = $result_clients->fetch_assoc();
    $total_clients = $data_clients['total_clients'];
    $stmt_clients->close();
} else {
    echo "<p class='text-red-500'>Error fetching client count: " . $conn->error . "</p>";
}

$email_card_color = "bg-red-500";
$total_unread_emails = 0;

?>

<!-- Dashboard specific content goes here -->
<h2 class="text-3xl font-bold mb-6 text-gray-800">Dashboard Overview</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">

    <div
        class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-blue-500 flex items-center justify-between transition duration-300 ease-in-out hover:shadow-xl transform hover:scale-[1.01]">
        <div>
            <div class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Cars</div>
            <div class="text-5xl font-extrabold text-gray-900 mt-1"><?php echo $total_cars; ?></div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-14 h-14 text-blue-500 opacity-60">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375M7.5 4.5V2.25c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V4.5m-1.5 8.25-.75.75m-.75-3v3m3-6.75V9V2.25C12.75 1.629 12.246 1.125 11.625 1.125H7.875c-.621 0-1.125.504-1.125 1.125v4.5m4.5-4.5v4.5m4.5-12h-3c-.621 0-1.125.504-1.125 1.125V4.5m7.5-3v2.25c0 .621-.504 1.125-1.125 1.125h-3.75a1.125 1.125 0 0 1-1.125-1.125V1.5c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125ZM10.5 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375M7.5 4.5V2.25c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V4.5m-1.5 8.25-.75.75m-.75-3v3m3-6.75V9V2.25C12.75 1.629 12.246 1.125 11.625 1.125H7.875c-.621 0-1.125.504-1.125 1.125v4.5m4.5-4.5v4.5m4.5-12h-3c-.621 0-1.125.504-1.125 1.125V4.5m7.5-3v2.25c0 .621-.504 1.125-1.125 1.125h-3.75a1.125 1.125 0 0 1-1.125-1.125V1.5c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125Z" />
        </svg>
    </div>

    <div
        class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-green-500 flex items-center justify-between transition duration-300 ease-in-out hover:shadow-xl transform hover:scale-[1.01]">
        <div>
            <div class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Bookings</div>
            <div class="text-5xl font-extrabold text-gray-900 mt-1"><?php echo $total_bookings; ?></div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-14 h-14 text-green-500 opacity-60">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h3.75M9 15h3.75M9 18h3.75M12 6.109V2.25m0 3.859-1.5 1.5M12 6.109l1.5 1.5M12 6.109V2.25M12 3a.75.75 0 0 0-.75.75V6a.75.75 0 0 0 .75.75M14.25 7.5a.75.75 0 0 0-.75-.75H12a.75.75 0 0 0-.75.75M16.5 7.5a.75.75 0 0 0-.75-.75H12a.75.75 0 0 0-.75.75" />
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M11.25 11.25H9M11.25 15.75h-3M11.25 20.25h-3M21.75 12c0 2.492-1.543 4.654-3.75 5.5v-1.091c0-.472-.344-.863-.812-.916a48.814 48.814 0 0 1-3.238-.346 48.814 48.814 0 0 0-3.238-.346c-.468-.053-.812-.444-.812-.916V12c0-2.492 1.543-4.654 3.75-5.5C17.16 6.58 19 6.25 21 6.25" />
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 2.25c-5.567 0-10.125 4.303-10.125 9.613 0 2.881 1.09 5.547 2.923 7.59l.862.928.842-.842a6.837 6.837 0 0 0 1.764 1.157c.71-.165 1.401-.365 2.062-.601M7.5 4.5V2.25C7.5 1.629 7.946 1.125 8.575 1.125h3.85c.621 0 1.125.504 1.125 1.125V4.5m-6 9.75V15h3m-3-2.25H3.75m.75-3.75h-.375c-.621 0-1.125.504-1.125 1.125V15c0 .621.504 1.125 1.125 1.125h.375M7.5 12h-.375c-.621 0-1.125.504-1.125 1.125V15c0 .621.504 1.125 1.125 1.125h.375M15 15h3.75M15 18h3.75" />
        </svg>
    </div>

    <div
        class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-purple-500 flex items-center justify-between transition duration-300 ease-in-out hover:shadow-xl transform hover:scale-[1.01]">
        <div>
            <div class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Clients</div>
            <div class="text-5xl font-extrabold text-gray-900 mt-1"><?php echo $total_clients; ?></div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-14 h-14 text-purple-500 opacity-60">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
    </div>

    <a href="viewer_IMAP.php" class="block">
        <div
            class="<?php echo $email_card_color; ?> text-white p-6 rounded-xl shadow-lg flex items-center justify-between transition duration-300 ease-in-out hover:shadow-xl transform hover:scale-[1.01]">

            <div>
                <div class="text-sm font-semibold opacity-90 uppercase tracking-wider">Unread Emails</div>

                <div class="text-5xl font-extrabold mt-1"><?php echo $total_unread_emails; ?></div>

                <div class="text-xs mt-1 font-medium opacity-80">Click to view messages</div>
            </div>

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-14 h-14 opacity-70">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5h-15a1.5 1.5 0 0 1-1.5-1.5V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0c1.01 0 1.905.378 2.597 1.054a3.75 3.75 0 0 1 1.053 2.596m-19.5 0a3.75 3.75 0 0 0-1.054 2.596C2.25 14.905 2.628 15.799 3.304 16.49M2.25 6.75h19.5M8.25 15.75h-2.25V9.75h2.25v6Z" />
            </svg>
        </div>
    </a>
</div>

<h2 class="text-2xl font-semibold mb-4 text-gray-700">Recent Activities & Logs</h2>
<div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-gray-600">
    <p class="italic">This section will show recent bookings, critical system logs, or car updates.</p>
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