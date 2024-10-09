<?php
// Include your database connection file
include '../database.php'; // Adjust the path as necessary

// Check user role (assumed to be set previously)
session_start();
$user_role = $_SESSION['user_role'] ?? 'client'; // Default to 'client' if not set
$dashboard_url = $user_role === 'admin' ? 'admin_dashboard.php' : 'driver_dashboard.php'; // Adjust URL based on role

// Initialize search variable
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of entries per page
$offset = ($page - 1) * $limit;

// Prepare the SQL query
try {
    // Base query
    $query = "SELECT o.id, c.name AS client_name, d.name AS driver_name, o.address, o.status, o.created_at
              FROM orders o
              LEFT JOIN clients c ON o.client_id = c.id
              LEFT JOIN drivers d ON o.driver_id = d.id";

    // Add search functionality if a search term is provided
    if (!empty($search)) {
        $query .= " WHERE c.name LIKE :search OR d.name LIKE :search OR DATE(o.created_at) LIKE :search";
    }

    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind parameters
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch all results
    $deliveries = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database Query Error: " . $e->getMessage(), 3, 'error_log.txt');
    echo "An error occurred while retrieving delivery history. Please try again later.";
    exit; // Stop further execution
}

// Fetch total number of deliveries for pagination
$totalQuery = "SELECT COUNT(*) FROM orders";
$totalStmt = $pdo->query($totalQuery);
$totalDeliveries = $totalStmt->fetchColumn();
$totalPages = ceil($totalDeliveries / $limit);

// Render the HTML for the history page
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
        <div class="text-center mb-4">
            <h2 class="display-4">Delivery History</h2>
        </div>

        <div class="text-center mb-3">
            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn btn-outline-secondary btn-lg">Back to Dashboard</a>
        </div>

        <!-- Search Form for Admins -->
        <?php if ($user_role === 'admin'): ?>
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by client/driver name or date" class="form-control">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Driver</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Date</th>
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
                                <td><?= htmlspecialchars(isset($delivery['driver_name']) ? $delivery['driver_name'] : 'No driver assigned') ?></td>
                                <td><?= htmlspecialchars($delivery['address']) ?></td>
                                <td><?= htmlspecialchars($delivery['status']) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($delivery['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 . ($search ? "&search=" . htmlspecialchars($search) : "") ?>">Previous</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i . ($search ? "&search=" . htmlspecialchars($search) : "") ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 . ($search ? "&search=" . htmlspecialchars($search) : "") ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>