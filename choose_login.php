<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/choose_login.css"> <title>Choose Role</title>
</head>
<body class="choose-login">
<?php
// Include functions.php to start the session and access login status
include("includes/db.php");
include("includes/functions.php");

?>
<div class="login-wrapper">

    <div class="login-text">
        <h1>SintaDrive</h1>
        <h3>
            Welcome back to SintaDrive — your trusted car rental solution. Access your account to manage bookings,
            track rentals, and get back on the road in minutes. With our wide range of vehicles, flexible options, and
            24/7 support, SintaDrive makes every trip smooth, simple, and stress-free.
        </h3>
    </div>

    <div class="login-choice-container">
        <h2>Select Login Role</h2>
        <a href="admin/login.php" class="role-btn">ADMIN</a>
        <a href="customer/login.php" class="role-btn">CLIENT</a>
    </div>
</div>
</body>
</html>