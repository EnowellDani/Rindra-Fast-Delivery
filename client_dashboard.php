<?php
session_start();
require 'database.php';  // Include database connection

// Ensure the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');  // Redirect to login if not logged in or not a client
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch client information
$stmt = $pdo->prepare("SELECT name FROM clients WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$client_name = $stmt->fetchColumn();

// Fetch active orders for the logged-in client including client phone number
$stmt = $pdo->prepare("SELECT o.*, d.name AS driver_name, c.phone AS client_phone 
                        FROM orders o 
                        LEFT JOIN drivers d ON o.driver_id = d.id 
                        LEFT JOIN clients c ON o.client_id = c.id 
                        WHERE o.client_id = :client_id AND o.status != 'delivered'");
$stmt->execute([':client_id' => $user_id]);
$active_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order history (delivered orders)
$stmt = $pdo->prepare("SELECT o.*, d.name AS driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id WHERE o.client_id = :client_id AND o.status = 'completed'");
$stmt->execute([':client_id' => $user_id]);
$order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HTML for Client Dashboard -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">  <!-- Link to your external CSS file -->
    <title>Client Dashboard</title>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="index.php">Rindra Delivery Service</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
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

<div class="container mt-5">
    <div class="dashboard-container">
        <h2 class="dashboard-header">Welcome to Your Dashboard, <?= htmlspecialchars($client_name); ?>!</h2>

        <h4 class="text-center mb-4">Active Orders</h4>

        <!-- Active Orders Table -->
        <?php if (!empty($active_orders)): ?>
            <table class="table table-custom table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Driver Name</th>
                        <th>Delivery Address</th>
                        <th>Contact Info</th> <!-- Updated to show client's phone -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']); ?></td>
                            <td><?= ucfirst(htmlspecialchars($order['status'])); ?></td>
                            <td><?= htmlspecialchars($order['driver_name'] ?: 'Not Assigned'); ?></td>
                            <td><?= htmlspecialchars($order['address']); ?></td>
                            <td><?= htmlspecialchars($order['client_phone'] ?? ''); ?></td> <!-- Updated line -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">You have no active orders.</p>
        <?php endif; ?>

        <h4 class="text-center mb-4 mt-5">Order History</h4>

        <!-- Order History Table -->
        <?php if (!empty($order_history)): ?>
            <table class="table table-custom table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Driver Name</th>
                        <th>Updated At</th>
                        <th>Delivery Address</th>
                        <th>Contact Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_history as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']); ?></td>
                            <td><?= ucfirst(htmlspecialchars($order['status'])); ?></td>
                            <td><?= htmlspecialchars($order['driver_name'] ?: 'Not Assigned'); ?></td>
                            <td><?= htmlspecialchars($order['updated_at']); ?></td>
                            <td><?= htmlspecialchars($order['address']); ?></td>
                            <td><?= htmlspecialchars($order['client_phone'] ?? ''); ?></td> <!-- Updated line -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">You have no past orders.</p>
        <?php endif; ?>

        <div class="footer">
            &copy; 2024 Rindra Delivery. All rights reserved.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>