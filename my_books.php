<?php
include 'student_header.php';
require 'db_connect.php';

$user_id = $_SESSION['id'];

// Fetch user's borrowed books, AND get the file_path from the books table
$sql = "SELECT 
            b.title, 
            b.author,
            b.file_path,  -- <<< THE NEWLY ADDED COLUMN
            br.borrow_date, 
            br.due_date,
            br.borrow_id,
            DATEDIFF(br.due_date, CURDATE()) AS days_remaining
        FROM borrow_records br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = ? AND br.return_date IS NULL
        ORDER BY br.due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<title>My Borrowed Books</title>

<div class="container mt-5">
    <h1 class="mb-4">My Borrowed Books</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th> </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                $status_badge = 'bg-success';
                                $status_text = $row['days_remaining'] . ' days remaining';
                                if ($row['days_remaining'] < 0) {
                                    $status_badge = 'bg-danger';
                                    $status_text = abs($row['days_remaining']) . ' days overdue';
                                } elseif ($row['days_remaining'] <= 3) {
                                    $status_badge = 'bg-warning text-dark';
                                    $status_text = $row['days_remaining'] . ' days remaining';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['borrow_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['due_date'])); ?></td>
                                    <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                    
                                    <td>
                                        <div class="d-flex gap-2">
                                            <?php if (!empty($row['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="bi bi-book"></i> Read Online
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="return_process.php?borrow_id=<?php echo $row['borrow_id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               onclick="return confirm('Are you sure you want to return this book?');">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </a>
                                        </div>
                                    </td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">You have no books currently borrowed. <a href="browse_books.php">Browse the catalog!</a></td>
                            </tr>
                        <?php endif; ?>
                        <?php $stmt->close(); $conn->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>