<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page (or choose_login.php if that's the main entry point)
header("Location: ../choose_login.php");
exit();
?>