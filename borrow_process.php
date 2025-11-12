<?php
require 'db_connect.php';
require 'auth_check.php';
checkRole('student'); // Only students can borrow

// Set message for feedback
function setMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

if (isset($_GET['book_id'])) {
    $book_id = (int)$_GET['book_id'];
    $user_id = (int)$_SESSION['id'];
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+14 days')); // 14-day loan period

    // Use a transaction for safety
    $conn->begin_transaction();

    try {
        // 1. Check if book is available
        $stmt_check = $conn->prepare("SELECT available_copies FROM books WHERE book_id = ? AND available_copies > 0");
        $stmt_check->bind_param("i", $book_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("This book is no longer available.");
        }

        // 2. Check if user already borrowed this book
        $stmt_user_check = $conn->prepare("SELECT * FROM borrow_records WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
        $stmt_user_check->bind_param("ii", $user_id, $book_id);
        $stmt_user_check->execute();
        if ($stmt_user_check->get_result()->num_rows > 0) {
            throw new Exception("You have already borrowed this book and not returned it.");
        }
        
        // 3. Decrement available_copies in 'books' table
        $stmt_update = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
        $stmt_update->bind_param("i", $book_id);
        $stmt_update->execute();
        
        // 4. Insert new record into 'borrow_records'
        $stmt_insert = $conn->prepare("INSERT INTO borrow_records (book_id, user_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("iiss", $book_id, $user_id, $borrow_date, $due_date);
        $stmt_insert->execute();

        // 5. Commit the transaction
        $conn->commit();
        setMessage("Book borrowed successfully! Due on $due_date.", "success");

    } catch (Exception $e) {
        // 6. Rollback on error
        $conn->rollback();
        setMessage("Error borrowing book: " . $e->getMessage(), "danger");
    }

    $stmt_check->close();
    if(isset($stmt_user_check)) $stmt_user_check->close();
    if(isset($stmt_update)) $stmt_update->close();
    if(isset($stmt_insert)) $stmt_insert->close();
    $conn->close();

} else {
    setMessage("Invalid request.", "danger");
}

// Redirect back to the browse page
header('location: browse_books.php');
exit;
?>