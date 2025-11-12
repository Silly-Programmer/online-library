<?php
// Example: create a password hash (without POST)
$password = "mastimajak"; // default value

// Hash the password
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";

// Example: verify password
$entered = "MySecurePassword123!"; // pretend this came from user input

