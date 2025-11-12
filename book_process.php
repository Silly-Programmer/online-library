<?php
require 'db_connect.php';
require 'auth_check.php';
checkRole('admin'); // Only admins can process books

$UPLOAD_DIR = 'uploads/';

// Set message for feedback
function setMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Function to handle file upload
// Returns the file path on success, or sets an error and returns false
function handleFileUpload($fileInputName) {
    global $UPLOAD_DIR;
    
    // Check if file was uploaded
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] != UPLOAD_ERR_OK) {
        if ($_FILES[$fileInputName]['error'] == UPLOAD_ERR_NO_FILE) {
            return 'NO_FILE'; // Special case for 'no file uploaded'
        }
        setMessage("File upload error: " . $_FILES[$fileInputName]['error'], "danger");
        return false;
    }

    $file = $_FILES[$fileInputName];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];

    // Check file size (e.g., 20MB max)
    if ($file_size > 20 * 1024 * 1024) { 
        setMessage("Error: File size must be under 20MB.", "danger");
        return false;
    }

    // Check file type (MIME type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($mime_type != 'application/pdf') {
        setMessage("Error: Only PDF files are allowed.", "danger");
        return false;
    }

    // Create unique filename to prevent overwrites
    $target_file = $UPLOAD_DIR . uniqid() . '_' . basename($file_name);

    if (move_uploaded_file($file_tmp, $target_file)) {
        return $target_file; // Success, return the path
    } else {
        setMessage("Error: Failed to move uploaded file.", "danger");
        return false;
    }
}

// --- ADD BOOK ---
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $genre = $_POST['genre'];
    $total_copies = $_POST['total_copies'];
    $available_copies = $total_copies; // New book starts with all copies available
    $file_path = null;

    $uploadResult = handleFileUpload('book_file');
    if ($uploadResult === false) { // Hard error
        header('location: manage_books.php');
        exit;
    } elseif ($uploadResult != 'NO_FILE') { // New file was uploaded
        $file_path = $uploadResult;
    }

    // We no longer use 'book_type'. We insert file_path (which is null if no file)
    $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, genre, file_path, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $title, $author, $isbn, $genre, $file_path, $total_copies, $available_copies);
    
    if ($stmt->execute()) {
        setMessage("Book added successfully!", "success");
    } else {
        setMessage("Error: " . $stmt->error, "danger");
        // If DB insert fails, delete the file we just uploaded
        if ($file_path && file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $stmt->close();
}

// --- UPDATE BOOK ---
if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $genre = $_POST['genre'];
    
    // Get current book data
    $stmt_get = $conn->prepare("SELECT file_path, total_copies, available_copies FROM books WHERE book_id = ?");
    $stmt_get->bind_param("i", $book_id);
    $stmt_get->execute();
    $current_book = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    // Handle physical copies update
    $new_total_copies = $_POST['total_copies'];
    $borrowed_count = $current_book['total_copies'] - $current_book['available_copies'];
    $new_available_copies = $new_total_copies - $borrowed_count;
    
    if ($new_available_copies < 0) {
        setMessage("Error: Cannot set total copies below the number currently borrowed ($borrowed_count).", "danger");
        header('location: manage_books.php');
        exit;
    }

    // Handle file path update
    $new_file_path = $current_book['file_path']; // Assume no change
    $uploadResult = handleFileUpload('book_file');
    
    if ($uploadResult === false) { // Hard error
        header('location: manage_books.php');
        exit;
    } elseif ($uploadResult != 'NO_FILE') { // New file was successfully uploaded
        // Delete old file if it exists
        if ($current_book['file_path'] && file_exists($current_book['file_path'])) {
            unlink($current_book['file_path']);
        }
        $new_file_path = $uploadResult; // Set the new path
    }
    // If $uploadResult == 'NO_FILE', we just keep $new_file_path as the old path

    $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, genre = ?, file_path = ?, total_copies = ?, available_copies = ? WHERE book_id = ?");
    $stmt->bind_param("sssssiii", $title, $author, $isbn, $genre, $new_file_path, $new_total_copies, $new_available_copies, $book_id);

    if ($stmt->execute()) {
        setMessage("Book updated successfully!", "success");
    } else {
        setMessage("Error: " . $stmt->error, "danger");
    }
    $stmt->close();
}

// --- DELETE BOOK ---
if (isset($_GET['delete_id'])) {
    $book_id = $_GET['delete_id'];

    // Get book data before deleting
    $stmt_get = $conn->prepare("SELECT file_path FROM books WHERE book_id = ?");
    $stmt_get->bind_param("i", $book_id);
    $stmt_get->execute();
    $book = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    // Check if book is currently borrowed
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id = ? AND return_date IS NULL");
    $stmt_check->bind_param("i", $book_id);
    $stmt_check->execute();
    $borrowed_count = $stmt_check->get_result()->fetch_row()[0];
    $stmt_check->close();

    if ($borrowed_count > 0) {
        setMessage("Error: Cannot delete book. It is currently borrowed by $borrowed_count user(s).", "danger");
        header('location: manage_books.php');
        exit;
    }

    // Safe to delete
    $conn->begin_transaction();
    try {
        // 1. Delete borrow records
        $stmt_del_records = $conn->prepare("DELETE FROM borrow_records WHERE book_id = ?");
        $stmt_del_records->bind_param("i", $book_id);
        $stmt_del_records->execute();
        $stmt_del_records->close();
        
        // 2. Delete the book
        $stmt_del_book = $conn->prepare("DELETE FROM books WHERE book_id = ?");
        $stmt_del_book->bind_param("i", $book_id);
        $stmt_del_book->execute();
        $stmt_del_book->close();

        // 3. Delete its PDF file, if it had one
        if ($book['file_path'] && file_exists($book['file_path'])) {
            unlink($book['file_path']);
        }

        // 4. Commit
        $conn->commit();
        setMessage("Book and associated records/files deleted successfully!", "success");

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        setMessage("Error deleting book: " . $exception->getMessage(), "danger");
    }
}

// Redirect back to the manage_books page
header('location: manage_books.php');
exit;
?>