<?php
include 'admin_header.php'; // Includes auth check and navbar
require 'db_connect.php'; // For getting stats

// Fetch some stats for the dashboard
$total_books = $conn->query("SELECT COUNT(*) FROM books")->fetch_row()[0];
$total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0];
$total_borrowed = $conn->query("SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL")->fetch_row()[0];
?>
<title>Admin Dashboard</title>

<div class="container mt-5">
    <h1 class="mb-4">Admin Dashboard</h1>
    
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Manage Books</h5>
                        <p class="card-text">Total Books: <strong><?php echo $total_books; ?></strong></p>
                        <a href="manage_books.php" class="btn btn-primary">Go to Books</a>
                    </div>
                    <i class="bi bi-journal-album card-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Manage Students</h5>
                        <p class="card-text">Total Students: <strong><?php echo $total_students; ?></strong></p>
                        <a href="manage_students.php" class="btn btn-primary">Go to Students</a>
                    </div>
                    <i class="bi bi-people-fill card-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Borrow Records</h5>
                        <p class="card-text">Currently Borrowed: <strong><?php echo $total_borrowed; ?></strong></p>
                        <a href="view_records.php" class="btn btn-primary">View Records</a>
                    </div>
                    <i class="bi bi-card-checklist card-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>