<?php
session_start();
require 'database.php';  // Include database connection
require 'order.php';     // Include the Order class
require 'driver.php';    // Include the Driver class

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Redirect to login page if not logged in or not admin
    exit;
}

// Fetch pending users
$pending_users = $pdo->query("SELECT * FROM pending_users WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all orders
$orders = $pdo->query("SELECT orders.*, clients.name AS client_name, clients.phone AS client_phone 
                        FROM orders 
                        JOIN clients ON orders.client_id = clients.id 
                        WHERE orders.driver_id IS NULL")->fetchAll(PDO::FETCH_ASSOC); // Fetch orders without a driver

// Fetch all drivers
$drivers = $pdo->query("SELECT * FROM drivers")->fetchAll(PDO::FETCH_ASSOC);

// Handle user approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Approve or reject logic (existing code)
}

// Handle order assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['order_action']; // 'assign' or 'decline'

    if ($action === 'assign') {
        $driver_id = $_POST['driver_id'];
        // Assign the driver to the order
        $stmt = $pdo->prepare("UPDATE orders SET driver_id = :driver_id, status = 'pending' WHERE id = :order_id");
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
</head>
<body>
<div class="container mt-2">
    <form method="POST" action="logout.php">
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
<div class="container mt-5">
    <h2>Pending User Approvals</h2>
    <table class="table">
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
                        <form method="POST" action="admin_dashboard.php">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">Orders Overview</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Client Name</th>
                <th>Client Phone</th>
                <th>Delivery Address</th>
                <th>Assign Driver</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['id']) ?></td>
                    <td><?= htmlspecialchars($order['client_name']) ?></td>
                    <td><?= htmlspecialchars($order['client_phone']) ?></td>
                    <td><?= htmlspecialchars($order['address']) ?></td>
                    <td>
                        <form method="POST" action="admin_dashboard.php">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                            <select name="driver_id" class="form-select" required>
                                <option value="">Select Driver</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?= htmlspecialchars($driver['id']) ?>">
                                        <?= htmlspecialchars($driver['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="order_action" value="assign" class="btn btn-primary mt-2">Assign</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">Create New Order</h2>
    <form method="POST" action="admin_dashboard.php">
        <div class="mb-3">
            <label for="client_id" class="form-label">Select Client</label>
            <select name="client_id" class="form-select" required>
                <option value="">Choose a client...</option>
                <?php
                // Fetch all clients for the order creation form
                $clients = $pdo->query("SELECT id, name FROM clients")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($clients as $client): ?>
                    <option value="<?= htmlspecialchars($client['id']); ?>"><?= htmlspecialchars($client['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Delivery Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
        <button type="submit" name="create_order" class="btn btn-success">Create Order</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>