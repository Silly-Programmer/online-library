<?php
require 'db_connect.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id, username, password_hash, role FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                // Check if username exists
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    
                    if ($stmt->fetch()) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            
                            // Redirect user based on role
                            if ($role == 'admin') {
                                header("location: admin_dashboard.php");
                            } else {
                                header("location: student_dashboard.php");
                            }
                            exit;
                        } else {
                            // Password is not valid
                            $error_message = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
    
    // If there was an error, redirect back to login page with an error message
    if (!empty($error_message)) {
        session_start();
        $_SESSION['login_error'] = $error_message;
        header("location: index.php");
        exit;
    }
}
?>