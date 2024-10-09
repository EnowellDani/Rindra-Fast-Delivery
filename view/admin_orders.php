<?php
session_start();
require_once '../database.php'; // Ensure this path is correct
require '../auth.php'; // Ensure this file is correctly required

// Check if the user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Admin role check
if ($_SESSION['role'] !== 'admin') {
    header('Location: not_authorized.php');
    exit();
}

// Initialize variables for search and filter
$clientSearch = '';
$driverSearch = '';
$statusFilter = '';
$selectedDriver = '';
$deliveries = [];
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Calculate the offset
$totalDeliveries = 0; // Initialize to avoid undefined variable warning

// Fetch drivers for the dropdown filter
try {
    $driverStmt = $pdo->prepare("SELECT id, name FROM drivers");
    $driverStmt->execute();
    $drivers = $driverStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching drivers: " . htmlspecialchars($e->getMessage());
}

// Prepare the main SQL query to fetch orders based on search and filters
$sql = "SELECT o.*, c.name AS client_name, d.name AS driver_name 
        FROM orders o 
        JOIN clients c ON o.client_id = c.id 
        LEFT JOIN drivers d ON o.driver_id = d.id 
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

// Handle status filter
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $statusFilter = $_GET['status'];
    $sql .= " AND o.status = :statusFilter";
    $params[':statusFilter'] = $statusFilter; // Bind value directly
}

// Handle driver filter
if (isset($_GET['driver']) && $_GET['driver'] !== '') {
    $selectedDriver = $_GET['driver'];
    $sql .= " AND o.driver_id = :selectedDriver";
    $params[':selectedDriver'] = $selectedDriver; // Bind value directly
}

// Count total deliveries for pagination
try {
    // Prepare the count statement based on the search conditions
    $countSql = "SELECT COUNT(*) FROM orders o 
                 JOIN clients c ON o.client_id = c.id 
                 LEFT JOIN drivers d ON o.driver_id = d.id 
                 WHERE 1=1"; // Same condition structure

    // Add the same filters to the count SQL
    if (isset($_GET['client_search']) && !empty($_GET['client_search'])) {
        $countSql .= " AND c.name LIKE :clientSearch";
    }
    if (isset($_GET['driver_search']) && !empty($_GET['driver_search'])) {
        $countSql .= " AND d.name LIKE :driverSearch";
    }
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $countSql .= " AND o.status = :statusFilter";
    }
    if (isset($_GET['driver']) && $_GET['driver'] !== '') {
        $countSql .= " AND o.driver_id = :selectedDriver";
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
    echo "Error fetching orders: " . htmlspecialchars($e->getMessage());
}

// Pagination logic
$totalPages = ceil($totalDeliveries / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Manage Orders</h2>

    <!-- Button to go back to admin dashboard -->
    <div class="text-center mb-4">
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-4">
        <div class="row mb-2">
            <div class="col-md-4">
                <input type="text" name="client_search" value="<?= htmlspecialchars($clientSearch) ?>" class="form-control" placeholder="Search by Client Name">
            </div>
            <div class="col-md-4">
                <input type="text" name="driver_search" value="<?= htmlspecialchars($driverSearch) ?>" class="form-control" placeholder="Search by Driver Name">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Filter by Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in-progress" <?= $statusFilter === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="canceled" <?= $statusFilter === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="driver" class="form-select">
                    <option value="">Filter by Driver</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['id'] ?>" <?= $selectedDriver == $driver['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($driver['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Search & Filter</button>
    </form>

    <!-- Orders Table -->
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Client Name</th>
            <th>Driver Name</th>
            <th>Address</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($deliveries)): ?>
            <tr>
                <td colspan="6" class="text-center">No orders found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($deliveries as $delivery): ?>
                <tr>
                    <td><?= htmlspecialchars($delivery['id']) ?></td>
                    <td><?= htmlspecialchars($delivery['client_name']) ?></td>
                    <td><?= htmlspecialchars($delivery['driver_name'] ?? 'Not Assigned') ?></td>
                    <td><?= htmlspecialchars($delivery['address']) ?></td>
                    <td><?= htmlspecialchars($delivery['status']) ?></td>
                    <td><?= htmlspecialchars($delivery['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination Links -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&client_search=<?= urlencode($clientSearch) ?>&driver_search=<?= urlencode($driverSearch) ?>&status=<?= urlencode($statusFilter) ?>&driver=<?= urlencode($selectedDriver) ?>">Previous</a>
            </li>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&client_search=<?= urlencode($clientSearch) ?>&driver_search=<?= urlencode($driverSearch) ?>&status=<?= urlencode($statusFilter) ?>&driver=<?= urlencode($selectedDriver) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&client_search=<?= urlencode($clientSearch) ?>&driver_search=<?= urlencode($driverSearch) ?>&status=<?= urlencode($statusFilter) ?>&driver=<?= urlencode($selectedDriver) ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>