<?php
session_start();
require 'database.php';  // Include database connection
require 'order.php';     // Include the Order class

// Check if the user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php"); // Redirect to login page if not logged in or not driver
    exit;
}

// Fetch assigned orders for the driver
$driver_id = $_SESSION['user_id']; // Get logged-in driver's ID
$orders = $pdo->prepare("SELECT orders.*, clients.name AS client_name FROM orders JOIN clients ON orders.client_id = clients.id WHERE orders.driver_id = :driver_id");
$orders->execute([':driver_id' => $driver_id]);
$assigned_orders = $orders->fetchAll(PDO::FETCH_ASSOC);

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Validate the status value to ensure it matches the ENUM values in the database
    $valid_statuses = ['pending', 'completed', 'canceled'];
    if (in_array($status, $valid_statuses)) {
        // Update the order status
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id AND driver_id = :driver_id");
        $stmt->execute([':status' => $status, ':order_id' => $order_id, ':driver_id' => $driver_id]);

        echo '<div class="alert alert-success">Order status updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Invalid status selected!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-2">
    <form method="POST" action="logout.php">
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
<div class="container mt-5">
    <h2>Your Assigned Orders</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Client Name</th>
                <th>Delivery Address</th>
                <th>Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assigned_orders)): ?>
                <tr>
                    <td colspan="5" class="text-center">No orders assigned to you.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($assigned_orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['client_name']) ?></td>
                        <td><?= htmlspecialchars($order['address']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td>
                            <form method="POST" action="driver_dashboard.php">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <select name="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary mt-2">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>