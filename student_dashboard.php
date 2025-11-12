<?php
include 'student_header.php'; // Includes auth check and navbar
require 'db_connect.php'; // For getting stats

// Fetch stats for this student
$user_id = $_SESSION['id'];
$borrowed_count = $conn->query("SELECT COUNT(*) FROM borrow_records WHERE user_id = $user_id AND return_date IS NULL")->fetch_row()[0];
?>
<title>Student Dashboard</title>

<div class="container mt-5">
    <h1 class="mb-4">Student Dashboard</h1>
    
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Browse Books</h5>
                        <p class="card-text">Search the catalog and find books.</p>
                        <a href="browse_books.php" class="btn btn-primary">Browse Catalog</a>
                    </div>
                    <i class="bi bi-search card-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">My Borrowed Books</h5>
                        <p class="card-text">You have <strong><?php echo $borrowed_count; ?></strong> books out.</p>
                        <a href="my_books.php" class="btn btn-primary">View My Books</a>
                    </div>
                    <i class="bi bi-bookmarks-fill card-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">My Profile</h5>
                        <p class="card-text">Update your account details.</p>
                        <a href="#" class="btn btn-secondary disabled">My Account</a>
                    </div>
                    <i class="bi bi-person-circle card-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>