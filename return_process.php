<?php
require 'db_connect.php';
require 'auth_check.php';
checkRole('student'); // Only students can return

// Set message for feedback
function setMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

if (isset($_GET['borrow_id'])) {
    $borrow_id = (int)$_GET['borrow_id'];
    $user_id = (int)$_SESSION['id'];
    $return_date = date('Y-m-d');

    // Use a transaction for safety
    $conn->begin_transaction();

    try {
        // 1. Find the borrow record and get the book_id
        // We MUST verify this record belongs to the logged-in user!
        $stmt_find = $conn->prepare("SELECT book_id FROM borrow_records WHERE borrow_id = ? AND user_id = ? AND return_date IS NULL");
        $stmt_find->bind_param("ii", $borrow_id, $user_id);
        $stmt_find->execute();
        $result = $stmt_find->get_result();

        if ($result->num_rows == 0) {
            // This means the record doesn't exist, isn't theirs, or is already returned
            throw new Exception("Invalid return request.");
        }

        $row = $result->fetch_assoc();
        $book_id = $row['book_id'];
        $stmt_find->close();

        // 2. Update the 'borrow_records' table by setting the return_date
        $stmt_update_record = $conn->prepare("UPDATE borrow_records SET return_date = ? WHERE borrow_id = ?");
        $stmt_update_record->bind_param("si", $return_date, $borrow_id);
        $stmt_update_record->execute();
        $stmt_update_record->close();
        
        // 3. Increment 'available_copies' in the 'books' table
        $stmt_update_book = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?");
        $stmt_update_book->bind_param("i", $book_id);
        $stmt_update_book->execute();
        $stmt_update_book->close();

        // 4. Commit
        $conn->commit();
        setMessage("Book returned successfully!", "success");

    } catch (Exception $e) {
        // 5. Rollback
        $conn->rollback();
        setMessage("Error returning book: " . $e->getMessage(), "danger");
    }
    
    $conn->close();

} else {
    setMessage("Invalid request.", "danger");
}

// Redirect back to the "My Books" page
header('location: my_books.php');
exit;
?>