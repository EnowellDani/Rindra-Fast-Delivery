<?php
session_start();
require 'database.php';  // Include database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Redirect to login page if not logged in or not admin
    exit;
}

// Fetch pending users
$pending_users = $pdo->query("SELECT * FROM pending_users WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);

// Handle user approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        // Get the role of the user
        $stmt = $pdo->prepare("SELECT role, email, username, phone, password FROM pending_users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $role = $user['role'];

        // Check if the email already exists in the corresponding table
        if ($role === 'client') {
            $check_stmt = $pdo->prepare("SELECT email FROM clients WHERE email = :email");
        } else {
            $check_stmt = $pdo->prepare("SELECT email FROM users WHERE email = :email");  // Assuming drivers and admins go into 'users' table
        }
        $check_stmt->execute([':email' => $user['email']]);
        $existingEmail = $check_stmt->fetchColumn();

        if ($existingEmail) {
            echo '<div class="alert alert-danger">Error: The email address is already registered.</div>';
        } else {
            // Insert into the appropriate table based on role
            if ($role === 'client') {
                // Insert into clients table
                $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, password) 
                                        VALUES (:username, :email, :phone, :password)");
            } else {
                // Insert into users table for driver or admin
                $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, role, password)
                                        VALUES (:username, :email, :phone, :role, :password)");
            }
            $stmt->execute([
                ':username' => $user['username'],
                ':email' => $user['email'],
                ':phone' => $user['phone'],
                ':password' => $user['password'],
                ':role' => $role
            ]);

            // Update the pending user to approved status
            $stmt = $pdo->prepare("UPDATE pending_users SET status = 'approved' WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);

            echo '<div class="alert alert-success">User approved successfully!</div>';
        }
    } elseif ($action === 'reject') {
        // Update the user to denied status
        $stmt = $pdo->prepare("UPDATE pending_users SET status = 'denied' WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        echo '<div class="alert alert-danger">User rejected successfully!</div>';
    }

    // Refresh pending users list
    $pending_users = $pdo->query("SELECT * FROM pending_users WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);
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
                    <td><?= htmlspecialchars($user['username']) ?></td>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>