<?php
session_start();
require 'database.php';  // Ensure this path is correct

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];  // Using email for login
    $password = $_POST['password'];

    // Initialize variables for roles
    $admin = null;
    $user = null;
    $client = null;

    // Fetch the admin from the database by email
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // If not an admin, check for a driver
    if (!$admin) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // If not a driver, check for a client
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
            $stmt->execute([$email]);
            $client = $stmt->fetch();
        }
    }

    // Check if the admin exists and if the password is correct
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        header('Location: admin_dashboard.php');
        exit;

    // Check if the driver exists and if the password is correct
    } elseif ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'driver';
        header('Location: driver_dashboard.php');
        exit;

    // Check if the client exists and if the password is correct
    } elseif ($client && password_verify($password, $client['password'])) {
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['role'] = 'client';
        header('Location: client_dashboard.php');
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <form method="POST" action="index.php"> <!-- Ensure this points to the correct file -->
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <!-- Sign Up Button -->
    <div class="mt-3">
        <p>Don't have an account? <a href="sign_up.php" class="btn btn-secondary">Sign Up</a></p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>