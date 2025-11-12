<?php
include 'student_header.php';
require 'db_connect.php';

// Handle search
$search_query = "";
// We ONLY show books with available physical copies
$sql = "SELECT * FROM books"; 
$where_clauses = ["available_copies > 0"];
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    $where_clauses[] = "(title LIKE ? OR author LIKE ? OR genre LIKE ?)";
    $search_term = "%" . $search_query . "%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= "sss";
}

$sql .= " WHERE " . implode(" AND ", $where_clauses);
$sql .= " ORDER BY title";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<title>Browse Books</title>

<div class="container mt-5">
    <h1 class="mb-4">Browse Available Books</h1>

    <div class="row mb-4">
        <div class="col-md-8 offset-md-2">
            <form action="browse_books.php" method="GET">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" placeholder="Search by title, author, or genre..." name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while($book = $result->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($book['author']); ?></h6>
                        <p class="card-text">
                            <strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?><br>
                            <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?><br>
                            
                            <span class="badge bg-primary">
                                <?php echo $book['available_copies']; ?> Physical Copies Available
                            </span>
                        </p>
                        
                        <div class="mt-auto d-grid">
                            <a href="borrow_process.php?book_id=<?php echo $book['book_id']; ?>" class="btn btn-primary" onclick="return confirm('Are you sure you want to borrow this book?');">
                                <i class="bi bi-bookmark-plus"></i> Borrow Physical
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No physical books are available for borrowing.</div>
            </div>
        <?php endif; ?>
        <?php $stmt->close(); $conn->close(); ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>