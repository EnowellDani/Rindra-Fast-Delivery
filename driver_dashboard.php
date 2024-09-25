<?php
session_start();
require 'database.php';  // Include database connection

// Check if the user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php"); // Redirect to login page if not logged in or not a driver
    exit;
}

// Fetch assigned orders for the driver
$driver_id = $_SESSION['user_id']; // Assuming user_id is the driver's ID
$stmt = $pdo->prepare("SELECT orders.*, clients.name AS client_name, clients.phone AS client_phone 
                        FROM orders 
                        JOIN clients ON orders.client_id = clients.id 
                        WHERE orders.driver_id = :driver_id");
$stmt->execute([':driver_id' => $driver_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// Handle order status update
$successMessage = ''; // Initialize success message variable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status']; // 'pending', 'completed', or 'canceled'

    // Update the order status in the orders table
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->execute([':status' => $status, ':order_id' => $order_id]);

    // Fetch the order details
    $orderDetails = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch(PDO::FETCH_ASSOC);

    // Check if the order details were fetched successfully
    if ($orderDetails) {
        // If the order is completed or canceled, move it to the order_history table
        if ($status === 'completed' || $status === 'canceled') {
            // Insert into order_history
            $historyStmt = $pdo->prepare("INSERT INTO order_history (client_id, driver_id, address, status) 
                                          VALUES (:client_id, :driver_id, :address, :status)");
            $historyStmt->execute([
                ':client_id' => $orderDetails['client_id'],
                ':driver_id' => $orderDetails['driver_id'],
                ':address' => $orderDetails['address'],
                ':status' => $status
            ]);

            // Delete the order from the orders table
            $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = :order_id");
            $deleteStmt->execute([':order_id' => $order_id]);
        }

        $successMessage = 'Order status updated successfully!'; // Set the success message
    } else {
        // Handle the case where the order is not found
        $successMessage = 'Order not found. Please try again.';
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
    <link href="style.css" rel="stylesheet"> <!-- Link your CSS file -->
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="index.php">Rindra Delivery Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <div class="container mt-2">
        <?php if ($successMessage): // Display the success message if set ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        
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
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No orders assigned.</td>
                        </tr>
                    <?php else: ?>
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
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="canceled">Canceled</option>
                                        </select>
                                        <button type="submit" name="update_order" class="btn btn-primary btn-sm mt-2">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>