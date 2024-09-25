<?php
session_start();
require 'database.php';  // Include database connection
require 'order.php';     // Include the Order class
require 'driver.php';    // Include the Driver class

// Check if the driver is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php");  // Redirect to login if not authenticated
    exit;
}

// Instantiate the driver object using the session user ID
$driver = new Driver($pdo, $_SESSION['user_id']);

// Fetch assigned orders for this driver
$assignedOrders = $driver->getAssignedOrders();

// Handle the order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];

    // Update order status using the Order class
    $order = new Order($pdo, $orderId);
    if ($order->updateStatus($newStatus)) {
        $message = "Order status updated successfully!";
    } else {
        $message = "Failed to update order status!";
    }

    // Refresh the assigned orders after updating the status
    $assignedOrders = $driver->getAssignedOrders();
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
<div class="container mt-4">
    <h1>Driver Dashboard</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <h2>Assigned Orders</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Delivery Address</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assignedOrders)): ?>
                <tr>
                    <td colspan="4" class="text-center">No assigned orders found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($assignedOrders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['client_name']) ?></td>
                        <td><?= htmlspecialchars($order['delivery_address']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                                <button type="submit" class="btn btn-primary mt-2">Update</button>
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