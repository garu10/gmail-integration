<?php
// includes/functions.php
// This file should handle session start and global helper functions.

if (session_status() == PHP_SESSION_NONE) { // Check if session is not already active
    session_start();
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client'; // Corrected to 'client'
}
?>