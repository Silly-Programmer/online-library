<?php
// We must start the session on every page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in. If not, redirect to login page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Optional: Role-specific check
// You can pass a parameter to this script to check for a specific role
function checkRole($role) {
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== $role) {
        // If not the correct role, redirect to a "forbidden" page or back to login
        echo "Access Denied. You do not have permission to view this page.";
        // Or redirect to a general dashboard
        // header("location: index.php"); 
        exit;
    }
}
?>