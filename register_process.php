<?php
require 'db_connect.php'; // Includes session_start()

$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and retrieve inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 2. Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // 3. Check database for existing user (if no validation errors so far)
    if (empty($errors)) {
        // Check for existing username
        $sql_check_user = "SELECT id FROM users WHERE username = ?";
        if ($stmt_check_user = $conn->prepare($sql_check_user)) {
            $stmt_check_user->bind_param("s", $username);
            $stmt_check_user->execute();
            $stmt_check_user->store_result();
            if ($stmt_check_user->num_rows > 0) {
                $errors[] = "This username is already taken.";
            }
            $stmt_check_user->close();
        }

        // Check for existing email
        $sql_check_email = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check_email = $conn->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $errors[] = "This email is already in use.";
            }
            $stmt_check_email->close();
        }
    }

    // 4. Register the user (if still no errors)
    if (empty($errors)) {
        // Hash the password for security
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student'; // Hardcode role as 'student'

        $sql_insert = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
        
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssss", $username, $email, $password_hash, $role);
            
            if ($stmt_insert->execute()) {
                // SUCCESS: Automatically log the new user in
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $conn->insert_id; // Get the ID of the new user
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $role;
                
                // Redirect to the student dashboard
                header("location: student_dashboard.php");
                exit;
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later.";
            }
            $stmt_insert->close();
        }
    }

    // 5. Handle errors
    if (!empty($errors)) {
        // Store the first error in the session to display on the register page
        $_SESSION['register_error'] = $errors[0];
        header("location: register.php");
        exit;
    }

    $conn->close();
}
?>