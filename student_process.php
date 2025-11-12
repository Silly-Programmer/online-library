<?php
require 'db_connect.php';
require 'auth_check.php';
checkRole('admin'); // Only admins can access this script

// Set message for feedback
function setMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type; // e.g., 'success', 'danger'
}

// --- DELETE STUDENT ---
if (isset($_GET['delete_id'])) {
    $user_id = (int)$_GET['delete_id'];

    // SAFETY CHECK: Check if the student has any *un-returned* books
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id = ? AND return_date IS NULL");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $borrowed_count = $stmt_check->get_result()->fetch_row()[0];
    $stmt_check->close();

    if ($borrowed_count > 0) {
        // If they have books, block deletion
        setMessage("Cannot delete student. They have $borrowed_count book(s) currently borrowed. They must return them first.", "danger");
    } else {
        // Safe to delete. Use a transaction to delete all records.
        $conn->begin_transaction();
        try {
            // 1. Delete all their (old) borrow records
            $stmt_del_records = $conn->prepare("DELETE FROM borrow_records WHERE user_id = ?");
            $stmt_del_records->bind_param("i", $user_id);
            $stmt_del_records->execute();
            $stmt_del_records->close();
            
            // 2. Delete the user from the users table
            $stmt_del_user = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt_del_user->bind_param("i", $user_id);
            $stmt_del_user->execute();
            $stmt_del_user->close();

            // 3. Commit the changes
            $conn->commit();
            setMessage("Student account and all associated records have been permanently deleted.", "success");

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            setMessage("Error deleting student: " . $exception->getMessage(), "danger");
        }
    }
}

// Redirect back to the manage_students page
header('location: manage_students.php');
exit;
?>