<?php
session_start();
require 'database.php';  // Include database connection
require 'order.php';     // Include the Order class
require 'driver.php';    // Include the Driver class

// Check if the user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php"); // Redirect to login page if not logged in or not a driver
    exit;
}

// Fetch assigned orders for the driver
$driver_id = $_SESSION['user_id']; // Assuming user_id is the driver's ID
$orders = $pdo->query("SELECT orders.*, clients.name AS client_name, clients.phone AS client_phone 
                        FROM orders 
                        JOIN clients ON orders.client_id = clients.id 
                        WHERE orders.driver_id = $driver_id")->fetchAll(PDO::FETCH_ASSOC); 

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status']; // E.g., 'completed' or 'in-progress'

    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->execute([':status' => $status, ':order_id' => $order_id]);
    echo '<div class="alert alert-success">Order status updated successfully!</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet"> <!-- Link your CSS file -->
</head>
<body>
<div class="container mt-2">
    <div class="card dashboard-container">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Driver Dashboard</h2>
            <form method="POST" action="logout.php">
                <button type="submit" class="btn btn-danger logout-btn">Logout</button>
            </form>
        </div>
        <div class="card-body">
            <h3 class="dashboard-header">Assigned Orders</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Client Phone</th>
                        <th>Delivery Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['client_name']) ?></td>
                            <td><?= htmlspecialchars($order['client_phone']) ?></td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <form method="POST" action="driver_dashboard.php">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                    <select name="status" class="form-select" required>
                                        <option value="">Update Status</option>
                                        <option value="in-progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                    <button type="submit" name="update_order" class="btn btn-primary btn-sm mt-2">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>