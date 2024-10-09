<?php
session_start();
require 'D:\ApplicationDir\laragon\Rindra-Fast-Delivery\database.php';  // Include database connection
require '../auth.php'; // Include the authentication file

check_role('driver'); // Ensure only driver can access this page

// Check if the user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../login.php"); // Redirect to login page if not logged in or not a driver
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
    $status = $_POST['status']; // 'in-progress', 'completed', or 'canceled'

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
    <link href="../style.css" rel="stylesheet"> <!-- Link your CSS file -->
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="../index.php">Rindra Delivery Service</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <div class="ms-auto">
                        <form action="../logout.php" method="post" class="d-inline">
                            <button type="submit" class="btn btn-danger">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container mt-4">
        <?php if ($successMessage): // Display the success message if set ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Assigned Orders</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Client Name</th>
                            <th>Client Phone</th>
                            <th>Address</th>
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
                                    <form action="" method="post" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <select name="status" class="form-select d-inline" style="width: auto;">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="in-progress" <?= $order['status'] == 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="canceled" <?= $order['status'] == 'canceled' ? 'selected' : '' ?>>Canceled</option>
                                        </select>
                                        <button type="submit" name="update_order" class="btn btn-secondary btn-sm ms-2">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="text-center mt-4">
                    <a href="history.php" class="btn btn-info">View Delivery History</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>