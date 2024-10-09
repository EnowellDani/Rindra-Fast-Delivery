<?php
session_start();
require 'D:\ApplicationDir\laragon\Rindra-Fast-Delivery\database.php';  // Include database connection
require '../auth.php'; // Include the authentication file

check_role('client'); // Ensure only client can access this page

// Ensure the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');  // Redirect to login if not logged in or not a client
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch client information
$stmt = $pdo->prepare("SELECT name, phone AS client_phone FROM clients WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$client_info = $stmt->fetch(PDO::FETCH_ASSOC);
$client_name = $client_info['name'];
$client_phone = $client_info['client_phone'];

// Fetch active orders for the logged-in client
$stmt = $pdo->prepare("SELECT o.*, d.name AS driver_name 
                        FROM orders o 
                        LEFT JOIN drivers d ON o.driver_id = d.id 
                        WHERE o.client_id = :client_id AND o.status != 'completed'");
$stmt->execute([':client_id' => $user_id]);
$active_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">Rindra Delivery Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-danger">Logout</a> <!-- Styled Logout button -->
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow">
                <div class="card-header text-center bg-primary text-white">
                    <h2 class="mb-0">Welcome to Your Dashboard, <?= htmlspecialchars($client_name); ?>!</h2>
                </div>
                <div class="card-body">

                    <h4 class="text-center mb-4">Active Orders</h4>

                    <!-- Active Orders Table -->
                    <?php if (!empty($active_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Status</th>
                                        <th>Driver Name</th>
                                        <th>Delivery Address</th>
                                        <th>Contact Info</th> <!-- Show client's phone -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_orders as $order): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['id']); ?></td>
                                            <td><?= ucfirst(htmlspecialchars($order['status'])); ?></td>
                                            <td><?= htmlspecialchars($order['driver_name'] ?: 'Not Assigned'); ?></td>
                                            <td><?= htmlspecialchars($order['address']); ?></td>
                                            <td><?= htmlspecialchars($client_phone); ?></td> <!-- Show client's phone -->
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">You have no active orders.</p>
                    <?php endif; ?>

                    <!-- Button to redirect to History Page -->
                    <div class="text-center mt-5">
                        <a href="history.php" class="btn btn-primary btn-lg">View Order History</a>
                    </div>

                </div>
                <div class="card-footer text-center text-muted">
                    &copy; 2024 Rindra Delivery. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>