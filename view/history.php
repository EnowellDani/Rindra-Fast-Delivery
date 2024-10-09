<?php
session_start();
require_once '../database.php'; // Ensure this path is correct
require '../auth.php'; // Ensure this file is correctly required

// Check if the user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Get the user role from the session
$user_role = $_SESSION['role']; // Define the variable here

// Admin role check
if ($user_role !== 'admin') {
    header('Location: not_authorized.php');
    exit();
}

// Initialize variables for search and filter
$clientSearch = '';
$driverSearch = '';
$search = ''; // Initialize the search variable to avoid undefined variable warning
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Calculate the offset
$totalDeliveries = 0; // Initialize to avoid undefined variable warning

// Prepare the main SQL query to fetch history based on search and filters
$sql = "SELECT h.*, c.name AS client_name, d.name AS driver_name 
        FROM order_history h 
        JOIN clients c ON h.client_id = c.id 
        LEFT JOIN drivers d ON h.driver_id = d.id 
        WHERE 1=1"; // Base condition

// Initialize parameters array
$params = [];

// Handle search input for client name
if (isset($_GET['client_search']) && !empty($_GET['client_search'])) {
    $clientSearch = $_GET['client_search'];
    $sql .= " AND c.name LIKE :clientSearch";
    $params[':clientSearch'] = "%$clientSearch%"; // Bind value directly
}

// Handle search input for driver name
if (isset($_GET['driver_search']) && !empty($_GET['driver_search'])) {
    $driverSearch = $_GET['driver_search'];
    $sql .= " AND d.name LIKE :driverSearch";
    $params[':driverSearch'] = "%$driverSearch%"; // Bind value directly
}

// Count total deliveries for pagination
try {
    // Prepare the count statement based on the search conditions
    $countSql = "SELECT COUNT(*) FROM order_history h 
                 JOIN clients c ON h.client_id = c.id 
                 LEFT JOIN drivers d ON h.driver_id = d.id 
                 WHERE 1=1"; // Same condition structure

    // Add the same filters to the count SQL
    if (isset($_GET['client_search']) && !empty($_GET['client_search'])) {
        $countSql .= " AND c.name LIKE :clientSearch";
    }
    if (isset($_GET['driver_search']) && !empty($_GET['driver_search'])) {
        $countSql .= " AND d.name LIKE :driverSearch";
    }

    // Prepare the count statement
    $countStmt = $pdo->prepare($countSql);
    
    // Bind parameters for count
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    
    $countStmt->execute();
    $totalDeliveries = $countStmt->fetchColumn(); // Total count

    // Add limit and offset for pagination in the main query
    $sql .= " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Bind parameters for the main query
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching history: " . htmlspecialchars($e->getMessage());
}

// Pagination logic
$totalPages = ceil($totalDeliveries / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History - Rindra Delivery Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container mt-5">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="display-4">Delivery History</h2>
        </div>

        <!-- Back to Dashboard Button -->
        <div class="text-center mb-3">
            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn btn-outline-secondary btn-lg">Back to Dashboard</a>
        </div>

        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="client_search" class="form-control me-2" placeholder="Search by Client Name" value="<?= htmlspecialchars($clientSearch ?? '') ?>">
                    <input type="text" name="driver_search" class="form-control me-2" placeholder="Search by Driver Name" value="<?= htmlspecialchars($driverSearch ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <!-- Deliveries Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Client Name</th>
                        <th>Driver Name</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No deliveries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td><?= htmlspecialchars($delivery['id']) ?></td>
                                <td><?= htmlspecialchars($delivery['client_name']) ?></td>
                                <td><?= htmlspecialchars($delivery['driver_name']) ?></td>
                                <td><?= htmlspecialchars($delivery['address']) ?></td>
                                <td><?= htmlspecialchars($delivery['status']) ?></td>
                                <td><?= htmlspecialchars($delivery['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>