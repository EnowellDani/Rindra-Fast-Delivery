<?php
session_start();
require_once '../database.php'; // Ensure this path is correct
require '../auth.php'; // Ensure this file is correctly required

// Check if the user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Determine user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Initialize variables for fetching deliveries
$deliveries = [];
$search = '';
$totalDeliveries = 0; // Initialize the totalDeliveries variable
$stmt = null; // Initialize $stmt to avoid undefined variable error
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Calculate the offset

// Handle search query for admin
if ($user_role === 'admin' && isset($_GET['search'])) {
    $search = $_GET['search'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders
            LEFT JOIN clients ON orders.client_id = clients.id
            LEFT JOIN drivers ON orders.driver_id = drivers.id
            WHERE clients.name LIKE :searchClient 
            OR drivers.name LIKE :searchDriver 
            OR orders.created_at LIKE :searchDate");
        $stmt->bindValue(':searchClient', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':searchDriver', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':searchDate', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        $totalDeliveries = $stmt->fetchColumn(); // Get the total count of deliveries

        $stmt = $pdo->prepare("SELECT o.id, o.client_id, o.driver_id, o.address, o.status, o.created_at 
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.id
            LEFT JOIN drivers d ON o.driver_id = d.id
            WHERE c.name LIKE :searchClient 
            OR d.name LIKE :searchDriver 
            OR o.created_at LIKE :searchDate
            LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':searchClient', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':searchDriver', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':searchDate', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($deliveries as &$delivery) {
            $stmt = $pdo->prepare("SELECT name FROM clients WHERE id = :client_id");
            $stmt->bindValue(':client_id', $delivery['client_id'], PDO::PARAM_INT);
            $stmt->execute();
            $delivery['client_name'] = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = :driver_id");
            $stmt->bindValue(':driver_id', $delivery['driver_id'], PDO::PARAM_INT);
            $stmt->execute();
            $delivery['driver_name'] = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    // Fetch deliveries based on user role
    try {
        if ($user_role === 'client') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_history
                WHERE client_id = :client_id 
                AND status IN ('completed', 'in-progress')");
            $stmt->bindValue(':client_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $totalDeliveries = $stmt->fetchColumn(); // Get the total count of deliveries

            $stmt = $pdo->prepare("SELECT oh.id, oh.client_id, oh.driver_id, oh.address, oh.status, oh.created_at 
                FROM order_history oh
                WHERE oh.client_id = :client_id
                LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':client_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($user_role === 'driver') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_history
                WHERE driver_id = :driver_id");
            $stmt->bindValue(':driver_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $totalDeliveries = $stmt->fetchColumn(); // Get the total count of deliveries

            $stmt = $pdo->prepare("SELECT oh.id, oh.client_id, oh.driver_id, oh.address, oh.status, oh.created_at 
                FROM order_history oh
                WHERE oh.driver_id = :driver_id
                LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':driver_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($deliveries as &$delivery) {
            $stmt = $pdo->prepare("SELECT name FROM clients WHERE id = :client_id");
            $stmt->bindValue(':client_id', $delivery['client_id'], PDO::PARAM_INT);
            $stmt->execute();
            $delivery['client_name'] = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = :driver_id");
            $stmt->bindValue(':driver_id', $delivery['driver_id'], PDO::PARAM_INT);
            $stmt->execute();
            $delivery['driver_name'] = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Calculate the total number of pages
$totalPages = ceil($totalDeliveries / $limit);

// Determine the dashboard URL based on user role
$dashboard_url = '';
switch ($user_role) {
    case 'admin':
        $dashboard_url = 'admin_dashboard.php';
        break;
    case 'client':
        $dashboard_url = 'client_dashboard.php';
        break;
    case 'driver':
        $dashboard_url = 'driver_dashboard.php';
        break;
}
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

        <!-- Search Form for Admins -->
        <?php if ($user_role === 'admin'): ?>
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by client/driver name or date" class="form-control">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Table for Deliveries -->
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