<?php
session_start();
require 'D:\ApplicationDir\laragon\Rindra-Fast-Delivery\database.php';  // Include database connection
require 'D:\ApplicationDir\laragon\Rindra-Fast-Delivery\class\order.php';     // Include the Order class
require 'D:\ApplicationDir\laragon\Rindra-Fast-Delivery\class\driver.php';    // Include the Driver class
require '../auth.php'; // Include the authentication file

check_role('admin'); // Ensure only admin can access this page

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Redirect to login page if not logged in or not admin
    exit;
}

// Fetch pending users
$pending_users = $pdo->query("SELECT * FROM pending_users WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all pending orders
$orders = $pdo->query("SELECT orders.*, clients.name AS client_name, clients.phone AS client_phone 
                        FROM orders 
                        JOIN clients ON orders.client_id = clients.id 
                        WHERE orders.driver_id IS NULL")->fetchAll(PDO::FETCH_ASSOC); // Fetch orders without a driver

// Fetch all drivers
$drivers = $pdo->query("SELECT * FROM drivers")->fetchAll(PDO::FETCH_ASSOC);

// Fetch order history (completed or cancelled orders)
try {
    $stmt = $pdo->prepare("SELECT o.*, d.name AS driver_name, c.name AS client_name, c.phone AS client_phone 
                            FROM orders o 
                            LEFT JOIN drivers d ON o.driver_id = d.id 
                            JOIN clients c ON o.client_id = c.id 
                            WHERE o.status IN ('completed', 'canceled')");
    $stmt->execute();
    $order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error (log it, display a message, etc.)
    echo "Error: " . $e->getMessage();
}

// Handle user approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        // Update the pending user to approved
        $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, password) 
                                SELECT username, email, phone, password FROM pending_users 
                                WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        // Delete the user from the pending_users table
        $stmt = $pdo->prepare("DELETE FROM pending_users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        echo '<div class="alert alert-success">User approved successfully!</div>';
    } elseif ($action === 'reject') {
        // Delete the user from the pending_users table
        $stmt = $pdo->prepare("DELETE FROM pending_users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        echo '<div class="alert alert-danger">User rejected successfully!</div>';
    }
}

// Handle order assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['order_action']; // 'assign' or 'decline'

    if ($action === 'assign') {
        $driver_id = $_POST['driver_id'];
        // Assign the driver to the order
        $stmt = $pdo->prepare("UPDATE orders SET driver_id = :driver_id, status = 'in-progress' WHERE id = :order_id");
        $stmt->execute([':driver_id' => $driver_id, ':order_id' => $order_id]);

        echo '<div class="alert alert-success">Driver assigned to order successfully!</div>';
    } elseif ($action === 'decline') {
        // Delete the order from the database
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        echo '<div class="alert alert-danger">Order declined successfully!</div>';
    }
}

// Handle order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $client_id = $_POST['client_id'];
    $address = $_POST['address'];
    
    // Basic validation
    if (!empty($client_id) && !empty($address)) {
        // Check for existing orders with the same client and address
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE client_id = :client_id AND address = :address AND status = 'pending'");
        $stmt->execute([':client_id' => $client_id, ':address' => $address]);
        $existing_order = $stmt->fetch();

        if ($existing_order) {
            echo '<div class="alert alert-warning">An order with the same client and address already exists!</div>';
        } else {
            // Insert new order into the database
            $stmt = $pdo->prepare("INSERT INTO orders (client_id, address, status) VALUES (:client_id, :address, 'pending')");
            $stmt->execute([
                ':client_id' => $client_id,
                ':address' => $address
            ]);

            echo '<div class="alert alert-success">Order created successfully!</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Please fill in all fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet"> <!-- Link your CSS file -->
</head>
<body>

<!-- Navbar -->
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><strong>Rindra Delivery Service</strong></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="history.php" class="btn btn-info me-2">View Order History</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_orders.php" class="btn btn-primary me-2">Manage Current Orders</a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-danger">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Admin Dashboard</h2>
        </div>
        <div class="card-body">
            <!-- Pending User Approvals -->
            <h3 class="dashboard-header mt-3">Pending User Approvals</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td>
                                    <form method="POST" action="admin_dashboard.php" class="d-flex">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-1">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Current Orders -->
            <h3 class="dashboard-header mt-5">Manage Current Orders</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
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
                                    <form method="POST" action="admin_dashboard.php" class="d-flex">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <select name="driver_id" class="form-select me-1" required>
                                            <option value="">Select Driver</option>
                                            <?php foreach ($drivers as $driver): ?>
                                                <option value="<?= htmlspecialchars($driver['id']) ?>"><?= htmlspecialchars($driver['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="order_action" value="assign" class="btn btn-success btn-sm me-1">Assign</button>
                                        <button type="submit" name="order_action" value="decline" class="btn btn-danger btn-sm">Decline</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Create New Order -->
            <h3 class="dashboard-header mt-5">Create New Order</h3>
            <form method="POST" action="admin_dashboard.php">
                <div class="mb-3">
                    <label for="client_id" class="form-label">Select Client:</label>
                    <select name="client_id" id="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        <?php
                        $clients = $pdo->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($clients as $client): ?>
                            <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Delivery Address:</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>
                <button type="submit" name="create_order" class="btn btn-primary">Create Order</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>