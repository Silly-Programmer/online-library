<?php
include 'admin_header.php';
require 'db_connect.php';

// Check if we are editing a book
$edit_mode = false;
$book_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $book_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $book_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch all books for the table
$books_result = $conn->query("SELECT * FROM books ORDER BY title");
?>
<title>Manage Books</title>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="card-title"><?php echo $edit_mode ? 'Edit Book' : 'Add New Book'; ?></h4>
                    
                    <form action="book_process.php" method="POST" enctype="multipart/form-data">
                        
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="book_id" value="<?php echo $book_to_edit['book_id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $edit_mode ? htmlspecialchars($book_to_edit['title']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo $edit_mode ? htmlspecialchars($book_to_edit['author']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo $edit_mode ? htmlspecialchars($book_to_edit['isbn']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <input type="text" class="form-control" id="genre" name="genre" value="<?php echo $edit_mode ? htmlspecialchars($book_to_edit['genre']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_copies" class="form-label">Total Physical Copies</label>
                            <input type="number" class="form-control" id="total_copies" name="total_copies" value="<?php echo $edit_mode ? $book_to_edit['total_copies'] : '1'; ?>" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="book_file" class="form-label">Book PDF (Optional)</label>
                            <input type="file" class="form-control" id="book_file" name="book_file" accept=".pdf">
                            <?php if ($edit_mode && $book_to_edit['file_path']): ?>
                                <small class="text-muted">Current file: <?php echo basename($book_to_edit['file_path']); ?> (Leave blank to keep)</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="update_book" class="btn btn-warning">Update Book</button>
                                <a href="manage_books.php" class="btn btn-secondary">Cancel Edit</a>
                            <?php else: ?>
                                <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h4>Book List</h4>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Copies (Avail/Total)</th>
                            <th>Online</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books_result->num_rows > 0): ?>
                            <?php while($book = $books_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo $book['available_copies'] . ' / ' . $book['total_copies']; ?></td>
                                <td>
                                    <?php if($book['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($book['file_path']); ?>" target="_blank" class="badge bg-success" style="text-decoration: none;">Yes</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manage_books.php?edit_id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="book_process.php?delete_id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this book?');"><i class="bi bi-trash-fill"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No books found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>