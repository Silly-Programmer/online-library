<?php
include 'admin_header.php'; // Includes auth check and navbar
require 'db_connect.php'; // For database connection

// Get the current filter from URL, default to 'borrowed'
$filter = $_GET['filter'] ?? 'borrowed'; // 'all', 'borrowed', 'overdue'

// Base SQL query
$sql = "SELECT 
            br.borrow_id,
            b.title AS book_title,
            u.username AS student_username,
            br.borrow_date,
            br.due_date,
            br.return_date,
            DATEDIFF(CURDATE(), br.due_date) AS days_overdue
        FROM 
            borrow_records br
        JOIN 
            books b ON br.book_id = b.book_id
        JOIN 
            users u ON br.user_id = u.id";

// Apply filters
$page_title = "All Borrow Records";
switch ($filter) {
    case 'borrowed':
        $sql .= " WHERE br.return_date IS NULL";
        $page_title = "Currently Borrowed Books";
        break;
    case 'overdue':
        $sql .= " WHERE br.return_date IS NULL AND CURDATE() > br.due_date";
        $page_title = "Overdue Books";
        break;
    case 'all':
    default:
        // No WHERE clause, show all
        $page_title = "All Borrow Records";
        break;
}

$sql .= " ORDER BY br.borrow_date DESC";

$result = $conn->query($sql);
?>
<title><?php echo $page_title; ?></title>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $page_title; ?></h1>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'borrowed' ? 'active' : ''; ?>" href="view_records.php?filter=borrowed">Currently Borrowed</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'overdue' ? 'active' : ''; ?>" href="view_records.php?filter=overdue">Overdue</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="view_records.php?filter=all">View All</a>
        </li>
    </ul>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Book Title</th>
                            <th>Student</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Returned On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($record = $result->fetch_assoc()): ?>
                                <?php
                                $status_badge = '';
                                $status_text = '';
                                if ($record['return_date']) {
                                    $status_badge = 'bg-success';
                                    $status_text = 'Returned';
                                } elseif ($record['days_overdue'] > 0) {
                                    $status_badge = 'bg-danger';
                                    $status_text = $record['days_overdue'] . ' days overdue';
                                } else {
                                    $status_badge = 'bg-warning text-dark';
                                    $status_text = 'Borrowed';
                                }
                                ?>
                                <tr class="<?php echo ($record['days_overdue'] > 0 && !$record['return_date']) ? 'table-danger' : ''; ?>">
                                    <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                                    <td><?php echo htmlspecialchars($record['student_username']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($record['borrow_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($record['due_date'])); ?></td>
                                    <td>
                                        <?php echo $record['return_date'] ? date('M j, Y', strtotime($record['return_date'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No records found for this filter.</td>
                            </tr>
                        <?php endif; ?>
                        <?php $conn->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>